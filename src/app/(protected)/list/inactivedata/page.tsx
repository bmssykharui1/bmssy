'use client';

import { useState, useEffect, useMemo } from 'react';
import { getInactiveDataList } from '@/app/actions/list';
import { Search, RefreshCw, ChevronLeft, ChevronRight, UserMinus, Download, Loader2, AlertCircle } from 'lucide-react';
import { Toast } from '@/lib/toast';

export default function InactiveDataListPage() {
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  
  // Filters
  const [searchTerm, setSearchTerm] = useState('');
  
  const [currentPage, setCurrentPage] = useState(1);
  const rowsPerPage = 20;

  const loadData = async () => {
    setLoading(true);
    const result = await getInactiveDataList();
    setData(result);
    setLoading(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  const filteredData = useMemo(() => {
    return data.filter(item => {
      const s = searchTerm.toLowerCase();
      return (item.beneficiary_name?.toLowerCase().includes(s)) ||
             (item.approved_ssin?.includes(s)) ||
             (item.phone_no?.includes(s)) ||
             (item.remark?.toLowerCase().includes(s));
    });
  }, [data, searchTerm]);

  const totalPages = Math.ceil(filteredData.length / rowsPerPage);
  const currentData = filteredData.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);

  const exportExcel = () => {
    if (filteredData.length === 0) {
      Toast.fire({ title: 'No Data to export', icon: 'warning' });
      return;
    }

    const headers = ['SL NO', 'Name of Beneficiary', 'SSIN Number', 'Date of 60', 'Phone No', 'Reason for Rejection', 'Rejection Date'];
    const csvContent = [
      headers.join(','),
      ...filteredData.map((row, idx) => `${idx + 1},"${row.beneficiary_name}",${row.approved_ssin},${row.date_of_attaining_60},${row.phone_no},"${row.remark}",${row.last_update}`)
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Rejected_Data_List_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    Toast.fire({ title: 'Exported Successfully', icon: 'success' });
  };

  return (
    <>
      <header className="app-topbar" style={{ background: 'var(--surface)', backdropFilter: 'blur(10px)', borderBottom: '1px solid rgba(0,0,0,0.05)' }}>
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '18px' }}>
            <UserMinus size={22} style={{ color: 'var(--error)' }} />
            Rejected / Inactive Data
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
                  placeholder="Search Name, SSIN, Phone or Reason..." 
                  value={searchTerm}
                  onChange={(e) => { setSearchTerm(e.target.value); setCurrentPage(1); }}
                  className="app-input"
                  style={{ paddingLeft: '40px', width: '300px', height: '38px', borderRadius: '100px', background: 'var(--background)', color: 'var(--text-main)', border: '1px solid var(--border)', fontSize: '13px' }}
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
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Reason</th>
                  <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Rejection Date</th>
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
                    <tr key={idx} style={{ borderBottom: '1px solid var(--border)', background: 'rgba(211, 47, 47, 0.02)' }}>
                      <td style={{ padding: '12px 20px', fontWeight: 600, color: 'var(--text-main)' }}>{row.beneficiary_name}</td>
                      <td style={{ padding: '12px 20px', fontFamily: 'monospace', color: 'var(--primary)', fontWeight: 600 }}>{row.approved_ssin}</td>
                      <td style={{ padding: '12px 20px', color: 'var(--text-main)' }}>{row.phone_no || 'N/A'}</td>
                      <td style={{ padding: '12px 20px', color: 'var(--error)' }}>
                        <div style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', background: 'rgba(211, 47, 47, 0.1)', padding: '4px 10px', borderRadius: '100px', fontSize: '12px', fontWeight: 600 }}>
                          <AlertCircle size={12} /> {row.remark}
                        </div>
                      </td>
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
