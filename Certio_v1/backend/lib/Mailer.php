<?php
/**
 * Mailer.php — Wrapper d'envoi d'emails
 *
 * Modes :
 *   - disabled : aucun envoi (mode par defaut si aucune config)
 *   - dev      : ecrit les emails dans data/logs/emails.log (pas d'envoi reel)
 *   - prod     : envoie via PHP mail() ou SMTP selon config
 *
 * Configuration (config.php) :
 *   define('MAILER_MODE', 'dev');              // disabled | dev | prod
 *   define('MAILER_FROM', 'noreply@ipssi.fr'); // Expediteur
 *   define('MAILER_FROM_NAME', 'IPSSI Examens');
 *   define('MAILER_REPLY_TO', 'm.elafrit@ecole-ipssi.net');
 *
 *   Pour SMTP (optionnel, sinon mail() standard) :
 *   define('MAILER_SMTP_HOST', 'smtp.ovh.net');
 *   define('MAILER_SMTP_PORT', 587);
 *   define('MAILER_SMTP_USER', 'noreply@ipssi.fr');
 *   define('MAILER_SMTP_PASS', '...');
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class Mailer
{
    public const MODE_DISABLED = 'disabled';
    public const MODE_DEV = 'dev';
    public const MODE_PROD = 'prod';

    private string $mode;
    private string $from;
    private string $fromName;
    private string $replyTo;
    private Logger $logger;
    private string $logPath;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?? new Logger('mailer');
        $this->mode = defined('MAILER_MODE') ? MAILER_MODE : self::MODE_DISABLED;
        $this->from = defined('MAILER_FROM') ? MAILER_FROM : 'noreply@localhost';
        $this->fromName = defined('MAILER_FROM_NAME') ? MAILER_FROM_NAME : 'IPSSI Examens';
        $this->replyTo = defined('MAILER_REPLY_TO') ? MAILER_REPLY_TO : $this->from;

        // Chemin du log en mode dev
        if (function_exists('data_path')) {
            $this->logPath = data_path('logs') . '/emails.log';
        } else {
            $this->logPath = __DIR__ . '/../../data/logs/emails.log';
        }

        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
    }

    /**
     * Envoyer un email.
     *
     * @param string|array $to Email destinataire (ou tableau)
     * @param string $subject Objet
     * @param string $htmlBody Corps HTML
     * @param array $options {
     *   text_body?: string,    // Version texte brut (auto-genere sinon)
     *   attachments?: array,   // Pour futures pieces jointes
     *   cc?: array,
     *   bcc?: array,
     *   reply_to?: string,
     * }
     * @return bool Succes
     */
    public function send($to, string $subject, string $htmlBody, array $options = []): bool
    {
        // Normaliser destinataires
        $recipients = is_array($to) ? $to : [$to];

        // Validation emails
        foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->logger->error('Mailer : email invalide', ['email' => $email]);
                return false;
            }
        }

        // Construire message
        $message = [
            'to' => $recipients,
            'from' => $this->from,
            'from_name' => $this->fromName,
            'reply_to' => $options['reply_to'] ?? $this->replyTo,
            'subject' => $subject,
            'html_body' => $htmlBody,
            'text_body' => $options['text_body'] ?? $this->htmlToText($htmlBody),
            'cc' => $options['cc'] ?? [],
            'bcc' => $options['bcc'] ?? [],
            'sent_at' => date('c'),
            'mode' => $this->mode,
        ];

        // Dispatcher selon le mode
        switch ($this->mode) {
            case self::MODE_DISABLED:
                $this->logger->info('Mailer disabled, email ignore', [
                    'to' => $recipients,
                    'subject' => $subject,
                ]);
                return true; // On retourne true pour ne pas casser l'appelant

            case self::MODE_DEV:
                return $this->writeToLog($message);

            case self::MODE_PROD:
                return $this->sendReal($message);

            default:
                $this->logger->error('Mailer : mode inconnu', ['mode' => $this->mode]);
                return false;
        }
    }

    /**
     * Mode dev : ecrire dans data/logs/emails.log (format JSON lines).
     */
    private function writeToLog(array $message): bool
    {
        $entry = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        $ok = @file_put_contents($this->logPath, $entry, FILE_APPEND | LOCK_EX);

        if ($ok === false) {
            $this->logger->error('Mailer dev : impossible d\'ecrire le log', [
                'path' => $this->logPath,
            ]);
            return false;
        }

        $this->logger->info('Email ecrit dans log dev', [
            'to' => $message['to'],
            'subject' => $message['subject'],
            'path' => $this->logPath,
        ]);
        return true;
    }

    /**
     * Mode prod : envoi reel.
     * Utilise SMTP si configure, sinon PHP mail() standard.
     */
    private function sendReal(array $message): bool
    {
        $useSmtp = defined('MAILER_SMTP_HOST') && MAILER_SMTP_HOST;

        if ($useSmtp) {
            return $this->sendViaSmtp($message);
        }

        return $this->sendViaMail($message);
    }

    /**
     * Envoi via PHP mail() standard.
     */
    private function sendViaMail(array $message): bool
    {
        $to = implode(', ', $message['to']);
        $subject = $this->encodeHeader($message['subject']);

        // Boundary pour email multipart
        $boundary = '==BOUNDARY_' . md5(uniqid()) . '==';

        $headers = [];
        $headers[] = 'From: ' . $this->encodeHeader($message['from_name']) . ' <' . $message['from'] . '>';
        $headers[] = 'Reply-To: ' . $message['reply_to'];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        if (!empty($message['cc'])) {
            $headers[] = 'Cc: ' . implode(', ', $message['cc']);
        }
        if (!empty($message['bcc'])) {
            $headers[] = 'Bcc: ' . implode(', ', $message['bcc']);
        }

        $headers[] = 'X-Mailer: IPSSI-Examens/1.0';
        $headers[] = 'X-Priority: 3';

        // Body multipart
        $body = '';
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($message['text_body'])) . "\r\n";

        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($message['html_body'])) . "\r\n";

        $body .= "--$boundary--";

        $ok = @mail($to, $subject, $body, implode("\r\n", $headers));

        if ($ok) {
            $this->logger->info('Email envoye (mail())', [
                'to' => $message['to'],
                'subject' => $message['subject'],
            ]);
        } else {
            $this->logger->error('Email echec (mail())', [
                'to' => $message['to'],
                'subject' => $message['subject'],
            ]);
        }

        return $ok;
    }

    /**
     * Envoi via SMTP (connexion directe socket).
     * Implementation minimale sans dependance externe.
     */
    private function sendViaSmtp(array $message): bool
    {
        $host = defined('MAILER_SMTP_HOST') ? MAILER_SMTP_HOST : 'localhost';
        $port = defined('MAILER_SMTP_PORT') ? (int) MAILER_SMTP_PORT : 25;
        $user = defined('MAILER_SMTP_USER') ? MAILER_SMTP_USER : null;
        $pass = defined('MAILER_SMTP_PASS') ? MAILER_SMTP_PASS : null;

        $this->logger->info('SMTP : tentative connexion', ['host' => $host, 'port' => $port]);

        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);

        if (!$socket) {
            $this->logger->error('SMTP : connexion echouee', [
                'errno' => $errno,
                'errstr' => $errstr,
            ]);
            return false;
        }

        try {
            // Lecture bienvenue
            $response = fgets($socket, 512);
            if (!str_starts_with($response, '220')) throw new \RuntimeException("SMTP greeting: $response");

            // EHLO
            fwrite($socket, "EHLO localhost\r\n");
            while ($line = fgets($socket, 512)) {
                if (substr($line, 3, 1) === ' ') break;
            }

            // AUTH si credentials
            if ($user && $pass) {
                fwrite($socket, "AUTH LOGIN\r\n");
                $response = fgets($socket, 512);
                if (!str_starts_with($response, '334')) throw new \RuntimeException("AUTH LOGIN: $response");

                fwrite($socket, base64_encode($user) . "\r\n");
                $response = fgets($socket, 512);
                if (!str_starts_with($response, '334')) throw new \RuntimeException("AUTH user: $response");

                fwrite($socket, base64_encode($pass) . "\r\n");
                $response = fgets($socket, 512);
                if (!str_starts_with($response, '235')) throw new \RuntimeException("AUTH pass: $response");
            }

            // MAIL FROM
            fwrite($socket, "MAIL FROM:<{$message['from']}>\r\n");
            $response = fgets($socket, 512);
            if (!str_starts_with($response, '250')) throw new \RuntimeException("MAIL FROM: $response");

            // RCPT TO
            foreach ($message['to'] as $rcpt) {
                fwrite($socket, "RCPT TO:<$rcpt>\r\n");
                $response = fgets($socket, 512);
                if (!str_starts_with($response, '250')) throw new \RuntimeException("RCPT TO: $response");
            }

            // DATA
            fwrite($socket, "DATA\r\n");
            $response = fgets($socket, 512);
            if (!str_starts_with($response, '354')) throw new \RuntimeException("DATA: $response");

            // Construire et envoyer l'email
            $emailContent = $this->buildSmtpMessage($message);
            fwrite($socket, $emailContent . "\r\n.\r\n");
            $response = fgets($socket, 512);
            if (!str_starts_with($response, '250')) throw new \RuntimeException("DATA end: $response");

            // QUIT
            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            $this->logger->info('Email envoye (SMTP)', [
                'to' => $message['to'],
                'subject' => $message['subject'],
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('SMTP : erreur', ['error' => $e->getMessage()]);
            @fclose($socket);
            return false;
        }
    }

    private function buildSmtpMessage(array $message): string
    {
        $boundary = '==BOUNDARY_' . md5(uniqid()) . '==';

        $headers = [];
        $headers[] = "To: " . implode(', ', $message['to']);
        $headers[] = "From: " . $this->encodeHeader($message['from_name']) . " <{$message['from']}>";
        $headers[] = "Subject: " . $this->encodeHeader($message['subject']);
        $headers[] = "Reply-To: {$message['reply_to']}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
        $headers[] = "X-Mailer: IPSSI-Examens/1.0";
        $headers[] = "Date: " . date('r');

        $body = '';
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($message['text_body'])) . "\r\n";

        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($message['html_body'])) . "\r\n";

        $body .= "--$boundary--";

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    /**
     * Encoder header (RFC 2047) pour sujet/nom avec caracteres non-ASCII.
     */
    private function encodeHeader(string $str): string
    {
        if (mb_check_encoding($str, 'ASCII')) return $str;
        return '=?UTF-8?B?' . base64_encode($str) . '?=';
    }

    /**
     * Convertir HTML en texte brut (fallback).
     */
    private function htmlToText(string $html): string
    {
        // Supprimer <style> et <script>
        $html = preg_replace('/<(style|script)[^>]*>.*?<\/\1>/is', '', $html);
        // Remplacer <br> et <p> par saut de ligne
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p\s*>/i', "\n\n", $html);
        $html = preg_replace('/<\/h[1-6]\s*>/i', "\n\n", $html);
        $html = preg_replace('/<\/li\s*>/i', "\n", $html);
        // Decoder entites et supprimer tags
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Normaliser whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    // ========================================================================
    // Getters (utile pour tests)
    // ========================================================================

    public function getMode(): string { return $this->mode; }
    public function getLogPath(): string { return $this->logPath; }

    /**
     * Lire les emails du log (mode dev).
     */
    public function readLog(int $last = 10): array
    {
        if (!file_exists($this->logPath)) return [];
        $lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $emails = [];
        foreach (array_slice($lines, -$last) as $line) {
            $parsed = json_decode($line, true);
            if ($parsed !== null) $emails[] = $parsed;
        }
        return $emails;
    }
}
