<?php
/**
 * AnalyticsManager.php — Agrégations pour analytics de la plateforme
 *
 * Responsabilités :
 *   - Historique des passages avec tri, filtres, recherche
 *   - Distribution des scores (pour histogrammes)
 *   - Stats par question (taux de réussite, distracteurs)
 *   - Heatmap des événements anti-triche
 *   - Analytics par étudiant (historique complet)
 *   - Comparaison multi-examens
 *
 * Note : toutes les agrégations sont calculées à la volée.
 * Pas de cache (acceptable pour <10k passages).
 * Pour scale plus grande, on ajoutera cache + index.
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class AnalyticsManager
{
    private FileStorage $storage;
    private Logger $logger;
    private ExamenManager $examens;
    private PassageManager $passages;
    private BanqueManager $banque;

    public function __construct(
        ?FileStorage $storage = null,
        ?Logger $logger = null,
        ?ExamenManager $examens = null,
        ?PassageManager $passages = null,
        ?BanqueManager $banque = null
    ) {
        $this->storage = $storage ?? new FileStorage();
        $this->logger = $logger ?? new Logger('analytics');
        $this->banque = $banque ?? new BanqueManager($this->storage, $this->logger);
        $this->examens = $examens ?? new ExamenManager($this->storage, $this->logger, $this->banque);
        $this->passages = $passages ?? new PassageManager(
            $this->storage, $this->logger, $this->examens, $this->banque
        );
    }

    // ========================================================================
    // HISTORIQUE DES PASSAGES (sous-phase prioritaire)
    // ========================================================================

    /**
     * Liste enrichie des passages avec tri + filtres + recherche.
     *
     * @param array $options {
     *   examen_id?: string,          // filtre par examen
     *   status?: string|array,       // filtre par status
     *   search?: string,             // recherche dans nom/prenom/email
     *   email?: string,              // filtre exact email
     *   sort?: 'date'|'score'|'name'|'duration',
     *   order?: 'asc'|'desc',
     *   since?: string,
     *   until?: string,
     *   min_score_pct?: float,
     *   max_score_pct?: float,
     *   with_anomalies?: bool,       // filtrer passages avec anomalies graves
     *   limit?: int,
     *   offset?: int,
     * }
     * @return array {passages: [...], total: int, count: int, filters: array}
     */
    public function listPassagesEnriched(array $options = []): array
    {
        $filters = [];
        if (isset($options['examen_id'])) $filters['examen_id'] = $options['examen_id'];
        if (isset($options['status'])) $filters['status'] = $options['status'];
        if (isset($options['email'])) $filters['email'] = $options['email'];
        if (isset($options['since'])) $filters['since'] = $options['since'];
        if (isset($options['until'])) $filters['until'] = $options['until'];

        $passages = $this->passages->list($filters);

        // Recherche textuelle
        if (!empty($options['search'])) {
            $query = mb_strtolower($options['search']);
            $passages = array_filter($passages, function($p) use ($query) {
                $s = $p['student_info'];
                $full = mb_strtolower(
                    ($s['prenom'] ?? '') . ' ' . ($s['nom'] ?? '') . ' ' . ($s['email'] ?? '')
                );
                return str_contains($full, $query);
            });
        }

        // Filtre par plage de score
        if (isset($options['min_score_pct']) || isset($options['max_score_pct'])) {
            $min = $options['min_score_pct'] ?? 0;
            $max = $options['max_score_pct'] ?? 100;
            $passages = array_filter($passages, function($p) use ($min, $max) {
                $pct = $p['score_pct'] ?? null;
                if ($pct === null) return false;
                return $pct >= $min && $pct <= $max;
            });
        }

        // Filtre anomalies
        if (!empty($options['with_anomalies'])) {
            $passages = array_filter($passages, function($p) {
                foreach (($p['focus_events'] ?? []) as $evt) {
                    if (in_array($evt['type'] ?? '', ['copy', 'paste', 'devtools'], true)) {
                        return true;
                    }
                }
                return false;
            });
        }

        // Enrichissement
        $enriched = array_map(function($p) {
            $anomalies = 0;
            foreach (($p['focus_events'] ?? []) as $evt) {
                if (in_array($evt['type'] ?? '', ['copy', 'paste', 'devtools'], true)) {
                    $anomalies++;
                }
            }
            return array_merge($p, [
                'anomalies_count' => $anomalies,
                'focus_events_count' => count($p['focus_events'] ?? []),
                'nb_answered' => count((array)($p['answers'] ?? [])),
            ]);
        }, $passages);
        $enriched = array_values($enriched);

        // Tri
        $sort = $options['sort'] ?? 'date';
        $order = $options['order'] ?? 'desc';
        $dir = $order === 'asc' ? 1 : -1;

        usort($enriched, function($a, $b) use ($sort, $dir) {
            switch ($sort) {
                case 'score':
                    return $dir * (($a['score_pct'] ?? 0) <=> ($b['score_pct'] ?? 0));
                case 'name':
                    $na = mb_strtolower(($a['student_info']['nom'] ?? '') . ($a['student_info']['prenom'] ?? ''));
                    $nb = mb_strtolower(($b['student_info']['nom'] ?? '') . ($b['student_info']['prenom'] ?? ''));
                    return $dir * strcmp($na, $nb);
                case 'duration':
                    return $dir * (($a['duration_sec'] ?? 0) <=> ($b['duration_sec'] ?? 0));
                case 'date':
                default:
                    return $dir * strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
            }
        });

        $total = count($enriched);

        // Pagination
        $limit = isset($options['limit']) ? max(1, min(500, (int)$options['limit'])) : null;
        $offset = max(0, (int)($options['offset'] ?? 0));
        if ($limit !== null) {
            $enriched = array_slice($enriched, $offset, $limit);
        }

        return [
            'passages' => $enriched,
            'total' => $total,
            'count' => count($enriched),
            'offset' => $offset,
            'limit' => $limit,
            'applied' => [
                'sort' => $sort, 'order' => $order,
                'search' => $options['search'] ?? null,
                'with_anomalies' => $options['with_anomalies'] ?? false,
                'min_score_pct' => $options['min_score_pct'] ?? null,
                'max_score_pct' => $options['max_score_pct'] ?? null,
            ],
        ];
    }

    // ========================================================================
    // KPIs / OVERVIEW PAR EXAMEN
    // ========================================================================

    /**
     * Vue d'ensemble d'un examen (KPIs principaux).
     */
    public function getExamenOverview(string $examenId): array
    {
        $examen = $this->examens->get($examenId);
        if ($examen === null) {
            throw new \RuntimeException("Examen introuvable : $examenId");
        }

        $passages = $this->passages->list([
            'examen_id' => $examenId,
            'status' => [PassageManager::STATUS_SUBMITTED, PassageManager::STATUS_EXPIRED],
        ]);

        $scores = [];
        $durations = [];
        $emails = [];
        $anomalyPassages = 0;
        $notAnsweredRatio = [];

        foreach ($passages as $p) {
            if ($p['score_pct'] !== null) $scores[] = $p['score_pct'];
            if ($p['duration_sec'] > 0) $durations[] = $p['duration_sec'];
            $emails[mb_strtolower($p['student_info']['email'] ?? '')] = true;

            $hasAnom = false;
            foreach (($p['focus_events'] ?? []) as $evt) {
                if (in_array($evt['type'] ?? '', ['copy', 'paste', 'devtools'], true)) {
                    $hasAnom = true;
                    break;
                }
            }
            if ($hasAnom) $anomalyPassages++;

            // Ratio de questions non répondues
            $nbQ = count($p['question_order'] ?? []);
            $nbAns = count((array)($p['answers'] ?? []));
            if ($nbQ > 0) {
                $notAnsweredRatio[] = (($nbQ - $nbAns) / $nbQ) * 100;
            }
        }

        $totalPassages = count($passages);
        $avgScore = !empty($scores) ? array_sum($scores) / count($scores) : 0;
        $avgDuration = !empty($durations) ? array_sum($durations) / count($durations) : 0;
        $avgNotAnswered = !empty($notAnsweredRatio) ? array_sum($notAnsweredRatio) / count($notAnsweredRatio) : 0;

        // Écart-type score
        $stdDev = 0;
        if (count($scores) > 1) {
            $sumSq = array_sum(array_map(fn($s) => ($s - $avgScore) ** 2, $scores));
            $stdDev = sqrt($sumSq / (count($scores) - 1));
        }

        // Médiane
        $median = 0;
        if (!empty($scores)) {
            $sorted = $scores;
            sort($sorted);
            $n = count($sorted);
            $median = $n % 2 === 0
                ? ($sorted[$n/2 - 1] + $sorted[$n/2]) / 2
                : $sorted[intdiv($n, 2)];
        }

        return [
            'examen' => [
                'id' => $examen['id'],
                'titre' => $examen['titre'],
                'status' => $examen['status'],
                'nb_questions' => count($examen['questions']),
                'duree_sec' => $examen['duree_sec'],
            ],
            'total_passages' => $totalPassages,
            'unique_students' => count($emails),
            'avg_score_pct' => round($avgScore, 2),
            'median_score_pct' => round($median, 2),
            'min_score_pct' => !empty($scores) ? round(min($scores), 2) : 0,
            'max_score_pct' => !empty($scores) ? round(max($scores), 2) : 0,
            'std_dev' => round($stdDev, 2),
            'avg_duration_sec' => (int)$avgDuration,
            'avg_not_answered_pct' => round($avgNotAnswered, 2),
            'anomaly_passages' => $anomalyPassages,
            'anomaly_rate_pct' => $totalPassages > 0
                ? round(($anomalyPassages / $totalPassages) * 100, 2)
                : 0,
        ];
    }

    // ========================================================================
    // DISTRIBUTION DES SCORES (pour histogramme)
    // ========================================================================

    /**
     * Distribution des scores par tranches de 10%.
     * Renvoie aussi les mentions et le détail.
     */
    public function getScoreDistribution(string $examenId): array
    {
        $passages = $this->passages->list([
            'examen_id' => $examenId,
            'status' => [PassageManager::STATUS_SUBMITTED, PassageManager::STATUS_EXPIRED],
        ]);

        // Tranches 0-9, 10-19, ..., 90-100
        $buckets = array_fill(0, 10, 0);
        $labels = ['0-9', '10-19', '20-29', '30-39', '40-49',
                   '50-59', '60-69', '70-79', '80-89', '90-100'];
        $scores = [];

        foreach ($passages as $p) {
            $pct = $p['score_pct'] ?? null;
            if ($pct === null) continue;
            $scores[] = $pct;
            $idx = min(9, (int)floor($pct / 10));
            $buckets[$idx]++;
        }

        // Mentions
        $mentions = [
            'excellent' => 0,    // >=90
            'tres_bien' => 0,    // 80-89
            'bien' => 0,         // 70-79
            'assez_bien' => 0,   // 60-69
            'passable' => 0,     // 50-59
            'insuffisant' => 0,  // 30-49
            'tres_insuf' => 0,   // <30
        ];
        foreach ($scores as $s) {
            if ($s >= 90) $mentions['excellent']++;
            elseif ($s >= 80) $mentions['tres_bien']++;
            elseif ($s >= 70) $mentions['bien']++;
            elseif ($s >= 60) $mentions['assez_bien']++;
            elseif ($s >= 50) $mentions['passable']++;
            elseif ($s >= 30) $mentions['insuffisant']++;
            else $mentions['tres_insuf']++;
        }

        return [
            'total' => count($scores),
            'histogram' => array_map(function($label, $count) {
                return ['range' => $label, 'count' => $count];
            }, $labels, $buckets),
            'mentions' => $mentions,
            'pass_rate_pct' => !empty($scores)
                ? round((count(array_filter($scores, fn($s) => $s >= 50)) / count($scores)) * 100, 2)
                : 0,
        ];
    }

    // ========================================================================
    // ANALYSE PAR QUESTION + DISTRACTEURS
    // ========================================================================

    /**
     * Pour chaque question de l'examen :
     *   - Nombre total d'étudiants ayant rencontré la Q
     *   - Nombre de bonnes réponses
     *   - Nombre de non-répondues
     *   - Distribution des choix (distracteurs analysis)
     *   - Taux de réussite
     *
     * @param string $examenId
     * @param bool $withFullDetails Inclure l'énoncé complet de chaque Q
     */
    public function getQuestionStats(string $examenId, bool $withFullDetails = false): array
    {
        $passages = $this->passages->list([
            'examen_id' => $examenId,
            'status' => [PassageManager::STATUS_SUBMITTED, PassageManager::STATUS_EXPIRED],
        ]);

        $byQuestion = [];
        foreach ($passages as $p) {
            $shuffleMaps = $p['option_shuffle_maps'] ?? [];
            $answers = (array)($p['answers'] ?? []);

            foreach ($p['question_order'] as $qId) {
                if (!isset($byQuestion[$qId])) {
                    $byQuestion[$qId] = [
                        'question_id' => $qId,
                        'total' => 0,
                        'correct' => 0,
                        'not_answered' => 0,
                        'option_counts' => [0 => 0, 1 => 0, 2 => 0, 3 => 0],
                        'correct_index' => null,
                    ];
                }
                $byQuestion[$qId]['total']++;

                $q = $this->banque->getQuestion($qId);
                if ($q !== null) {
                    $byQuestion[$qId]['correct_index'] = $q['correct'];
                }

                if (!isset($answers[$qId])) {
                    $byQuestion[$qId]['not_answered']++;
                    continue;
                }

                $userShuffled = $answers[$qId]['answer_index'];
                $map = $shuffleMaps[$qId] ?? [0, 1, 2, 3];
                $userOriginal = $map[$userShuffled] ?? $userShuffled;

                // Compter quelle option originale a été choisie
                if ($userOriginal >= 0 && $userOriginal <= 3) {
                    $byQuestion[$qId]['option_counts'][$userOriginal]++;
                }

                if ($q !== null && $userOriginal === ($q['correct'] ?? 0)) {
                    $byQuestion[$qId]['correct']++;
                }
            }
        }

        // Enrichir avec taux et analyse distracteurs
        $result = [];
        foreach ($byQuestion as $qId => $stats) {
            $total = $stats['total'];
            $question = $this->banque->getQuestion($qId);

            $optionAnalysis = [];
            foreach ($stats['option_counts'] as $idx => $count) {
                $optionAnalysis[] = [
                    'index' => $idx,
                    'letter' => ['A', 'B', 'C', 'D'][$idx],
                    'is_correct' => $idx === ($stats['correct_index'] ?? 0),
                    'count' => $count,
                    'rate_pct' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
                    'text' => $withFullDetails && $question
                        ? ($question['options'][$idx] ?? '')
                        : null,
                ];
            }

            $row = [
                'question_id' => $qId,
                'total' => $total,
                'correct' => $stats['correct'],
                'not_answered' => $stats['not_answered'],
                'success_rate_pct' => $total > 0 ? round(($stats['correct'] / $total) * 100, 2) : 0,
                'not_answered_rate_pct' => $total > 0 ? round(($stats['not_answered'] / $total) * 100, 2) : 0,
                'correct_index' => $stats['correct_index'],
                'option_analysis' => $optionAnalysis,
                'difficulte' => $question['difficulte'] ?? null,
                'type' => $question['type'] ?? null,
            ];

            if ($withFullDetails && $question) {
                $row['enonce'] = $question['enonce'];
                $row['explanation'] = $question['explanation'] ?? '';
                $row['options'] = $question['options'] ?? [];
            }

            $result[] = $row;
        }

        // Tri par taux de réussite croissant (les plus difficiles en premier)
        usort($result, fn($a, $b) => $a['success_rate_pct'] <=> $b['success_rate_pct']);

        return [
            'examen_id' => $examenId,
            'nb_passages' => count($passages),
            'nb_questions' => count($result),
            'questions' => $result,
        ];
    }

    // ========================================================================
    // TIMELINE (passages dans le temps)
    // ========================================================================

    /**
     * Évolution des passages par heure/jour.
     */
    public function getTimeline(string $examenId): array
    {
        $passages = $this->passages->list([
            'examen_id' => $examenId,
            'status' => [PassageManager::STATUS_SUBMITTED, PassageManager::STATUS_EXPIRED],
        ]);

        $byHour = [];
        foreach ($passages as $p) {
            $ts = strtotime($p['start_time'] ?? '');
            if ($ts === false) continue;
            $hourKey = date('Y-m-d H:00', $ts);
            if (!isset($byHour[$hourKey])) {
                $byHour[$hourKey] = [
                    'hour' => $hourKey,
                    'count' => 0,
                    'avg_score' => 0,
                    'scores' => [],
                ];
            }
            $byHour[$hourKey]['count']++;
            if ($p['score_pct'] !== null) {
                $byHour[$hourKey]['scores'][] = $p['score_pct'];
            }
        }

        foreach ($byHour as &$h) {
            $h['avg_score'] = !empty($h['scores'])
                ? round(array_sum($h['scores']) / count($h['scores']), 2)
                : 0;
            unset($h['scores']); // cleanup
        }
        unset($h);

        ksort($byHour);
        return array_values($byHour);
    }

    // ========================================================================
    // HISTORIQUE PAR ÉTUDIANT
    // ========================================================================

    /**
     * Tous les passages d'un étudiant (multi-examens).
     */
    public function getStudentHistory(string $email): array
    {
        $email = mb_strtolower(trim($email));
        $passages = $this->passages->list(['email' => $email]);

        $enriched = [];
        foreach ($passages as $p) {
            $examen = $this->examens->get($p['examen_id']);
            $enriched[] = [
                'passage_id' => $p['id'],
                'examen_id' => $p['examen_id'],
                'examen_titre' => $examen['titre'] ?? '(supprimé)',
                'status' => $p['status'],
                'start_time' => $p['start_time'],
                'end_time' => $p['end_time'],
                'duration_sec' => $p['duration_sec'] ?? 0,
                'score_brut' => $p['score_brut'],
                'score_max' => $p['score_max'],
                'score_pct' => $p['score_pct'],
                'anomalies_count' => count(array_filter(
                    $p['focus_events'] ?? [],
                    fn($e) => in_array($e['type'] ?? '', ['copy', 'paste', 'devtools'], true)
                )),
            ];
        }

        // Tri par date desc
        usort($enriched, fn($a, $b) => strcmp($b['start_time'] ?? '', $a['start_time'] ?? ''));

        // Stats globales
        $submittedScores = array_values(array_filter(
            array_map(fn($p) => $p['score_pct'], $enriched),
            fn($s) => $s !== null
        ));
        $avgScore = !empty($submittedScores) ? array_sum($submittedScores) / count($submittedScores) : 0;

        return [
            'email' => $email,
            'student_info' => !empty($passages)
                ? $passages[0]['student_info']
                : ['nom' => '', 'prenom' => '', 'email' => $email],
            'nb_passages' => count($enriched),
            'avg_score_pct' => round($avgScore, 2),
            'best_score_pct' => !empty($submittedScores) ? max($submittedScores) : 0,
            'worst_score_pct' => !empty($submittedScores) ? min($submittedScores) : 0,
            'total_time_sec' => array_sum(array_map(fn($p) => $p['duration_sec'] ?? 0, $enriched)),
            'passages' => $enriched,
        ];
    }

    // ========================================================================
    // HEATMAP FOCUS EVENTS
    // ========================================================================

    /**
     * Agrégation des événements anti-triche par type + timestamp.
     */
    public function getFocusHeatmap(string $examenId): array
    {
        $passages = $this->passages->list([
            'examen_id' => $examenId,
            'status' => [
                PassageManager::STATUS_SUBMITTED,
                PassageManager::STATUS_EXPIRED,
                PassageManager::STATUS_INVALIDATED,
            ],
        ]);

        $byType = [
            'blur' => 0, 'focus' => 0, 'visibility_change' => 0,
            'copy' => 0, 'paste' => 0, 'rightclick' => 0, 'devtools' => 0,
        ];

        $byPassage = [];
        foreach ($passages as $p) {
            $events = $p['focus_events'] ?? [];
            $counts = array_fill_keys(array_keys($byType), 0);
            foreach ($events as $evt) {
                $type = $evt['type'] ?? null;
                if ($type && isset($byType[$type])) {
                    $byType[$type]++;
                    $counts[$type]++;
                }
            }
            // Ne garder que les passages avec au moins un event
            if (array_sum($counts) > 0) {
                $byPassage[] = [
                    'passage_id' => $p['id'],
                    'student_name' => trim(
                        ($p['student_info']['prenom'] ?? '') . ' ' . ($p['student_info']['nom'] ?? '')
                    ),
                    'email' => $p['student_info']['email'] ?? '',
                    'score_pct' => $p['score_pct'],
                    'total_events' => array_sum($counts),
                    'events_by_type' => $counts,
                ];
            }
        }

        // Tri par total events desc
        usort($byPassage, fn($a, $b) => $b['total_events'] <=> $a['total_events']);

        return [
            'examen_id' => $examenId,
            'total_events' => array_sum($byType),
            'by_type' => $byType,
            'passages_with_events' => $byPassage,
            'anomaly_threshold' => [
                'copy' => $byType['copy'] + $byType['paste'] + $byType['devtools'],
            ],
        ];
    }

    // ========================================================================
    // OVERVIEW PROF (tous ses examens)
    // ========================================================================

    /**
     * Vue d'ensemble pour un prof : KPIs tous ses examens confondus.
     */
    public function getProfOverview(string $profId): array
    {
        $examens = $this->examens->list(['created_by' => $profId]);

        $totalExamens = count($examens);
        $byStatus = ['draft' => 0, 'published' => 0, 'closed' => 0, 'archived' => 0];
        $totalPassages = 0;
        $totalUniqueStudents = [];
        $allScores = [];

        $examSummaries = [];
        foreach ($examens as $e) {
            if (isset($byStatus[$e['status']])) $byStatus[$e['status']]++;

            $passages = $this->passages->list([
                'examen_id' => $e['id'],
                'status' => [PassageManager::STATUS_SUBMITTED, PassageManager::STATUS_EXPIRED],
            ]);
            $scores = [];
            foreach ($passages as $p) {
                if ($p['score_pct'] !== null) {
                    $scores[] = $p['score_pct'];
                    $allScores[] = $p['score_pct'];
                }
                $totalUniqueStudents[mb_strtolower($p['student_info']['email'] ?? '')] = true;
            }

            $examSummaries[] = [
                'id' => $e['id'],
                'titre' => $e['titre'],
                'status' => $e['status'],
                'created_at' => $e['created_at'] ?? null,
                'nb_passages' => count($passages),
                'avg_score_pct' => !empty($scores) ? round(array_sum($scores) / count($scores), 2) : 0,
            ];
            $totalPassages += count($passages);
        }

        usort($examSummaries, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        return [
            'prof_id' => $profId,
            'total_examens' => $totalExamens,
            'by_status' => $byStatus,
            'total_passages' => $totalPassages,
            'unique_students' => count($totalUniqueStudents),
            'global_avg_score_pct' => !empty($allScores) ? round(array_sum($allScores) / count($allScores), 2) : 0,
            'recent_examens' => array_slice($examSummaries, 0, 10),
            'all_examens' => $examSummaries,
        ];
    }
}
