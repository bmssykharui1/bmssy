'use client';

import { useState, useEffect } from 'react';
import { getForm4DownloadList } from '@/app/actions/form4';
import { Download, Search, FileCheck, Loader2 } from 'lucide-react';
import { Toast } from '@/lib/toast';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

export default function Form4DownloadPage() {
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  
  const [dateType, setDateType] = useState<'created_at' | 'date_of_collection'>('created_at');
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');

  const loadData = async () => {
    if (!fromDate || !toDate) {
      Toast.fire({ title: 'Select both dates', icon: 'warning' });
      return;
    }
    setLoading(true);
    const result = await getForm4DownloadList(dateType, fromDate, toDate);
    setData(result);
    setLoading(false);
  };

  const formatPeriodString = (dbString: string) => {
    if (!dbString) return '';
    if (dbString.includes(' - ')) {
      const parts = dbString.split(' - ');
      return parts[0].replace(/-/g, '/') + ' - ' + parts[1].replace(/-/g, '/');
    }
    return dbString.replace(/-/g, '/');
  };

  const formatToDDMMYYYY = (dateStr: string) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${day}/${month}/${d.getFullYear()}`;
  };

  const generatePDF = () => {
    if (data.length === 0) {
      Toast.fire({ title: 'No Data to generate PDF', icon: 'warning' });
      return;
    }

    const doc = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });
    const pageWidth = doc.internal.pageSize.getWidth();

    const w = [8, 25, 43, 14, 14, 38, 26, 22]; // Total = 190

    const headers = [[
      { content: "SL NO", styles: { halign: 'center', cellWidth: w[0] } },
      { content: "Reg. No", styles: { halign: 'center', cellWidth: w[1] } },
      { content: "Name of Benificiary", styles: { halign: 'center', cellWidth: w[2] } },
      { content: "Book No", styles: { halign: 'center', cellWidth: w[3] } },
      { content: "Receipt", styles: { halign: 'center', cellWidth: w[4] } },
      { content: "For the Month Of", styles: { halign: 'center', cellWidth: w[5] } },
      { content: "Date of Collection", styles: { halign: 'center', cellWidth: w[6] } },
      { content: "Amount", styles: { halign: 'center', cellWidth: w[7] } }
    ]];

    const bodyData = data.map((row, idx) => [
      (idx + 1).toString(),
      row.reg_no,
      row.beneficiary_name,
      row.book_no,
      row.receipt_no,
      formatPeriodString(row.for_month),
      formatToDDMMYYYY(row.date_of_collection),
      parseFloat(row.amount).toFixed(2)
    ]);

    const title1 = "West Bengal Building & Others Construction Worker's Welfare Fund";
    const title2 = "Registration/Subscription";
    const title3 = "Particulars of Benificiary Worker's";
    
    // Calculate Grand Total
    const grandTotal = data.reduce((sum, row) => sum + parseFloat(row.amount), 0);

    let pageSubTotal = 0;

    autoTable(doc, {
      startY: 35,
      head: headers as any,
      body: bodyData,
      theme: 'grid',
      styles: { font: "helvetica", fontSize: 7.5, cellPadding: 2, lineWidth: 0.2, lineColor: [0, 0, 0] },
      headStyles: { font: "helvetica", fontSize: 7.5, fontStyle: 'bold', halign: 'center', valign: 'middle', fillColor: [230, 230, 230], textColor: [0, 0, 0] },
      bodyStyles: { textColor: [0, 0, 0] },
      columnStyles: {
        0: { halign: 'center' },
        1: { halign: 'center' },
        3: { halign: 'center' },
        4: { halign: 'center' },
        5: { halign: 'center' },
        6: { halign: 'center' },
        7: { halign: 'right' }
      },
      margin: { top: 35, bottom: 20, left: 10, right: 10 },
      tableWidth: 190,
      
      willDrawCell: function(data) {
        // Accumulate subtotal right before drawing the cell on the current page
        if (data.section === 'body' && data.column.index === 7) {
          pageSubTotal += parseFloat(data.cell.raw || "0");
        }
      },

      didDrawPage: function (hookData) {
        const pageNumber = doc.internal.getNumberOfPages();
        
        // Headers
        doc.setFont("helvetica", "bold");
        doc.setFontSize(14);
        doc.text(title1, pageWidth / 2, 15, { align: "center" });
        
        doc.setFont("helvetica", "normal");
        doc.setFontSize(12);
        doc.text(title2, pageWidth / 2, 22, { align: "center" });
        
        doc.setFontSize(11);
        doc.text(title3, pageWidth / 2, 28, { align: "center" });

        // Draw subtotal at the bottom of the table on every page
        const yPos = hookData.cursor.y;
        
        doc.setDrawColor(0, 0, 0); // Black border
        doc.setTextColor(0, 0, 0); // Black text
        
        doc.rect(10, yPos, 168, 8, 'S'); // S = Stroke only (transparent background)
        doc.setFont("helvetica", "bold");
        doc.setFontSize(8);
        doc.text("TOTAL:", 176, yPos + 5.5, { align: 'right' });
        
        doc.rect(178, yPos, 22, 8, 'S'); // S = Stroke only
        doc.text(pageSubTotal.toFixed(2), 198, yPos + 5.5, { align: 'right' });

        // Reset subtotal for the next page
        pageSubTotal = 0;
      }
    });

    doc.save(`Form4_Export_${new Date().toISOString().replace(/[:.]/g, '')}.pdf`);
    Toast.fire({ title: 'PDF Downloaded!', icon: 'success' });
  };

  return (
    <>
      <header className="app-topbar" style={{ background: 'var(--surface)', backdropFilter: 'blur(10px)', borderBottom: '1px solid rgba(0,0,0,0.05)' }}>
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '18px' }}>
            <span style={{ background: 'rgba(13, 148, 136, 0.1)', color: '#0d9488', padding: '6px 12px', borderRadius: '8px', fontWeight: 700 }}>Form 4</span>
            Download PDF
          </h1>
        </div>
      </header>

      <div className="content-scroll" style={{ padding: '20px' }}>
        <div style={{ maxWidth: '1200px', margin: '0 auto', width: '100%' }}>
          
          <div className="md-card" style={{ padding: '24px', background: 'var(--surface)', borderRadius: '16px', boxShadow: '0 4px 12px rgba(0,0,0,0.03)' }}>
            
            {/* Filter Section */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '16px', alignItems: 'end', marginBottom: '24px' }}>
              
              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>Filter By</label>
                <div style={{ display: 'flex', background: 'var(--background)', padding: '4px', borderRadius: '12px', gap: '4px' }}>
                  <button 
                    onClick={() => setDateType('date_of_collection')}
                    style={{ flex: 1, padding: '10px', borderRadius: '8px', border: 'none', background: dateType === 'date_of_collection' ? 'var(--surface)' : 'transparent', color: dateType === 'date_of_collection' ? '#0d9488' : 'var(--text-muted)', fontWeight: 600, fontSize: '13px', cursor: 'pointer', boxShadow: dateType === 'date_of_collection' ? '0 2px 6px rgba(0,0,0,0.05)' : 'none', transition: '0.2s' }}
                  >
                    Date of Coll.
                  </button>
                  <button 
                    onClick={() => setDateType('created_at')}
                    style={{ flex: 1, padding: '10px', borderRadius: '8px', border: 'none', background: dateType === 'created_at' ? 'var(--surface)' : 'transparent', color: dateType === 'created_at' ? '#0d9488' : 'var(--text-muted)', fontWeight: 600, fontSize: '13px', cursor: 'pointer', boxShadow: dateType === 'created_at' ? '0 2px 6px rgba(0,0,0,0.05)' : 'none', transition: '0.2s' }}
                  >
                    Create Date
                  </button>
                </div>
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>From Date</label>
                <input type="date" value={fromDate} onChange={(e) => setFromDate(e.target.value)} style={{ padding: '14px 16px', background: 'var(--background)', border: '2px solid transparent', borderRadius: '12px', outline: 'none', color: 'var(--text-main)', fontSize: '14px' }} />
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>To Date</label>
                <input type="date" value={toDate} onChange={(e) => setToDate(e.target.value)} style={{ padding: '14px 16px', background: 'var(--background)', border: '2px solid transparent', borderRadius: '12px', outline: 'none', color: 'var(--text-main)', fontSize: '14px' }} />
              </div>

              <button onClick={loadData} disabled={loading} style={{ background: '#0d9488', color: '#fff', border: 'none', borderRadius: '100px', padding: '14px 20px', fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', minHeight: '48px', boxShadow: '0 4px 12px rgba(13, 148, 136, 0.2)' }}>
                {loading ? <Loader2 size={18} className="spinner" /> : <Search size={18} />} Search Preview
              </button>

              <button onClick={generatePDF} style={{ background: '#b3261e', color: '#fff', border: 'none', borderRadius: '100px', padding: '14px 20px', fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', minHeight: '48px', boxShadow: '0 4px 12px rgba(179, 38, 30, 0.2)' }}>
                <Download size={18} /> Generate PDF
              </button>

            </div>

            {/* Table */}
            <div style={{ width: '100%', overflowX: 'auto', border: '1px solid var(--border)', borderRadius: '12px' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                <thead style={{ background: 'var(--background)' }}>
                  <tr>
                    <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', textTransform: 'uppercase', borderBottom: '1px solid var(--border)' }}>SL</th>
                    <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', textTransform: 'uppercase', borderBottom: '1px solid var(--border)' }}>Reg. No</th>
                    <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', textTransform: 'uppercase', borderBottom: '1px solid var(--border)' }}>Beneficiary Name</th>
                    <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', textTransform: 'uppercase', borderBottom: '1px solid var(--border)' }}>Book No</th>
                    <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', textTransform: 'uppercase', borderBottom: '1px solid var(--border)' }}>Receipt No</th>
                    <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', textTransform: 'uppercase', borderBottom: '1px solid var(--border)' }}>For the Period</th>
                    <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', textTransform: 'uppercase', borderBottom: '1px solid var(--border)' }}>Date of Coll.</th>
                    <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', textTransform: 'uppercase', borderBottom: '1px solid var(--border)', textAlign: 'right' }}>Amount (₹)</th>
                  </tr>
                </thead>
                <tbody>
                  {data.length === 0 ? (
                    <tr>
                      <td colSpan={8} style={{ textAlign: 'center', padding: '40px', color: 'var(--text-muted)', fontSize: '14px' }}>
                        {loading ? 'Loading...' : 'Select dates and click Search Preview'}
                      </td>
                    </tr>
                  ) : (
                    data.map((row, index) => (
                      <tr key={index} style={{ borderBottom: '1px solid var(--border)' }}>
                        <td style={{ padding: '12px 16px', fontSize: '14px', color: 'var(--text-main)' }}>{index + 1}</td>
                        <td style={{ padding: '12px 16px', fontSize: '14px', color: '#0d9488', fontFamily: 'monospace', fontWeight: 600 }}>{row.reg_no}</td>
                        <td style={{ padding: '12px 16px', fontSize: '14px', color: 'var(--text-main)', fontWeight: 600 }}>{row.beneficiary_name}</td>
                        <td style={{ padding: '12px 16px', fontSize: '14px', color: 'var(--text-main)' }}>{row.book_no}</td>
                        <td style={{ padding: '12px 16px', fontSize: '14px', color: 'var(--text-main)' }}>{row.receipt_no}</td>
                        <td style={{ padding: '12px 16px', fontSize: '14px', color: 'var(--text-muted)' }}>{formatPeriodString(row.for_month)}</td>
                        <td style={{ padding: '12px 16px', fontSize: '14px', color: 'var(--text-muted)' }}>{formatToDDMMYYYY(row.date_of_collection)}</td>
                        <td style={{ padding: '12px 16px', fontSize: '14px', color: 'var(--success)', fontWeight: 700, textAlign: 'right' }}>{parseFloat(row.amount).toFixed(2)}</td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
            
          </div>
        </div>
        <div style={{ height: '40px' }}></div>
      </div>

      <style dangerouslySetInnerHTML={{__html: `
        .spinner { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
      `}} />
    </>
  );
}
