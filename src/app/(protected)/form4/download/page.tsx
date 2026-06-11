'use client';

import { useState } from 'react';
import { fetchForm4Data } from '@/app/actions/form4';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { FileDown, Calendar, Filter } from 'lucide-react';

const MySwal = withReactContent(Swal);

export default function Form4DownloadPage() {
  const [dateType, setDateType] = useState('date_of_collection');
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');
  const [isGenerating, setIsGenerating] = useState(false);

  const Toast = MySwal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
  });

  const generatePDF = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!fromDate || !toDate) {
      Toast.fire({ icon: 'error', title: 'Please select both dates' });
      return;
    }

    setIsGenerating(true);

    MySwal.fire({
      title: 'Generating PDF...',
      text: 'Fetching data and building the document.',
      allowOutsideClick: false,
      didOpen: () => {
        MySwal.showLoading();
      }
    });

    const result = await fetchForm4Data(dateType, fromDate, toDate);

    if (result.error) {
      MySwal.close();
      setIsGenerating(false);
      Toast.fire({ icon: 'error', title: result.error });
      return;
    }

    const data = result.data || [];

    if (data.length === 0) {
      MySwal.close();
      setIsGenerating(false);
      MySwal.fire({
        icon: 'info',
        title: 'No Data Found',
        text: 'There are no records for the selected date range.',
        confirmButtonColor: 'var(--primary)'
      });
      return;
    }

    // Initialize jsPDF (Portrait, mm, A4)
    const doc = new jsPDF('p', 'mm', 'a4');
    
    // Page Settings
    const pageWidth = doc.internal.pageSize.getWidth();
    const margin = 10;
    
    // Helper to format "For Month" 
    const formatForMonth = (forMonth: string) => {
      const parts = forMonth.split(' - ');
      if (parts.length === 2) {
        const d1 = new Date(parts[0]);
        const d2 = new Date(parts[1]);
        if (!isNaN(d1.getTime()) && !isNaN(d2.getTime())) {
          return `${d1.getDate().toString().padStart(2, '0')}/${(d1.getMonth()+1).toString().padStart(2, '0')}/${d1.getFullYear()} - ${d2.getDate().toString().padStart(2, '0')}/${(d2.getMonth()+1).toString().padStart(2, '0')}/${d2.getFullYear()}`;
        }
      }
      return forMonth;
    };

    // Prepare Table Body and Calculate Totals
    let grandTotal = 0;
    const tableBody = data.map((row: any, index: number) => {
      grandTotal += row.amount_numeric;
      return [
        (index + 1).toString(),
        row.reg_no || '',
        row.beneficiary_name || '',
        row.book_no || '',
        row.receipt_no || '',
        formatForMonth(row.for_month),
        row.date_of_collection_formatted,
        row.amount_numeric.toFixed(2)
      ];
    });

    // Add Total Row at the bottom
    tableBody.push([
      { content: 'TOTAL:', colSpan: 7, styles: { halign: 'right', fontStyle: 'bold', fillColor: [245, 245, 245] } },
      { content: grandTotal.toFixed(2), styles: { halign: 'right', fontStyle: 'bold', fillColor: [245, 245, 245], textColor: [20, 108, 46] } }
    ]);

    // Build the PDF Using AutoTable
    autoTable(doc, {
      head: [['SL NO', 'Reg. No', 'Name of Benificiary', 'Book No', 'Receipt', 'For the Month Of', 'Date of Collection', 'Amount']],
      body: tableBody,
      startY: 35,
      margin: { left: margin, right: margin },
      theme: 'grid',
      headStyles: {
        fillColor: [230, 230, 230],
        textColor: [0, 0, 0],
        fontStyle: 'bold',
        fontSize: 8,
        halign: 'center',
        valign: 'middle'
      },
      bodyStyles: {
        fontSize: 8,
        valign: 'middle'
      },
      columnStyles: {
        0: { halign: 'center', cellWidth: 10 },
        1: { halign: 'center', cellWidth: 24 },
        2: { halign: 'left', cellWidth: 46 }, // Name
        3: { halign: 'center', cellWidth: 16 },
        4: { halign: 'center', cellWidth: 16 },
        5: { halign: 'center', cellWidth: 32 },
        6: { halign: 'center', cellWidth: 26 },
        7: { halign: 'right', cellWidth: 20 },
      },
      didDrawPage: function (dataHook) {
        // Draw Header on every page
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text("West Bengal Building & Others Construction Worker's Welfare Fund", pageWidth / 2, 15, { align: 'center' });
        
        doc.setFontSize(12);
        doc.setFont('helvetica', 'normal');
        doc.text("Registration/Subscription", pageWidth / 2, 22, { align: 'center' });
        
        doc.setFontSize(11);
        doc.text("Particulars of Benificiary Worker's", pageWidth / 2, 29, { align: 'center' });
      }
    });

    // Save PDF
    const filename = `Form4_Export_${new Date().toISOString().replace(/[:.-]/g, '_')}.pdf`;
    doc.save(filename);

    MySwal.close();
    setIsGenerating(false);
    Toast.fire({ icon: 'success', title: 'PDF Downloaded!' });
  };

  return (
    <>
      <div className="header">
        <div>
          <h1 className="page-title" style={{ fontSize: '1.5rem', fontWeight: 700, display: 'flex', alignItems: 'center', gap: '10px' }}>
            <FileDown size={28} style={{ color: 'var(--primary)' }} />
            Download Form 4 PDF
          </h1>
        </div>
      </div>

      <div className="content-scroll">
        <div style={{ maxWidth: '600px', margin: '0 auto', width: '100%' }}>
          
          <div className="md-card">
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '24px', fontSize: '20px', fontWeight: 700, color: 'var(--primary)' }}>
              <Filter size={28} />
              FILTER DATA
            </div>

            <form onSubmit={generatePDF}>
              <div className="form-group" style={{ marginBottom: '20px' }}>
                <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }}>Select Date Type</label>
                <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                  <select 
                    value={dateType} 
                    onChange={(e) => setDateType(e.target.value)}
                    className="app-input"
                    style={{ paddingLeft: '16px', width: '100%', appearance: 'none' }}
                  >
                    <option value="date_of_collection">Date of Collection</option>
                    <option value="created_at">Date of Entry (Created At)</option>
                  </select>
                </div>
              </div>

              <div className="form-group" style={{ marginBottom: '20px' }}>
                <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }}>From Date</label>
                <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                  <Calendar size={20} style={{ position: 'absolute', left: '16px', color: 'var(--text-muted)' }} />
                  <input 
                    type="date" 
                    required
                    value={fromDate}
                    onChange={(e) => setFromDate(e.target.value)}
                    className="app-input"
                    style={{ paddingLeft: '48px', width: '100%' }}
                  />
                </div>
              </div>

              <div className="form-group" style={{ marginBottom: '24px' }}>
                <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }}>To Date</label>
                <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                  <Calendar size={20} style={{ position: 'absolute', left: '16px', color: 'var(--text-muted)' }} />
                  <input 
                    type="date" 
                    required
                    value={toDate}
                    onChange={(e) => setToDate(e.target.value)}
                    className="app-input"
                    style={{ paddingLeft: '48px', width: '100%' }}
                  />
                </div>
              </div>

              <button type="submit" disabled={isGenerating} className="btn-primary" style={{ width: '100%', padding: '16px' }}>
                <FileDown size={20} />
                Generate & Download PDF
              </button>
            </form>
          </div>

        </div>
      </div>
    </>
  );
}
