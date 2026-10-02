<?php
/**
 * Mail helper — logs to file (placeholder for real SMTP)
 */
function send_mail(string $to, string $subject, string $body): bool {
    $dir = STORAGE_PATH . 'logs/';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents($dir . 'mail.log', "\n=== MAIL " . date('Y-m-d H:i:s') . " ===\nTo: $to\nSubject: $subject\n\n$body\n", FILE_APPEND);
    return true;
}
