'use client';

import { useState, useEffect, useMemo } from 'react';
import { getDSEntryCandidates, saveDSEntry } from '@/app/actions/ds';
import { Toast } from '@/lib/toast';
import { Search, RefreshCw, CheckCircle, Save, ChevronLeft, ChevronRight, Hash, User, Loader2, Users, ShieldCheck } from 'lucide-react';

function DSEntryRow({ row, onSaveSuccess }: any) {
  const [dsno, setDsno] = useState('');
  const [isSaving, setIsSaving] = useState(false);

  const handleSave = async () => {
    if (!dsno.trim()) {
      Toast.fire({ title: 'Please enter a DS NO', icon: 'warning' });
      return;
    }

    setIsSaving(true);
    const res = await saveDSEntry(row.approved_ssin, row.beneficiary_name, dsno);
    setIsSaving(false);

    if (res.error) {
      Toast.fire({ title: res.error, icon: 'error' });
    } else {
      Toast.fire({ title: 'DS Entry Saved Successfully', icon: 'success' });
      onSaveSuccess(row.approved_ssin);
    }
  };

  return (
    <tr style={{ borderBottom: '1px solid rgba(0,0,0,0.03)', transition: 'background 0.2s' }} onMouseOver={(e) => e.currentTarget.style.background = 'rgba(11, 87, 208, 0.02)'} onMouseOut={(e) => e.currentTarget.style.background = 'transparent'}>
      <td style={{ padding: '12px 20px', fontWeight: 600, color: 'var(--text-main)' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <div style={{ width: '28px', height: '28px', borderRadius: '50%', background: 'var(--primary-container)', color: 'var(--primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '12px', fontWeight: 700 }}>
            {row.beneficiary_name.charAt(0).toUpperCase()}
          </div>
          {row.beneficiary_name}
        </div>
      </td>
      <td style={{ padding: '12px 20px', fontFamily: 'monospace', fontSize: '14px', color: 'var(--primary)', fontWeight: 600 }}>{row.approved_ssin}</td>
      <td style={{ padding: '12px 20px', color: 'var(--text-muted)' }}>{row.phone}</td>
      <td style={{ padding: '12px 10px' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
          <input 
            type="text" 
            placeholder="Enter DS NO"
            value={dsno} 
            onChange={(e) => setDsno(e.target.value)}
            style={{ 
              padding: '6px 12px', border: '1px solid var(--border)', borderRadius: '100px', 
              fontSize: '13px', outline: 'none', background: 'var(--surface)', color: 'var(--text-main)',
              width: '120px'
            }}
          />
        </div>
      </td>
      <td style={{ padding: '12px 20px', textAlign: 'right' }}>
        <button 
          onClick={handleSave}
          disabled={isSaving || !dsno.trim()}
          style={{ background: 'rgba(11, 87, 208, 0.1)', color: 'var(--primary)', border: 'none', padding: '6px 16px', borderRadius: '100px', cursor: (isSaving || !dsno.trim()) ? 'not-allowed' : 'pointer', opacity: (isSaving || !dsno.trim()) ? 0.7 : 1, fontWeight: 600, display: 'inline-flex', alignItems: 'center', gap: '6px', fontSize: '12px', transition: '0.2s' }}
          onMouseOver={(e) => { if(!isSaving && dsno.trim()) { e.currentTarget.style.background = 'var(--primary)'; e.currentTarget.style.color = '#fff'; } }}
          onMouseOut={(e) => { if(!isSaving && dsno.trim()) { e.currentTarget.style.background = 'rgba(11, 87, 208, 0.1)'; e.currentTarget.style.color = 'var(--primary)'; } }}
        >
          {isSaving ? <Loader2 size={14} className="spinner" /> : <Save size={14} />} 
          {isSaving ? 'Saving...' : 'Save'}
        </button>
      </td>
    </tr>
  );
}

export default function DSEntryPage() {
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  
  const [searchTerm, setSearchTerm] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const rowsPerPage = 20;

  const loadData = async () => {
    setLoading(true);
    const result = await getDSEntryCandidates();
    setData(result);
    setLoading(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  const filteredData = useMemo(() => {
    return data.filter(item => {
      const s = searchTerm.toLowerCase();
      const matchesSearch = (item.beneficiary_name && item.beneficiary_name.toLowerCase().includes(s)) ||
                            (item.approved_ssin && item.approved_ssin.includes(s));
      const matchesCategory = categoryFilter === '' || item.approved_ssin.startsWith(categoryFilter);
      return matchesSearch && matchesCategory;
    });
  }, [data, searchTerm, categoryFilter]);

  const totalPages = Math.ceil(filteredData.length / rowsPerPage);
  const currentData = filteredData.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);

  const handleSaveSuccess = (ssin: string) => {
    setData(prev => prev.filter(item => item.approved_ssin !== ssin));
  };

  return (
    <>
      <header className="app-topbar" style={{ background: 'var(--surface)', backdropFilter: 'blur(10px)', borderBottom: '1px solid rgba(0,0,0,0.05)' }}>
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '18px' }}>
            <Users size={22} style={{ color: 'var(--primary)' }} />
            Duare Sorkar <span style={{ color: 'var(--text-muted)', fontWeight: 400 }}>/ Entry</span>
          </h1>
        </div>
      </header>

      <div className="content-scroll" style={{ padding: '20px' }}>
        <div style={{ maxWidth: '1400px', margin: '0 auto', width: '100%' }}>
          
          <div className="md-card" style={{ padding: '0', overflow: 'hidden', border: '1px solid var(--border)', background: 'var(--surface)', borderRadius: '16px' }}>
            
            <div style={{ padding: '16px 20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px solid var(--border)', flexWrap: 'wrap', gap: '16px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <div style={{ position: 'relative' }}>
                  <Search size={16} style={{ position: 'absolute', left: '14px', top: '50%', transform: 'translateY(-50%)', color: 'var(--primary)' }} />
                  <input 
                    type="text" 
                    placeholder="Search Name or SSIN..." 
                    value={searchTerm}
                    onChange={(e) => { setSearchTerm(e.target.value); setCurrentPage(1); }}
                    className="app-input"
                    style={{ paddingLeft: '40px', width: '280px', height: '38px', borderRadius: '100px', background: 'var(--background)', color: 'var(--text-main)', border: '1px solid var(--border)', fontSize: '13px' }}
                  />
                </div>
                <select 
                  value={categoryFilter} 
                  onChange={(e) => { setCategoryFilter(e.target.value); setCurrentPage(1); }}
                  style={{ padding: '8px 16px', borderRadius: '100px', background: 'var(--background)', color: 'var(--text-main)', border: '1px solid var(--border)', fontSize: '13px', outline: 'none' }}
                >
                  <option value="">All Categories</option>
                  <option value="142">Others (142)</option>
                  <option value="242">Constructions (242)</option>
                </select>
              </div>
              
              <button 
                onClick={loadData} 
                disabled={loading}
                style={{ borderRadius: '100px', padding: '8px 16px', background: 'var(--primary-container)', color: 'var(--primary)', border: 'none', cursor: 'pointer', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px' }}
              >
                <RefreshCw size={14} className={loading ? 'spinner' : ''} />
                Refresh List
              </button>
            </div>

            <div style={{ overflowX: 'auto', minHeight: '400px' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '13px' }}>
                <thead style={{ background: 'rgba(0,0,0,0.01)', color: 'var(--text-muted)', textTransform: 'uppercase', fontSize: '11px', letterSpacing: '0.5px' }}>
                  <tr>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Beneficiary Name</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>SSIN Number</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid var(--border)' }}>Phone</th>
                    <th style={{ padding: '12px 10px', fontWeight: 600, borderBottom: '1px solid var(--border)', width: '160px' }}>DS NO</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, textAlign: 'right', borderBottom: '1px solid var(--border)' }}>Action</th>
                  </tr>
                </thead>
                <tbody>
                  {loading ? (
                    <tr>
                      <td colSpan={5} style={{ textAlign: 'center', padding: '80px 20px', color: 'var(--text-muted)' }}>
                        <Loader2 size={32} className="spinner" style={{ margin: '0 auto 16px auto', color: 'var(--primary)' }} />
                        <div style={{ fontSize: '14px', fontWeight: 500 }}>Scanning for Pending Records...</div>
                      </td>
                    </tr>
                  ) : currentData.length === 0 ? (
                    <tr>
                      <td colSpan={5} style={{ textAlign: 'center', padding: '80px 20px', color: 'var(--text-muted)' }}>
                        <div style={{ background: 'rgba(0,0,0,0.02)', width: '64px', height: '64px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 16px auto' }}>
                          <CheckCircle size={24} color="var(--success)" />
                        </div>
                        <div style={{ fontSize: '15px', fontWeight: 600, color: 'var(--text-main)' }}>All Caught Up!</div>
                        <div style={{ fontSize: '13px', marginTop: '4px' }}>No pending candidates for DS Entry found.</div>
                      </td>
                    </tr>
                  ) : (
                    currentData.map((row) => (
                      <DSEntryRow 
                        key={row.approved_ssin} 
                        row={row} 
                        onSaveSuccess={handleSaveSuccess} 
                      />
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
