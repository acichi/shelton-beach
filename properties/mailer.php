<?php
// Simple PHPMailer wrapper configured via .env for Gmail SMTP
// Usage: sendEmail('user@example.com', 'Subject', '<p>HTML</p>', 'Plain text');

require_once __DIR__ . '/../config/env.php';

// Include PHPMailer classes (no Composer)
require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('sendEmail')) {
    /**
     * Send an email via SMTP.
     * @param string|array $to One recipient email, or an array of emails
     * @param string $subject
     * @param string $htmlBody HTML body
     * @param string $altBody Plain-text fallback body
     * @return array{success:bool,error:?string}
     */
    function sendEmail($to, string $subject, string $htmlBody, string $altBody = ''): array {
        $host       = (string)env('SMTP_HOST', 'smtp.gmail.com');
        $port       = (int)env('SMTP_PORT', 587);
        $username   = (string)env('SMTP_USER', '');
        $password   = (string)env('SMTP_PASS', '');
        $encryption = (string)env('SMTP_ENCRYPTION', 'tls'); // tls or ssl
        $from       = (string)env('SMTP_FROM', $username);
        $fromName   = (string)env('SMTP_FROM_NAME', 'Shelton Resort');
        $logoPath   = (string)env('EMAIL_LOGO_PATH', __DIR__ . '/../pics/logo2.png');
        $debugLevel = (int)env('SMTP_DEBUG', 0); // 0=off, 1,2,3,4 for increasing verbosity
        $debugOut   = (string)env('SMTP_DEBUG_OUTPUT', 'error_log'); // 'error_log' | 'html'
        $timeoutSec = (int)env('SMTP_TIMEOUT', 15);
        $allowSelf  = (bool)env('SMTP_ALLOW_SELF_SIGNED', false);

        if ($username === '' || $password === '') {
            return ['success' => false, 'error' => 'SMTP credentials not configured'];
        }

        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->CharSet    = 'UTF-8';
            $mailer->Timeout    = $timeoutSec;
            $mailer->SMTPDebug  = $debugLevel;
            $mailer->Debugoutput = $debugOut;
            $mailer->Host       = $host;
            $mailer->SMTPAuth   = true;
            $mailer->Username   = $username;
            $mailer->Password   = $password;
            $mailer->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Port       = $port;

            if ($allowSelf) {
                $mailer->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }

            $mailer->setFrom($from !== '' ? $from : $username, $fromName);

            if (is_array($to)) {
                foreach ($to as $addr) { if ($addr) { $mailer->addAddress($addr); } }
            } else {
                if ($to) { $mailer->addAddress($to); }
            }

            // Embed brand logo if available so templates can reference cid:brandlogo
            if ($logoPath !== '' && @is_readable($logoPath)) {
                try { $mailer->addEmbeddedImage($logoPath, 'brandlogo', 'logo.png'); } catch (Throwable $e) { /* ignore */ }
            }

            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body    = $htmlBody;
            $mailer->AltBody = $altBody !== '' ? $altBody : strip_tags($htmlBody);

            $ok = $mailer->send();
            return ['success' => (bool)$ok, 'error' => $ok ? null : ($mailer->ErrorInfo ?: 'send() returned false')];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

?>


