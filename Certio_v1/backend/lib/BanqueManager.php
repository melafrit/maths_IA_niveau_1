<?php
/**
 * BanqueManager.php — Gestion de la banque de questions (CRUD + tirage + recherche)
 *
 * Responsabilités :
 *   - Lire/écrire les fichiers JSON de la banque (data/banque/{module}/{chapitre}/{theme}.json)
 *   - CRUD sur les questions (create, read, update, delete)
 *   - Tirage aléatoire personnalisable (nombre de questions par niveau)
 *   - Recherche full-text dans énoncés, tags, IDs
 *   - Validation de cohérence (IDs uniques, structure, options)
 *
 * Structure d'un fichier thème :
 *   {
 *     "_meta": { module, chapitre, theme, total_questions, difficulty_distribution, ... },
 *     "questions": [
 *       { id, enonce, options[4], correct, difficulte, type, tags[], hint, explanation, traps, references }
 *     ]
 *   }
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class BanqueManager
{
    private FileStorage $storage;
    private Logger $logger;
    private string $banqueRoot;

    /** Niveaux de difficulté valides */
    public const LEVELS = ['facile', 'moyen', 'difficile', 'expert'];

    /** Types de questions valides */
    public const TYPES = ['conceptuel', 'calcul', 'code', 'formule'];

    /** Nombre d'options par question (strict) */
    public const N_OPTIONS = 4;

    /** Champs obligatoires pour chaque question */
    public const REQUIRED_FIELDS = [
        'id', 'enonce', 'options', 'correct', 'difficulte',
        'type', 'tags', 'hint', 'explanation', 'traps', 'references'
    ];

    public function __construct(?FileStorage $storage = null, ?Logger $logger = null, ?string $banqueRoot = null)
    {
        $this->storage = $storage ?? new FileStorage();
        $this->logger = $logger ?? new Logger('banque');

        // Utilise data_path() si définie (bootstrap chargé), sinon fallback
        if ($banqueRoot !== null) {
            $this->banqueRoot = $banqueRoot;
        } elseif (function_exists('data_path')) {
            $this->banqueRoot = data_path('banque');
        } else {
            $this->banqueRoot = realpath(__DIR__ . '/../../data/banque') ?: __DIR__ . '/../../data/banque';
        }
    }

    // ========================================================================
    // LECTURE — Modules / Chapitres / Thèmes
    // ========================================================================

    /**
     * Liste les modules disponibles (sous-dossiers de data/banque/)
     *
     * @return array Liste ['maths-ia', 'autre-module', ...]
     */
    public function listModules(): array
    {
        if (!is_dir($this->banqueRoot)) {
            return [];
        }

        $modules = [];
        foreach (scandir($this->banqueRoot) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $this->banqueRoot . '/' . $entry;
            if (is_dir($path)) {
                $modules[] = $entry;
            }
        }
        sort($modules);
        return $modules;
    }

    /**
     * Liste les chapitres d'un module (ex: j1-representation, j2-optimisation, ...)
     *
     * @param string $module Nom du module (ex: 'maths-ia')
     * @return array Liste ordonnée alphabétiquement
     */
    public function listChapitres(string $module): array
    {
        $moduleDir = $this->banqueRoot . '/' . $this->sanitize($module);
        if (!is_dir($moduleDir)) {
            return [];
        }

        $chapitres = [];
        foreach (scandir($moduleDir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $moduleDir . '/' . $entry;
            if (is_dir($path)) {
                $chapitres[] = $entry;
            }
        }
        sort($chapitres);
        return $chapitres;
    }

    /**
     * Liste les thèmes d'un chapitre (fichiers *.json)
     *
     * @param string $module
     * @param string $chapitre
     * @return array Liste des noms de thèmes (sans .json)
     */
    public function listThemes(string $module, string $chapitre): array
    {
        $chapDir = $this->banqueRoot . '/' . $this->sanitize($module) . '/' . $this->sanitize($chapitre);
        if (!is_dir($chapDir)) {
            return [];
        }

        $themes = [];
        foreach (scandir($chapDir) as $entry) {
            if (str_ends_with($entry, '.json')) {
                $themes[] = substr($entry, 0, -5); // retirer .json
            }
        }
        sort($themes);
        return $themes;
    }

    /**
     * Charge un thème complet (fichier JSON entier : _meta + questions)
     *
     * @param string $module
     * @param string $chapitre
     * @param string $theme
     * @return array|null null si introuvable
     */
    public function getTheme(string $module, string $chapitre, string $theme): ?array
    {
        $path = $this->themePath($module, $chapitre, $theme);
        if (!file_exists($path)) {
            return null;
        }

        $data = $this->storage->read($path);
        if ($data === null) {
            $this->logger->warning('Thème illisible', ['path' => $path]);
            return null;
        }

        return $data;
    }

    // ========================================================================
    // CRUD Questions (Priorité #1)
    // ========================================================================

    /**
     * Liste toutes les questions avec filtres optionnels.
     *
     * @param array $filters {
     *   module?: string,
     *   chapitre?: string,
     *   theme?: string,
     *   difficulte?: string|array,
     *   type?: string|array,
     *   tags?: array (AU MOINS UN de ces tags présent)
     * }
     * @return array Tableau de questions enrichies avec 'module', 'chapitre', 'theme'
     */
    public function listQuestions(array $filters = []): array
    {
        $results = [];
        $modules = isset($filters['module']) ? [$filters['module']] : $this->listModules();

        foreach ($modules as $module) {
            $chapitres = isset($filters['chapitre']) ? [$filters['chapitre']] : $this->listChapitres($module);

            foreach ($chapitres as $chapitre) {
                $themes = isset($filters['theme']) ? [$filters['theme']] : $this->listThemes($module, $chapitre);

                foreach ($themes as $theme) {
                    $data = $this->getTheme($module, $chapitre, $theme);
                    if ($data === null) continue;

                    foreach ($data['questions'] ?? [] as $q) {
                        if (!$this->matchesFilters($q, $filters)) continue;

                        // Enrichir la question avec son contexte
                        $q['_module'] = $module;
                        $q['_chapitre'] = $chapitre;
                        $q['_theme'] = $theme;
                        $results[] = $q;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Récupère UNE question par son ID (parcourt tous les thèmes)
     *
     * @param string $id Identifiant unique (ex: 'vec-faci-01')
     * @return array|null Question avec contexte, ou null
     */
    public function getQuestion(string $id): ?array
    {
        foreach ($this->listModules() as $module) {
            foreach ($this->listChapitres($module) as $chapitre) {
                foreach ($this->listThemes($module, $chapitre) as $theme) {
                    $data = $this->getTheme($module, $chapitre, $theme);
                    if ($data === null) continue;

                    foreach ($data['questions'] ?? [] as $q) {
                        if (($q['id'] ?? null) === $id) {
                            $q['_module'] = $module;
                            $q['_chapitre'] = $chapitre;
                            $q['_theme'] = $theme;
                            return $q;
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * Créer une nouvelle question dans un thème existant.
     *
     * @param string $module
     * @param string $chapitre
     * @param string $theme
     * @param array $question Données de la question (sans _meta fields)
     * @return array Question créée
     * @throws \InvalidArgumentException Si données invalides
     * @throws \RuntimeException Si ID en conflit ou thème introuvable
     */
    public function createQuestion(string $module, string $chapitre, string $theme, array $question): array
    {
        // 1. Validation structure
        $errors = $this->validateQuestion($question);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Question invalide : ' . json_encode($errors));
        }

        $path = $this->themePath($module, $chapitre, $theme);
        if (!file_exists($path)) {
            throw new \RuntimeException("Thème introuvable : $module/$chapitre/$theme");
        }

        // 2. Vérifier unicité de l'ID (dans TOUTE la banque)
        if ($this->getQuestion($question['id']) !== null) {
            throw new \RuntimeException("ID déjà existant : " . $question['id']);
        }

        // 3. Mise à jour atomique via FileStorage::update
        $success = $this->storage->update($path, function(array $data) use ($question) {
            $data['questions'][] = $question;

            // Mettre à jour _meta
            if (!isset($data['_meta'])) {
                $data['_meta'] = [];
            }
            $data['_meta']['total_questions'] = count($data['questions']);
            $data['_meta']['difficulty_distribution'] = $this->countByDifficulty($data['questions']);
            $data['_meta']['updated_at'] = date('c');

            return $data;
        });

        if (!$success) {
            throw new \RuntimeException('Échec écriture thème');
        }

        $this->logger->info('Question créée', ['id' => $question['id'], 'theme' => $theme]);
        return $question;
    }

    /**
     * Mettre à jour une question existante.
     *
     * @param string $id
     * @param array $updates Champs à modifier
     * @return array Question mise à jour
     * @throws \RuntimeException Si question introuvable
     */
    public function updateQuestion(string $id, array $updates): array
    {
        $q = $this->getQuestion($id);
        if ($q === null) {
            throw new \RuntimeException("Question introuvable : $id");
        }

        $module = $q['_module'];
        $chapitre = $q['_chapitre'];
        $theme = $q['_theme'];

        // Empêcher le changement d'ID (pour cohérence cross-examens)
        if (isset($updates['id']) && $updates['id'] !== $id) {
            throw new \InvalidArgumentException("Impossible de modifier l'ID d'une question");
        }
        unset($updates['id']);

        // Fusionner les mises à jour
        unset($q['_module'], $q['_chapitre'], $q['_theme']);
        $merged = array_merge($q, $updates);

        // Valider la question mise à jour
        $errors = $this->validateQuestion($merged);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Question invalide après update : ' . json_encode($errors));
        }

        // Écrire
        $path = $this->themePath($module, $chapitre, $theme);
        $success = $this->storage->update($path, function(array $data) use ($id, $merged) {
            foreach ($data['questions'] as &$q) {
                if ($q['id'] === $id) {
                    $q = $merged;
                    break;
                }
            }
            unset($q);
            $data['_meta']['difficulty_distribution'] = $this->countByDifficulty($data['questions']);
            $data['_meta']['updated_at'] = date('c');
            return $data;
        });

        if (!$success) {
            throw new \RuntimeException('Échec mise à jour question');
        }

        $this->logger->info('Question mise à jour', ['id' => $id]);
        $merged['_module'] = $module;
        $merged['_chapitre'] = $chapitre;
        $merged['_theme'] = $theme;
        return $merged;
    }

    /**
     * Supprimer une question.
     *
     * @param string $id
     * @return bool true si supprimée
     * @throws \RuntimeException Si question introuvable
     */
    public function deleteQuestion(string $id): bool
    {
        $q = $this->getQuestion($id);
        if ($q === null) {
            throw new \RuntimeException("Question introuvable : $id");
        }

        $module = $q['_module'];
        $chapitre = $q['_chapitre'];
        $theme = $q['_theme'];

        $path = $this->themePath($module, $chapitre, $theme);

        $success = $this->storage->update($path, function(array $data) use ($id) {
            $data['questions'] = array_values(array_filter(
                $data['questions'],
                fn($q) => $q['id'] !== $id
            ));
            $data['_meta']['total_questions'] = count($data['questions']);
            $data['_meta']['difficulty_distribution'] = $this->countByDifficulty($data['questions']);
            $data['_meta']['updated_at'] = date('c');
            return $data;
        });

        if ($success) {
            $this->logger->info('Question supprimée', ['id' => $id]);
        }
        return $success;
    }

    // ========================================================================
    // TIRAGE ALÉATOIRE (Priorité #2) — Stratégie PERSONNALISABLE
    // ========================================================================

    /**
     * Tirer N questions aléatoirement selon une stratégie personnalisable.
     *
     * Stratégie par DÉFAUT : personnalisable via $quotas.
     * Si $quotas = ['facile' => 3, 'moyen' => 3, 'difficile' => 2, 'expert' => 2],
     * on tire 3 questions faciles, 3 moyennes, 2 difficiles, 2 expert (10 au total).
     *
     * @param array $scope Portée du tirage : ['module' => '...', 'chapitre' => ..., 'theme' => ...]
     * @param array $quotas Nombre par niveau : ['facile' => N1, 'moyen' => N2, 'difficile' => N3, 'expert' => N4]
     * @param int|null $seed Seed pour reproductibilité (optional)
     * @return array Questions tirées, mélangées aléatoirement à la fin
     * @throws \InvalidArgumentException Si pas assez de questions pour satisfaire les quotas
     */
    public function drawRandom(array $scope, array $quotas, ?int $seed = null): array
    {
        if ($seed !== null) {
            mt_srand($seed);
        }

        // Récupérer toutes les questions du scope
        $allQuestions = $this->listQuestions($scope);

        // Grouper par niveau
        $byLevel = [];
        foreach (self::LEVELS as $level) {
            $byLevel[$level] = array_filter($allQuestions, fn($q) => ($q['difficulte'] ?? null) === $level);
            $byLevel[$level] = array_values($byLevel[$level]); // réindexer
        }

        // Valider les quotas
        $result = [];
        foreach ($quotas as $level => $n) {
            if (!in_array($level, self::LEVELS, true)) {
                throw new \InvalidArgumentException("Niveau invalide : $level");
            }
            if ($n < 0) {
                throw new \InvalidArgumentException("Quota négatif pour $level");
            }
            if (!isset($byLevel[$level]) || count($byLevel[$level]) < $n) {
                $available = count($byLevel[$level] ?? []);
                throw new \InvalidArgumentException(
                    "Quota $level=$n mais seulement $available question(s) disponible(s)"
                );
            }

            // Tirage aléatoire sans remise
            $pool = $byLevel[$level];
            $indices = array_keys($pool);
            shuffle($indices);
            $selected = array_slice($indices, 0, $n);

            foreach ($selected as $idx) {
                $result[] = $pool[$idx];
            }
        }

        // Mélanger l'ordre final (facile/moyen/difficile ne doivent pas être en bloc)
        shuffle($result);

        return $result;
    }

    /**
     * Stratégie "équitable" : tirage équilibré automatique (helper).
     *
     * Tire N/4 par niveau (si N multiple de 4), sinon répartit au mieux.
     *
     * @param array $scope
     * @param int $n Nombre total
     * @param int|null $seed
     * @return array
     */
    public function drawEquitable(array $scope, int $n, ?int $seed = null): array
    {
        if ($n <= 0 || $n > 400) {
            throw new \InvalidArgumentException('n doit être entre 1 et 400');
        }

        // Répartition équitable (priorité aux niveaux du milieu si imparfait)
        $base = intdiv($n, 4);
        $rem = $n % 4;
        $quotas = [
            'facile' => $base,
            'moyen' => $base + ($rem >= 2 ? 1 : 0),
            'difficile' => $base + ($rem >= 1 ? 1 : 0),
            'expert' => $base + ($rem === 3 ? 1 : 0),
        ];

        return $this->drawRandom($scope, $quotas, $seed);
    }

    // ========================================================================
    // RECHERCHE (Priorité #3)
    // ========================================================================

    /**
     * Recherche full-text dans les questions.
     *
     * @param string $query Terme recherché (case-insensitive)
     * @param array $filters Mêmes filtres que listQuestions()
     * @param array $fields Champs à fouiller ['enonce', 'tags', 'id', 'explanation']
     * @return array Questions matchantes, triées par pertinence (nb occurrences)
     */
    public function searchQuestions(string $query, array $filters = [], array $fields = ['enonce', 'tags', 'id']): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $queryLower = mb_strtolower($query);
        $questions = $this->listQuestions($filters);
        $scored = [];

        foreach ($questions as $q) {
            $score = 0;

            foreach ($fields as $field) {
                $value = $q[$field] ?? null;
                if (is_array($value)) {
                    $value = implode(' ', $value);
                }
                if (!is_string($value) || $value === '') continue;

                $valueLower = mb_strtolower($value);
                // Compter les occurrences
                $count = substr_count($valueLower, $queryLower);
                // Pondération : id > tags > enonce > reste
                $weight = match($field) {
                    'id' => 10,
                    'tags' => 5,
                    'enonce' => 3,
                    default => 1,
                };
                $score += $count * $weight;
            }

            if ($score > 0) {
                $q['_score'] = $score;
                $scored[] = $q;
            }
        }

        // Trier par score décroissant
        usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);

        return $scored;
    }

    // ========================================================================
    // VALIDATION (Priorité #4)
    // ========================================================================

    /**
     * Valider la structure d'une question.
     *
     * @param array $q
     * @return array Liste des erreurs (vide = valide)
     */
    public function validateQuestion(array $q): array
    {
        $errors = [];

        // Champs requis
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $q)) {
                $errors[] = "Champ manquant : $field";
            }
        }

        // ID format
        if (isset($q['id'])) {
            if (!is_string($q['id']) || !preg_match('/^[a-z0-9]{3}-[a-z]{4}-\d{2,}$/', $q['id'])) {
                $errors[] = "ID invalide (format attendu : xxx-yyyy-NN)";
            }
        }

        // Options : tableau de 4 strings
        if (isset($q['options'])) {
            if (!is_array($q['options'])) {
                $errors[] = "options doit être un tableau";
            } elseif (count($q['options']) !== self::N_OPTIONS) {
                $errors[] = "options doit contenir exactement " . self::N_OPTIONS . " éléments";
            } else {
                foreach ($q['options'] as $i => $opt) {
                    if (!is_string($opt) || $opt === '') {
                        $errors[] = "option #$i invalide ou vide";
                    }
                }
            }
        }

        // Correct : 0, 1, 2 ou 3
        if (isset($q['correct'])) {
            if (!is_int($q['correct']) || $q['correct'] < 0 || $q['correct'] >= self::N_OPTIONS) {
                $errors[] = "correct doit être 0, 1, 2 ou 3";
            }
        }

        // Difficulté
        if (isset($q['difficulte']) && !in_array($q['difficulte'], self::LEVELS, true)) {
            $errors[] = "difficulte invalide (attendu: " . implode(', ', self::LEVELS) . ")";
        }

        // Type
        if (isset($q['type']) && !in_array($q['type'], self::TYPES, true)) {
            $errors[] = "type invalide (attendu: " . implode(', ', self::TYPES) . ")";
        }

        // Tags
        if (isset($q['tags']) && !is_array($q['tags'])) {
            $errors[] = "tags doit être un tableau";
        }

        // Strings non vides
        foreach (['enonce', 'hint', 'explanation', 'traps', 'references'] as $field) {
            if (isset($q[$field]) && (!is_string($q[$field]) || trim($q[$field]) === '')) {
                $errors[] = "$field doit être une chaîne non vide";
            }
        }

        return $errors;
    }

    /**
     * Valider la cohérence d'un thème entier.
     *
     * @param string $module
     * @param string $chapitre
     * @param string $theme
     * @return array Rapport de validation : { valid, errors, warnings, stats }
     */
    public function validateTheme(string $module, string $chapitre, string $theme): array
    {
        $data = $this->getTheme($module, $chapitre, $theme);
        if ($data === null) {
            return [
                'valid' => false,
                'errors' => ['Thème introuvable'],
                'warnings' => [],
                'stats' => [],
            ];
        }

        $errors = [];
        $warnings = [];
        $ids = [];
        $byLevel = array_fill_keys(self::LEVELS, 0);
        $byType = [];

        foreach ($data['questions'] ?? [] as $i => $q) {
            // Valider individuellement
            $qErrors = $this->validateQuestion($q);
            foreach ($qErrors as $err) {
                $errors[] = "Q#$i (id=" . ($q['id'] ?? '?') . ") : $err";
            }

            // Unicité des IDs
            $id = $q['id'] ?? null;
            if ($id !== null) {
                if (isset($ids[$id])) {
                    $errors[] = "ID dupliqué : $id (positions $ids[$id] et $i)";
                }
                $ids[$id] = $i;
            }

            // Compter par niveau/type
            $level = $q['difficulte'] ?? null;
            if (in_array($level, self::LEVELS, true)) {
                $byLevel[$level]++;
            }
            $type = $q['type'] ?? null;
            if ($type) {
                $byType[$type] = ($byType[$type] ?? 0) + 1;
            }
        }

        // Vérifier cohérence _meta
        $actualTotal = count($data['questions'] ?? []);
        $metaTotal = $data['_meta']['total_questions'] ?? null;
        if ($metaTotal !== null && $metaTotal !== $actualTotal) {
            $warnings[] = "_meta.total_questions=$metaTotal mais réel=$actualTotal";
        }

        // Équilibre des niveaux (warning si gros déséquilibre)
        $maxLevel = max($byLevel);
        $minLevel = min($byLevel);
        if ($actualTotal >= 4 && $maxLevel > 3 * max(1, $minLevel)) {
            $warnings[] = "Déséquilibre de niveaux : " . json_encode($byLevel);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'stats' => [
                'total' => $actualTotal,
                'by_level' => $byLevel,
                'by_type' => $byType,
                'unique_ids' => count($ids),
            ],
        ];
    }

    // ========================================================================
    // STATISTIQUES GLOBALES
    // ========================================================================

    /**
     * Statistiques globales de la banque.
     *
     * @return array { modules: [...], total_questions, by_module, by_level }
     */
    public function getStats(): array
    {
        $stats = [
            'modules' => [],
            'total_questions' => 0,
            'by_level' => array_fill_keys(self::LEVELS, 0),
            'by_type' => [],
        ];

        foreach ($this->listModules() as $module) {
            $moduleStats = [
                'module' => $module,
                'chapitres' => [],
                'total' => 0,
            ];

            foreach ($this->listChapitres($module) as $chapitre) {
                $chapStats = [
                    'chapitre' => $chapitre,
                    'themes' => [],
                    'total' => 0,
                ];

                foreach ($this->listThemes($module, $chapitre) as $theme) {
                    $data = $this->getTheme($module, $chapitre, $theme);
                    if ($data === null) continue;

                    $count = count($data['questions'] ?? []);
                    $chapStats['themes'][] = [
                        'theme' => $theme,
                        'count' => $count,
                    ];
                    $chapStats['total'] += $count;

                    foreach ($data['questions'] ?? [] as $q) {
                        $stats['total_questions']++;
                        $level = $q['difficulte'] ?? null;
                        if (in_array($level, self::LEVELS, true)) {
                            $stats['by_level'][$level]++;
                        }
                        $type = $q['type'] ?? null;
                        if ($type) {
                            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
                        }
                    }
                }

                $moduleStats['chapitres'][] = $chapStats;
                $moduleStats['total'] += $chapStats['total'];
            }

            $stats['modules'][] = $moduleStats;
        }

        return $stats;
    }

    // ========================================================================
    // HELPERS PRIVÉS
    // ========================================================================

    /**
     * Vérifier qu'une question matche les filtres.
     */
    private function matchesFilters(array $q, array $filters): bool
    {
        // difficulte
        if (isset($filters['difficulte'])) {
            $allowed = (array) $filters['difficulte'];
            if (!in_array($q['difficulte'] ?? null, $allowed, true)) {
                return false;
            }
        }

        // type
        if (isset($filters['type'])) {
            $allowed = (array) $filters['type'];
            if (!in_array($q['type'] ?? null, $allowed, true)) {
                return false;
            }
        }

        // tags (AU MOINS UN présent)
        if (isset($filters['tags']) && is_array($filters['tags']) && !empty($filters['tags'])) {
            $qTags = $q['tags'] ?? [];
            $found = false;
            foreach ($filters['tags'] as $tag) {
                if (in_array($tag, $qTags, true)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) return false;
        }

        return true;
    }

    /**
     * Compter les questions par difficulté (pour _meta).
     */
    private function countByDifficulty(array $questions): array
    {
        $counts = array_fill_keys(self::LEVELS, 0);
        foreach ($questions as $q) {
            $level = $q['difficulte'] ?? null;
            if (in_array($level, self::LEVELS, true)) {
                $counts[$level]++;
            }
        }
        return $counts;
    }

    /**
     * Construire le chemin absolu d'un fichier thème.
     */
    private function themePath(string $module, string $chapitre, string $theme): string
    {
        return $this->banqueRoot . '/' .
               $this->sanitize($module) . '/' .
               $this->sanitize($chapitre) . '/' .
               $this->sanitize($theme) . '.json';
    }

    /**
     * Nettoyer un identifiant de chemin (module/chapitre/theme).
     * Empêche les attaques par traversée de répertoire.
     */
    private function sanitize(string $s): string
    {
        // Autoriser uniquement [a-z0-9_-]
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,64}$/', $s)) {
            throw new \InvalidArgumentException("Identifiant invalide : $s");
        }
        return $s;
    }
}
