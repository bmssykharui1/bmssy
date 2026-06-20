'use client';

import { useState, useEffect, useMemo } from 'react';
import { getPFUpdateList } from '@/app/actions/list';
import { Search, RefreshCw, ChevronLeft, ChevronRight, FileCheck, Download, Loader2 } from 'lucide-react';
import { Toast } from '@/lib/toast';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

export default function PFUpdateListPage() {
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  
  // Filters
  const [searchTerm, setSearchTerm] = useState('');
  const [periodFrom, setPeriodFrom] = useState('');
  const [periodTo, setPeriodTo] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  
  const [currentPage, setCurrentPage] = useState(1);
  const rowsPerPage = 35;

  // Set default dates on mount and load data
  useEffect(() => {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    const formatLocal = (date: Date) => {
      const y = date.getFullYear();
      const m = String(date.getMonth() + 1).padStart(2, '0');
      const d = String(date.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    };
    
    const initialFrom = formatLocal(firstDay);
    const initialTo = formatLocal(lastDay);
    
    setPeriodFrom(initialFrom);
    setPeriodTo(initialTo);
    
    loadData(initialFrom, initialTo, typeFilter);
  }, []);

  const loadData = async (from?: any, to?: any, type?: any) => {
    setLoading(true);
    const finalFrom = typeof from === 'string' ? from : periodFrom;
    const finalTo = typeof to === 'string' ? to : periodTo;
    const finalType = typeof type === 'string' ? type : typeFilter;
    
    const result = await getPFUpdateList(finalFrom, finalTo, finalType);
    setData(result);
    setLoading(false);
    setCurrentPage(1);
  };

  const filteredData = useMemo(() => {
    return data.filter(item => {
      const s = searchTerm.toLowerCase();
      const matchSearch = (item.beneficiary_name?.toLowerCase().includes(s)) ||
             (item.approved_ssin?.includes(s));
      return matchSearch;
    });
  }, [data, searchTerm]);

  const totalPages = Math.ceil(filteredData.length / rowsPerPage);
  const currentData = filteredData.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);

  const generatePDF = () => {
    if (filteredData.length === 0) {
      Toast.fire({ title: 'No Data to generate PDF', icon: 'warning' });
      return;
    }

    const doc = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });
    const pageWidth = doc.internal.pageSize.getWidth();

    const headers = [
      [
        { content: "SL NO", rowSpan: 2, styles: { halign: 'center', valign: 'middle' } },
        { content: "Name of the beneficiary", rowSpan: 2, styles: { halign: 'center', valign: 'middle' } },
        { content: "Date of attaining 60 years of age", rowSpan: 2, styles: { halign: 'center', valign: 'middle' } },
        { content: "Approved SSIN", rowSpan: 2, styles: { halign: 'center', valign: 'middle' } },
        { content: "Month of the Government Grant", colSpan: 2, styles: { halign: 'center', valign: 'middle' } },
        { content: "Date of entry in the Pass Book", rowSpan: 2, styles: { halign: 'center', valign: 'middle' } }
      ],
      [
        { content: "PERIOD FROM", styles: { halign: 'center', valign: 'middle' } },
        { content: "PERIOD TO", styles: { halign: 'center', valign: 'middle' } }
      ]
    ];

    const bodyData = filteredData.map((row, idx) => [
      (idx + 1).toString(),
      row.beneficiary_name,
      row.date_of_attaining_60,
      row.approved_ssin,
      row.period_form,
      row.period_to,
      row.last_update
    ]);

    let minYear = 9999;
    let maxYear = 0;
    filteredData.forEach(row => {
      if (row.period_form) {
        const y = parseInt(row.period_form.split('-')[0]);
        if (y < minYear) minYear = y;
      }
      if (row.period_to) {
        const y = parseInt(row.period_to.split('-')[0]);
        if (y > maxYear) maxYear = y;
      }
    });

    const yStr = (minYear !== 9999) ? `${minYear} - ${maxYear}` : '____';
    const title1 = "Statement of recording Government Grant (Construction/ Transport/ Others)";
    const title2 = `Code of LWFC: 4207112, Quarter: __________, Year: ${yStr}.`;
    
    autoTable(doc, {
      startY: 28,
      head: headers,
      body: bodyData,
      theme: 'grid',
      styles: { font: "helvetica", fontSize: 7.5, fontStyle: 'bold', cellPadding: 1.5, lineWidth: 0.3, lineColor: [0, 0, 0] },
      headStyles: { font: "helvetica", fontSize: 7.5, fontStyle: 'bold', halign: 'center', valign: 'middle', lineWidth: 0.5, lineColor: [0, 0, 0], fillColor: [204, 153, 255], textColor: [0, 0, 0] },
      bodyStyles: { halign: 'center', valign: 'middle', fontStyle: 'bold', lineWidth: 0.3, lineColor: [0, 0, 0], textColor: [0, 0, 0] },
      margin: { top: 10, bottom: 25, left: 5, right: 5 },
      tableWidth: 'auto',
      didDrawPage: function (data: any) {
        const pageNumber = doc.internal.getNumberOfPages();
        if (pageNumber === 1) {
          doc.setFont("helvetica", "bold");
          doc.setFontSize(11);
          doc.text(title1, pageWidth / 2, 14, { align: "center" });
          doc.text(title2, pageWidth / 2, 20, { align: "center" });
        }

        const totalPages = doc.internal.getNumberOfPages();
        if (pageNumber === totalPages) {
          const yAfterTable = data.cursor.y + 6;
          doc.setFont("helvetica", "normal");
          doc.setFontSize(8);
          const footerText = "*Strike out whichever is not applicable. Certified that I have made all the relevant entries up to the quarter ending on___________________In the Passbook of the beneficiaries and all the data recorded in the above statement is in consonance with the entries made by me.";
          const wrappedFooter = doc.splitTextToSize(footerText, pageWidth - 10);
          doc.text(wrappedFooter, 5, yAfterTable);

          const footerHeight = wrappedFooter.length * 3.5;
          const signatureX = pageWidth - 10;
          doc.text("_________________________\nSignature of the CA/SLO\nName: MAMATA JANA\nCode No.: 4207112", signatureX, yAfterTable + footerHeight + 4, { align: "right" });
        }
      }
    });

    doc.save(`PF_Update_Report_${new Date().toISOString().split('T')[0]}.pdf`);
    Toast.fire({ title: 'PDF Downloaded!', icon: 'success' });
  };

  return (
    <>
      <header className="app-topbar" style={{ background: 'var(--surface)', backdropFilter: 'blur(10px)', borderBottom: '1px solid rgba(0,0,0,0.05)' }}>
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '18px' }}>
            <FileCheck size={22} style={{ color: 'var(--primary)' }} />
            PF Update List
          </h1>
        </div>
      </header>

      <div className="content-scroll" style={{ padding: '20px' }}>
        <div className="md-card" style={{ padding: '0', overflow: 'hidden', border: '1px solid var(--border)', background: 'var(--surface)', borderRadius: '16px' }}>
          
          <div style={{ padding: '16px 20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px solid var(--border)', flexWrap: 'wrap', gap: '16px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px', flexWrap: 'wrap' }}>
              <div style={{ position: 'relative' }}>
                <Search size={16} style={{ position: 'absolute', left: '14px', top: '50%', transform: 'translateY(-50%)', color: 'var(--primary)' }} />
                <input 
                  type="text" 
                  placeholder="Search Name or SSIN..." 
                  value={searchTerm}
                  onChange={(e) => { setSearchTerm(e.target.value); setCurrentPage(1); }}
                  className="app-input"
                  style={{ paddingLeft: '40px', width: '220px', height: '38px', borderRadius: '100px', background: 'var(--background)', color: 'var(--text-main)', border: '1px solid var(--border)', fontSize: '13px' }}
                />
              </div>
              
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', background: 'var(--background)', padding: '4px 12px', borderRadius: '100px', border: '1px solid var(--border)' }}>
                <span style={{ fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)' }}>From:</span>
                <input 
                  type="date" 
                  value={periodFrom}
                  onChange={(e) => { setPeriodFrom(e.target.value); setCurrentPage(1); }}
                  style={{ border: 'none', background: 'transparent', outline: 'none', fontSize: '13px', color: 'var(--text-main)', padding: '4px' }}
                />
                <span style={{ fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', marginLeft: '4px' }}>To:</span>
                <input 
                  type="date" 
                  value={periodTo}
                  onChange={(e) => { setPeriodTo(e.target.value); setCurrentPage(1); }}
                  style={{ border: 'none', background: 'transparent', outline: 'none', fontSize: '13px', color: 'var(--text-main)', padding: '4px' }}
                />
              </div>

              <select 
                value={typeFilter} 
                onChange={(e) => { setTypeFilter(e.target.value); setCurrentPage(1); }}
                style={{ height: '38px', borderRadius: '100px', padding: '0 16px', border: '1px solid var(--border)', background: 'var(--background)', color: 'var(--text-main)', fontSize: '13px', outline: 'none' }}
              >
                <option value="">All Types</option>
                <option value="142">Others (142)</option>
                <option value="242">Construction (242)</option>
              </select>
            </div>
            
            <div style={{ display: 'flex', gap: '10px' }}>
              <button 
                onClick={generatePDF} 
                style={{ borderRadius: '100px', padding: '8px 16px', background: 'rgba(211, 47, 47, 0.1)', color: '#d32f2f', border: 'none', cursor: 'pointer', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px', transition: '0.2s' }}
                onMouseOver={(e) => { e.currentTarget.style.background = '#d32f2f'; e.currentTarget.style.color = '#fff'; }}
                onMouseOut={(e) => { e.currentTarget.style.background = 'rgba(211, 47, 47, 0.1)'; e.currentTarget.style.color = '#d32f2f'; }}
              >
                <Download size={14} />
                Download PDF
              </button>

              <button 
                onClick={() => loadData()} 
                disabled={loading}
                style={{ borderRadius: '100px', padding: '8px 16px', background: 'var(--primary-container)', color: 'var(--primary)', border: 'none', cursor: 'pointer', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px' }}
              >
                <RefreshCw size={14} className={loading ? 'spinner' : ''} />
                Search
              </button>
            </div>
          </div>

          <div style={{ overflowX: 'auto', minHeight: '400px' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '13px' }}>
              <thead style={{ background: 'var(--background)', color: 'var(--text-muted)', textTransform: 'uppercase', fontSize: '11px', letterSpacing: '0.5px' }}>
                <tr>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Name</th>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>SSIN</th>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Date of 60</th>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Period From</th>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Period To</th>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Update Date</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan={6} style={{ textAlign: 'center', padding: '80px 20px', color: 'var(--text-muted)' }}>
                      <Loader2 size={32} className="spinner" style={{ margin: '0 auto 16px auto', color: 'var(--primary)' }} />
                      <div style={{ fontSize: '14px', fontWeight: 500 }}>Loading Records...</div>
                    </td>
                  </tr>
                ) : currentData.length === 0 ? (
                  <tr>
                    <td colSpan={6} style={{ textAlign: 'center', padding: '80px 20px', color: 'var(--text-muted)' }}>
                      <div style={{ fontSize: '14px', fontWeight: 500 }}>No records found matching criteria.</div>
                    </td>
                  </tr>
                ) : (
                  currentData.map((row, idx) => (
                    <tr key={idx} style={{ borderBottom: '1px solid var(--border)' }}>
                      <td style={{ padding: '12px 20px', fontWeight: 600, color: 'var(--text-main)' }}>{row.beneficiary_name}</td>
                      <td style={{ padding: '12px 20px', fontFamily: 'monospace', color: 'var(--primary)', fontWeight: 600 }}>{row.approved_ssin}</td>
                      <td style={{ padding: '12px 20px', color: 'var(--text-main)' }}>{row.date_of_attaining_60}</td>
                      <td style={{ padding: '12px 20px', color: 'var(--success)', fontWeight: 600 }}>{row.period_form}</td>
                      <td style={{ padding: '12px 20px', color: 'var(--error)', fontWeight: 600 }}>{row.period_to}</td>
                      <td style={{ padding: '12px 20px', color: 'var(--text-muted)' }}>{row.last_update}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {!loading && filteredData.length > 0 && (
            <div style={{ padding: '12px 20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: 'var(--background)', borderTop: '1px solid var(--border)' }}>
              <div style={{ fontSize: '12px', color: 'var(--text-muted)' }}>
                Showing <strong style={{ color: 'var(--text-main)' }}>{((currentPage - 1) * rowsPerPage) + 1}</strong> to <strong style={{ color: 'var(--text-main)' }}>{Math.min(currentPage * rowsPerPage, filteredData.length)}</strong> of <strong style={{ color: 'var(--text-main)' }}>{filteredData.length}</strong> records
              </div>
              <div style={{ display: 'flex', gap: '6px' }}>
                <button disabled={currentPage === 1} onClick={() => setCurrentPage(p => p - 1)} style={{ padding: '6px 12px', border: '1px solid var(--border)', background: 'var(--surface)', borderRadius: '6px', cursor: currentPage === 1 ? 'not-allowed' : 'pointer', opacity: currentPage === 1 ? 0.5 : 1 }}><ChevronLeft size={16} color="var(--text-main)" /></button>
                <button disabled={currentPage === totalPages} onClick={() => setCurrentPage(p => p + 1)} style={{ padding: '6px 12px', border: '1px solid var(--border)', background: 'var(--surface)', borderRadius: '6px', cursor: currentPage === totalPages ? 'not-allowed' : 'pointer', opacity: currentPage === totalPages ? 0.5 : 1 }}><ChevronRight size={16} color="var(--text-main)" /></button>
              </div>
            </div>
          )}
          
        </div>
        <div style={{ height: '40px' }}></div>
      </div>
    </>
  );
}
