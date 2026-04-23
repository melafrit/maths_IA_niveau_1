<?php
/**
 * Auth — Authentification et gestion des comptes enseignants
 *
 * Stockage : data/comptes/enseignants.json (un fichier unique)
 *
 * Format enseignants.json :
 * {
 *   "version": 1,
 *   "comptes": [
 *     {
 *       "id": "AAAA-BBBB-CCCC",
 *       "email": "prof@ipssi.fr",
 *       "password_hash": "$2y$12$...",         // bcrypt
 *       "nom": "Dupont",
 *       "prenom": "Marie",
 *       "role": "admin" | "enseignant",
 *       "active": true,
 *       "created_at": "2026-04-21T10:00:00+02:00",
 *       "last_login_at": null,
 *       "last_login_ip": null,
 *       "created_by": null,                     // id du créateur (null = self pour 1er admin)
 *       "ia_keys": {                            // clés API IA chiffrées (P4)
 *         "anthropic": null,
 *         "openai": null
 *       },
 *       "preferences": {                        // préférences par enseignant
 *         "theme": "light",
 *         "language": "fr"
 *       }
 *     }
 *   ]
 * }
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class Auth
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ENSEIGNANT = 'enseignant';

    private const SESSION_USER_KEY = '_auth_user';
    private const COMPTES_FILE = 'comptes/enseignants.json';

    private FileStorage $storage;
    private Logger $logger;

    public function __construct(?FileStorage $storage = null, ?Logger $logger = null)
    {
        $this->storage = $storage ?? new FileStorage();
        $this->logger = $logger ?? new Logger('auth');
    }

    // ========================================================================
    // LOGIN / LOGOUT
    // ========================================================================

    /**
     * Tente une connexion. Retourne le compte (sans hash) si succès, null sinon.
     *
     * @return array|null Compte authentifié ou null
     */
    public function login(string $email, string $password): ?array
    {
        $email = Utils::normalizeEmail($email);
        $compte = $this->findByEmail($email);

        if ($compte === null) {
            $this->logger->warning('Login échec : email inconnu', [
                'email' => $email,
                'ip' => Utils::clientIp(),
            ]);
            return null;
        }

        if (!($compte['active'] ?? true)) {
            $this->logger->warning('Login échec : compte désactivé', ['email' => $email]);
            return null;
        }

        if (!password_verify($password, $compte['password_hash'])) {
            $this->logger->warning('Login échec : mot de passe incorrect', [
                'email' => $email,
                'ip' => Utils::clientIp(),
            ]);
            return null;
        }

        // Re-hash si le coût a changé dans la config
        $cost = (int)config('security.bcrypt_cost', 12);
        if (password_needs_rehash($compte['password_hash'], PASSWORD_BCRYPT, ['cost' => $cost])) {
            $this->updatePassword($compte['id'], $password);
        }

        // Mettre à jour last_login
        $this->updateCompte($compte['id'], [
            'last_login_at' => Utils::now(),
            'last_login_ip' => Utils::clientIp(),
        ]);

        // Régénérer la session pour anti-fixation
        Session::regenerate();
        Csrf::regenerate();

        $compte = $this->findByEmail($email); // recharger
        unset($compte['password_hash']);
        Session::set(self::SESSION_USER_KEY, $compte);

        $this->logger->info('Login réussi', [
            'email' => $email,
            'id' => $compte['id'],
            'role' => $compte['role'],
        ]);

        return $compte;
    }

    /**
     * Déconnexion : détruit la session.
     */
    public function logout(): void
    {
        $user = $this->user();
        if ($user) {
            $this->logger->info('Logout', ['email' => $user['email']]);
        }
        Csrf::clear();
        Session::destroy();
    }

    // ========================================================================
    // ÉTAT D'AUTHENTIFICATION
    // ========================================================================

    /**
     * Retourne le compte connecté (sans password_hash) ou null.
     */
    public function user(): ?array
    {
        return Session::get(self::SESSION_USER_KEY);
    }

    public function isAuthenticated(): bool
    {
        return $this->user() !== null;
    }

    public function isAdmin(): bool
    {
        $user = $this->user();
        return $user !== null && ($user['role'] ?? '') === self::ROLE_ADMIN;
    }

    /**
     * Middleware : exige une session active. Sinon, 401.
     */
    public function requireAuth(): array
    {
        $user = $this->user();
        if ($user === null) {
            Response::unauthorized('Vous devez être connecté pour accéder à cette ressource.');
        }
        return $user;
    }

    /**
     * Middleware : exige le rôle admin. Sinon, 403.
     */
    public function requireAdmin(): array
    {
        $user = $this->requireAuth();
        if (($user['role'] ?? '') !== self::ROLE_ADMIN) {
            Response::forbidden('Cette action est réservée aux administrateurs.');
        }
        return $user;
    }

    // ========================================================================
    // GESTION DES COMPTES (CRUD)
    // ========================================================================

    /**
     * Liste tous les comptes (sans password_hash).
     */
    public function listComptes(): array
    {
        $data = $this->loadComptes();
        return array_map(function ($c) {
            unset($c['password_hash']);
            return $c;
        }, $data['comptes']);
    }

    /**
     * Trouve un compte par email (avec password_hash, usage interne).
     */
    public function findByEmail(string $email): ?array
    {
        $email = Utils::normalizeEmail($email);
        $data = $this->loadComptes();
        foreach ($data['comptes'] as $c) {
            if (Utils::normalizeEmail($c['email']) === $email) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Trouve un compte par ID (sans password_hash).
     */
    public function findById(string $id): ?array
    {
        $data = $this->loadComptes();
        foreach ($data['comptes'] as $c) {
            if (($c['id'] ?? '') === $id) {
                unset($c['password_hash']);
                return $c;
            }
        }
        return null;
    }

    /**
     * Crée un nouveau compte. Retourne le compte créé (sans hash).
     *
     * @throws \InvalidArgumentException si email déjà utilisé ou données invalides
     */
    public function createCompte(array $data): array
    {
        $email = Utils::normalizeEmail($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $role = $data['role'] ?? self::ROLE_ENSEIGNANT;
        $createdBy = $data['created_by'] ?? null;

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide.');
        }
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit faire au moins 8 caractères.');
        }
        if ($nom === '' || $prenom === '') {
            throw new \InvalidArgumentException('Nom et prénom requis.');
        }
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_ENSEIGNANT], true)) {
            throw new \InvalidArgumentException('Rôle invalide.');
        }
        if ($this->findByEmail($email) !== null) {
            throw new \InvalidArgumentException('Cet email est déjà utilisé.');
        }

        $compte = [
            'id'            => Utils::generateId(),
            'email'         => $email,
            'password_hash' => $this->hashPassword($password),
            'nom'           => $nom,
            'prenom'        => $prenom,
            'role'          => $role,
            'active'        => true,
            'created_at'    => Utils::now(),
            'created_by'    => $createdBy,
            'last_login_at' => null,
            'last_login_ip' => null,
            'ia_keys'       => ['anthropic' => null, 'openai' => null],
            'preferences'   => ['theme' => 'light', 'language' => 'fr'],
        ];

        $this->storage->update($this->comptesPath(), function (array $d) use ($compte): array {
            $d['version'] = $d['version'] ?? 1;
            $d['comptes'] = $d['comptes'] ?? [];
            $d['comptes'][] = $compte;
            return $d;
        });

        $this->logger->info('Compte créé', [
            'email' => $email,
            'role' => $role,
            'created_by' => $createdBy,
        ]);

        unset($compte['password_hash']);
        return $compte;
    }

    /**
     * Met à jour un compte (champs autorisés uniquement).
     */
    public function updateCompte(string $id, array $updates): bool
    {
        $allowed = [
            'nom', 'prenom', 'role', 'active',
            'last_login_at', 'last_login_ip',
            'ia_keys', 'preferences',
        ];
        $filtered = array_intersect_key($updates, array_flip($allowed));

        $found = false;
        $this->storage->update($this->comptesPath(), function (array $d) use ($id, $filtered, &$found): array {
            foreach ($d['comptes'] ?? [] as $i => $c) {
                if (($c['id'] ?? '') === $id) {
                    $d['comptes'][$i] = array_merge($c, $filtered);
                    $found = true;
                    break;
                }
            }
            return $d;
        });

        // Si le compte mis à jour est l'utilisateur en session, rafraîchir la session
        $user = $this->user();
        if ($user && $user['id'] === $id) {
            $refreshed = $this->findById($id);
            if ($refreshed) {
                Session::set(self::SESSION_USER_KEY, $refreshed);
            }
        }

        return $found;
    }

    /**
     * Met à jour le mot de passe d'un compte.
     */
    public function updatePassword(string $id, string $newPassword): bool
    {
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit faire au moins 8 caractères.');
        }

        $hash = $this->hashPassword($newPassword);
        $found = false;
        $this->storage->update($this->comptesPath(), function (array $d) use ($id, $hash, &$found): array {
            foreach ($d['comptes'] ?? [] as $i => $c) {
                if (($c['id'] ?? '') === $id) {
                    $d['comptes'][$i]['password_hash'] = $hash;
                    $d['comptes'][$i]['password_changed_at'] = Utils::now();
                    $found = true;
                    break;
                }
            }
            return $d;
        });

        if ($found) {
            $this->logger->info('Mot de passe modifié', ['id' => $id]);
        }
        return $found;
    }

    /**
     * Désactive un compte (soft delete, on garde l'historique).
     */
    public function disableCompte(string $id): bool
    {
        return $this->updateCompte($id, ['active' => false]);
    }

    /**
     * Réactive un compte.
     */
    public function enableCompte(string $id): bool
    {
        return $this->updateCompte($id, ['active' => true]);
    }

    /**
     * Supprime DÉFINITIVEMENT un compte (hard delete).
     * À utiliser avec précaution.
     */
    public function deleteCompte(string $id): bool
    {
        $found = false;
        $this->storage->update($this->comptesPath(), function (array $d) use ($id, &$found): array {
            $before = count($d['comptes'] ?? []);
            $d['comptes'] = array_values(array_filter(
                $d['comptes'] ?? [],
                fn($c) => ($c['id'] ?? '') !== $id
            ));
            $found = count($d['comptes']) < $before;
            return $d;
        });

        if ($found) {
            $this->logger->warning('Compte supprimé définitivement', ['id' => $id]);
        }
        return $found;
    }

    // ========================================================================
    // INTROSPECTION
    // ========================================================================

    /**
     * Compte le nombre de comptes (utile pour savoir si init nécessaire).
     */
    public function countComptes(): int
    {
        $data = $this->loadComptes();
        return count($data['comptes'] ?? []);
    }

    /**
     * Compte uniquement les admins actifs.
     */
    public function countActiveAdmins(): int
    {
        $data = $this->loadComptes();
        $count = 0;
        foreach ($data['comptes'] ?? [] as $c) {
            if (($c['role'] ?? '') === self::ROLE_ADMIN && ($c['active'] ?? true)) {
                $count++;
            }
        }
        return $count;
    }

    // ========================================================================
    // INTERNES
    // ========================================================================

    private function comptesPath(): string
    {
        return data_path(self::COMPTES_FILE);
    }

    private function loadComptes(): array
    {
        $data = $this->storage->read($this->comptesPath());
        if (!is_array($data)) {
            return ['version' => 1, 'comptes' => []];
        }
        return $data;
    }

    private function hashPassword(string $password): string
    {
        $cost = (int)config('security.bcrypt_cost', 12);
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
        if ($hash === false) {
            throw new \RuntimeException('Échec du hash du mot de passe.');
        }
        return $hash;
    }
}
