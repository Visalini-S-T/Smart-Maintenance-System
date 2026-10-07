<?php
/**
 * SMTP-ready email scaffold.
 * For production, install PHPMailer with Composer:
 * composer require phpmailer/phpmailer
 *
 * Then use PHPMailer SMTP with credentials stored in environment variables.
 * This file deliberately does not contain real credentials.
 */
function sendMaintenanceEmail(string $to, string $subject, string $body): bool {
    // Demo fallback. Replace with PHPMailer SMTP in production.
    $headers = "MIME-Version: 1.0\r\nContent-type:text/plain;charset=UTF-8\r\n";
    $headers .= "From: maintenance@example.com\r\n";
    return @mail($to, $subject, $body, $headers);
}
