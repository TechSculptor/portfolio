// src/utils/generate_link.js
const crypto = require('crypto');

const uniqueId = crypto.randomBytes(16).toString('hex');
const baseUrl = 'http://localhost:3000/track'; // Adjust if deploying

console.log('\n--- Email Tracking Link Generator ---');
console.log(`Unique Tracking ID: ${uniqueId}`);
console.log(`Tracking URL:       ${baseUrl}?id=${uniqueId}`);
console.log(`HTML Embed Code:    <img src="${baseUrl}?id=${uniqueId}" width="1" height="1" alt="" />`);
console.log('-------------------------------------\n');
