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
    const lastDay = new Date(y, m, 0).getDate();
    const endOfMonth = `${y}-${m.toString().padStart(2, '0')}-${lastDay.toString().padStart(2, '0')}`;

    console.log("Date range:", startOfMonth, "to", endOfMonth);

    const res = await pool.query(`
      SELECT approved_ssin, reason, date 
      FROM pf_update 
      WHERE date >= $1 AND date <= $2
      ORDER BY date DESC
    `, [startOfMonth, endOfMonth]);
    
    console.log("Rows this month:", res.rows);

  } catch(e) {
    console.error(e);
  } finally {
    pool.end();
  }
}

run();
