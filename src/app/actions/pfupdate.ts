'use server';

import pool from '@/lib/db';
import { revalidatePath } from 'next/cache';

// 1. Get Global Settings
export async function getGlobalSettings() {
  let client;
  try {
    client = await pool.connect();
    const res = await client.query(`SELECT TO_CHAR(period_form, 'YYYY-MM-DD') as period_form, TO_CHAR(period_to, 'YYYY-MM-DD') as period_to FROM global_settings WHERE id = 1 LIMIT 1`);
    if (res.rows.length > 0) {
      return {
        period_from: res.rows[0].period_form || '',
        period_to: res.rows[0].period_to || ''
      };
    }
    return { period_from: '', period_to: '' };
  } catch (error) {
    console.error("Error fetching global settings:", error);
    return { period_from: '', period_to: '' };
  } finally {
    if (client) client.release();
  }
}

// 2. Update Global Settings
export async function updateGlobalSettings(period_from: string, period_to: string) {
  let client;
  try {
    client = await pool.connect();
    await client.query(
      `UPDATE global_settings SET period_form = $1, period_to = $2, updated_at = NOW() WHERE id = 1`,
      [period_from, period_to]
    );
    return { success: true };
  } catch (error: any) {
    console.error("Error updating global settings:", error);
    return { error: error.message || 'Failed to update settings' };
  } finally {
    if (client) client.release();
  }
}

// 3. Get Pending PF Updates for a specific SSIN prefix (142 or 242)
export async function getPendingPFUpdates(prefix: string) {
  let client;
  try {
    client = await pool.connect();
    // We need the global period_to to filter out already updated ones
    const settings = await getGlobalSettings();
    const globalPeriodTo = settings.period_to; // e.g. "2026-03-30"

    // Single fast query using LEFT JOIN LATERAL to get the latest pf_update period_to
    const res = await client.query(`
      SELECT 
        b.id, 
        b.beneficiary_name, 
        b.approved_ssin,
        TO_CHAR(pf.period_to, 'YYYY-MM-DD') as latest_period_to
      FROM beneficiaries b
      LEFT JOIN LATERAL (
        SELECT period_to 
        FROM pf_update 
        WHERE approved_ssin = b.approved_ssin 
        ORDER BY id DESC 
        LIMIT 1
      ) pf ON true
      WHERE (b.remark IS NULL OR b.remark NOT ILIKE '%reject%')
        AND b.approved_ssin LIKE $1
      ORDER BY b.id DESC
    `, [`${prefix}%`]);

    const pendingData = [];

    for (const row of res.rows) {
      const pfPeriodTo = row.latest_period_to || null;

      // If the latest period_to matches the global period_to, they are already updated -> SKIP
      if (pfPeriodTo && globalPeriodTo && pfPeriodTo === globalPeriodTo) {
        continue;
      }

      pendingData.push({
        id: row.id,
        beneficiary_name: row.beneficiary_name,
        approved_ssin: row.approved_ssin,
        latest_period_to: pfPeriodTo
      });
    }

    return pendingData;
  } catch (error) {
    console.error("Error fetching pending updates:", error);
    return [];
  } finally {
    if (client) client.release();
  }
}

// 4. Accept PF Update
export async function acceptPFUpdate(ssin: string, name: string, period_from: string, period_to: string) {
  let client;
  try {
    client = await pool.connect();
    // Get Beneficiary ID
    const bRes = await client.query(`SELECT id FROM beneficiaries WHERE approved_ssin = $1`, [ssin]);
    if (bRes.rows.length === 0) {
      return { error: 'SSIN not found in beneficiaries table' };
    }
    const beneficiary_id = bRes.rows[0].id;

    // Insert into pf_update
    await client.query(`
      INSERT INTO pf_update (beneficiary_name, approved_ssin, date, beneficiary_id, period_form, period_to, last_update, reason)
      VALUES ($1, $2, (NOW() AT TIME ZONE 'Asia/Kolkata')::date, $3, $4, $5, NOW(), NULL)
    `, [name, ssin, beneficiary_id, period_from, period_to]);

    revalidatePath('/pfupdate/others');
    revalidatePath('/pfupdate/constractions');
    revalidatePath('/dashboard');

    return { success: true };
  } catch (error: any) {
    console.error("Error accepting PF Update:", error);
    return { error: error.message || 'Failed to save PF update' };
  } finally {
    if (client) client.release();
  }
}

// 5. Reject PF Update
export async function rejectPFUpdate(ssin: string, name: string, reason: string) {
  let client;
  try {
    client = await pool.connect();
    await client.query('BEGIN');

    // Get Beneficiary ID
    const bRes = await client.query(`SELECT id FROM beneficiaries WHERE approved_ssin = $1`, [ssin]);
    if (bRes.rows.length === 0) {
      throw new Error('SSIN not found in beneficiaries table');
    }
    const beneficiary_id = bRes.rows[0].id;

    // Insert Rejection into pf_update
    await client.query(`
      INSERT INTO pf_update (beneficiary_name, approved_ssin, date, beneficiary_id, reason, last_update)
      VALUES ($1, $2, (NOW() AT TIME ZONE 'Asia/Kolkata')::date, $3, $4, NOW())
    `, [name, ssin, beneficiary_id, reason]);

    // Mark as inactive in beneficiaries
    await client.query(`
      UPDATE beneficiaries 
      SET remark = $1, last_update = NOW()
      WHERE approved_ssin = $2
    `, [`Rejected: ${reason}`, ssin]);

    await client.query('COMMIT');

    revalidatePath('/pfupdate/others');
    revalidatePath('/pfupdate/constractions');
    revalidatePath('/dashboard');
    revalidatePath('/list/alldata');

    return { success: true };
  } catch (error: any) {
    if (client) await client.query('ROLLBACK');
    console.error("Error rejecting PF Update:", error);
    return { error: error.message || 'Failed to reject beneficiary' };
  } finally {
    if (client) client.release();
  }
}
