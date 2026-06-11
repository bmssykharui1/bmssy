const { Pool } = require('pg');

const pool = new Pool({
  connectionString: 'postgresql://postgres.yachsrzzgolxskqxdthe:Subhadeep2006@aws-1-ap-south-1.pooler.supabase.com:5432/postgres',
});

async function run() {
  try {
    const today = new Date();
    const y = today.getFullYear();
    const m = today.getMonth() + 1;
    const startOfMonth = `${y}-${m.toString().padStart(2, '0')}-01`;
    
    // Get last day of month
    const lastDay = new Date(y, m, 0).getDate();
    const endOfMonth = `${y}-${m.toString().padStart(2, '0')}-${lastDay.toString().padStart(2, '0')}`;

    const pf142 = await pool.query(`SELECT COUNT(*) FROM pf_update WHERE approved_ssin LIKE '142%' AND (reason = 'Accepted' OR reason = 'update' OR reason = 'TAGGED' OR reason IS NULL) AND date >= $1 AND date <= $2`, [startOfMonth, endOfMonth]);
    console.log('PF 142 month:', pf142.rows[0].count);

    const pf242 = await pool.query(`SELECT COUNT(*) FROM pf_update WHERE approved_ssin LIKE '242%' AND (reason = 'Accepted' OR reason = 'update' OR reason = 'TAGGED' OR reason IS NULL) AND date >= $1 AND date <= $2`, [startOfMonth, endOfMonth]);
    console.log('PF 242 month:', pf242.rows[0].count);

    const rej = await pool.query(`SELECT COUNT(*) FROM pf_update WHERE reason ILIKE '%Reject%' AND date >= $1 AND date <= $2`, [startOfMonth, endOfMonth]);
    console.log('PF Rejected month:', rej.rows[0].count);

    const month142 = await pool.query(`SELECT COUNT(*) FROM beneficiaries WHERE approved_ssin LIKE '142%' AND created_at >= $1 AND created_at <= $2`, [startOfMonth, endOfMonth]);
    console.log('New 142 month:', month142.rows[0].count);

  } catch(e) {
    console.error(e);
  } finally {
    pool.end();
  }
}

run();
