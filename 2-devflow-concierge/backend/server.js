import express from 'express';
import cors from 'cors';
import pg from 'pg';
import dotenv from 'dotenv';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 5000;

// Middleware
app.use(cors({
  origin: '*',
  credentials: true
}));
app.use(express.json());

// PostgreSQL connection pool
const pool = new pg.Pool({
  user: process.env.POSTGRES_USER,
  host: process.env.POSTGRES_HOST,
  database: process.env.POSTGRES_DB,
  password: process.env.POSTGRES_PASSWORD,
  port: parseInt(process.env.POSTGRES_PORT || '5432'),
});

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Submit Requirements
app.post('/api/requirements', async (req, res) => {
  try {
    const { session_id, service_type, client_info, messages } = req.body;

    console.log(`[Requirements] Received submission for session ${session_id}`);

    // Validate payload
    if (!session_id || !messages) {
      return res.status(400).json({ success: false, error: 'Missing required fields' });
    }

    // Insert into DB
    const query = `
      INSERT INTO client_requirements (session_id, service_type, data)
      VALUES ($1, $2, $3)
      RETURNING id, created_at
    `;

    // Store the whole body as the JSON blob, or structure it as needed. 
    // The schema has 'data' column. We can just store the whole request body or the relevant parts.
    // Let's store the relevant parts object.
    const dataToStore = {
      client_info,
      messages,
      metadata: {
        submitted_at: new Date().toISOString()
      }
    };

    const values = [session_id, service_type, dataToStore];

    const result = await pool.query(query, values);

    console.log(`[Requirements] Saved requirements with ID: ${result.rows[0].id}`);

    res.json({
      success: true,
      message: 'Requirements saved successfully',
      id: result.rows[0].id
    });

  } catch (error) {
    console.error('Error processing requirements:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Backend server running on port ${PORT}`);
  console.log(`📊 Health check: http://localhost:${PORT}/health`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('SIGTERM signal received: closing HTTP server');
  pool.end();
  process.exit(0);
});
