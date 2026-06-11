const { Pool } = require('pg');

const pool = new Pool({
  connectionString: 'postgresql://postgres.yachsrzzgolxskqxdthe:Subhadeep2006@aws-1-ap-south-1.pooler.supabase.com:5432/postgres',
});

async function run() {
  try {
    const res = await pool.query(`
      SELECT table_name 
      FROM information_schema.tables 
      WHERE table_schema = 'public'
    `);
    console.log(res.rows);

    // If global_settings exists, select from it
    const hasGlobalSettings = res.rows.some(r => r.table_name === 'global_settings');
    if (hasGlobalSettings) {
      const globalRes = await pool.query(`SELECT * FROM global_settings`);
      console.log("Global Settings Data:", globalRes.rows);
    } else {
      console.log("Table global_settings DOES NOT EXIST.");
    }
  } catch(e) {
    console.error(e);
  } finally {
    pool.end();
  }
}

run();
