<?php
/**
 * /api/corrections — Acces aux corrections d'examens
 *
 * Routes :
 *
 *   PUBLIQUES (etudiants via token) :
 *     GET /api/corrections/{token}           - Correction detaillee d'un passage
 *
 *   AUTHENTIFIEES (prof/admin) :
 *     GET /api/corrections/passage/{id}      - Correction par passage ID
 *     GET /api/corrections/stats/{examenId}  - Stats correction par question (prof)
 *
 * Regles d'acces (route publique /token) :
 *   1. Token doit exister → 404 not_found sinon
 *   2. Passage status = submitted ou expired → 403 sinon
 *   3. Examen.show_correction_after = true → 403 sinon
 *   4. Si correction_delay_min > 0, delai doit etre ecoule → 403 sinon
 *
 * Renvoie pour chaque question :
 *   - Enonce + options (dans l'ordre shuffle pour preserver contexte etudiant)
 *   - user_answer_index (position shuffled)
 *   - correct_answer_index_shuffled (position shuffled de la bonne reponse)
 *   - is_correct
 *   - hint, explanation, traps, references (spoilers OK maintenant !)
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

use Examens\Lib\Auth;
use Examens\Lib\BanqueManager;
use Examens\Lib\ExamenManager;
use Examens\Lib\Logger;
use Examens\Lib\PassageManager;
use Examens\Lib\Response;
use Examens\Lib\Session;

Session::start();

$auth = new Auth();
$logger = new Logger('corrections');
$pm = new PassageManager();
$em = new ExamenManager();
$banque = new BanqueManager();

// ============================================================================
// Parsing URL
// ============================================================================

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$TOKEN_PATTERN = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
$ID_PATTERN = 'PSG-[A-Z0-9]{4}-[A-Z0-9]{4}';
$EXAM_PATTERN = 'EXM-[A-Z0-9]{4}-[A-Z0-9]{4}';

// ============================================================================
// Helper : construire la correction complete
// ============================================================================

function buildCorrectionPayload(array $passage, array $examen, BanqueManager $banque): array
{
    $questions = [];
    $shuffleMaps = $passage['option_shuffle_maps'] ?? [];
    $answers = (array) ($passage['answers'] ?? []);

    foreach ($passage['question_order'] as $qId) {
        $q = $banque->getQuestion($qId);
        if ($q === null) {
            $questions[] = [
                'id' => $qId,
                'error' => 'Question introuvable dans la banque',
            ];
            continue;
        }

        $map = $shuffleMaps[$qId] ?? [0, 1, 2, 3];
        $correctOriginal = $q['correct'] ?? 0;

        // Remapper la bonne reponse vers sa position shuffled (celle vue par l'etudiant)
        $correctShuffled = array_search($correctOriginal, $map, true);

        // Options dans l'ordre shuffled
        $shuffledOptions = [];
        foreach ($map as $origIdx) {
            $shuffledOptions[] = $q['options'][$origIdx] ?? '';
        }

        $userAnswerShuffled = $answers[$qId]['answer_index'] ?? null;
        $isCorrect = ($userAnswerShuffled !== null) && ($userAnswerShuffled === $correctShuffled);

        $questions[] = [
            'id' => $q['id'],
            'enonce' => $q['enonce'],
            'options' => $shuffledOptions,
            'type' => $q['type'] ?? 'inconnu',
            'difficulte' => $q['difficulte'] ?? 'inconnu',
            'user_answer_index' => $userAnswerShuffled,
            'correct_answer_index' => $correctShuffled,
            'is_correct' => $isCorrect,
            'was_answered' => $userAnswerShuffled !== null,
            'hint' => $q['hint'] ?? '',
            'explanation' => $q['explanation'] ?? '',
            'traps' => $q['traps'] ?? '',
            'references' => $q['references'] ?? '',
            'tags' => $q['tags'] ?? [],
        ];
    }

    // Stats par difficulte
    $byDifficulte = [];
    $byType = [];
    foreach ($questions as $q) {
        $d = $q['difficulte'] ?? 'inconnu';
        $t = $q['type'] ?? 'inconnu';
        if (!isset($byDifficulte[$d])) {
            $byDifficulte[$d] = ['total' => 0, 'correct' => 0];
        }
        if (!isset($byType[$t])) {
            $byType[$t] = ['total' => 0, 'correct' => 0];
        }
        $byDifficulte[$d]['total']++;
        $byType[$t]['total']++;
        if (!empty($q['is_correct'])) {
            $byDifficulte[$d]['correct']++;
            $byType[$t]['correct']++;
        }
    }

    return [
        'passage' => [
            'id' => $passage['id'],
            'status' => $passage['status'],
            'start_time' => $passage['start_time'],
            'end_time' => $passage['end_time'],
            'duration_sec' => $passage['duration_sec'] ?? 0,
            'student_info' => $passage['student_info'],
            'score_brut' => $passage['score_brut'],
            'score_max' => $passage['score_max'],
            'score_pct' => $passage['score_pct'],
        ],
        'examen' => [
            'id' => $examen['id'],
            'titre' => $examen['titre'],
            'description' => $examen['description'] ?? '',
            'duree_sec' => $examen['duree_sec'],
        ],
        'questions' => $questions,
        'stats_by_difficulte' => $byDifficulte,
        'stats_by_type' => $byType,
        'generated_at' => date('c'),
    ];
}

// ============================================================================
// ROUTE 1 : GET /api/corrections/{token} (PUBLIC)
// ============================================================================
if (preg_match("#^/api/corrections/($TOKEN_PATTERN)/?$#", $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $token = $m[1];
    $passage = $pm->getByToken($token);

    if ($passage === null) {
        Response::notFound("Token invalide");
    }

    // Verifier que le passage est termine
    if (!in_array($passage['status'], [
        PassageManager::STATUS_SUBMITTED,
        PassageManager::STATUS_EXPIRED,
    ], true)) {
        Response::error(
            'not_ready',
            "Correction disponible uniquement apres soumission du passage. Status actuel : {$passage['status']}",
            403,
            ['status' => $passage['status']]
        );
    }

    // Verifier que l'examen autorise la correction
    $examen = $em->get($passage['examen_id']);
    if ($examen === null) {
        Response::notFound("Examen associe introuvable");
    }

    if (empty($examen['show_correction_after'])) {
        Response::error(
            'correction_disabled',
            "La correction n'est pas disponible pour cet examen.",
            403
        );
    }

    // Verifier le delai
    $delayMin = (int) ($examen['correction_delay_min'] ?? 0);
    if ($delayMin > 0 && $passage['end_time']) {
        $endTs = strtotime($passage['end_time']);
        $availableTs = $endTs + ($delayMin * 60);
        if (time() < $availableTs) {
            Response::error(
                'delay_not_elapsed',
                "La correction sera disponible dans " . round(($availableTs - time()) / 60) . " minutes.",
                403,
                [
                    'available_at' => date('c', $availableTs),
                    'available_in_sec' => $availableTs - time(),
                ]
            );
        }
    }

    // Construire la correction
    try {
        $payload = buildCorrectionPayload($passage, $examen, $banque);
        Response::json($payload);
    } catch (\Throwable $e) {
        $logger->error('Erreur correction token', ['error' => $e->getMessage(), 'token' => $token]);
        Response::serverError('Erreur correction');
    }
}

// ============================================================================
// ROUTE 2 : GET /api/corrections/passage/{id} (AUTH prof/admin)
// ============================================================================
if (preg_match("#^/api/corrections/passage/($ID_PATTERN)/?$#", $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $id = $m[1];
    $user = $auth->requireAuth();
    $isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;

    $passage = $pm->get($id);
    if ($passage === null) {
        Response::notFound("Passage introuvable : $id");
    }

    $examen = $em->get($passage['examen_id']);
    if ($examen === null) {
        Response::notFound("Examen associe introuvable");
    }

    // Auth : owner examen ou admin
    if (!$isAdmin && ($examen['created_by'] ?? null) !== ($user['id'] ?? null)) {
        Response::forbidden('Vous ne pouvez voir que les corrections de vos examens.');
    }

    // Status doit etre termine
    if (!in_array($passage['status'], [
        PassageManager::STATUS_SUBMITTED,
        PassageManager::STATUS_EXPIRED,
        PassageManager::STATUS_INVALIDATED,
    ], true)) {
        Response::error(
            'not_ready',
            "Passage pas encore termine (status={$passage['status']})",
            400
        );
    }

    try {
        $payload = buildCorrectionPayload($passage, $examen, $banque);
        // Prof voit aussi le status invalidated + signature + focus events
        $payload['passage']['status_full'] = $passage['status'];
        $payload['passage']['invalidation_reason'] = $passage['invalidation_reason'] ?? null;
        $payload['passage']['signature_sha256'] = $passage['signature_sha256'] ?? null;
        $payload['passage']['signature_valid'] = $passage['signature_sha256']
            ? $pm->verifySignature($passage)
            : null;
        $payload['passage']['focus_events_count'] = count($passage['focus_events'] ?? []);
        $payload['passage']['focus_events'] = $passage['focus_events'] ?? [];
        Response::json($payload);
    } catch (\Throwable $e) {
        $logger->error('Erreur correction passage', ['error' => $e->getMessage(), 'id' => $id]);
        Response::serverError('Erreur correction');
    }
}

// ============================================================================
// ROUTE 3 : GET /api/corrections/stats/{examenId} (AUTH owner/admin)
// ============================================================================
if (preg_match("#^/api/corrections/stats/($EXAM_PATTERN)/?$#", $path, $m)) {
    if ($method !== 'GET') Response::methodNotAllowed();

    $examenId = $m[1];
    $user = $auth->requireAuth();
    $isAdmin = ($user['role'] ?? '') === Auth::ROLE_ADMIN;

    $examen = $em->get($examenId);
    if ($examen === null) {
        Response::notFound("Examen introuvable : $examenId");
    }

    if (!$isAdmin && ($examen['created_by'] ?? null) !== ($user['id'] ?? null)) {
        Response::forbidden();
    }

    // Recuperer tous les passages soumis
    $passages = $pm->list([
        'examen_id' => $examenId,
        'status' => [PassageManager::STATUS_SUBMITTED, PassageManager::STATUS_EXPIRED],
    ]);

    if (empty($passages)) {
        Response::json([
            'examen_id' => $examenId,
            'nb_passages' => 0,
            'by_question' => [],
        ]);
    }

    // Agreger : par question, combien de corrects / incorrects
    $byQuestion = [];
    foreach ($passages as $p) {
        $shuffleMaps = $p['option_shuffle_maps'] ?? [];
        $answers = (array) ($p['answers'] ?? []);

        foreach ($p['question_order'] as $qId) {
            if (!isset($byQuestion[$qId])) {
                $byQuestion[$qId] = [
                    'question_id' => $qId,
                    'total' => 0,
                    'correct' => 0,
                    'not_answered' => 0,
                    'most_common_wrong' => null,
                ];
            }

            $byQuestion[$qId]['total']++;

            $q = $banque->getQuestion($qId);
            if ($q === null) continue;

            $userAnswerShuffled = $answers[$qId]['answer_index'] ?? null;
            if ($userAnswerShuffled === null) {
                $byQuestion[$qId]['not_answered']++;
                continue;
            }

            $map = $shuffleMaps[$qId] ?? [0, 1, 2, 3];
            $userAnswerOriginal = $map[$userAnswerShuffled] ?? $userAnswerShuffled;
            $correctOriginal = $q['correct'] ?? 0;

            if ($userAnswerOriginal === $correctOriginal) {
                $byQuestion[$qId]['correct']++;
            }
        }
    }

    // Calculer les taux de reussite
    foreach ($byQuestion as $qId => $stats) {
        $total = $stats['total'];
        $byQuestion[$qId]['success_rate'] = $total > 0
            ? round(($stats['correct'] / $total) * 100, 1)
            : 0;
        $byQuestion[$qId]['not_answered_rate'] = $total > 0
            ? round(($stats['not_answered'] / $total) * 100, 1)
            : 0;
    }

    // Tri par taux de reussite croissant (questions les plus difficiles en premier)
    usort($byQuestion, function ($a, $b) {
        return $a['success_rate'] <=> $b['success_rate'];
    });

    Response::json([
        'examen_id' => $examenId,
        'examen_titre' => $examen['titre'],
        'nb_passages' => count($passages),
        'by_question' => $byQuestion,
        'generated_at' => date('c'),
    ]);
}

// ============================================================================
// Route non reconnue
// ============================================================================
Response::notFound('Route /api/corrections non reconnue : ' . $path);
