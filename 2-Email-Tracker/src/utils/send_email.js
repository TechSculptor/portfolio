// src/utils/send_email.js
const nodemailer = require('nodemailer');
const fs = require('fs');
const path = require('path');

// Configuration depuis les variables d'environnement
const GMAIL_USER = process.env.GMAIL_USER;
const GMAIL_APP_PASSWORD = process.env.GMAIL_APP_PASSWORD;
const TO_EMAIL = process.env.TO_EMAIL || GMAIL_USER;
const TRACKING_BASE_URL = process.env.TRACKING_URL || 'http://localhost:3000';

// Générer un ID de tracking unique
function generateTrackingId() {
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(2, 8).toUpperCase();
    return `EMAIL-${timestamp}-${random}`;
}

// Créer le contenu HTML de l'email avec le pixel de tracking
function createEmailHtml(trackingId) {
    // Add cache buster to prevent caching (especially Gmail)
    const trackingUrl = `${TRACKING_BASE_URL}/track?id=${trackingId}&t=${Date.now()}`;

    return `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter - Offre Exclusive</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    
    <!-- PIXEL DE TRACKING INVISIBLE -->
    <img src="${trackingUrl}" width="1" height="1" style="display:none;" alt="" />

    <!-- Container principal -->
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                <h1 style="color: white; margin: 0; font-size: 28px; font-weight: 600;">
                    🚀 Offre Exclusive
                </h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 16px;">
                    Votre newsletter personnalisée
                </p>
            </td>
        </tr>

        <!-- Image principale (cliquable) -->
        <tr>
            <td style="padding: 0;">
                <a href="${TRACKING_BASE_URL}/track?id=${trackingId}-CLICK-IMAGE">
                    <img src="https://images.unsplash.com/photo-1551434678-e076c223a692?w=600&h=300&fit=crop" 
                         alt="Tech workspace" 
                         width="600" 
                         style="width: 100%; height: auto; display: block; cursor: pointer;">
                </a>
            </td>
        </tr>

        <!-- Contenu -->
        <tr>
            <td style="padding: 30px;">
                <h2 style="color: #333; margin: 0 0 15px; font-size: 22px;">
                    Bonjour ! 👋
                </h2>
                <p style="color: #666; line-height: 1.6; margin: 0 0 20px;">
                    Ceci est un email de test pour vérifier le système de tracking.
                    <br><br>
                    <strong>Tracking ID:</strong> <code>${trackingId}</code>
                </p>

                <!-- Bouton CTA -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td align="center" style="padding: 20px 0;">
                            <a href="${TRACKING_BASE_URL}/track?id=${trackingId}-CLICK" 
                               style="display: inline-block; 
                                      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                                      color: white; 
                                      padding: 15px 40px; 
                                      text-decoration: none; 
                                      border-radius: 50px; 
                                      font-weight: 600;
                                      font-size: 16px;">
                                🎁 Cliquez ici pour tester
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 25px 30px; background: #2d3748; text-align: center;">
                <p style="color: #a0aec0; font-size: 12px; margin: 0;">
                    © 2024 Email Tracker Demo - Projet Portfolio
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
`;
}

async function sendTestEmail() {
    // Vérifier la configuration
    if (!GMAIL_USER || !GMAIL_APP_PASSWORD) {
        console.error('❌ Configuration manquante!');
        console.log('\nAjoutez ces variables à votre fichier .env:');
        console.log('  GMAIL_USER=votre.email@gmail.com');
        console.log('  GMAIL_APP_PASSWORD=votre_mot_de_passe_app');
        console.log('  TO_EMAIL=destinataire@example.com (optionnel)');
        console.log('\n💡 Pour obtenir un mot de passe d\'application:');
        console.log('   1. Allez sur https://myaccount.google.com/apppasswords');
        console.log('   2. Générez un mot de passe pour "Mail"');
        process.exit(1);
    }

    const trackingId = generateTrackingId();
    const htmlContent = createEmailHtml(trackingId);

    // Créer le transporteur SMTP
    const transporter = nodemailer.createTransport({
        service: 'gmail',
        auth: {
            user: GMAIL_USER,
            pass: GMAIL_APP_PASSWORD
        }
    });

    // Options de l'email
    const mailOptions = {
        from: `"Email Tracker Demo" <${GMAIL_USER}>`,
        to: TO_EMAIL,
        subject: `🔬 Test Email Tracker - ${trackingId}`,
        html: htmlContent
    };

    try {
        console.log('📧 Envoi de l\'email de test...');
        console.log(`   De: ${GMAIL_USER}`);
        console.log(`   À: ${TO_EMAIL}`);
        console.log(`   Tracking ID: ${trackingId}`);

        const info = await transporter.sendMail(mailOptions);

        console.log('\n✅ Email envoyé avec succès!');
        console.log(`   Message ID: ${info.messageId}`);
        console.log('\n📊 Prochaines étapes:');
        console.log('   1. Ouvrez l\'email dans votre boîte de réception');
        console.log('   2. Vérifiez le Google Sheet pour voir les données de tracking');
        console.log(`   3. Cliquez sur le bouton dans l'email pour un second tracking`);

    } catch (error) {
        console.error('❌ Erreur lors de l\'envoi:', error.message);
        if (error.message.includes('Invalid login')) {
            console.log('\n💡 Vérifiez:');
            console.log('   - Votre mot de passe d\'application est correct');
            console.log('   - La double authentification est activée sur Gmail');
        }
    }
}

sendTestEmail();
