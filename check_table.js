const { Pool } = require('pg');
require('dotenv').config({ path: '.env.local' });

const pool = new Pool({
  connectionString: process.env.DATABASE_URL
});

async function run() {
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
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log("Table form4_entries ensured.");
  } catch (err) {
    console.error(err);
  } finally {
    client.release();
    pool.end();
  }
}

run();
