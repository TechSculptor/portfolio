<?php
/**
 * Email Helper
 * Handles sending emails via SMTP (MailHog for development)
 */

class EmailHelper
{
    private $smtpHost;
    private $smtpPort;
    private $fromEmail;
    private $fromName;

    public function __construct()
    {
        $this->smtpHost = getenv('SMTP_HOST') ?: 'mailhog';
        $this->smtpPort = getenv('SMTP_PORT') ?: 1025;
        $this->fromEmail = 'noreply@cabinet-medical.com';
        $this->fromName = 'Cabinet Médical';
    }

    /**
     * Send verification email to new patient
     * 
     * @return bool
     */
    public function sendVerificationEmail($toEmail, $username, $token): bool
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST']; // Will capture localhost or custom domain
        $verificationLink = "{$protocol}://{$host}/verify_email?token=" . urlencode($token);

        $subject = "Vérifiez votre adresse email - Cabinet Médical";

        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #d32f2f; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 12px 30px; background-color: #1976d2; 
                          color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Cabinet Médical</h1>
                </div>
                <div class='content'>
                    <h2>Bienvenue, {$username}!</h2>
                    <p>Merci de vous être inscrit sur notre plateforme de gestion de rendez-vous.</p>
                    <p>Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous:</p>
                    <p style='text-align: center;'>
                        <a href='{$verificationLink}' class='button'>Vérifier mon adresse email</a>
                    </p>
                    <p>Ou copiez ce lien dans votre navigateur:</p>
                    <p style='word-break: break-all; font-size: 12px;'>{$verificationLink}</p>
                    <p>Ce lien est valide pendant 24 heures.</p>
                    <p>Si vous n'avez pas créé de compte, veuillez ignorer cet email.</p>
                </div>
                <div class='footer'>
                    <p>© 2025 Cabinet Médical - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $textBody = "
Bienvenue, {$username}!

Merci de vous être inscrit sur notre plateforme.

Pour activer votre compte, veuillez cliquer sur le lien suivant:
{$verificationLink}

Ce lien est valide pendant 24 heures.

Si vous n'avez pas créé de compte, veuillez ignorer cet email.

© 2025 Cabinet Médical
        ";

        return $this->sendEmail($toEmail, $subject, $htmlBody, $textBody);
    }

    /**
     * Send appointment confirmation email with PDF attachment
     * 
     * @return bool
     */
    public function sendAppointmentConfirmation($toEmail, $patientName, $appointment): bool
    {
        require_once __DIR__ . '/PdfHelper.php';

        $subject = "Confirmation de rendez-vous - Cabinet Médical";

        $date = date('d/m/Y', strtotime($appointment['appointment_date']));
        $time = substr($appointment['appointment_time'], 0, 5);
        $doctor = "Dr. " . $appointment['doctor_first_name'] . " " . $appointment['doctor_last_name'];

        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #d32f2f; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background-color: #f9f9f9; }
                .appointment-box { background: white; padding: 20px; border-left: 4px solid #1976d2; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Confirmation de Rendez-vous</h1>
                </div>
                <div class='content'>
                    <p>Bonjour {$patientName},</p>
                    <p>Votre rendez-vous a été confirmé avec succès.</p>
                    <div class='appointment-box'>
                        <h3>Détails du rendez-vous</h3>
                        <p><strong>Date:</strong> {$date}</p>
                        <p><strong>Heure:</strong> {$time}</p>
                        <p><strong>Médecin:</strong> {$doctor}</p>
                        <p><strong>Motif:</strong> {$appointment['reason']}</p>
                    </div>
                    <p><strong>Adresse du cabinet:</strong><br>
                    123 Rue de la Santé, 75000 Paris</p>
                    <p>En cas d'empêchement, veuillez annuler votre rendez-vous au moins 24h à l'avance.</p>
                    <p><em>Un récapitulatif PDF est joint à cet email pour vos dossiers.</em></p>
                </div>
                <div class='footer'>
                    <p>© 2025 Cabinet Médical - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $textBody = "
Confirmation de Rendez-vous

Bonjour {$patientName},

Votre rendez-vous a été confirmé:

Date: {$date}
Heure: {$time}
Médecin: {$doctor}
Motif: {$appointment['reason']}

Adresse: 123 Rue de la Santé, 75000 Paris

En cas d'empêchement, veuillez annuler au moins 24h à l'avance.

Un récapitulatif PDF est joint à cet email.

© 2025 Cabinet Médical
        ";

        // Generate PDF
        $pdfHelper = new PdfHelper();
        $pdfData = [
            'patient_name' => $patientName,
            'patient_email' => $toEmail,
            'doctor_name' => $doctor,
            'specialty' => $appointment['specialty'] ?? '',
            'appointment_date' => $appointment['appointment_date'],
            'appointment_time' => $appointment['appointment_time'],
            'reason' => $appointment['reason'],
            'is_first_appointment' => $appointment['is_first_appointment'] ?? false,
            'status' => $appointment['status'] ?? 'pending'
        ];

        $pdfContent = $pdfHelper->generateAppointmentPDF($pdfData);

        return $this->sendEmailWithAttachment(
            $toEmail,
            $subject,
            $htmlBody,
            $textBody,
            $pdfContent,
            'rendez-vous-confirmation.pdf'
        );
    }

    /**
     * Send email with PDF attachment
     * 
     * @return bool
     */
    private function sendEmailWithAttachment($to, $subject, $htmlBody, $textBody, $pdfContent, $pdfFilename): bool
    {
        $boundary = "----=_NextPart_" . md5(time());
        $boundaryAlt = "----=_NextPart_Alt_" . md5(time() + 1);

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"{$boundaryAlt}\"\r\n\r\n";

        // Plain text version
        $message .= "--{$boundaryAlt}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $textBody . "\r\n\r\n";

        // HTML version
        $message .= "--{$boundaryAlt}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";

        $message .= "--{$boundaryAlt}--\r\n\r\n";

        // PDF attachment
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: application/pdf; name=\"{$pdfFilename}\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"{$pdfFilename}\"\r\n\r\n";
        $message .= chunk_split(base64_encode($pdfContent)) . "\r\n";

        $message .= "--{$boundary}--";

        // Configure SMTP
        ini_set("SMTP", $this->smtpHost);
        ini_set("smtp_port", $this->smtpPort);
        ini_set("sendmail_from", $this->fromEmail);

        return mail($to, $subject, $message, $headers);
    }

    /**
     * Send email using SMTP (without attachment)
     * 
     * @return bool
     */
    private function sendEmail($to, $subject, $htmlBody, $textBody): bool
    {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"boundary-" . md5(time()) . "\"\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $boundary = "boundary-" . md5(time());

        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $textBody . "\r\n";

        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlBody . "\r\n";

        $message .= "--{$boundary}--";

        // Configure SMTP
        ini_set("SMTP", $this->smtpHost);
        ini_set("smtp_port", $this->smtpPort);
        ini_set("sendmail_from", $this->fromEmail);

        return mail($to, $subject, $message, $headers);
    }
}
