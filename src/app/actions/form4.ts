'use server';

import pool from '@/lib/db';

export async function fetchForm4Data(dateType: string, fromDate: string, toDate: string) {
  if (!fromDate || !toDate) {
    return { error: 'Both From and To dates are required' };
  }

  const validDateType = dateType === 'created_at' ? 'created_at' : 'date_of_collection';
  
  // If created_at, adjust toDate to include the full day
  const queryToDate = validDateType === 'created_at' ? `${toDate} 23:59:59` : toDate;

  const client = await pool.connect();
  try {
    const res = await client.query(`
      SELECT * FROM form4_entries 
      WHERE ${validDateType} >= $1 AND ${validDateType} <= $2 
      ORDER BY ${validDateType} ASC
    `, [fromDate, queryToDate]);

    // Format dates for the frontend so we don't have to deal with timezone mismatches
    const formattedData = res.rows.map(row => {
      const collDate = new Date(row.date_of_collection);
      return {
        ...row,
        date_of_collection_formatted: `${collDate.getDate().toString().padStart(2, '0')}/${(collDate.getMonth()+1).toString().padStart(2, '0')}/${collDate.getFullYear()}`,
        // For month parsing if it's "YYYY-MM-DD - YYYY-MM-DD"
        amount_numeric: parseFloat(row.amount)
      };
    });

    return { success: true, data: formattedData };
  } catch (error: any) {
    console.error('fetchForm4Data Error:', error);
    return { error: 'Failed to fetch PDF data from database' };
  } finally {
    client.release();
  }
}
