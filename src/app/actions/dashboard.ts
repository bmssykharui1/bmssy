'use server';

import pool from '@/lib/db';

export async function getDashboardStats() {
  const client = await pool.connect();
  try {
    // 1. OTHERS (142...)
    const res142 = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE approved_ssin LIKE '142%' AND status = 'active'");
    const count142 = parseInt(res142.rows[0].total, 10);

    // 2. CONTRACTIONS (242...)
    const res242 = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE approved_ssin LIKE '242%' AND status = 'active'");
    const count242 = parseInt(res242.rows[0].total, 10);

    // 3. Rejected
    const resRejected = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE status = 'rejected'");
    const totalRejected = parseInt(resRejected.rows[0].total, 10);

    // 4. PF Updates (Accepted amounts for 142 and 242)
    // Assuming we can join pf_update with beneficiaries to check SSIN type
    const resPf142 = await client.query(`
      SELECT COALESCE(SUM(p.amount), 0) AS total 
      FROM pf_update p
      JOIN beneficiaries b ON p.approved_ssin = b.approved_ssin
      WHERE p.status = 'Accepted' AND b.approved_ssin LIKE '142%'
    `);
    const pf142 = parseFloat(resPf142.rows[0].total);

    const resPf242 = await client.query(`
      SELECT COALESCE(SUM(p.amount), 0) AS total 
      FROM pf_update p
      JOIN beneficiaries b ON p.approved_ssin = b.approved_ssin
      WHERE p.status = 'Accepted' AND b.approved_ssin LIKE '242%'
    `);
    const pf242 = parseFloat(resPf242.rows[0].total);

    // 5. New Add Timeline (Today, Yesterday, Month)
    // We assume created_at is a DATE or TIMESTAMP
    const todayStr = new Date().toISOString().split('T')[0];
    
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const yesterdayStr = yesterday.toISOString().split('T')[0];

    const thisMonthPrefix = todayStr.substring(0, 7); // 'YYYY-MM'

    const resToday = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE created_at = $1::date", [todayStr]);
    const totalToday = parseInt(resToday.rows[0].total, 10);

    const resYesterday = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE created_at = $1::date", [yesterdayStr]);
    const totalYesterday = parseInt(resYesterday.rows[0].total, 10);

    const resMonth = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE TO_CHAR(created_at, 'YYYY-MM') = $1", [thisMonthPrefix]);
    const totalThisMonth = parseInt(resMonth.rows[0].total, 10);

    // 6. New Monthly SSINs (Like the old logic)
    const resNew142 = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE approved_ssin LIKE '142%' AND TO_CHAR(created_at, 'YYYY-MM') = $1", [thisMonthPrefix]);
    const newCount142 = parseInt(resNew142.rows[0].total, 10);

    const resNew242 = await client.query("SELECT COUNT(*) AS total FROM beneficiaries WHERE approved_ssin LIKE '242%' AND TO_CHAR(created_at, 'YYYY-MM') = $1", [thisMonthPrefix]);
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
    // Return empty stats if error so the UI doesn't crash completely
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
