<?php
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    error_log('PHPMailer autoload introuvable; le mailer est désactivé.');
    return;
}

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail($to, $subject, $body, $attachmentPath = null, $attachmentName = null) {
    $mail = new PHPMailer(true);
    try {
        // Paramètres du serveur
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // À configurer avec le vrai serveur SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'donotreply.ejce@gmail.com'; // À configurer
        $mail->Password   = 'ptqi epnj gsnf ghpa'; // À configurer
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->CharSet    = 'UTF-8';
        
        // Expéditeur (utiliser l'adresse Gmail pour éviter les spams)
        $mail->setFrom('donotreply.ejce@gmail.com', 'EJCE Notification');
        
        // Destinataire(s)
        $mail->addAddress($to);
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
        
        // Pièce jointe optionnelle
        if ($attachmentPath && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath, $attachmentName);
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email: {$mail->ErrorInfo}");
        return false;
    }
}
