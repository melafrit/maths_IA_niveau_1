<?php
/**
 * scripts/init_comptes.php
 *
 * Script CLI interactif pour créer le PREMIER compte admin de la plateforme.
 *
 * Usage :
 *   cd examens
 *   php scripts/init_comptes.php
 *
 * Le script :
 *   1. Vérifie qu'aucun admin actif n'existe déjà (sécurité)
 *      - Force-flag : --force pour bypasser (avec confirmation supplémentaire)
 *   2. Demande email, prénom, nom, mot de passe (avec confirmation)
 *   3. Crée le compte avec role=admin
 *   4. Confirme la création et donne les prochaines étapes
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

// Vérifier qu'on est bien en CLI
if (php_sapi_name() !== 'cli') {
    echo "Ce script doit être exécuté en ligne de commande (CLI).\n";
    exit(1);
}

// Charger le bootstrap
$bootstrap = __DIR__ . '/../backend/bootstrap.php';
if (!file_exists($bootstrap)) {
    echo "ERREUR : bootstrap.php introuvable à : $bootstrap\n";
    exit(1);
}
require $bootstrap;

use Examens\Lib\Auth;

// ============================================================================
// Helpers de saisie
// ============================================================================

function ansi(string $color, string $text): string
{
    if (!stream_isatty(STDOUT)) {
        return $text; // Pas de couleurs si pas terminal interactif
    }
    $codes = [
        'reset' => "\033[0m",
        'bold'  => "\033[1m",
        'red'   => "\033[31m",
        'green' => "\033[32m",
        'yellow'=> "\033[33m",
        'blue'  => "\033[34m",
        'cyan'  => "\033[36m",
        'gray'  => "\033[90m",
    ];
    return ($codes[$color] ?? '') . $text . $codes['reset'];
}

function prompt(string $question, ?string $default = null, bool $required = true): string
{
    while (true) {
        $hint = $default !== null ? ansi('gray', " [$default]") : '';
        echo ansi('cyan', '? ') . $question . $hint . ' ';
        $line = fgets(STDIN);
        $value = $line === false ? '' : trim($line);

        if ($value === '' && $default !== null) {
            return $default;
        }
        if ($value === '' && !$required) {
            return '';
        }
        if ($value !== '') {
            return $value;
        }
        echo ansi('red', "  ✗ Réponse requise.\n");
    }
}

function promptPassword(string $question, int $minLength = 8): string
{
    while (true) {
        echo ansi('cyan', '? ') . $question . ' ';

        // Désactiver l'écho terminal (Linux/Mac uniquement)
        if (DIRECTORY_SEPARATOR !== '\\') {
            shell_exec('stty -echo');
        }
        $password = fgets(STDIN);
        if (DIRECTORY_SEPARATOR !== '\\') {
            shell_exec('stty echo');
        }
        echo "\n";

        $password = $password === false ? '' : trim($password);

        if (strlen($password) < $minLength) {
            echo ansi('red', "  ✗ Minimum $minLength caractères.\n");
            continue;
        }
        return $password;
    }
}

function promptYesNo(string $question, bool $default = false): bool
{
    $hint = $default ? '[O/n]' : '[o/N]';
    echo ansi('cyan', '? ') . $question . " $hint ";
    $line = fgets(STDIN);
    $value = $line === false ? '' : strtolower(trim($line));
    if ($value === '') {
        return $default;
    }
    return in_array($value, ['o', 'oui', 'y', 'yes', '1'], true);
}

function section_header(string $text): void
{
    echo "\n" . ansi('bold', $text) . "\n";
    echo str_repeat('─', mb_strlen($text)) . "\n";
}

// ============================================================================
// Banner
// ============================================================================

echo "\n";
echo ansi('blue', "╔══════════════════════════════════════════════════════════════╗\n");
echo ansi('blue', "║       Plateforme d'examens IPSSI — Initialisation           ║\n");
echo ansi('blue', "╚══════════════════════════════════════════════════════════════╝\n");
echo "\n";
echo ansi('gray', "Ce script crée le PREMIER compte administrateur de la plateforme.\n");
echo ansi('gray', "C'est une étape unique à faire après l'installation initiale.\n");
echo "\n";

// ============================================================================
// Vérification : pas déjà d'admin
// ============================================================================

$auth = new Auth();
$forceMode = in_array('--force', $argv ?? [], true);

$nbAdmins = $auth->countActiveAdmins();
if ($nbAdmins > 0 && !$forceMode) {
    echo ansi('yellow', "⚠ Il existe déjà $nbAdmins administrateur(s) actif(s).\n");
    echo ansi('yellow', "  Pour ajouter un autre admin, utilisez l'interface web (recommandé).\n");
    echo ansi('yellow', "  Pour forcer l'utilisation de ce script, relancez avec : --force\n\n");
    exit(0);
}

if ($forceMode && $nbAdmins > 0) {
    echo ansi('red', "⚠ Mode --force activé.\n");
    echo ansi('red', "  $nbAdmins administrateur(s) actif(s) existe(nt) déjà.\n\n");
    if (!promptYesNo('Êtes-vous CERTAIN de vouloir créer un admin supplémentaire ici ?', false)) {
        echo "Abandon.\n";
        exit(0);
    }
    echo "\n";
}

// ============================================================================
// Saisie des informations
// ============================================================================

section_header('Informations du compte');

$email = strtolower(prompt('Email du compte admin'));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo ansi('red', "✗ Email invalide.\n");
    exit(1);
}

$existing = $auth->findByEmail($email);
if ($existing !== null) {
    echo ansi('red', "✗ Cet email est déjà utilisé par le compte ID {$existing['id']}.\n");
    exit(1);
}

$prenom = prompt('Prénom');
$nom = prompt('Nom');

section_header('Mot de passe');
echo ansi('gray', "  • Minimum 8 caractères\n");
echo ansi('gray', "  • Recommandé : 12+ caractères avec majuscules, minuscules, chiffres et symboles\n\n");

while (true) {
    $password = promptPassword('Mot de passe', 8);
    $confirm  = promptPassword('Confirmer le mot de passe', 8);
    if ($password === $confirm) {
        break;
    }
    echo ansi('red', "  ✗ Les deux saisies ne correspondent pas. Recommencez.\n\n");
}

// ============================================================================
// Récapitulatif et confirmation
// ============================================================================

section_header('Récapitulatif');
echo "  Email   : " . ansi('green', $email) . "\n";
echo "  Prénom  : " . ansi('green', $prenom) . "\n";
echo "  Nom     : " . ansi('green', $nom) . "\n";
echo "  Rôle    : " . ansi('green', 'admin') . "\n";
echo "  Mot de passe : " . ansi('green', str_repeat('•', strlen($password))) . " (" . strlen($password) . " caractères)\n";
echo "\n";

if (!promptYesNo('Confirmer la création du compte ?', true)) {
    echo "Abandon. Aucun compte n'a été créé.\n";
    exit(0);
}

// ============================================================================
// Création
// ============================================================================

try {
    $compte = $auth->createCompte([
        'email'      => $email,
        'password'   => $password,
        'nom'        => $nom,
        'prenom'     => $prenom,
        'role'       => Auth::ROLE_ADMIN,
        'created_by' => null, // self
    ]);

    echo "\n";
    echo ansi('green', "✓ Compte admin créé avec succès !\n\n");
    echo "  ID interne : " . ansi('bold', $compte['id']) . "\n";
    echo "  Email      : " . ansi('bold', $compte['email']) . "\n";
    echo "\n";

    section_header('Prochaines étapes');
    echo "  1. " . ansi('cyan', "Connectez-vous via la page de login :") . "\n";
    echo "     " . ansi('blue', config('app.base_url', 'http://localhost:8000') . '/login.html') . "\n";
    echo "\n";
    echo "  2. " . ansi('cyan', "Une fois connecté, vous pourrez :") . "\n";
    echo "     • Créer d'autres comptes enseignants depuis l'interface\n";
    echo "     • Configurer la banque de questions (Phase P3)\n";
    echo "     • Créer votre premier examen (Phase P5)\n";
    echo "\n";
    echo "  3. " . ansi('cyan', "Configurez votre fichier backend/config.php") . " (si pas déjà fait) :\n";
    echo "     • Définir base_url avec votre domaine de production\n";
    echo "     • Définir un app_salt aléatoire et une encryption_key\n";
    echo "     • Activer email + SMTP OVH (Phase P6)\n";
    echo "\n";

    echo ansi('gray', "Bonne utilisation de la plateforme !\n");
    echo ansi('gray', "© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0\n\n");

    exit(0);

} catch (\Throwable $e) {
    echo "\n";
    echo ansi('red', "✗ Erreur lors de la création du compte :\n");
    echo "  " . $e->getMessage() . "\n";
    exit(1);
}
