'use server';

import pool from '@/lib/db';

export async function checkSSINAction(ssin: string) {
  if (!ssin || ssin.length !== 12 || !/^\d+$/.test(ssin)) {
    return { error: 'Invalid SSIN format' };
  }

  const client = await pool.connect();
  try {
    // 1. Check Beneficiary (removed status column, using remark if available, else active)
    const benRes = await client.query(`
      SELECT beneficiary_name, date_of_attaining_60, approved_ssin, phone_no, remark, last_update 
      FROM beneficiaries 
      WHERE approved_ssin = $1
    `, [ssin]);

    if (benRes.rows.length === 0) {
      return { exists: false };
    }

    const row = benRes.rows[0];
    
    const formatDate = (dateObj: Date) => {
      const d = new Date(dateObj);
      return `${d.getDate().toString().padStart(2, '0')}-${(d.getMonth()+1).toString().padStart(2, '0')}-${d.getFullYear()}`;
    };

    let status = 'ACTIVE';
    if (row.remark && row.remark.toLowerCase().includes('reject')) {
      status = 'REJECTED';
    } else if (row.remark && row.remark.toLowerCase().includes('inactive')) {
      status = 'INACTIVE';
    }

    const response = {
      exists: true,
      ssin: row.approved_ssin,
      name: row.beneficiary_name,
      date_of_attaining_60: formatDate(row.date_of_attaining_60),
      phone_no: row.phone_no,
      status: status,
      last_update: row.last_update ? row.last_update.toISOString() : null,
      pf_updates: [] as any[],
      total_amount: 0
    };

    // 2. Fetch PF Updates (calculating months/amount manually since they aren't in DB)
    const pfRes = await client.query(`
      SELECT period_form, period_to, last_update
      FROM pf_update 
      WHERE approved_ssin = $1
    `, [ssin]);

    let totalAmount = 0;

    for (const pfRow of pfRes.rows) {
      if (!pfRow.period_form || !pfRow.period_to) continue;

      const from = new Date(pfRow.period_form);
      const to = new Date(pfRow.period_to);
      to.setDate(to.getDate() + 1);

      let months = (to.getFullYear() - from.getFullYear()) * 12 + (to.getMonth() - from.getMonth());
      if (to.getDate() > from.getDate()) {
        months++;
      }
      if (months <= 0) months = 1;

      const amount = months * 55;
      totalAmount += amount;

      response.pf_updates.push({
        period_from: formatDate(pfRow.period_form),
        period_to: formatDate(pfRow.period_to),
        last_update: pfRow.last_update ? pfRow.last_update.toISOString() : null,
        months: months,
        amount: amount
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
    const checkRes = await client.query('SELECT approved_ssin FROM beneficiaries WHERE approved_ssin = $1', [ssin]);
    if (checkRes.rows.length > 0) {
      return { error: 'SSIN already exists' };
    }

    const now = new Date();
    
    // Instead of inserting 'status', we insert 'remark' as active if needed, or leave null.
    await client.query(`
      INSERT INTO beneficiaries 
      (approved_ssin, beneficiary_name, date_of_attaining_60, phone_no, remark, last_update, created_at) 
      VALUES ($1, $2, $3, $4, 'ACTIVE', $5, CURRENT_DATE)
    `, [ssin, name.toUpperCase(), date_of_attaining_60, phone_no, now]);

    return { success: true };
  } catch (error: any) {
    console.error('submitSSINAction Error:', error);
    return { error: 'Failed to insert data to database' };
  } finally {
    client.release();
  }
}
