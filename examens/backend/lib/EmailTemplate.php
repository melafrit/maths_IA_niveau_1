<?php
/**
 * EmailTemplate.php — Gestionnaire de templates d'emails
 *
 * Charge un template depuis backend/templates/emails/ et substitue les variables.
 *
 * Templates disponibles :
 *   - etudiant_submission : confirmation de soumission
 *   - etudiant_correction : correction disponible
 *   - prof_examen_cree    : confirmation creation examen
 *   - prof_premier_passage: premier passage recu
 *   - prof_cloture        : examen cloture + stats
 *
 * Tous les templates sont des fichiers PHP qui extraient $vars et renvoient
 * [subject: string, html: string].
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class EmailTemplate
{
    private string $templatesDir;

    public const TPL_ETUDIANT_SUBMISSION = 'etudiant_submission';
    public const TPL_ETUDIANT_CORRECTION = 'etudiant_correction';
    public const TPL_PROF_EXAMEN_CREE = 'prof_examen_cree';
    public const TPL_PROF_PREMIER_PASSAGE = 'prof_premier_passage';
    public const TPL_PROF_CLOTURE = 'prof_cloture';

    public const ALL_TEMPLATES = [
        self::TPL_ETUDIANT_SUBMISSION,
        self::TPL_ETUDIANT_CORRECTION,
        self::TPL_PROF_EXAMEN_CREE,
        self::TPL_PROF_PREMIER_PASSAGE,
        self::TPL_PROF_CLOTURE,
    ];

    public function __construct(?string $templatesDir = null)
    {
        if ($templatesDir !== null) {
            $this->templatesDir = $templatesDir;
        } else {
            $this->templatesDir = __DIR__ . '/../templates/emails';
        }
    }

    /**
     * Rendre un template avec des variables.
     *
     * @param string $templateName ex. 'etudiant_submission'
     * @param array $vars Variables à substituer
     * @return array ['subject' => string, 'html' => string]
     * @throws \RuntimeException Si template introuvable
     */
    public function render(string $templateName, array $vars = []): array
    {
        if (!in_array($templateName, self::ALL_TEMPLATES, true)) {
            throw new \InvalidArgumentException("Template inconnu : $templateName");
        }

        $templatePath = $this->templatesDir . '/' . $templateName . '.php';
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template introuvable : $templatePath");
        }

        // Rendre le contenu (php renvoie subject + html)
        extract($vars, EXTR_SKIP);

        ob_start();
        $rendered = require $templatePath;
        $captured = ob_get_clean();

        // Support 2 conventions :
        //   1) Le template return ['subject' => ..., 'html' => ...]
        //   2) Le template echo le HTML et definit $subject + $html via vars
        if (is_array($rendered) && isset($rendered['subject'])) {
            return $rendered;
        }

        throw new \RuntimeException("Template $templateName doit retourner ['subject' => ..., 'html' => ...]");
    }

    /**
     * Rendre le layout de base avec un contenu enfant.
     */
    public function renderBase(string $title, string $content, string $preheader = ''): string
    {
        $basePath = $this->templatesDir . '/base.php';
        if (!file_exists($basePath)) {
            // Fallback minimal
            return '<html><body><h1>' . htmlspecialchars($title) . '</h1>' . $content . '</body></html>';
        }

        $vars = compact('title', 'content', 'preheader');
        extract($vars);

        ob_start();
        require $basePath;
        return ob_get_clean();
    }

    /**
     * Echapper une valeur pour HTML.
     */
    public static function e($val): string
    {
        return htmlspecialchars((string) $val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
