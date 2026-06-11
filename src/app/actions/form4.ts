'use server';

import pool from '@/lib/db';
import { revalidatePath } from 'next/cache';

// Initialize the table
export async function initForm4Table() {
  const client = await pool.connect();
  try {
    await client.query(`
      CREATE TABLE IF NOT EXISTS form4_entries (
          id SERIAL PRIMARY KEY,
          reg_no VARCHAR(50) NOT NULL,
          beneficiary_name VARCHAR(150) NOT NULL,
          book_no VARCHAR(50) NOT NULL,
          receipt_no VARCHAR(50) NOT NULL,
          for_month VARCHAR(100) NOT NULL,
          date_of_collection DATE NOT NULL,
          amount DECIMAL(10,2) NOT NULL,
          created_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Kolkata')
      )
    `);
  } catch (error) {
    console.error("Form 4 Table Init Error:", error);
  } finally {
    client.release();
  }
}

export async function saveForm4Entry(data: {
  reg_no: string;
  beneficiary_name: string;
  book_no: string;
  receipt_no: string;
  for_month_from: string;
  for_month_to: string;
  date_of_collection: string;
  amount: number;
}) {
  let client;
  try {
    client = await pool.connect();
    
    // Format for_month exactly as the old system: "DD-MM-YYYY - DD-MM-YYYY"
    const fromParts = data.for_month_from.split('-'); // YYYY-MM-DD
    const toParts = data.for_month_to.split('-');
    const for_month = `${fromParts[2]}-${fromParts[1]}-${fromParts[0]} - ${toParts[2]}-${toParts[1]}-${toParts[0]}`;

    await client.query(`
      INSERT INTO form4_entries 
        (reg_no, beneficiary_name, book_no, receipt_no, for_month, date_of_collection, amount, created_at)
      VALUES 
        ($1, $2, $3, $4, $5, $6, $7, CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Kolkata')
    `, [
      data.reg_no, data.beneficiary_name, data.book_no, data.receipt_no, 
      for_month, data.date_of_collection, data.amount
    ]);
    
    revalidatePath('/form4/addnew');
    return { success: true };
  } catch (error: any) {
    console.error("Save Form 4 Error:", error);
    return { error: error.message || 'Failed to save entry' };
  } finally {
    if (client) client.release();
  }
}

export async function getLatestForm4Entry() {
  let client;
  try {
    client = await pool.connect();
    const res = await client.query(`
      SELECT 
        id, reg_no, beneficiary_name, book_no, receipt_no, for_month, 
        TO_CHAR(date_of_collection, 'YYYY-MM-DD') as date_of_collection, 
        amount, created_at 
      FROM form4_entries 
      ORDER BY id DESC LIMIT 1
    `);
    return res.rows[0] || null;
  } catch (error: any) {
    console.error("Get Latest Form 4 Error:", error);
    return null;
  } finally {
    if (client) client.release();
  }
}

export async function searchForm4Entry(reg_no: string, book_no: string) {
  let client;
  try {
    client = await pool.connect();
    const res = await client.query(`
      SELECT id, beneficiary_name, amount 
      FROM form4_entries 
      WHERE reg_no = $1 AND book_no = $2 
      ORDER BY id DESC LIMIT 1
    `, [reg_no, book_no]);
    
    return res.rows[0] || null;
  } catch (error: any) {
    console.error("Search Form 4 Error:", error);
    return null;
  } finally {
    if (client) client.release();
  }
}

export async function updateForm4Entry(id: number, data: { beneficiary_name: string; amount: number }) {
  let client;
  try {
    client = await pool.connect();
    await client.query(`
      UPDATE form4_entries 
      SET beneficiary_name = $1, amount = $2 
      WHERE id = $3
    `, [data.beneficiary_name, data.amount, id]);
    
    revalidatePath('/form4/addnew');
    return { success: true };
  } catch (error: any) {
    console.error("Update Form 4 Error:", error);
    return { error: error.message || 'Failed to update entry' };
  } finally {
    if (client) client.release();
  }
}

export async function getForm4DownloadList(dateType: 'created_at' | 'date_of_collection', fromDate: string, toDate: string) {
  let client;
  try {
    client = await pool.connect();
    
    let query = `
      SELECT 
        id, reg_no, beneficiary_name, book_no, receipt_no, for_month, 
        TO_CHAR(date_of_collection, 'YYYY-MM-DD') as date_of_collection, 
        amount, 
        TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at
      FROM form4_entries
    `;

    const params: any[] = [];
    
    if (fromDate && toDate) {
      if (dateType === 'created_at') {
        query += ` WHERE created_at >= $1 AND created_at <= $2`;
        params.push(`${fromDate} 00:00:00`, `${toDate} 23:59:59`);
      } else {
        query += ` WHERE date_of_collection >= $1 AND date_of_collection <= $2`;
        params.push(fromDate, toDate);
      }
      query += ` ORDER BY ${dateType} ASC`;
    } else {
      query += ` ORDER BY id DESC LIMIT 50`;
    }

    const res = await client.query(query, params);
    return res.rows;
  } catch (error: any) {
    console.error("Form 4 Download List Error:", error);
    return [];
  } finally {
    if (client) client.release();
  }
}
