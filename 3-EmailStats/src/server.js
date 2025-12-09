// src/server.js
const express = require('express');
const db = require('./db');
const app = express();
const port = process.env.PORT || 3000;

// Transparent 1x1 GIF Buffer
const PIXEL = Buffer.from(
    'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==',
    'base64'
);

// Health check / Root
app.get('/', (req, res) => {
    res.send('Email Interaction Tracker Running');
});

// Tracking Endpoint
app.get('/track', async (req, res) => {
    const trackingId = req.query.id;
    const userAgent = req.get('User-Agent') || 'Unknown';
    // Attempt to get IP from x-forwarded-for (if behind proxy) or socket
    const ipAddress = req.headers['x-forwarded-for'] || req.socket.remoteAddress || 'Unknown';

    if (trackingId) {
        try {
            await db.query(
                'INSERT INTO email_opens (tracking_id, user_agent, ip_address) VALUES ($1, $2, $3)',
                [trackingId, userAgent, ipAddress]
            );
            console.log(`Tracked: ${trackingId}`);
        } catch (err) {
            console.error('Error logging track:', err.message);
        }
    }

    // Always return the pixel
    res.set('Content-Type', 'image/gif');
    res.set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    res.send(PIXEL);
});

// Start Server
app.listen(port, () => {
    console.log(`Server running at http://localhost:${port}`);
});
