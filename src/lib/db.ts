import { Pool } from 'pg';

const pool = new Pool({
  connectionString: 'postgresql://postgres.yachsrzzgolxskqxdthe:Subhadeep2006@aws-1-ap-south-1.pooler.supabase.com:5432/postgres',
});

// Helper function to initialize the database
export async function initializeDatabase() {
  const client = await pool.connect();
  try {
    // Auto-create agents table if it doesn't exist
    await client.query(`
      CREATE TABLE IF NOT EXISTS agents (
        id VARCHAR(50) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        area VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Check if master agent 4207112 exists
    const masterAgent = await client.query('SELECT * FROM agents WHERE id = $1', ['4207112']);
    
    // Insert if it doesn't exist
    if (masterAgent.rows.length === 0) {
      await client.query(`
        INSERT INTO agents (id, name, area)
        VALUES ($1, $2, $3)
      `, ['4207112', 'MAMATA JANA', 'KHARUI 1']);
    }
  } finally {
    client.release();
  }
}

export async function verifyAgent(agentId: string) {
  // Ensure database is initialized first
  await initializeDatabase();

  const client = await pool.connect();
  try {
    const result = await client.query('SELECT * FROM agents WHERE id = $1', [agentId]);
    return result.rows.length > 0 ? result.rows[0] : null;
  } finally {
    client.release();
  }
}

export default pool;
