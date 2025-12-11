// src/db.js
const { google } = require('googleapis');
const fs = require('fs');

const SHEET_ID = process.env.GOOGLE_SHEET_ID;

// Initialize authentication lazily/safely
let auth;
try {
    // Check if credentials file exists if specified
    if (process.env.GOOGLE_APPLICATION_CREDENTIALS && !fs.existsSync(process.env.GOOGLE_APPLICATION_CREDENTIALS)) {
        console.warn('⚠️  Warning: Credentials file not found. Google Sheets logging will be disabled.');
    } else {
        auth = new google.auth.GoogleAuth({
            scopes: ['https://www.googleapis.com/auth/spreadsheets'],
        });
    }
} catch (e) {
    console.error('⚠️  Failed to initialize Google Auth:', e.message);
}

// Format options for Paris timezone
const timeZone = 'Europe/Paris';

/**
 * Format date as string (prevents Google Sheets from converting to serial number)
 */
function formatDate(date) {
    return new Intl.DateTimeFormat('fr-FR', {
        timeZone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).format(date); // DD/MM/YYYY
}

function formatTime(date) {
    return new Intl.DateTimeFormat('fr-FR', {
        timeZone,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    }).format(date); // HH:MM:SS
}

function formatDateTime(date) {
    return `${formatDate(date)} ${formatTime(date)}`;
}

/**
 * Get day of week in French
 */
function getDayOfWeek(date) {
    return new Intl.DateTimeFormat('fr-FR', {
        timeZone,
        weekday: 'long'
    }).format(date); // Lundi, Mardi...
}

/**
 * Clean IP address (remove IPv6 prefix)
 */
function cleanIP(ip) {
    if (!ip) return 'Unknown';
    // Remove ::ffff: prefix for IPv4 addresses
    return ip.replace(/^::ffff:/, '');
}

/**
 * Appends a new event row to the appropriate Google Sheet (Opens or Clicks).
 * 10 Columns in correct order
 */
async function logInteraction(data) {
    if (!SHEET_ID) {
        console.warn('GOOGLE_SHEET_ID is missing. Skipping log.');
        return;
    }

    const now = new Date();

    // Prepare 10 columns of data - USE STRINGS to prevent conversion
    const rowData = [
        String(data.trackingId),                           // 1. Tracking ID
        String(data.eventType),                            // 2. Event Type (OPEN/CLICK)
        formatDate(now),                                   // 3. Date (DD/MM/YYYY)
        formatTime(now),                                   // 4. Time (HH:MM:SS)
        getDayOfWeek(now),                                 // 5. Day of Week
        cleanIP(data.ipAddress),                           // 6. IP Address (cleaned)
        `${data.browser || 'Unknown'} ${data.browserVersion || ''}`.trim(),  // 7. Browser
        data.device || 'desktop',                          // 8. Device Type
        `${data.os || 'Unknown'} ${data.osVersion || ''}`.trim(),  // 9. OS
        String(data.userAgent || '').substring(0, 100),    // 10. User Agent (truncated)
    ];

    console.log('📝 Data to log:', rowData);

    if (!auth) {
        console.log('ℹ️  Google Auth not configured. Skipping Sheet update.');
        return;
    }

    try {
        const client = await auth.getClient();
        const sheets = google.sheets({ version: 'v4', auth: client });

        // Determine which sheet to use based on event type
        const sheetName = data.eventType === 'CLICK' ? 'Clicks' : 'Opens';

        // First, try to ensure the sheet exists
        await ensureSheetExists(sheets, SHEET_ID, sheetName);

        // Append the row with RAW input option to prevent conversion
        const response = await sheets.spreadsheets.values.append({
            spreadsheetId: SHEET_ID,
            range: `${sheetName}!A:J`,
            valueInputOption: 'RAW',  // Changed from USER_ENTERED to RAW
            insertDataOption: 'INSERT_ROWS',
            requestBody: {
                values: [rowData],
            },
        });

        console.log(`✅ Logged to ${sheetName}: ${data.trackingId}`);
        return response.data;
    } catch (err) {
        console.error('❌ Google Sheets API Error:', err.message);

        // Fallback: try to append to default sheet if named sheet fails
        try {
            const client = await auth.getClient();
            const sheets = google.sheets({ version: 'v4', auth: client });

            await sheets.spreadsheets.values.append({
                spreadsheetId: SHEET_ID,
                range: 'A:J',
                valueInputOption: 'RAW',
                insertDataOption: 'INSERT_ROWS',
                requestBody: {
                    values: [rowData],
                },
            });
            console.log(`✅ Logged to default sheet: ${data.trackingId}`);
        } catch (fallbackErr) {
            console.error('❌ Fallback also failed:', fallbackErr.message);
        }
    }
}

/**
 * Ensure a sheet with the given name exists, create it if not
 */
async function ensureSheetExists(sheets, spreadsheetId, sheetName) {
    try {
        // Get existing sheets
        const spreadsheet = await sheets.spreadsheets.get({
            spreadsheetId: spreadsheetId,
        });

        const existingSheets = spreadsheet.data.sheets.map(s => s.properties.title);

        if (!existingSheets.includes(sheetName)) {
            // Create the sheet
            await sheets.spreadsheets.batchUpdate({
                spreadsheetId: spreadsheetId,
                requestBody: {
                    requests: [{
                        addSheet: {
                            properties: {
                                title: sheetName,
                            }
                        }
                    }]
                }
            });

            // Add headers to the new sheet
            const headers = [
                'Tracking ID', 'Event Type', 'Date', 'Time', 'Day',
                'IP Address', 'Browser', 'Device', 'OS', 'User Agent'
            ];

            await sheets.spreadsheets.values.update({
                spreadsheetId: spreadsheetId,
                range: `${sheetName}!A1:J1`,
                valueInputOption: 'RAW',
                requestBody: {
                    values: [headers],
                },
            });

            console.log(`✅ Created sheet: ${sheetName} with headers`);
        }
    } catch (err) {
        // Sheet might already exist or other error, continue anyway
        console.log(`ℹ️ Note: ${err.message}`);
    }
}

// Keep backward compatibility
async function logEmailOpen(trackingId, userAgent, ipAddress) {
    return logInteraction({
        trackingId,
        eventType: 'OPEN',
        userAgent,
        ipAddress,
        browser: 'Unknown',
        browserVersion: '',
        os: 'Unknown',
        osVersion: '',
        device: 'Unknown',
        referer: '',
        acceptLanguage: ''
    });
}

module.exports = {
    logEmailOpen,
    logInteraction,
};
