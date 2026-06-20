'use client';

import { useState, useEffect, useMemo } from 'react';
import { getNewDataList } from '@/app/actions/list';
import { Search, RefreshCw, ChevronLeft, ChevronRight, UserPlus, Download, Loader2 } from 'lucide-react';
import { Toast } from '@/lib/toast';

export default function NewDataListPage() {
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  
  // Filters
  const [searchTerm, setSearchTerm] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  
  const [currentPage, setCurrentPage] = useState(1);
  const rowsPerPage = 20;

  const loadData = async () => {
    setLoading(true);
    const result = await getNewDataList();
    setData(result);
    setLoading(false);
  };

  useEffect(() => {
    loadData();
    // Default current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    const formatLocal = (date: Date) => {
      const y = date.getFullYear();
      const m = String(date.getMonth() + 1).padStart(2, '0');
      const d = String(date.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    };
    
    setDateFrom(formatLocal(firstDay));
    setDateTo(formatLocal(lastDay));
  }, []);

  const filteredData = useMemo(() => {
    return data.filter(item => {
      const s = searchTerm.toLowerCase();
      const matchSearch = (item.beneficiary_name?.toLowerCase().includes(s)) ||
             (item.approved_ssin?.includes(s)) ||
             (item.phone_no?.includes(s));
      
      let matchDate = true;
      if (dateFrom) matchDate = matchDate && (item.created_at >= dateFrom);
      if (dateTo) matchDate = matchDate && (item.created_at <= dateTo);

      return matchSearch && matchDate;
    });
  }, [data, searchTerm, dateFrom, dateTo]);

  const totalPages = Math.ceil(filteredData.length / rowsPerPage);
  const currentData = filteredData.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);

  const exportExcel = () => {
    if (filteredData.length === 0) {
      Toast.fire({ title: 'No Data to export', icon: 'warning' });
      return;
    }

    const headers = ['SL NO', 'Name of Beneficiary', 'SSIN Number', 'Date of 60', 'Phone No', 'Entry Date'];
    const csvContent = [
      headers.join(','),
      ...filteredData.map((row, idx) => `${idx + 1},"${row.beneficiary_name}",${row.approved_ssin},${row.date_of_attaining_60},${row.phone_no},${row.created_at}`)
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `New_Data_List_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    Toast.fire({ title: 'Exported Successfully', icon: 'success' });
  };

  return (
    <>
      <header className="app-topbar" style={{ background: 'var(--surface)', backdropFilter: 'blur(10px)', borderBottom: '1px solid rgba(0,0,0,0.05)' }}>
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '18px' }}>
            <UserPlus size={22} style={{ color: 'var(--primary)' }} />
            New Data List
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
                  placeholder="Search Name, SSIN or Phone..." 
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
                  value={dateFrom}
                  onChange={(e) => { setDateFrom(e.target.value); setCurrentPage(1); }}
                  style={{ border: 'none', background: 'transparent', outline: 'none', fontSize: '13px', color: 'var(--text-main)', padding: '4px' }}
                />
                <span style={{ fontSize: '12px', fontWeight: 600, color: 'var(--text-muted)', marginLeft: '4px' }}>To:</span>
                <input 
                  type="date" 
                  value={dateTo}
                  onChange={(e) => { setDateTo(e.target.value); setCurrentPage(1); }}
                  style={{ border: 'none', background: 'transparent', outline: 'none', fontSize: '13px', color: 'var(--text-main)', padding: '4px' }}
                />
              </div>
            </div>
            
            <div style={{ display: 'flex', gap: '10px' }}>
              <button 
                onClick={exportExcel} 
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
              <thead style={{ background: 'var(--background)', color: 'var(--text-muted)', textTransform: 'uppercase', fontSize: '11px', letterSpacing: '0.5px' }}>
                <tr>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Name</th>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>SSIN</th>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Phone No</th>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Date of 60</th>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Entry Date</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan={5} style={{ textAlign: 'center', padding: '80px 20px', color: 'var(--text-muted)' }}>
                      <Loader2 size={32} className="spinner" style={{ margin: '0 auto 16px auto', color: 'var(--primary)' }} />
                      <div style={{ fontSize: '14px', fontWeight: 500 }}>Loading Records...</div>
                    </td>
                  </tr>
                ) : currentData.length === 0 ? (
                  <tr>
                    <td colSpan={5} style={{ textAlign: 'center', padding: '80px 20px', color: 'var(--text-muted)' }}>
                      <div style={{ fontSize: '14px', fontWeight: 500 }}>No records found matching criteria.</div>
                    </td>
                  </tr>
                ) : (
                  currentData.map((row, idx) => (
                    <tr key={idx} style={{ borderBottom: '1px solid var(--border)' }}>
                      <td style={{ padding: '12px 20px', fontWeight: 600, color: 'var(--text-main)' }}>{row.beneficiary_name}</td>
                      <td style={{ padding: '12px 20px', fontFamily: 'monospace', color: 'var(--primary)', fontWeight: 600 }}>{row.approved_ssin}</td>
                      <td style={{ padding: '12px 20px', color: 'var(--text-main)' }}>{row.phone_no || 'N/A'}</td>
                      <td style={{ padding: '12px 20px', color: 'var(--text-main)' }}>{row.date_of_attaining_60}</td>
                      <td style={{ padding: '12px 20px', color: 'var(--text-muted)' }}>{row.created_at}</td>
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
