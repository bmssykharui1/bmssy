const { Pool } = require('pg');

const pool = new Pool({
  connectionString: 'postgresql://postgres.yachsrzzgolxskqxdthe:Subhadeep2006@aws-1-ap-south-1.pooler.supabase.com:5432/postgres',
});

async function run() {
  try {
    const res = await pool.query(`
      SELECT DISTINCT remark FROM beneficiaries;
    `);
    console.log(res.rows);
  } catch(e) {
    console.error(e);
  } finally {
    pool.end();
  }
}

run();
