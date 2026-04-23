<?php
/**
 * ExamenManager.php — Gestion des examens (CRUD + cycle de vie)
 *
 * Responsabilités :
 *   - CRUD des examens (create, read, update, delete)
 *   - Gestion du cycle de vie : draft → published → closed → archived
 *   - Stockage dans data/examens/{id}.json (1 fichier par examen)
 *   - Génération d'ID unique (EXM-XXXX-YYYY)
 *   - Génération d'access_code court (6 caractères)
 *   - Validation des champs (titre, durée, dates, questions)
 *   - Relations : vérifie que les questions existent dans la banque
 *
 * Modèle d'examen :
 *   {
 *     id, titre, description, created_by, status,
 *     questions[], duree_sec, date_ouverture, date_cloture,
 *     max_passages, shuffle_questions, shuffle_options,
 *     show_correction_after, correction_delay_min,
 *     access_code, created_at, updated_at
 *   }
 *
 * Cycle de vie :
 *   draft       → peut être modifié librement
 *   published   → visible aux étudiants, modifications limitées
 *   closed      → plus de passages possibles
 *   archived    → historique seulement
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class ExamenManager
{
    private FileStorage $storage;
    private Logger $logger;
    private BanqueManager $banque;
    private string $examensDir;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    public const ALL_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_CLOSED,
        self::STATUS_ARCHIVED,
    ];

    /** Champs requis à la création */
    public const REQUIRED_FIELDS = [
        'titre', 'questions', 'duree_sec', 'date_ouverture', 'date_cloture',
    ];

    /** Alphabet pour access_code (sans I/O/0/1 pour lisibilité) */
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(
        ?FileStorage $storage = null,
        ?Logger $logger = null,
        ?BanqueManager $banque = null,
        ?string $examensDir = null
    ) {
        $this->storage = $storage ?? new FileStorage();
        $this->logger = $logger ?? new Logger('examens');
        $this->banque = $banque ?? new BanqueManager($this->storage, $this->logger);

        if ($examensDir !== null) {
            $this->examensDir = $examensDir;
        } elseif (function_exists('data_path')) {
            $this->examensDir = data_path('examens');
        } else {
            $this->examensDir = realpath(__DIR__ . '/../../data/examens') ?: __DIR__ . '/../../data/examens';
        }

        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->examensDir)) {
            @mkdir($this->examensDir, 0750, true);
        }
    }

    // ========================================================================
    // LECTURE
    // ========================================================================

    /**
     * Liste les examens avec filtres optionnels.
     *
     * @param array $filters {
     *   created_by?: string,
     *   status?: string|array,
     *   after?: string (date ISO, ouverture après),
     *   before?: string (date ISO, ouverture avant)
     * }
     * @return array Liste des examens (tri : date_ouverture desc)
     */
    public function list(array $filters = []): array
    {
        $examens = [];

        if (!is_dir($this->examensDir)) {
            return [];
        }

        foreach (glob($this->examensDir . '/*.json') as $file) {
            $examen = $this->storage->read($file);
            if ($examen === null) continue;

            if (!$this->matchesFilters($examen, $filters)) continue;

            $examens[] = $examen;
        }

        // Tri par date_ouverture décroissante
        usort($examens, function ($a, $b) {
            return strcmp($b['date_ouverture'] ?? '', $a['date_ouverture'] ?? '');
        });

        return $examens;
    }

    /**
     * Récupère un examen par son ID.
     */
    public function get(string $id): ?array
    {
        $path = $this->examenPath($id);
        if (!file_exists($path)) {
            return null;
        }
        return $this->storage->read($path);
    }

    /**
     * Récupère un examen par access_code (pour les étudiants).
     */
    public function getByAccessCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        foreach ($this->list() as $examen) {
            if (($examen['access_code'] ?? '') === $code) {
                return $examen;
            }
        }
        return null;
    }

    // ========================================================================
    // CRÉATION
    // ========================================================================

    /**
     * Créer un nouvel examen.
     *
     * @throws \InvalidArgumentException si validation échoue
     * @throws \RuntimeException si écriture échoue
     */
    public function create(array $data, string $createdBy): array
    {
        // 1. Validation des champs requis
        $errors = $this->validateExamen($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Examen invalide : ' . json_encode($errors));
        }

        // 2. Vérifier que les questions existent dans la banque
        $missingQuestions = $this->findMissingQuestions($data['questions']);
        if (!empty($missingQuestions)) {
            throw new \InvalidArgumentException(
                'Questions inexistantes : ' . implode(', ', $missingQuestions)
            );
        }

        // 3. Construire l'examen complet
        $examen = [
            'id' => $this->generateId(),
            'titre' => trim($data['titre']),
            'description' => trim($data['description'] ?? ''),
            'created_by' => $createdBy,
            'status' => self::STATUS_DRAFT,
            'questions' => array_values($data['questions']),
            'duree_sec' => (int) $data['duree_sec'],
            'date_ouverture' => $data['date_ouverture'],
            'date_cloture' => $data['date_cloture'],
            'max_passages' => (int) ($data['max_passages'] ?? 1),
            'shuffle_questions' => (bool) ($data['shuffle_questions'] ?? true),
            'shuffle_options' => (bool) ($data['shuffle_options'] ?? true),
            'show_correction_after' => (bool) ($data['show_correction_after'] ?? true),
            'correction_delay_min' => (int) ($data['correction_delay_min'] ?? 0),
            'access_code' => $this->generateAccessCode(),
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];

        // 4. Écriture
        $path = $this->examenPath($examen['id']);
        if (!$this->storage->write($path, $examen)) {
            throw new \RuntimeException('Échec écriture examen');
        }

        $this->logger->info('Examen créé', [
            'id' => $examen['id'],
            'titre' => $examen['titre'],
            'created_by' => $createdBy,
            'nb_questions' => count($examen['questions']),
        ]);

        return $examen;
    }

    // ========================================================================
    // MISE À JOUR
    // ========================================================================

    /**
     * Mettre à jour un examen.
     * Certains champs sont immuables selon le status :
     *   - status draft     : tout modifiable (sauf id, created_by, access_code)
     *   - status published : titre, description, date_cloture modifiables
     *   - status closed    : archived uniquement
     *   - status archived  : rien
     */
    public function update(string $id, array $updates): array
    {
        $examen = $this->get($id);
        if ($examen === null) {
            throw new \RuntimeException("Examen introuvable : $id");
        }

        // Champs toujours immuables
        unset($updates['id'], $updates['created_by'], $updates['access_code'], $updates['created_at']);

        // Restrictions selon status
        $allowedFields = $this->getAllowedUpdateFields($examen['status']);
        foreach (array_keys($updates) as $key) {
            if (!in_array($key, $allowedFields, true)) {
                throw new \InvalidArgumentException(
                    "Champ '$key' non modifiable en status '{$examen['status']}'. Champs autorisés : " . implode(', ', $allowedFields)
                );
            }
        }

        // Fusion
        $merged = array_merge($examen, $updates);
        $merged['updated_at'] = date('c');

        // Re-valider si le status est draft et qu'on modifie structure
        if ($examen['status'] === self::STATUS_DRAFT) {
            $errors = $this->validateExamen($merged);
            if (!empty($errors)) {
                throw new \InvalidArgumentException('Examen invalide : ' . json_encode($errors));
            }
            // Re-vérifier questions si modifiées
            if (isset($updates['questions'])) {
                $missing = $this->findMissingQuestions($updates['questions']);
                if (!empty($missing)) {
                    throw new \InvalidArgumentException(
                        'Questions inexistantes : ' . implode(', ', $missing)
                    );
                }
            }
        }

        $path = $this->examenPath($id);
        if (!$this->storage->write($path, $merged)) {
            throw new \RuntimeException('Échec écriture examen');
        }

        $this->logger->info('Examen mis à jour', ['id' => $id, 'fields' => array_keys($updates)]);
        return $merged;
    }

    /**
     * Champs modifiables selon le status.
     */
    private function getAllowedUpdateFields(string $status): array
    {
        return match ($status) {
            self::STATUS_DRAFT => [
                'titre', 'description', 'status', 'questions', 'duree_sec',
                'date_ouverture', 'date_cloture', 'max_passages',
                'shuffle_questions', 'shuffle_options',
                'show_correction_after', 'correction_delay_min',
            ],
            self::STATUS_PUBLISHED => ['titre', 'description', 'date_cloture', 'status'],
            self::STATUS_CLOSED => ['status'], // uniquement pour archiver
            self::STATUS_ARCHIVED => [],
            default => [],
        };
    }

    // ========================================================================
    // TRANSITIONS DE STATUS
    // ========================================================================

    /**
     * Publier un examen (draft → published).
     */
    public function publish(string $id): array
    {
        $examen = $this->get($id);
        if ($examen === null) {
            throw new \RuntimeException("Examen introuvable : $id");
        }
        if ($examen['status'] !== self::STATUS_DRAFT) {
            throw new \InvalidArgumentException(
                "Impossible de publier : status actuel = '{$examen['status']}' (attendu : draft)"
            );
        }

        // Re-valider avant publication
        $errors = $this->validateExamen($examen);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Impossible de publier : ' . json_encode($errors));
        }

        $updated = $this->update($id, ['status' => self::STATUS_PUBLISHED]);

        // Hook email : notifier le prof
        $this->sendPublishEmail($updated);

        return $updated;
    }

    /**
     * Hook email apres publication (notifie le prof createur).
     */
    private function sendPublishEmail(array $examen): void
    {
        try {
            $creatorId = $examen['created_by'] ?? null;
            if (!$creatorId) return;

            // Charger le prof
            $profData = $this->loadProfData($creatorId);
            if ($profData === null || empty($profData['email'])) return;

            $mailer = new Mailer($this->logger);
            if ($mailer->getMode() === Mailer::MODE_DISABLED) return;

            $tpl = new EmailTemplate();
            $adminUrl = defined('BASE_URL') ? BASE_URL . '/admin/examens.html' : '/admin/examens.html';

            $rendered = $tpl->render('prof_examen_cree', [
                'profName' => $profData['nom'] ?? $profData['email'],
                'examTitle' => $examen['titre'],
                'examId' => $examen['id'],
                'accessCode' => $examen['access_code'],
                'nbQuestions' => count($examen['questions']),
                'dureeSec' => $examen['duree_sec'],
                'maxPassages' => $examen['max_passages'] ?? 1,
                'dateOuverture' => $examen['date_ouverture'],
                'dateCloture' => $examen['date_cloture'],
                'adminUrl' => $adminUrl,
            ]);

            $mailer->send($profData['email'], $rendered['subject'], $rendered['html']);
        } catch (\Throwable $e) {
            // Ne pas faire echouer la publication sur erreur email
            $this->logger->error('Hook email publish failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Charger les infos d'un prof depuis le compte.
     */
    private function loadProfData(string $profId): ?array
    {
        $comptePath = function_exists('data_path')
            ? data_path('comptes') . '/' . $profId . '.json'
            : __DIR__ . '/../../data/comptes/' . $profId . '.json';

        if (!file_exists($comptePath)) return null;
        return $this->storage->read($comptePath);
    }


    /**
     * Clôturer un examen (published → closed).
     */
    public function close(string $id): array
    {
        $examen = $this->get($id);
        if ($examen === null) {
            throw new \RuntimeException("Examen introuvable : $id");
        }
        if ($examen['status'] !== self::STATUS_PUBLISHED) {
            throw new \InvalidArgumentException(
                "Impossible de clôturer : status actuel = '{$examen['status']}' (attendu : published)"
            );
        }

        return $this->update($id, ['status' => self::STATUS_CLOSED]);
    }

    /**
     * Archiver un examen (closed → archived).
     */
    public function archive(string $id): array
    {
        $examen = $this->get($id);
        if ($examen === null) {
            throw new \RuntimeException("Examen introuvable : $id");
        }
        if (!in_array($examen['status'], [self::STATUS_CLOSED, self::STATUS_DRAFT], true)) {
            throw new \InvalidArgumentException(
                "Impossible d'archiver : status actuel = '{$examen['status']}'"
            );
        }

        return $this->update($id, ['status' => self::STATUS_ARCHIVED]);
    }

    // ========================================================================
    // SUPPRESSION (draft uniquement)
    // ========================================================================

    public function delete(string $id): bool
    {
        $examen = $this->get($id);
        if ($examen === null) {
            throw new \RuntimeException("Examen introuvable : $id");
        }
        if ($examen['status'] !== self::STATUS_DRAFT) {
            throw new \InvalidArgumentException(
                "Impossible de supprimer un examen en status '{$examen['status']}'. Archivez-le plutôt."
            );
        }

        $path = $this->examenPath($id);
        $success = $this->storage->delete($path);

        if ($success) {
            $this->logger->info('Examen supprimé', ['id' => $id]);
        }

        return $success;
    }

    // ========================================================================
    // VALIDATION
    // ========================================================================

    public function validateExamen(array $data): array
    {
        $errors = [];

        // Champs requis
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($data[$field])) {
                $errors[] = "Champ manquant : $field";
            }
        }

        // Titre
        if (isset($data['titre'])) {
            if (!is_string($data['titre']) || trim($data['titre']) === '') {
                $errors[] = "Titre obligatoire (string non vide)";
            } elseif (mb_strlen($data['titre']) > 200) {
                $errors[] = "Titre trop long (max 200 chars)";
            }
        }

        // Questions
        if (isset($data['questions'])) {
            if (!is_array($data['questions']) || empty($data['questions'])) {
                $errors[] = "Liste de questions non vide requise";
            } else {
                foreach ($data['questions'] as $i => $qId) {
                    if (!is_string($qId)) {
                        $errors[] = "Question #$i : doit être un ID string";
                    }
                }
            }
        }

        // Durée
        if (isset($data['duree_sec'])) {
            $duree = (int) $data['duree_sec'];
            if ($duree < 60) {
                $errors[] = "Durée trop courte (min 60 secondes)";
            } elseif ($duree > 4 * 3600) {
                $errors[] = "Durée trop longue (max 4 heures)";
            }
        }

        // Dates
        $openTs = null;
        $closeTs = null;
        if (isset($data['date_ouverture'])) {
            $openTs = strtotime($data['date_ouverture']);
            if ($openTs === false) {
                $errors[] = "date_ouverture invalide (format ISO 8601 attendu)";
            }
        }
        if (isset($data['date_cloture'])) {
            $closeTs = strtotime($data['date_cloture']);
            if ($closeTs === false) {
                $errors[] = "date_cloture invalide (format ISO 8601 attendu)";
            }
        }
        if ($openTs !== false && $closeTs !== false && $openTs !== null && $closeTs !== null) {
            if ($closeTs <= $openTs) {
                $errors[] = "date_cloture doit être après date_ouverture";
            }
            $diffMin = ($closeTs - $openTs) / 60;
            if ($diffMin < 5) {
                $errors[] = "La fenêtre d'ouverture doit faire au moins 5 minutes";
            }
        }

        // max_passages
        if (isset($data['max_passages'])) {
            $n = (int) $data['max_passages'];
            if ($n < 1 || $n > 10) {
                $errors[] = "max_passages doit être entre 1 et 10";
            }
        }

        // correction_delay_min
        if (isset($data['correction_delay_min'])) {
            $d = (int) $data['correction_delay_min'];
            if ($d < 0 || $d > 60 * 24 * 7) {
                $errors[] = "correction_delay_min doit être entre 0 et 10080 min (1 semaine)";
            }
        }

        return $errors;
    }

    /**
     * Vérifier que les questions existent dans la banque.
     * Retourne la liste des IDs introuvables.
     */
    private function findMissingQuestions(array $questionIds): array
    {
        $missing = [];
        foreach ($questionIds as $qId) {
            if (!is_string($qId) || $this->banque->getQuestion($qId) === null) {
                $missing[] = $qId;
            }
        }
        return $missing;
    }

    // ========================================================================
    // STATISTIQUES
    // ========================================================================

    public function getStats(): array
    {
        $all = $this->list();
        $byStatus = array_fill_keys(self::ALL_STATUSES, 0);
        $byOwner = [];
        $totalQuestions = 0;

        foreach ($all as $examen) {
            $status = $examen['status'] ?? 'unknown';
            if (isset($byStatus[$status])) {
                $byStatus[$status]++;
            }
            $owner = $examen['created_by'] ?? 'unknown';
            $byOwner[$owner] = ($byOwner[$owner] ?? 0) + 1;
            $totalQuestions += count($examen['questions'] ?? []);
        }

        return [
            'total' => count($all),
            'by_status' => $byStatus,
            'by_owner' => $byOwner,
            'total_questions_used' => $totalQuestions,
            'avg_questions_per_exam' => count($all) > 0 ? round($totalQuestions / count($all), 1) : 0,
        ];
    }

    // ========================================================================
    // HELPERS PRIVÉS
    // ========================================================================

    /**
     * Filtrer un examen selon les critères.
     */
    private function matchesFilters(array $examen, array $filters): bool
    {
        // created_by
        if (isset($filters['created_by']) && ($examen['created_by'] ?? null) !== $filters['created_by']) {
            return false;
        }

        // status
        if (isset($filters['status'])) {
            $allowed = (array) $filters['status'];
            if (!in_array($examen['status'] ?? null, $allowed, true)) {
                return false;
            }
        }

        // after
        if (isset($filters['after'])) {
            $afterTs = strtotime($filters['after']);
            $openTs = strtotime($examen['date_ouverture'] ?? '');
            if ($afterTs !== false && $openTs !== false && $openTs < $afterTs) {
                return false;
            }
        }

        // before
        if (isset($filters['before'])) {
            $beforeTs = strtotime($filters['before']);
            $openTs = strtotime($examen['date_ouverture'] ?? '');
            if ($beforeTs !== false && $openTs !== false && $openTs > $beforeTs) {
                return false;
            }
        }

        return true;
    }

    /**
     * Générer un ID unique : EXM-AAAA-BBBB (format similaire aux comptes)
     */
    private function generateId(): string
    {
        do {
            $part1 = $this->randomCode(4);
            $part2 = $this->randomCode(4);
            $id = "EXM-$part1-$part2";
        } while (file_exists($this->examenPath($id)));

        return $id;
    }

    /**
     * Générer un code d'accès court (6 caractères lisibles).
     */
    private function generateAccessCode(): string
    {
        $attempts = 0;
        do {
            $code = $this->randomCode(6);
            $exists = $this->getByAccessCode($code) !== null;
            $attempts++;
        } while ($exists && $attempts < 20);

        return $code;
    }

    /**
     * Générer un code random depuis l'alphabet.
     */
    private function randomCode(int $length): string
    {
        $code = '';
        $alphaLen = strlen(self::CODE_ALPHABET);
        for ($i = 0; $i < $length; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $alphaLen - 1)];
        }
        return $code;
    }

    /**
     * Chemin absolu d'un fichier examen.
     */
    private function examenPath(string $id): string
    {
        // Sanitize l'ID
        if (!preg_match('/^EXM-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $id)) {
            throw new \InvalidArgumentException("ID examen invalide : $id");
        }
        return $this->examensDir . '/' . $id . '.json';
    }
}
