const { Pool } = require('pg');

const pool = new Pool({
  connectionString: 'postgresql://postgres.yachsrzzgolxskqxdthe:Subhadeep2006@aws-1-ap-south-1.pooler.supabase.com:5432/postgres',
});

async function run() {
  try {
    const res = await pool.query(`
      SELECT column_name, data_type 
      FROM information_schema.columns 
      WHERE table_name = 'pf_update';
    `);
    console.log('pf_update:', res.rows);

    const res2 = await pool.query(`
      SELECT column_name, data_type 
      FROM information_schema.columns 
      WHERE table_name = 'form4_entries';
    `);
    console.log('form4_entries:', res2.rows);
  } catch(e) {
    console.error(e);
  } finally {
    pool.end();
  }
}

run();
