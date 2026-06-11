'use server';

import pool from '@/lib/db';

export async function getDashboardStats() {
  const client = await pool.connect();
  try {
    const today = new Date();
    // Force Kolkata timezone for current date extraction
    const formatter = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Kolkata', year: 'numeric', month: '2-digit', day: '2-digit' });
    const todayStr = formatter.format(today); // YYYY-MM-DD
    
    const [yStr, mStr, dStr] = todayStr.split('-');
    const y = parseInt(yStr, 10);
    const m = parseInt(mStr, 10);
    
    const startOfMonth = `${y}-${m.toString().padStart(2, '0')}-01`;
    const lastDay = new Date(y, m, 0).getDate();
    const endOfMonth = `${y}-${m.toString().padStart(2, '0')}-${lastDay.toString().padStart(2, '0')}`;

    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const yesterdayStr = formatter.format(yesterday);

    // 1. OTHERS (142...) - Try to exclude known negative remarks if status is missing
    const res142 = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE approved_ssin LIKE '142%' AND (remark IS NULL OR remark NOT ILIKE '%reject%')");
    const count142 = parseInt(res142.rows[0].total, 10);

    // 2. CONTRACTIONS (242...)
    const res242 = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE approved_ssin LIKE '242%' AND (remark IS NULL OR remark NOT ILIKE '%reject%')");
    const count242 = parseInt(res242.rows[0].total, 10);

    // 3. Rejected (Total)
    // Any non-null reason in pf_update is considered a rejection as per user instruction
    const resRejected = await client.query(`
      SELECT COUNT(*) AS total 
      FROM pf_update 
      WHERE reason IS NOT NULL 
        AND (date AT TIME ZONE 'Asia/Kolkata')::date >= $1 AND (date AT TIME ZONE 'Asia/Kolkata')::date <= $2
    `, [startOfMonth, endOfMonth]);
    const totalRejected = parseInt(resRejected.rows[0].total, 10);

    // 4. PF Updates (Monthly Accepted)
    // Accepted means reason IS NULL
    const resPf142 = await client.query(`SELECT COUNT(*) AS total FROM pf_update WHERE approved_ssin LIKE '142%' AND reason IS NULL AND (date AT TIME ZONE 'Asia/Kolkata')::date >= $1 AND (date AT TIME ZONE 'Asia/Kolkata')::date <= $2`, [startOfMonth, endOfMonth]);
    const pf142 = parseInt(resPf142.rows[0].total, 10);

    const resPf242 = await client.query(`SELECT COUNT(*) AS total FROM pf_update WHERE approved_ssin LIKE '242%' AND reason IS NULL AND (date AT TIME ZONE 'Asia/Kolkata')::date >= $1 AND (date AT TIME ZONE 'Asia/Kolkata')::date <= $2`, [startOfMonth, endOfMonth]);
    const pf242 = parseInt(resPf242.rows[0].total, 10);

    // 5. New Add Timeline
    const resToday = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE (created_at AT TIME ZONE 'Asia/Kolkata')::date = $1::date", [todayStr]);
    const totalToday = parseInt(resToday.rows[0].total, 10);

    const resYesterday = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE (created_at AT TIME ZONE 'Asia/Kolkata')::date = $1::date", [yesterdayStr]);
    const totalYesterday = parseInt(resYesterday.rows[0].total, 10);

    const resMonth = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE (created_at AT TIME ZONE 'Asia/Kolkata')::date >= $1 AND (created_at AT TIME ZONE 'Asia/Kolkata')::date <= $2", [startOfMonth, endOfMonth]);
    const totalThisMonth = parseInt(resMonth.rows[0].total, 10);

    // 6. New Monthly SSINs
    const resNew142 = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE approved_ssin LIKE '142%' AND (created_at AT TIME ZONE 'Asia/Kolkata')::date >= $1 AND (created_at AT TIME ZONE 'Asia/Kolkata')::date <= $2", [startOfMonth, endOfMonth]);
    const newCount142 = parseInt(resNew142.rows[0].total, 10);

    const resNew242 = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE approved_ssin LIKE '242%' AND (created_at AT TIME ZONE 'Asia/Kolkata')::date >= $1 AND (created_at AT TIME ZONE 'Asia/Kolkata')::date <= $2", [startOfMonth, endOfMonth]);
    const newCount242 = parseInt(resNew242.rows[0].total, 10);

    return {
      count142,
      count242,
      totalRejected,
      pf142,
      pf242,
      totalToday,
      totalYesterday,
      totalThisMonth,
      newCount142,
      newCount242
    };

  } catch (error) {
    console.error("Dashboard Stats Error:", error);
    return {
      count142: 0, count242: 0, totalRejected: 0,
      pf142: 0, pf242: 0, totalToday: 0,
      totalYesterday: 0, totalThisMonth: 0,
      newCount142: 0, newCount242: 0
    };
  } finally {
    client.release();
  }
}
