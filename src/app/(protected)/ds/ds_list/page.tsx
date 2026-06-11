'use client';

import { useState, useEffect, useMemo } from 'react';
import { getDSList } from '@/app/actions/ds';
import { Search, RefreshCw, ChevronLeft, ChevronRight, List, Loader2, Download } from 'lucide-react';
import { Toast } from '@/lib/toast';

export default function DSListPage() {
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  
  const [searchTerm, setSearchTerm] = useState('');
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');
  
  const [currentPage, setCurrentPage] = useState(1);
  const rowsPerPage = 20;

  const loadData = async () => {
    setLoading(true);
    const result = await getDSList();
    setData(result);
    setLoading(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  const filteredData = useMemo(() => {
    return data.filter(item => {
      const s = searchTerm.toLowerCase();
      const matchSearch = (item.name && item.name.toLowerCase().includes(s)) ||
             (item.ssin && item.ssin.includes(s)) ||
             (item.dsno && item.dsno.includes(s));
      
      let matchDate = true;
      if (fromDate) matchDate = matchDate && (item.created_date >= fromDate);
      if (toDate) matchDate = matchDate && (item.created_date <= toDate);

      return matchSearch && matchDate;
    });
  }, [data, searchTerm, fromDate, toDate]);

  const totalPages = Math.ceil(filteredData.length / rowsPerPage);
  const currentData = filteredData.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);

  const handleCopy = (text: string, label: string) => {
    navigator.clipboard.writeText(text);
    Toast.fire({ title: `${label} Copied!`, icon: 'success' });
  };

  const exportToCSV = () => {
    if (filteredData.length === 0) {
      Toast.fire({ title: 'No records to export', icon: 'warning' });
      return;
    }
    const headers = ['SSIN', 'Name', 'DS Number', 'Date'];
    const csvContent = [
      headers.join(','),
      ...filteredData.map(row => `${row.ssin},"${row.name}",${row.dsno},${row.created_date}`)
    ].join('\\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `DuareSorkar_List_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    Toast.fire({ title: 'Exported Successfully', icon: 'success' });
  };

  return (
    <>
      <header className="app-topbar" style={{ background: 'var(--surface)', backdropFilter: 'blur(10px)', borderBottom: '1px solid rgba(0,0,0,0.05)' }}>
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '18px' }}>
            <List size={22} style={{ color: 'var(--primary)' }} />
            Duare Sorkar <span style={{ color: 'var(--text-muted)', fontWeight: 400 }}>/ List</span>
          </h1>
        </div>
      </header>

      <div className="content-scroll" style={{ padding: '20px' }}>
        <div style={{ maxWidth: '1400px', margin: '0 auto', width: '100%' }}>
          
          <div className="md-card" style={{ padding: '0', overflow: 'hidden', border: '1px solid var(--border)', background: 'var(--surface)', borderRadius: '16px' }}>
            
            <div style={{ padding: '16px 20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px solid var(--border)', flexWrap: 'wrap', gap: '16px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px', flexWrap: 'wrap' }}>
                <div style={{ position: 'relative' }}>
                  <Search size={16} style={{ position: 'absolute', left: '14px', top: '50%', transform: 'translateY(-50%)', color: 'var(--primary)' }} />
                  <input 
                    type="text" 
                    placeholder="Search Name, SSIN, DS NO..." 
                    value={searchTerm}
                    onChange={(e) => { setSearchTerm(e.target.value); setCurrentPage(1); }}
                    className="app-input"
                    style={{ paddingLeft: '40px', width: '250px', height: '38px', borderRadius: '100px', background: 'var(--background)', color: 'var(--text-main)', border: '1px solid var(--border)', fontSize: '13px' }}
                  />
                </div>
                
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', background: 'var(--background)', padding: '4px 12px', borderRadius: '100px', border: '1px solid var(--border)' }}>
                  <span style={{ fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)' }}>From:</span>
                  <input 
                    type="date" 
                    value={fromDate}
                    onChange={(e) => { setFromDate(e.target.value); setCurrentPage(1); }}
                    style={{ border: 'none', background: 'transparent', outline: 'none', fontSize: '13px', color: 'var(--text-main)', padding: '4px' }}
                  />
                  <span style={{ fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', marginLeft: '4px' }}>To:</span>
                  <input 
                    type="date" 
                    value={toDate}
                    onChange={(e) => { setToDate(e.target.value); setCurrentPage(1); }}
                    style={{ border: 'none', background: 'transparent', outline: 'none', fontSize: '13px', color: 'var(--text-main)', padding: '4px' }}
                  />
                </div>
              </div>
              
              <div style={{ display: 'flex', gap: '10px' }}>
                <button 
                  onClick={exportToCSV} 
                  style={{ borderRadius: '100px', padding: '8px 16px', background: 'rgba(20, 108, 46, 0.1)', color: 'var(--success)', border: 'none', cursor: 'pointer', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px', transition: '0.2s' }}
                  onMouseOver={(e) => { e.currentTarget.style.background = 'var(--success)'; e.currentTarget.style.color = '#fff'; }}
                  onMouseOut={(e) => { e.currentTarget.style.background = 'rgba(20, 108, 46, 0.1)'; e.currentTarget.style.color = 'var(--success)'; }}
                >
                  <Download size={14} />
                  Export Excel
                </button>

                <button 
                  onClick={loadData} 
                  disabled={loading}
                  style={{ borderRadius: '100px', padding: '8px 16px', background: 'var(--primary-container)', color: 'var(--primary)', border: 'none', cursor: 'pointer', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px' }}
                >
                  <RefreshCw size={14} className={loading ? 'spinner' : ''} />
                  Refresh
                </button>
              </div>
            </div>

            <div style={{ overflowX: 'auto', minHeight: '400px' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '13px' }}>
                <thead style={{ background: 'rgba(0,0,0,0.01)', color: 'var(--text-muted)', textTransform: 'uppercase', fontSize: '11px', letterSpacing: '0.5px' }}>
                  <tr>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Beneficiary Name</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>SSIN Number</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>DS NO</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Added On</th>
                  </tr>
                </thead>
                <tbody>
                  {loading ? (
                    <tr>
                      <td colSpan={4} style={{ textAlign: 'center', padding: '80px 20px', color: 'var(--text-muted)' }}>
                        <Loader2 size={32} className="spinner" style={{ margin: '0 auto 16px auto', color: 'var(--primary)' }} />
                        <div style={{ fontSize: '14px', fontWeight: 500 }}>Loading Duare Sorkar Records...</div>
                      </td>
                    </tr>
                  ) : currentData.length === 0 ? (
                    <tr>
                      <td colSpan={4} style={{ textAlign: 'center', padding: '80px 20px', color: 'var(--text-muted)' }}>
                        <div style={{ background: 'rgba(0,0,0,0.02)', width: '64px', height: '64px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 16px auto' }}>
                          <List size={24} color="var(--text-muted)" />
                        </div>
                        <div style={{ fontSize: '15px', fontWeight: 600, color: 'var(--text-main)' }}>No Records Found</div>
                      </td>
                    </tr>
                  ) : (
                    currentData.map((row) => (
                      <tr key={row.id} style={{ borderBottom: '1px solid rgba(0,0,0,0.03)', transition: 'background 0.2s' }} onMouseOver={(e) => e.currentTarget.style.background = 'rgba(11, 87, 208, 0.02)'} onMouseOut={(e) => e.currentTarget.style.background = 'transparent'}>
                        <td style={{ padding: '12px 20px', fontWeight: 600, color: 'var(--text-main)' }}>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <div style={{ width: '28px', height: '28px', borderRadius: '50%', background: 'var(--primary-container)', color: 'var(--primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '12px', fontWeight: 700 }}>
                              {row.name.charAt(0).toUpperCase()}
                            </div>
                            {row.name}
                          </div>
                        </td>
                        <td style={{ padding: '12px 20px', fontFamily: 'monospace', fontSize: '14px', color: 'var(--primary)', fontWeight: 600, cursor: 'pointer' }} onClick={() => handleCopy(row.ssin, 'SSIN')} title="Click to Copy">
                          {row.ssin}
                        </td>
                        <td style={{ padding: '12px 20px' }}>
                          <span style={{ background: 'rgba(11, 87, 208, 0.08)', color: 'var(--primary)', padding: '4px 10px', borderRadius: '100px', fontSize: '12px', fontWeight: 700 }}>
                            {row.dsno}
                          </span>
                        </td>
                        <td style={{ padding: '12px 20px', color: 'var(--text-muted)' }}>
                          {row.created_date}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>

            {!loading && filteredData.length > 0 && (
              <div style={{ padding: '12px 20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: 'rgba(0,0,0,0.01)', borderTop: '1px solid var(--border)' }}>
                <div style={{ fontSize: '12px', color: 'var(--text-muted)' }}>
                  Showing <strong style={{ color: 'var(--text-main)' }}>{((currentPage - 1) * rowsPerPage) + 1}</strong> to <strong style={{ color: 'var(--text-main)' }}>{Math.min(currentPage * rowsPerPage, filteredData.length)}</strong> of <strong style={{ color: 'var(--text-main)' }}>{filteredData.length}</strong> records
                </div>
                <div style={{ display: 'flex', gap: '6px' }}>
                  <button disabled={currentPage === 1} onClick={() => setCurrentPage(p => p - 1)} style={{ padding: '6px 12px', border: '1px solid var(--border)', background: 'var(--surface)', color: 'var(--text-main)', borderRadius: '6px', cursor: currentPage === 1 ? 'not-allowed' : 'pointer', opacity: currentPage === 1 ? 0.5 : 1 }}><ChevronLeft size={16} /></button>
                  <button disabled={currentPage === totalPages} onClick={() => setCurrentPage(p => p + 1)} style={{ padding: '6px 12px', border: '1px solid var(--border)', background: 'var(--surface)', color: 'var(--text-main)', borderRadius: '6px', cursor: currentPage === totalPages ? 'not-allowed' : 'pointer', opacity: currentPage === totalPages ? 0.5 : 1 }}><ChevronRight size={16} /></button>
                </div>
              </div>
            )}
            
          </div>
        </div>
      </div>

      <style dangerouslySetInnerHTML={{__html: `
        .spinner { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
      `}} />
    </>
  );
}
