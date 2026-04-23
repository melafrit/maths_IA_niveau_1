<?php
/**
 * FileStorage — Lecture/écriture atomique de fichiers JSON
 *
 * Caractéristiques :
 *   - Verrouillage flock() pour éviter les races conditions
 *   - Écriture atomique via fichier temporaire + rename()
 *   - Création automatique des dossiers manquants
 *   - Retour de valeurs typées
 *
 * Design décision : on utilise des fichiers JSON plutôt qu'une base de données
 * pour la v1 (cf. décision Q1 du cadrage). Pour la v2, cette classe sera
 * remplacée par MysqlStorage avec la même interface.
 *
 * Usage :
 *   $store = new FileStorage();
 *   $store->read('data/banque/index.json');              // array|null
 *   $store->write('data/banque/index.json', ['foo']);    // bool
 *   $store->update('data/banque/index.json', function($data) {
 *       $data['count'] = ($data['count'] ?? 0) + 1;
 *       return $data;
 *   });
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class FileStorage
{
    private Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?? new Logger('storage');
    }

    /**
     * Lit un fichier JSON. Retourne null si le fichier n'existe pas.
     *
     * @return array|null Contenu décodé ou null
     */
    public function read(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $handle = @fopen($path, 'r');
        if ($handle === false) {
            $this->logger->error('Impossible d\'ouvrir le fichier en lecture', ['path' => $path]);
            return null;
        }

        // Verrouillage partagé (plusieurs lectures possibles simultanément)
        if (!flock($handle, LOCK_SH)) {
            fclose($handle);
            $this->logger->error('Impossible de verrouiller le fichier en lecture', ['path' => $path]);
            return null;
        }

        $content = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        if ($content === false || $content === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('JSON invalide dans le fichier', [
                'path' => $path,
                'error' => json_last_error_msg(),
            ]);
            return null;
        }

        return $decoded;
    }

    /**
     * Écrit un tableau dans un fichier JSON, de manière atomique.
     *
     * @return bool true si succès
     */
    public function write(string $path, array $data): bool
    {
        // Créer le dossier parent si nécessaire
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0750, true) && !is_dir($dir)) {
                $this->logger->error('Impossible de créer le dossier', ['dir' => $dir]);
                return false;
            }
        }

        $json = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        if ($json === false) {
            $this->logger->error('Impossible d\'encoder les données en JSON', [
                'path' => $path,
                'error' => json_last_error_msg(),
            ]);
            return false;
        }

        // Écriture atomique : écrire dans un fichier temporaire, puis rename
        $tmpPath = $path . '.tmp.' . bin2hex(random_bytes(4));

        $handle = @fopen($tmpPath, 'w');
        if ($handle === false) {
            $this->logger->error('Impossible d\'ouvrir le fichier temporaire', ['path' => $tmpPath]);
            return false;
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            @unlink($tmpPath);
            $this->logger->error('Impossible de verrouiller le fichier temporaire', ['path' => $tmpPath]);
            return false;
        }

        $written = fwrite($handle, $json);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        if ($written === false) {
            @unlink($tmpPath);
            return false;
        }

        // Rename atomique (garanti atomique sur même filesystem)
        if (!rename($tmpPath, $path)) {
            @unlink($tmpPath);
            $this->logger->error('Impossible de renommer le fichier temporaire', [
                'tmp' => $tmpPath,
                'target' => $path,
            ]);
            return false;
        }

        @chmod($path, 0640);
        return true;
    }

    /**
     * Modifie un fichier JSON de manière atomique via un callback.
     * Lit, applique le callback, écrit, le tout sous verrou exclusif.
     *
     * @param callable $callback function(array $data): array
     * @return bool true si succès
     */
    public function update(string $path, callable $callback): bool
    {
        // Créer le dossier si nécessaire
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0750, true) && !is_dir($dir)) {
                return false;
            }
        }

        // Ouvrir en lecture+écriture, créer si absent
        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            $this->logger->error('Impossible d\'ouvrir le fichier pour update', ['path' => $path]);
            return false;
        }

        // Verrou exclusif (bloquant)
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return false;
        }

        // Lire le contenu actuel
        $content = stream_get_contents($handle);
        $data = [];
        if (!empty($content)) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        // Appliquer le callback
        try {
            $newData = $callback($data);
        } catch (\Throwable $e) {
            flock($handle, LOCK_UN);
            fclose($handle);
            $this->logger->error('Callback update a lancé une exception', [
                'path' => $path,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }

        if (!is_array($newData)) {
            flock($handle, LOCK_UN);
            fclose($handle);
            $this->logger->error('Le callback update doit retourner un array', ['path' => $path]);
            return false;
        }

        // Réécrire le fichier depuis le début
        $json = json_encode(
            $newData,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        if ($json === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return false;
        }

        rewind($handle);
        ftruncate($handle, 0);
        $written = fwrite($handle, $json);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        @chmod($path, 0640);

        return $written !== false;
    }

    /**
     * Vérifie si un fichier existe.
     */
    public function exists(string $path): bool
    {
        return file_exists($path) && is_readable($path);
    }

    /**
     * Supprime un fichier (si existant).
     */
    public function delete(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }
        return @unlink($path);
    }

    /**
     * Liste les fichiers d'un dossier correspondant à un motif glob.
     *
     * @return array<string> Chemins absolus
     */
    public function glob(string $pattern): array
    {
        $result = @glob($pattern);
        return $result === false ? [] : $result;
    }
}
