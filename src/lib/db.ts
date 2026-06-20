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
        phone_number VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Ensure phone_number column exists for older tables
    try {
      await client.query('ALTER TABLE agents ADD COLUMN phone_number VARCHAR(20);');
    } catch (e) {
      // Column already exists, ignore
    }

    // Check if master agent 4207112 exists
    const masterAgent = await client.query('SELECT * FROM agents WHERE id = $1', ['4207112']);
    
    // Insert if it doesn't exist, otherwise update phone for testing if empty
    if (masterAgent.rows.length === 0) {
      await client.query(`
        INSERT INTO agents (id, name, area, phone_number)
        VALUES ($1, $2, $3, $4)
      `, ['4207112', 'MAMATA JANA', 'KHARUI 1', '+919999999999']);
    } else if (!masterAgent.rows[0].phone_number) {
      await client.query('UPDATE agents SET phone_number = $1 WHERE id = $2', ['+919999999999', '4207112']);
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

export async function setAgentPhone(agentId: string, phone: string) {
  const client = await pool.connect();
  try {
    await client.query('UPDATE agents SET phone_number = $1 WHERE id = $2', [phone, agentId]);
  } finally {
    client.release();
  }
}

export default pool;
