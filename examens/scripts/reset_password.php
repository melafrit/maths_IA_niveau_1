<?php
/**
 * scripts/reset_password.php
 *
 * Script CLI pour réinitialiser le mot de passe d'un compte enseignant.
 *
 * Usage :
 *   cd examens
 *   php scripts/reset_password.php
 *   php scripts/reset_password.php prof@ipssi.fr
 *
 * Le script :
 *   1. Demande l'email du compte (ou prend le 1er argument CLI)
 *   2. Vérifie l'existence
 *   3. Demande nouveau mot de passe (avec confirmation)
 *   4. Met à jour
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    echo "Ce script doit être exécuté en CLI.\n";
    exit(1);
}

require __DIR__ . '/../backend/bootstrap.php';

use Examens\Lib\Auth;

// ============================================================================
// Helpers (réutilisés depuis init_comptes.php)
// ============================================================================

function ansi(string $color, string $text): string
{
    if (!stream_isatty(STDOUT)) return $text;
    $codes = [
        'reset' => "\033[0m", 'bold' => "\033[1m", 'red' => "\033[31m",
        'green' => "\033[32m", 'yellow' => "\033[33m", 'blue' => "\033[34m",
        'cyan' => "\033[36m", 'gray' => "\033[90m",
    ];
    return ($codes[$color] ?? '') . $text . $codes['reset'];
}

function prompt(string $question, ?string $default = null): string
{
    while (true) {
        $hint = $default !== null ? ansi('gray', " [$default]") : '';
        echo ansi('cyan', '? ') . $question . $hint . ' ';
        $line = fgets(STDIN);
        $value = $line === false ? '' : trim($line);
        if ($value === '' && $default !== null) return $default;
        if ($value !== '') return $value;
        echo ansi('red', "  ✗ Réponse requise.\n");
    }
}

function promptPassword(string $question, int $minLength = 8): string
{
    while (true) {
        echo ansi('cyan', '? ') . $question . ' ';
        if (DIRECTORY_SEPARATOR !== '\\') shell_exec('stty -echo');
        $password = fgets(STDIN);
        if (DIRECTORY_SEPARATOR !== '\\') shell_exec('stty echo');
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
    if ($value === '') return $default;
    return in_array($value, ['o', 'oui', 'y', 'yes', '1'], true);
}

// ============================================================================
// Programme principal
// ============================================================================

echo "\n";
echo ansi('blue', "╔══════════════════════════════════════════════════════════════╗\n");
echo ansi('blue', "║       Plateforme d'examens IPSSI — Reset Password           ║\n");
echo ansi('blue', "╚══════════════════════════════════════════════════════════════╝\n");
echo "\n";

$auth = new Auth();

// Email : depuis argv[1] ou prompt
$email = $argv[1] ?? '';
if ($email === '') {
    $email = prompt('Email du compte à réinitialiser');
}
$email = strtolower(trim($email));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo ansi('red', "✗ Email invalide.\n");
    exit(1);
}

// Recherche du compte
$compte = $auth->findByEmail($email);
if ($compte === null) {
    echo ansi('red', "✗ Aucun compte trouvé avec cet email.\n");
    echo ansi('gray', "  Astuce : utilisez init_comptes.php pour créer un nouveau compte.\n");
    exit(1);
}

echo "\n";
echo "Compte trouvé :\n";
echo "  ID     : " . $compte['id'] . "\n";
echo "  Email  : " . $compte['email'] . "\n";
echo "  Nom    : " . ($compte['prenom'] ?? '') . ' ' . ($compte['nom'] ?? '') . "\n";
echo "  Rôle   : " . $compte['role'] . "\n";
echo "  Actif  : " . (($compte['active'] ?? false) ? 'oui' : ansi('red', 'NON')) . "\n";
echo "\n";

if (!promptYesNo('Réinitialiser le mot de passe de ce compte ?', false)) {
    echo "Abandon.\n";
    exit(0);
}

echo "\n";
while (true) {
    $password = promptPassword('Nouveau mot de passe (min. 8 caractères)', 8);
    $confirm  = promptPassword('Confirmer le mot de passe', 8);
    if ($password === $confirm) break;
    echo ansi('red', "  ✗ Les deux saisies ne correspondent pas. Recommencez.\n\n");
}

try {
    if ($auth->updatePassword($compte['id'], $password)) {
        echo "\n";
        echo ansi('green', "✓ Mot de passe modifié avec succès.\n\n");
        echo "  L'utilisateur peut maintenant se connecter avec son nouveau mot de passe :\n";
        echo "  " . ansi('blue', config('app.base_url', 'http://localhost:8000') . '/login.html') . "\n\n";
        exit(0);
    } else {
        echo ansi('red', "✗ Échec de la mise à jour.\n");
        exit(1);
    }
} catch (\Throwable $e) {
    echo ansi('red', "✗ Erreur : " . $e->getMessage() . "\n");
    exit(1);
}
