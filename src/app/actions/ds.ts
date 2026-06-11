'use server';

import pool from '@/lib/db';
import { getGlobalSettings } from './pfupdate';

// 1. Get DS Entry Candidates
export async function getDSEntryCandidates() {
  let client;
  try {
    client = await pool.connect();
    const settings = await getGlobalSettings();
    const globalPeriodTo = settings.period_to;

    // Get all SSINs already in ds_record
    const dsRes = await client.query(`SELECT ssin FROM ds_record`);
    const existingDs = new Set(dsRes.rows.map(r => r.ssin));

    const res = await client.query(`
      SELECT 
        b.id, b.beneficiary_name, b.approved_ssin, b.phone_no,
        TO_CHAR(pf.period_to, 'YYYY-MM-DD') as latest_period_to
      FROM beneficiaries b
      LEFT JOIN LATERAL (
        SELECT period_to FROM pf_update 
        WHERE approved_ssin = b.approved_ssin 
        ORDER BY id DESC LIMIT 1
      ) pf ON true
      WHERE (b.remark IS NULL OR b.remark NOT ILIKE '%reject%')
      AND (b.approved_ssin LIKE '142%' OR b.approved_ssin LIKE '242%')
      ORDER BY b.id DESC
    `);

    const candidates = [];
    for (const row of res.rows) {
      const pfPeriodTo = row.latest_period_to || null;
      
      // Skip if they already have a PF update for the current period
      // This ensures the list exactly matches "PF Update -> Others & Constructions"
      if (pfPeriodTo && globalPeriodTo && pfPeriodTo === globalPeriodTo) {
        continue;
      }

      candidates.push({
        approved_ssin: row.approved_ssin,
        beneficiary_name: row.beneficiary_name,
        phone: row.phone_no || '-',
        source: 'Pending PF Update'
      });
    }

    return candidates;
  } catch (error: any) {
    console.error("Error fetching DS Entry Candidates:", error);
    return [];
  } finally {
    if (client) client.release();
  }
}

// 2. Save DS Entry
export async function saveDSEntry(ssin: string, name: string, dsno: string) {
  let client;
  try {
    client = await pool.connect();
    // Extract only digits from dsno
    const cleanDsno = dsno.replace(/\D/g, '');
    
    // Check for duplicate
    const check = await client.query(`SELECT id FROM ds_record WHERE ssin = $1 LIMIT 1`, [ssin]);
    if (check.rows.length > 0) {
      await client.query(`
        UPDATE ds_record 
        SET dsno = $1, created_at = NOW() 
        WHERE ssin = $2
      `, [cleanDsno, ssin]);
    } else {
      await client.query(`
        INSERT INTO ds_record (ssin, name, dsno, created_at) 
        VALUES ($1, $2, $3, NOW())
      `, [ssin, name, cleanDsno]);
    }

    return { success: true };
  } catch (error: any) {
    console.error("Error saving DS entry:", error);
    return { error: error.message };
  } finally {
    if (client) client.release();
  }
}

// 3. Get Pending DS PF Updates
export async function getDSPendingPFUpdates() {
  let client;
  try {
    client = await pool.connect();
    const settings = await getGlobalSettings();
    const globalPeriodTo = settings.period_to;

    // Get DS records that don't have a PF update for the current period
    const res = await client.query(`
      SELECT 
        d.id, d.ssin, d.name, d.dsno, 
        TO_CHAR(d.created_at, 'YYYY-MM-DD') as ds_date,
        TO_CHAR(pf.period_to, 'YYYY-MM-DD') as latest_period_to
      FROM ds_record d
      LEFT JOIN LATERAL (
        SELECT period_to FROM pf_update 
        WHERE approved_ssin = d.ssin 
        ORDER BY id DESC LIMIT 1
      ) pf ON true
      ORDER BY d.id DESC
    `);

    const pending = [];
    for(const row of res.rows) {
      if (row.latest_period_to === globalPeriodTo) continue; // Already updated for current period
      
      pending.push({
        ssin: row.ssin,
        name: row.name,
        dsno: row.dsno,
        ds_date: row.ds_date
      });
    }

    return pending;
  } catch (error: any) {
    console.error("Error fetching DS Pending PF Updates:", error);
    return [];
  } finally {
    if (client) client.release();
  }
}

// 4. Save DS PF Update
export async function saveDSPFUpdate(ssin: string, name: string, dsno: string, ds_date: string, periodFrom: string, periodTo: string) {
  let client;
  try {
    client = await pool.connect();
    const bRes = await client.query(`SELECT id FROM beneficiaries WHERE approved_ssin = $1 LIMIT 1`, [ssin]);
    if (bRes.rows.length === 0) {
      return { error: 'Beneficiary not found in master records.' };
    }
    const bId = bRes.rows[0].id;

    await client.query(`
      INSERT INTO pf_update (beneficiary_name, approved_ssin, status, date, beneficiary_id, period_form, period_to, ds_no, ds_date, last_update)
      VALUES ($1, $2, 'Accepted', CURRENT_DATE, $3, $4, $5, $6, $7, NOW())
    `, [name, ssin, bId, periodFrom, periodTo, dsno, ds_date]);

    return { success: true };
  } catch (error: any) {
    console.error("Error saving DS PF update:", error);
    return { error: error.message };
  } finally {
    if (client) client.release();
  }
}

// 5. Get DS List
export async function getDSList() {
  let client;
  try {
    client = await pool.connect();
    const res = await client.query(`
      SELECT 
        id, ssin, name, dsno, 
        TO_CHAR(created_at, 'YYYY-MM-DD') as created_date
      FROM ds_record 
      ORDER BY created_at DESC
    `);
    return res.rows;
  } catch (error: any) {
    console.error("Error fetching DS List:", error);
    return [];
  } finally {
    if (client) client.release();
  }
}
