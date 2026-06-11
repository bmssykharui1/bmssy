'use server';

import pool from '@/lib/db';
import { revalidatePath } from 'next/cache';

export async function getAllBeneficiaries() {
  const client = await pool.connect();
  try {
    // Excluding rejected ones to simulate 'status = active'
    const res = await client.query(`
      SELECT id, beneficiary_name, approved_ssin, date_of_attaining_60, phone_no, last_update 
      FROM beneficiaries 
      WHERE remark IS NULL OR remark NOT ILIKE '%reject%'
      ORDER BY last_update DESC
    `);
    
    // Convert dates to string so they can be passed to Client Components
    const data = res.rows.map(row => ({
      ...row,
      date_of_attaining_60: row.date_of_attaining_60 ? new Date(row.date_of_attaining_60).toISOString().split('T')[0] : '',
      last_update: row.last_update ? new Date(row.last_update).toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' }) : ''
    }));

    return data;
  } catch (error: any) {
    console.error("Fetch Beneficiaries Error:", error);
    return [];
  } finally {
    client.release();
  }
}

export async function updateBeneficiary(id: number, data: { name: string, ssin: string, dob: string, phone: string }) {
  let client;
  try {
    client = await pool.connect();
    await client.query(`
      UPDATE beneficiaries 
      SET beneficiary_name = $1, approved_ssin = $2, date_of_attaining_60 = $3, phone_no = $4, last_update = NOW()
      WHERE id = $5
    `, [data.name, data.ssin, data.dob, data.phone, id]);

    revalidatePath('/list/alldata');
    return { success: true };
  } catch (error: any) {
    console.error("Update Beneficiary Error:", error);
    return { error: error.message || 'Failed to update beneficiary' };
  } finally {
    if (client) client.release();
  }
}

// Get PF Update History
export async function getPFUpdateList(periodFrom: string, periodTo: string, typeFilter: string) {
  let client;
  try {
    client = await pool.connect();
    
    let query = `
      SELECT 
        b.beneficiary_name, 
        b.approved_ssin, 
        TO_CHAR(b.date_of_attaining_60, 'YYYY-MM-DD') as date_of_attaining_60,
        TO_CHAR(p.period_form, 'YYYY-MM-DD') as period_form,
        TO_CHAR(p.period_to, 'YYYY-MM-DD') as period_to,
        TO_CHAR(p.last_update, 'YYYY-MM-DD') as last_update,
        p.reason
      FROM beneficiaries b 
      JOIN pf_update p ON b.id = p.beneficiary_id
      WHERE (p.reason IS NULL OR p.reason = 'Accepted')
    `;
    
    const params: any[] = [];
    let paramCount = 1;
    
    if (periodFrom) {
      query += ` AND (p.date AT TIME ZONE 'Asia/Kolkata')::date >= $${paramCount++}`;
      params.push(periodFrom);
    }
    if (periodTo) {
      query += ` AND (p.date AT TIME ZONE 'Asia/Kolkata')::date <= $${paramCount++}`;
      params.push(periodTo);
    }
    if (typeFilter) {
      query += ` AND b.approved_ssin LIKE $${paramCount++}`;
      params.push(`${typeFilter}%`);
    }

    query += ` ORDER BY p.id DESC`;

    const res = await client.query(query, params);
    return res.rows;
  } catch (error: any) {
    console.error("Fetch PF Update List Error:", error);
    return [];
  } finally {
    if (client) client.release();
  }
}

// Get New Data (Newly Added Beneficiaries)
export async function getNewDataList() {
  let client;
  try {
    client = await pool.connect();
    const res = await client.query(`
      SELECT 
        id, beneficiary_name, approved_ssin, phone_no,
        TO_CHAR(date_of_attaining_60, 'YYYY-MM-DD') as date_of_attaining_60,
        TO_CHAR(created_at, 'YYYY-MM-DD') as created_at
      FROM beneficiaries 
      WHERE remark IS NULL OR remark NOT ILIKE '%reject%'
      ORDER BY id DESC
    `);
    return res.rows;
  } catch (error: any) {
    console.error("Fetch New Data List Error:", error);
    return [];
  } finally {
    if (client) client.release();
  }
}

// Get Inactive Data (Rejected Beneficiaries)
export async function getInactiveDataList() {
  let client;
  try {
    client = await pool.connect();
    const res = await client.query(`
      SELECT 
        id, beneficiary_name, approved_ssin, phone_no, remark,
        TO_CHAR(date_of_attaining_60, 'YYYY-MM-DD') as date_of_attaining_60,
        TO_CHAR(last_update, 'YYYY-MM-DD') as last_update
      FROM beneficiaries 
      WHERE remark IS NOT NULL AND remark ILIKE '%reject%'
      ORDER BY last_update DESC
    `);
    return res.rows;
  } catch (error: any) {
    console.error("Fetch Inactive Data List Error:", error);
    return [];
  } finally {
    if (client) client.release();
  }
}
