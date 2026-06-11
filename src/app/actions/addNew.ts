'use server';

import pool from '@/lib/db';

export async function checkSSINAction(ssin: string) {
  if (!ssin || ssin.length !== 12 || !/^\d+$/.test(ssin)) {
    return { error: 'Invalid SSIN format' };
  }

  const client = await pool.connect();
  try {
    // 1. Check Beneficiary
    const benRes = await client.query(`
      SELECT beneficiary_name, date_of_attaining_60, approved_ssin, phone_no, status, last_update 
      FROM beneficiaries 
      WHERE approved_ssin = $1
    `, [ssin]);

    if (benRes.rows.length === 0) {
      return { exists: false };
    }

    const row = benRes.rows[0];
    
    // Formatting date helper for Postgres DATE type
    const formatDate = (dateObj: Date) => {
      const d = new Date(dateObj);
      return `${d.getDate().toString().padStart(2, '0')}-${(d.getMonth()+1).toString().padStart(2, '0')}-${d.getFullYear()}`;
    };

    const response = {
      exists: true,
      ssin: row.approved_ssin,
      name: row.beneficiary_name,
      date_of_attaining_60: formatDate(row.date_of_attaining_60),
      phone_no: row.phone_no,
      status: row.status,
      last_update: row.last_update ? row.last_update.toISOString() : null,
      pf_updates: [] as any[],
      total_amount: 0
    };

    // 2. Fetch PF Updates
    const pfRes = await client.query(`
      SELECT period_from, period_to, last_update, amount, months 
      FROM pf_update 
      WHERE approved_ssin = $1 AND status = 'Accepted'
    `, [ssin]);

    let totalAmount = 0;

    for (const pfRow of pfRes.rows) {
      totalAmount += parseFloat(pfRow.amount);
      response.pf_updates.push({
        period_from: formatDate(pfRow.period_from),
        period_to: formatDate(pfRow.period_to),
        last_update: pfRow.last_update ? pfRow.last_update.toISOString() : null,
        months: pfRow.months,
        amount: parseFloat(pfRow.amount)
      });
    }

    response.total_amount = totalAmount;

    return response;
  } catch (error: any) {
    console.error('checkSSINAction Error:', error);
    return { error: 'Database error occurred while checking SSIN' };
  } finally {
    client.release();
  }
}

export async function submitSSINAction(formData: FormData) {
  const ssin = formData.get('ssin') as string;
  const name = formData.get('name') as string;
  const date_of_attaining_60 = formData.get('date') as string;
  const phone_no = formData.get('phone') as string;
  
  if (!ssin || !name || !date_of_attaining_60 || !phone_no) {
    return { error: 'All fields are required' };
  }

  const client = await pool.connect();
  try {
    // Check if it exists
    const checkRes = await client.query('SELECT approved_ssin FROM beneficiaries WHERE approved_ssin = $1', [ssin]);
    if (checkRes.rows.length > 0) {
      return { error: 'SSIN already exists' };
    }

    const status = 'active';
    const now = new Date();
    
    await client.query(`
      INSERT INTO beneficiaries 
      (approved_ssin, beneficiary_name, date_of_attaining_60, phone_no, status, last_update, created_at) 
      VALUES ($1, $2, $3, $4, $5, $6, CURRENT_DATE)
    `, [ssin, name.toUpperCase(), date_of_attaining_60, phone_no, status, now]);

    return { success: true };
  } catch (error: any) {
    console.error('submitSSINAction Error:', error);
    return { error: 'Failed to insert data to database' };
  } finally {
    client.release();
  }
}
