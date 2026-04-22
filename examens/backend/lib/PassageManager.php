<?php
/**
 * PassageManager.php — Gestion des passages d'examen (etudiants)
 *
 * Responsabilites :
 *   - Demarrage d'un passage (start)
 *   - Sauvegarde progressive (saveProgress / logFocusEvent)
 *   - Soumission finale avec calcul score (submit)
 *   - Expiration automatique (checkExpiration)
 *   - Signature cryptographique SHA-256 (anti-fraude)
 *   - Respect de max_passages par etudiant/email
 *   - Stockage : data/passages/{id}.json (1 fichier par passage)
 *
 * Securite :
 *   - Token unique UUID v4 par passage
 *   - Signature SHA-256 calculee sur submit() avec SALT secret
 *   - Verification fenetre temps (date_ouverture/cloture de l'examen)
 *   - Verification de unicite email + examen + max_passages
 *
 * Modele de passage :
 *   {
 *     id, examen_id, token, access_code_used,
 *     student_info: {nom, prenom, email},
 *     question_order: [...],
 *     option_shuffle_maps: {qId: [0,2,1,3]},
 *     answers: {qId: {answer_index, timestamp}},
 *     start_time, end_time, duration_sec,
 *     status: in_progress|submitted|expired|invalidated,
 *     score_brut, score_max, score_pct,
 *     signature_sha256,
 *     focus_events: [{type, timestamp, duration_ms}],
 *     created_at, updated_at
 *   }
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class PassageManager
{
    private FileStorage $storage;
    private Logger $logger;
    private ExamenManager $examens;
    private BanqueManager $banque;
    private string $passagesDir;
    private string $salt;

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_INVALIDATED = 'invalidated';

    public const ALL_STATUSES = [
        self::STATUS_IN_PROGRESS,
        self::STATUS_SUBMITTED,
        self::STATUS_EXPIRED,
        self::STATUS_INVALIDATED,
    ];

    /** Evenements de focus-lock */
    public const FOCUS_EVENTS = ['blur', 'focus', 'visibility_change', 'copy', 'paste', 'rightclick', 'devtools'];

    public function __construct(
        ?FileStorage $storage = null,
        ?Logger $logger = null,
        ?ExamenManager $examens = null,
        ?BanqueManager $banque = null,
        ?string $passagesDir = null,
        ?string $salt = null
    ) {
        $this->storage = $storage ?? new FileStorage();
        $this->logger = $logger ?? new Logger('passages');
        $this->banque = $banque ?? new BanqueManager($this->storage, $this->logger);
        $this->examens = $examens ?? new ExamenManager($this->storage, $this->logger, $this->banque);

        if ($passagesDir !== null) {
            $this->passagesDir = $passagesDir;
        } elseif (function_exists('data_path')) {
            $this->passagesDir = data_path('passages');
        } else {
            $this->passagesDir = realpath(__DIR__ . '/../../data/passages') ?: __DIR__ . '/../../data/passages';
        }

        // Creer dossier si necessaire
        if (!is_dir($this->passagesDir)) {
            @mkdir($this->passagesDir, 0750, true);
        }

        // Salt pour signature (depuis config ou ENV)
        $this->salt = $salt ?? (defined('PASSAGE_SIGNATURE_SALT')
            ? PASSAGE_SIGNATURE_SALT
            : 'IPSSI_EXAMENS_SALT_CHANGE_ME_IN_CONFIG_PHP_' . date('Y'));
    }

    // ========================================================================
    // DEMARRAGE
    // ========================================================================

    /**
     * Demarrer un passage d'examen.
     *
     * Flow :
     *   1. Verifier que l'examen existe et est accessible (status published + fenetre temps)
     *   2. Verifier max_passages pour ce email
     *   3. Generer token unique + shuffle questions/options
     *   4. Creer le fichier de passage
     *   5. Retourner le passage avec le token
     *
     * @param string $examenId ID de l'examen (EXM-XXXX-XXXX)
     * @param array $studentInfo {nom, prenom, email}
     * @return array Le passage cree (avec token)
     * @throws \InvalidArgumentException Si validation echoue
     * @throws \RuntimeException Si examen non accessible
     */
    public function start(string $examenId, array $studentInfo): array
    {
        // 1. Validation des infos etudiant
        $errors = $this->validateStudentInfo($studentInfo);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Infos etudiant invalides : ' . json_encode($errors));
        }

        // 2. Recuperer et verifier l'examen
        $examen = $this->examens->get($examenId);
        if ($examen === null) {
            throw new \RuntimeException("Examen introuvable : $examenId");
        }

        if ($examen['status'] !== ExamenManager::STATUS_PUBLISHED) {
            throw new \RuntimeException(
                "Examen non disponible : status='{$examen['status']}' (attendu : published)"
            );
        }

        // Fenetre temps
        $now = time();
        $openTs = strtotime($examen['date_ouverture']);
        $closeTs = strtotime($examen['date_cloture']);

        if ($now < $openTs) {
            throw new \RuntimeException(
                "Examen pas encore ouvert. Ouverture : " . date('d/m/Y H:i', $openTs)
            );
        }

        if ($now > $closeTs) {
            throw new \RuntimeException(
                "Examen deja ferme. Cloture : " . date('d/m/Y H:i', $closeTs)
            );
        }

        // 3. Verifier max_passages pour cet email
        $email = strtolower(trim($studentInfo['email']));
        $existingPassages = $this->countPassagesByEmailAndExam($email, $examenId);
        $maxPassages = (int) ($examen['max_passages'] ?? 1);

        if ($existingPassages >= $maxPassages) {
            throw new \RuntimeException(
                "Nombre maximal de passages atteint : $existingPassages / $maxPassages"
            );
        }

        // 4. Preparer ordre des questions (shuffle si demande)
        $questions = $examen['questions'];
        if ($examen['shuffle_questions'] ?? true) {
            shuffle($questions);
        }

        // 5. Preparer shuffle des options par question (si demande)
        $optionShuffleMaps = [];
        if ($examen['shuffle_options'] ?? true) {
            foreach ($questions as $qId) {
                $map = [0, 1, 2, 3];
                shuffle($map);
                $optionShuffleMaps[$qId] = $map;
            }
        } else {
            foreach ($questions as $qId) {
                $optionShuffleMaps[$qId] = [0, 1, 2, 3]; // identite
            }
        }

        // 6. Construire le passage
        $passage = [
            'id' => $this->generateId(),
            'examen_id' => $examenId,
            'token' => $this->generateToken(),
            'access_code_used' => $examen['access_code'] ?? '',
            'student_info' => [
                'nom' => trim($studentInfo['nom']),
                'prenom' => trim($studentInfo['prenom']),
                'email' => $email,
            ],
            'question_order' => $questions,
            'option_shuffle_maps' => $optionShuffleMaps,
            'answers' => (object) [], // objet JSON vide (pas tableau)
            'start_time' => date('c'),
            'end_time' => null,
            'duration_sec' => 0,
            'status' => self::STATUS_IN_PROGRESS,
            'score_brut' => null,
            'score_max' => count($questions),
            'score_pct' => null,
            'signature_sha256' => null,
            'focus_events' => [],
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];

        // 7. Ecriture
        $path = $this->passagePath($passage['id']);
        if (!$this->storage->write($path, $passage)) {
            throw new \RuntimeException('Echec ecriture passage');
        }

        $this->logger->info('Passage demarre', [
            'id' => $passage['id'],
            'examen_id' => $examenId,
            'email' => $email,
            'nb_questions' => count($questions),
        ]);

        return $passage;
    }

    // ========================================================================
    // SAUVEGARDE PROGRESSIVE
    // ========================================================================

    /**
     * Sauvegarder une reponse pendant le passage.
     *
     * @param string $token Token unique du passage
     * @param string $questionId ID de la question
     * @param int $answerIndex Index de la reponse (0-3, apres shuffle)
     * @return array Le passage mis a jour
     */
    public function saveAnswer(string $token, string $questionId, int $answerIndex): array
    {
        $passage = $this->getByToken($token);
        if ($passage === null) {
            throw new \RuntimeException("Passage introuvable : token=$token");
        }

        if ($passage['status'] !== self::STATUS_IN_PROGRESS) {
            throw new \RuntimeException("Passage non modifiable : status='{$passage['status']}'");
        }

        // Verifier que la question fait bien partie de l'examen
        if (!in_array($questionId, $passage['question_order'], true)) {
            throw new \InvalidArgumentException("Question $questionId pas dans cet examen");
        }

        // Verifier que l'answer_index est valide (0-3)
        if ($answerIndex < 0 || $answerIndex > 3) {
            throw new \InvalidArgumentException("answer_index invalide : $answerIndex (attendu : 0-3)");
        }

        // Verifier qu'on n'est pas en retard (timer expire)
        if ($this->isExpired($passage)) {
            $this->expire($passage['id']);
            throw new \RuntimeException("Temps ecoule, passage expire");
        }

        // Update answer
        $answers = (array) ($passage['answers'] ?? []);
        $answers[$questionId] = [
            'answer_index' => $answerIndex,
            'timestamp' => date('c'),
        ];

        $passage['answers'] = $answers;
        $passage['updated_at'] = date('c');

        $path = $this->passagePath($passage['id']);
        $this->storage->write($path, $passage);

        return $passage;
    }

    /**
     * Logger un evenement de focus-lock (anti-triche).
     */
    public function logFocusEvent(string $token, array $event): array
    {
        $passage = $this->getByToken($token);
        if ($passage === null) {
            throw new \RuntimeException("Passage introuvable : token=$token");
        }

        // Validation event
        $type = $event['type'] ?? null;
        if (!in_array($type, self::FOCUS_EVENTS, true)) {
            throw new \InvalidArgumentException(
                "Type d'event invalide : $type. Valides : " . implode(', ', self::FOCUS_EVENTS)
            );
        }

        $focusEvents = $passage['focus_events'] ?? [];
        $focusEvents[] = [
            'type' => $type,
            'timestamp' => date('c'),
            'duration_ms' => (int) ($event['duration_ms'] ?? 0),
            'details' => $event['details'] ?? null,
        ];

        $passage['focus_events'] = $focusEvents;
        $passage['updated_at'] = date('c');

        $path = $this->passagePath($passage['id']);
        $this->storage->write($path, $passage);

        // Log critique pour anomalies graves
        if (in_array($type, ['copy', 'paste', 'devtools'], true)) {
            $this->logger->warning('Focus-lock anomalie grave', [
                'passage_id' => $passage['id'],
                'email' => $passage['student_info']['email'] ?? null,
                'type' => $type,
            ]);
        }

        return $passage;
    }

    // ========================================================================
    // SOUMISSION
    // ========================================================================

    /**
     * Soumettre le passage (finalisation).
     *
     * Flow :
     *   1. Verifier statut in_progress
     *   2. Calculer le score
     *   3. Generer signature SHA-256
     *   4. Marquer comme submitted
     */
    public function submit(string $token): array
    {
        $passage = $this->getByToken($token);
        if ($passage === null) {
            throw new \RuntimeException("Passage introuvable : token=$token");
        }

        if ($passage['status'] !== self::STATUS_IN_PROGRESS) {
            throw new \RuntimeException("Passage non soumettable : status='{$passage['status']}'");
        }

        // Calculer duree
        $startTs = strtotime($passage['start_time']);
        $endTs = time();
        $duration = max(0, $endTs - $startTs);

        // Calculer score
        $score = $this->calculateScore($passage);

        // Mise a jour
        $passage['end_time'] = date('c');
        $passage['duration_sec'] = $duration;
        $passage['status'] = self::STATUS_SUBMITTED;
        $passage['score_brut'] = $score['correct'];
        $passage['score_max'] = $score['total'];
        $passage['score_pct'] = $score['pct'];
        $passage['score_details'] = $score['details'];
        $passage['updated_at'] = date('c');

        // Signature SHA-256 (calculee a la FIN, sur les donnees finales)
        $passage['signature_sha256'] = $this->computeSignature($passage);

        $path = $this->passagePath($passage['id']);
        $this->storage->write($path, $passage);

        $this->logger->info('Passage soumis', [
            'id' => $passage['id'],
            'examen_id' => $passage['examen_id'],
            'email' => $passage['student_info']['email'],
            'score_brut' => $score['correct'],
            'score_pct' => $score['pct'],
            'duration_sec' => $duration,
        ]);

        return $passage;
    }

    /**
     * Forcer l'expiration d'un passage (temps ecoule).
     */
    public function expire(string $passageId): array
    {
        $passage = $this->get($passageId);
        if ($passage === null) {
            throw new \RuntimeException("Passage introuvable : $passageId");
        }

        if ($passage['status'] !== self::STATUS_IN_PROGRESS) {
            return $passage; // idempotent
        }

        // Meme flow que submit mais avec status=expired
        $score = $this->calculateScore($passage);
        $startTs = strtotime($passage['start_time']);

        $passage['end_time'] = date('c');
        $passage['duration_sec'] = max(0, time() - $startTs);
        $passage['status'] = self::STATUS_EXPIRED;
        $passage['score_brut'] = $score['correct'];
        $passage['score_max'] = $score['total'];
        $passage['score_pct'] = $score['pct'];
        $passage['score_details'] = $score['details'];
        $passage['updated_at'] = date('c');
        $passage['signature_sha256'] = $this->computeSignature($passage);

        $path = $this->passagePath($passage['id']);
        $this->storage->write($path, $passage);

        $this->logger->info('Passage expire (temps ecoule)', [
            'id' => $passageId,
            'score_pct' => $score['pct'],
        ]);

        return $passage;
    }

    /**
     * Invalider un passage (prof/admin, en cas de fraude detectee).
     */
    public function invalidate(string $passageId, string $reason = ''): array
    {
        $passage = $this->get($passageId);
        if ($passage === null) {
            throw new \RuntimeException("Passage introuvable : $passageId");
        }

        $passage['status'] = self::STATUS_INVALIDATED;
        $passage['invalidation_reason'] = $reason;
        $passage['updated_at'] = date('c');

        $path = $this->passagePath($passage['id']);
        $this->storage->write($path, $passage);

        $this->logger->warning('Passage invalide', ['id' => $passageId, 'reason' => $reason]);

        return $passage;
    }

    // ========================================================================
    // LECTURE
    // ========================================================================

    public function get(string $id): ?array
    {
        $path = $this->passagePath($id);
        return file_exists($path) ? $this->storage->read($path) : null;
    }

    public function getByToken(string $token): ?array
    {
        if (!is_dir($this->passagesDir)) return null;

        foreach (glob($this->passagesDir . '/*.json') as $file) {
            $passage = $this->storage->read($file);
            if ($passage !== null && ($passage['token'] ?? '') === $token) {
                return $passage;
            }
        }
        return null;
    }

    /**
     * Lister les passages avec filtres.
     *
     * @param array $filters {
     *   examen_id?, email?, status?, since?, until?
     * }
     */
    public function list(array $filters = []): array
    {
        $passages = [];
        if (!is_dir($this->passagesDir)) return [];

        foreach (glob($this->passagesDir . '/*.json') as $file) {
            $passage = $this->storage->read($file);
            if ($passage === null) continue;
            if (!$this->matchesFilters($passage, $filters)) continue;
            $passages[] = $passage;
        }

        // Tri par start_time desc
        usort($passages, function ($a, $b) {
            return strcmp($b['start_time'] ?? '', $a['start_time'] ?? '');
        });

        return $passages;
    }

    /**
     * Compter les passages d'un etudiant pour un examen.
     */
    public function countPassagesByEmailAndExam(string $email, string $examenId): int
    {
        $email = strtolower(trim($email));
        $count = 0;
        foreach ($this->list(['examen_id' => $examenId]) as $p) {
            if (strtolower($p['student_info']['email'] ?? '') === $email) {
                $count++;
            }
        }
        return $count;
    }

    // ========================================================================
    // CALCUL SCORE
    // ========================================================================

    /**
     * Calculer le score d'un passage.
     *
     * Compare les reponses etudiant avec les bonnes reponses en banque,
     * en tenant compte des shuffle_maps (remappe l'index shuffle -> original).
     */
    public function calculateScore(array $passage): array
    {
        $correct = 0;
        $total = count($passage['question_order'] ?? []);
        $details = [];
        $answers = (array) ($passage['answers'] ?? []);
        $shuffleMaps = $passage['option_shuffle_maps'] ?? [];

        foreach ($passage['question_order'] ?? [] as $qId) {
            $question = $this->banque->getQuestion($qId);
            if ($question === null) {
                $details[] = [
                    'question_id' => $qId,
                    'status' => 'not_found',
                    'correct' => false,
                ];
                continue;
            }

            $goodAnswerOriginal = $question['correct'] ?? 0;
            $userAnswer = $answers[$qId]['answer_index'] ?? null;

            if ($userAnswer === null) {
                $details[] = [
                    'question_id' => $qId,
                    'status' => 'not_answered',
                    'correct' => false,
                    'good_answer_original' => $goodAnswerOriginal,
                ];
                continue;
            }

            // Remapper la reponse shuffle -> original
            // shuffleMaps[qId] = [2, 0, 3, 1] signifie :
            //   position 0 affichee = option originale 2
            //   position 1 affichee = option originale 0
            // Donc si user a cliqué position X, il a choisi shuffleMap[qId][X]
            $map = $shuffleMaps[$qId] ?? [0, 1, 2, 3];
            $userAnswerOriginal = $map[$userAnswer] ?? $userAnswer;

            $isCorrect = $userAnswerOriginal === $goodAnswerOriginal;
            if ($isCorrect) $correct++;

            $details[] = [
                'question_id' => $qId,
                'difficulte' => $question['difficulte'] ?? 'inconnu',
                'type' => $question['type'] ?? 'inconnu',
                'user_answer' => $userAnswer,
                'user_answer_original' => $userAnswerOriginal,
                'good_answer_original' => $goodAnswerOriginal,
                'correct' => $isCorrect,
                'status' => 'answered',
            ];
        }

        $pct = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

        return [
            'correct' => $correct,
            'total' => $total,
            'pct' => $pct,
            'details' => $details,
        ];
    }

    // ========================================================================
    // SIGNATURE CRYPTOGRAPHIQUE (anti-fraude)
    // ========================================================================

    /**
     * Calculer la signature SHA-256 d'un passage soumis.
     *
     * Hash de : [nom, prenom, email, start_time, end_time, duration_sec,
     *            score_brut, score_max, question_order, answers, SALT]
     *
     * Cette signature permet de prouver l'integrite du passage.
     */
    public function computeSignature(array $passage): string
    {
        $data = [
            'id' => $passage['id'] ?? '',
            'examen_id' => $passage['examen_id'] ?? '',
            'nom' => $passage['student_info']['nom'] ?? '',
            'prenom' => $passage['student_info']['prenom'] ?? '',
            'email' => $passage['student_info']['email'] ?? '',
            'start_time' => $passage['start_time'] ?? '',
            'end_time' => $passage['end_time'] ?? '',
            'duration_sec' => $passage['duration_sec'] ?? 0,
            'score_brut' => $passage['score_brut'] ?? 0,
            'score_max' => $passage['score_max'] ?? 0,
            'question_order' => $passage['question_order'] ?? [],
            'answers' => $passage['answers'] ?? [],
            'SALT' => $this->salt,
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha256', $json);
    }

    /**
     * Verifier la signature d'un passage soumis.
     */
    public function verifySignature(array $passage): bool
    {
        $storedSig = $passage['signature_sha256'] ?? null;
        if ($storedSig === null) return false;

        $computedSig = $this->computeSignature($passage);
        return hash_equals($storedSig, $computedSig);
    }

    // ========================================================================
    // VALIDATION
    // ========================================================================

    private function validateStudentInfo(array $info): array
    {
        $errors = [];

        if (empty($info['nom']) || trim($info['nom']) === '') {
            $errors[] = 'nom obligatoire';
        } elseif (mb_strlen($info['nom']) > 100) {
            $errors[] = 'nom trop long (max 100 chars)';
        }

        if (empty($info['prenom']) || trim($info['prenom']) === '') {
            $errors[] = 'prenom obligatoire';
        } elseif (mb_strlen($info['prenom']) > 100) {
            $errors[] = 'prenom trop long (max 100 chars)';
        }

        if (empty($info['email']) || !filter_var($info['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email obligatoire et valide';
        }

        return $errors;
    }

    /**
     * Verifier si un passage in_progress a depasse son temps.
     */
    public function isExpired(array $passage): bool
    {
        if ($passage['status'] !== self::STATUS_IN_PROGRESS) {
            return false;
        }

        $examen = $this->examens->get($passage['examen_id']);
        if ($examen === null) return true;

        $startTs = strtotime($passage['start_time']);
        $dureeSec = (int) ($examen['duree_sec'] ?? 0);
        $deadlineTs = $startTs + $dureeSec;

        // Aussi verifier la fenetre d'ouverture de l'examen
        $examCloseTs = strtotime($examen['date_cloture']);
        $effectiveDeadline = min($deadlineTs, $examCloseTs);

        return time() > $effectiveDeadline;
    }

    // ========================================================================
    // STATISTIQUES
    // ========================================================================

    public function getStats(?string $examenId = null): array
    {
        $filters = $examenId ? ['examen_id' => $examenId] : [];
        $all = $this->list($filters);

        $byStatus = array_fill_keys(self::ALL_STATUSES, 0);
        $scores = [];
        $durations = [];
        $focusAnomalies = 0;

        foreach ($all as $p) {
            $status = $p['status'] ?? 'unknown';
            if (isset($byStatus[$status])) $byStatus[$status]++;

            if ($p['score_pct'] !== null) {
                $scores[] = $p['score_pct'];
            }
            if ($p['duration_sec'] > 0) {
                $durations[] = $p['duration_sec'];
            }
            // Compter anomalies graves
            foreach (($p['focus_events'] ?? []) as $evt) {
                if (in_array($evt['type'] ?? '', ['copy', 'paste', 'devtools'], true)) {
                    $focusAnomalies++;
                    break;
                }
            }
        }

        return [
            'total' => count($all),
            'by_status' => $byStatus,
            'avg_score_pct' => !empty($scores) ? round(array_sum($scores) / count($scores), 2) : 0,
            'min_score_pct' => !empty($scores) ? min($scores) : 0,
            'max_score_pct' => !empty($scores) ? max($scores) : 0,
            'avg_duration_sec' => !empty($durations) ? (int) (array_sum($durations) / count($durations)) : 0,
            'anomalies_count' => $focusAnomalies,
            'anomalies_pct' => count($all) > 0 ? round(($focusAnomalies / count($all)) * 100, 2) : 0,
        ];
    }

    // ========================================================================
    // HELPERS PRIVES
    // ========================================================================

    private function matchesFilters(array $passage, array $filters): bool
    {
        if (isset($filters['examen_id']) && ($passage['examen_id'] ?? null) !== $filters['examen_id']) {
            return false;
        }

        if (isset($filters['email'])) {
            $email = strtolower($filters['email']);
            $pEmail = strtolower($passage['student_info']['email'] ?? '');
            if ($email !== $pEmail) return false;
        }

        if (isset($filters['status'])) {
            $allowed = (array) $filters['status'];
            if (!in_array($passage['status'] ?? null, $allowed, true)) {
                return false;
            }
        }

        if (isset($filters['since'])) {
            $sinceTs = strtotime($filters['since']);
            $startTs = strtotime($passage['start_time'] ?? '');
            if ($sinceTs !== false && $startTs !== false && $startTs < $sinceTs) {
                return false;
            }
        }

        if (isset($filters['until'])) {
            $untilTs = strtotime($filters['until']);
            $startTs = strtotime($passage['start_time'] ?? '');
            if ($untilTs !== false && $startTs !== false && $startTs > $untilTs) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generer ID unique : PSG-AAAA-BBBB
     */
    private function generateId(): string
    {
        do {
            $part1 = $this->randomCode(4);
            $part2 = $this->randomCode(4);
            $id = "PSG-$part1-$part2";
        } while (file_exists($this->passagePath($id)));
        return $id;
    }

    /**
     * Generer token UUID v4 unique.
     */
    private function generateToken(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }

    /**
     * Code random depuis alphabet lisible.
     */
    private function randomCode(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        $alphaLen = strlen($alphabet);
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $alphaLen - 1)];
        }
        return $code;
    }

    /**
     * Chemin absolu du fichier passage.
     */
    private function passagePath(string $id): string
    {
        if (!preg_match('/^PSG-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $id)) {
            throw new \InvalidArgumentException("ID passage invalide : $id");
        }
        return $this->passagesDir . '/' . $id . '.json';
    }
}
