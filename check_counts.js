const { Pool } = require('pg');

const pool = new Pool({
  connectionString: 'postgresql://postgres.yachsrzzgolxskqxdthe:Subhadeep2006@aws-1-ap-south-1.pooler.supabase.com:5432/postgres',
});

async function run() {
  try {
    const res142 = await pool.query(`SELECT COUNT(*) FROM beneficiaries WHERE approved_ssin LIKE '142%'`);
    console.log('142 count:', res142.rows[0].count);

    const res242 = await pool.query(`SELECT COUNT(*) FROM beneficiaries WHERE approved_ssin LIKE '242%'`);
    console.log('242 count:', res242.rows[0].count);

    const pf142 = await pool.query(`SELECT COUNT(*) FROM pf_update WHERE approved_ssin LIKE '142%' AND (reason IS NULL OR reason = '')`);
    console.log('PF 142 accepted:', pf142.rows[0].count);

    const pf242 = await pool.query(`SELECT COUNT(*) FROM pf_update WHERE approved_ssin LIKE '242%' AND (reason IS NULL OR reason = '')`);
    console.log('PF 242 accepted:', pf242.rows[0].count);

    const pfRej = await pool.query(`SELECT COUNT(*) FROM pf_update WHERE reason ILIKE '%reject%'`);
    console.log('PF Rejected:', pfRej.rows[0].count);

  } catch(e) {
    console.error(e);
  } finally {
    pool.end();
  }
}

run();
