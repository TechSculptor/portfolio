// src/server.js
const express = require('express');
const UAParser = require('ua-parser-js');
const nodemailer = require('nodemailer');
const path = require('path');
const db = require('./db');
const app = express();
const port = process.env.PORT || 3000;

// Middleware
app.use(express.json());
app.use(express.static(path.join(__dirname, '../public')));

// Configuration SMTP
const SMTP_HOST = process.env.SMTP_HOST || 'mailhog';
const SMTP_PORT = process.env.SMTP_PORT || 1025;
const GMAIL_USER = process.env.GMAIL_USER;
const GMAIL_APP_PASSWORD = process.env.GMAIL_APP_PASSWORD;

// Transparent 1x1 GIF Buffer
const PIXEL = Buffer.from(
    'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==',
    'base64'
);

// Helper: Generate ID
function generateTrackingId() {
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(2, 8).toUpperCase();
    return `EMAIL-${timestamp}-${random}`;
}

// Parse User Agent for detailed info
function parseUserAgent(uaString) {
    const parser = new UAParser(uaString);
    const result = parser.getResult();
    return {
        browser: result.browser.name || 'Unknown',
        browserVersion: result.browser.version || '',
        os: result.os.name || 'Unknown',
        osVersion: result.os.version || '',
        device: result.device.type || 'desktop',
        deviceVendor: result.device.vendor || '',
        deviceModel: result.device.model || ''
    };
}

// Tracking Endpoint
app.get('/track', async (req, res) => {
    const trackingId = req.query.id || 'UNKNOWN';
    const userAgent = req.get('User-Agent') || 'Unknown';
    const ipAddress = req.headers['x-forwarded-for'] || req.socket.remoteAddress || 'Unknown';
    const referer = req.get('Referer') || 'Direct';
    const acceptLanguage = req.get('Accept-Language') || 'Unknown';

    // Determine event type based on tracking ID
    const isClick = trackingId.includes('CLICK') || trackingId.includes('click');
    const eventType = isClick ? 'CLICK' : 'OPEN';

    // Parse user agent for detailed info
    const uaInfo = parseUserAgent(userAgent);

    try {
        // Log to Google Sheets with extended data
        await db.logInteraction({
            trackingId,
            eventType,
            userAgent,
            ipAddress,
            referer,
            acceptLanguage,
            ...uaInfo
        });
        console.log(`Tracked [${eventType}]: ${trackingId}`);
    } catch (err) {
        console.error('Error logging track:', err.message);
    }

    // For clicks, show a confirmation page instead of downloading a file
    if (isClick) {
        res.set('Content-Type', 'text/html');
        res.send(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Merci !</title>
                <style>
                    body { 
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        display: flex; 
                        justify-content: center; 
                        align-items: center; 
                        min-height: 100vh; 
                        margin: 0;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    }
                    .card {
                        background: white;
                        padding: 40px 60px;
                        border-radius: 16px;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                        text-align: center;
                    }
                    h1 { color: #333; margin: 0 0 10px; }
                    p { color: #666; margin: 0; }
                    .emoji { font-size: 48px; margin-bottom: 20px; }
                </style>
            </head>
            <body>
                <div class="card">
                    <div class="emoji">✅</div>
                    <h1>Clic enregistré !</h1>
                    <p>Tracking ID: <code>${trackingId}</code></p>
                </div>
            </body>
            </html>
        `);
    } else {
        // For opens (pixel tracking), return invisible 1x1 GIF
        res.set('Content-Type', 'image/gif');
        res.set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        res.send(PIXEL);
    }
});

// Send Test Email Endpoint
app.post('/send-test', async (req, res) => {
    const { email } = req.body;
    if (!email) {
        return res.status(400).json({ success: false, message: 'Email required' });
    }

    const trackingId = generateTrackingId();
    const appUrl = `${req.protocol}://${req.get('host')}`;
    const trackingPixelUrl = `${appUrl}/track?id=${trackingId}`;
    const clickUrl = `${appUrl}/track?id=${trackingId}-CLICK`;

    // Configure Transport
    let transporter;
    let isSimulated = false;

    if (GMAIL_USER && GMAIL_APP_PASSWORD) {
        // Production (Gmail)
        transporter = nodemailer.createTransport({
            service: 'gmail',
            auth: { user: GMAIL_USER, pass: GMAIL_APP_PASSWORD }
        });
    } else if (SMTP_HOST === 'mailhog') {
        // Development detected (MailHog)
        // Check if we are actually able to reach mailhog?
        // Safest is to rely on env vars. 
        // If we are in 'production' (Render) and SMTP_HOST is still 'mailhog' (default), 
        // it means user forgot to set GMAIL keys. We should mock it to prevent crash.

        if (process.env.NODE_ENV === 'production') {
            console.warn("⚠️ Production environment detected but no Email Credentials found. Simulating email send.");
            isSimulated = true;
            transporter = nodemailer.createTransport({
                jsonTransport: true
            });
        } else {
            // Local Docker with MailHog
            transporter = nodemailer.createTransport({
                host: SMTP_HOST,
                port: SMTP_PORT,
                ignoreTLS: true
            });
        }
    } else {
        // Fallback
        isSimulated = true;
        transporter = nodemailer.createTransport({ jsonTransport: true });
    }

    const htmlContent = `
        <div style="font-family: Arial, sans-serif; padding: 20px; text-align: center;">
            <h2>Test Portfolio</h2>
            <p>Ceci est un email de test pour démontrer le tracking.</p>
            <p><strong>Votre ID unique :</strong> ${trackingId}</p>
            <br>
            <a href="${clickUrl}" style="background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Tester le Clic</a>
            <br><br>
            <img src="${trackingPixelUrl}" alt="" width="1" height="1" border="0" />
            <p style="color: grey; font-size: 10px;">(Image de tracking invisible ci-dessus)</p>
        </div>
    `;

    try {
        const info = await transporter.sendMail({
            from: '"TechSculptor Demo" <demo@techsculptor.com>',
            to: email,
            subject: `Demo Tracking: ${trackingId}`,
            html: htmlContent
        });

        if (isSimulated) {
            res.json({ success: true, trackingId, message: "Simulation (Pas d'identifiants SMTP configurés)" });
        } else {
            res.json({ success: true, trackingId });
        }
    } catch (error) {
        console.error("Send Error:", error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// Start Server
app.listen(port, () => {
    console.log(`Server running at http://localhost:${port}`);
    console.log(`SMTP Configured: ${GMAIL_USER ? 'Gmail' : 'MailHog (' + SMTP_HOST + ':' + SMTP_PORT + ')'}`);
});
