// src/db.js
const { Pool } = require('pg');

const pool = new Pool({
  host: process.env.DB_HOST || 'db',
  database: process.env.DB_NAME || 'email_tracker',
  user: process.env.DB_USER || 'tracker_user',
  password: process.env.DB_PASSWORD || 'secure_tracker_pass',
  port: 5432,
});

pool.on('error', (err, client) => {
  console.error('Unexpected error on idle client', err);
  process.exit(-1);
});

module.exports = {
  query: (text, params) => pool.query(text, params),
};
