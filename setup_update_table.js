const { Pool } = require('pg');

const pool = new Pool({
  connectionString: 'postgresql://postgres.yachsrzzgolxskqxdthe:Subhadeep2006@aws-1-ap-south-1.pooler.supabase.com:5432/postgres',
});

async function run() {
  try {
    await pool.query(`
      CREATE TABLE IF NOT EXISTS app_updates (
        id SERIAL PRIMARY KEY,
        version_code INTEGER NOT NULL,
        version_name VARCHAR(50) NOT NULL,
        apk_url TEXT NOT NULL,
        release_notes TEXT,
        is_mandatory BOOLEAN DEFAULT false,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
      );
    `);
    console.log("Created app_updates table.");

    // Insert dummy record for v1.0.0 (Code: 1)
    await pool.query(`
      INSERT INTO app_updates (version_code, version_name, apk_url, release_notes, is_mandatory)
      VALUES (1, '1.0.0', 'https://example.com/app.apk', 'Initial Release.', false)
      ON CONFLICT DO NOTHING; -- No unique constraint to conflict on, but just in case.
    `);
    console.log("Inserted initial version data.");

  } catch(e) {
    console.error(e);
  } finally {
    pool.end();
  }
}

run();
