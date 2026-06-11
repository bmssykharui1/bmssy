'use client';

import { useState, useEffect, useMemo } from 'react';
import { getPendingPFUpdates, getGlobalSettings, acceptPFUpdate, rejectPFUpdate } from '@/app/actions/pfupdate';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { Toast } from '@/lib/toast';
import { Search, RefreshCw, CheckCircle, XCircle, X, Save, ChevronLeft, ChevronRight, Hash, Calendar, User, Loader2, FileClock, AlertTriangle } from 'lucide-react';

const MySwal = withReactContent(Swal);

// Component for individual rows to handle inline state (Inputs + Loading)
function PFTableRow({ row, globalSettings, onAcceptSuccess, openRejectModal }: any) {
  const [periodFrom, setPeriodFrom] = useState(globalSettings.period_from);
  const [periodTo, setPeriodTo] = useState(globalSettings.period_to);
  const [isAccepting, setIsAccepting] = useState(false);

  // Sync state if global settings load later
  useEffect(() => {
    setPeriodFrom(globalSettings.period_from);
    setPeriodTo(globalSettings.period_to);
  }, [globalSettings.period_from, globalSettings.period_to]);

  const handleAccept = async () => {
    setIsAccepting(true);
    const res = await acceptPFUpdate(row.approved_ssin, row.beneficiary_name, periodFrom, periodTo);
    setIsAccepting(false);

    if (res.error) {
      MySwal.fire({ title: 'Error', text: res.error, icon: 'error' });
    } else {
      Toast.fire({ title: 'PF Updated Successfully', icon: 'success' });
      onAcceptSuccess(row.approved_ssin);
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
      <td style={{ padding: '12px 10px' }}>
        <input 
          type="date" 
          value={periodFrom} 
          onChange={(e) => setPeriodFrom(e.target.value)}
          style={{ padding: '6px 10px', border: '1px solid rgba(0,0,0,0.1)', borderRadius: '6px', fontSize: '12px', outline: 'none', background: 'var(--surface)', color: 'var(--text-main)' }}
        />
      </td>
      <td style={{ padding: '12px 10px' }}>
        <input 
          type="date" 
          value={periodTo} 
          onChange={(e) => setPeriodTo(e.target.value)}
          style={{ padding: '6px 10px', border: '1px solid rgba(0,0,0,0.1)', borderRadius: '6px', fontSize: '12px', outline: 'none', background: 'var(--surface)', color: 'var(--text-main)' }}
        />
      </td>
      <td style={{ padding: '12px 20px', textAlign: 'right' }}>
        <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
          <button 
            onClick={handleAccept}
            disabled={isAccepting}
            style={{ background: 'rgba(20, 108, 46, 0.1)', color: 'var(--success)', border: 'none', padding: '6px 14px', borderRadius: '100px', cursor: isAccepting ? 'not-allowed' : 'pointer', opacity: isAccepting ? 0.7 : 1, fontWeight: 600, display: 'inline-flex', alignItems: 'center', gap: '6px', fontSize: '12px', transition: '0.2s' }}
            onMouseOver={(e) => { if(!isAccepting) { e.currentTarget.style.background = 'var(--success)'; e.currentTarget.style.color = '#fff'; } }}
            onMouseOut={(e) => { if(!isAccepting) { e.currentTarget.style.background = 'rgba(20, 108, 46, 0.1)'; e.currentTarget.style.color = 'var(--success)'; } }}
          >
            {isAccepting ? <Loader2 size={12} className="spinner" /> : <CheckCircle size={12} />} 
            {isAccepting ? 'Saving...' : 'Accept'}
          </button>
          <button 
            onClick={() => openRejectModal(row)}
            disabled={isAccepting}
            style={{ background: 'rgba(179, 38, 30, 0.1)', color: 'var(--error)', border: 'none', padding: '6px 14px', borderRadius: '100px', cursor: isAccepting ? 'not-allowed' : 'pointer', fontWeight: 600, display: 'inline-flex', alignItems: 'center', gap: '6px', fontSize: '12px', transition: '0.2s' }}
            onMouseOver={(e) => { if(!isAccepting) { e.currentTarget.style.background = 'var(--error)'; e.currentTarget.style.color = '#fff'; } }}
            onMouseOut={(e) => { if(!isAccepting) { e.currentTarget.style.background = 'rgba(179, 38, 30, 0.1)'; e.currentTarget.style.color = 'var(--error)'; } }}
          >
            <XCircle size={12} /> Reject
          </button>
        </div>
      </td>
    </tr>
  );
}

export default function PFUpdateConstructionsPage() {
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [globalSettings, setGlobalSettings] = useState({ period_from: '', period_to: '' });
  
  // Pagination & Search
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const rowsPerPage = 20;

  // Modal State - Reject
  const [isRejectModalOpen, setIsRejectModalOpen] = useState(false);
  const [isRejecting, setIsRejecting] = useState(false);
  const [rejectForm, setRejectForm] = useState({ ssin: '', name: '', reason: '' });

  const loadData = async () => {
    setLoading(true);
    const [settings, result] = await Promise.all([
      getGlobalSettings(),
      getPendingPFUpdates('242')
    ]);
    setGlobalSettings(settings);
    setData(result);
    setLoading(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  const filteredData = useMemo(() => {
    return data.filter(item => {
      const s = searchTerm.toLowerCase();
      return (
        (item.beneficiary_name && item.beneficiary_name.toLowerCase().includes(s)) ||
        (item.approved_ssin && item.approved_ssin.includes(s))
      );
    });
  }, [data, searchTerm]);

  const totalPages = Math.ceil(filteredData.length / rowsPerPage);
  const currentData = filteredData.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);

  // Success Callback from Row
  const handleAcceptSuccess = (ssin: string) => {
    setData(prev => prev.filter(item => item.approved_ssin !== ssin));
  };

  // Reject Logic
  const openRejectModal = (row: any) => {
    setRejectForm({
      ssin: row.approved_ssin,
      name: row.beneficiary_name,
      reason: ''
    });
    setIsRejectModalOpen(true);
  };

  const handleReject = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsRejecting(true);
    
    const res = await rejectPFUpdate(rejectForm.ssin, rejectForm.name, rejectForm.reason);
    setIsRejecting(false);

    if (res.error) {
      MySwal.fire({ title: 'Error', text: res.error, icon: 'error' });
    } else {
      Toast.fire({ title: 'Beneficiary Rejected', icon: 'success' });
      setIsRejectModalOpen(false);
      setData(prev => prev.filter(item => item.approved_ssin !== rejectForm.ssin));
    }
  };

  return (
    <>
      <header className="app-topbar" style={{ background: 'var(--surface)', backdropFilter: 'blur(10px)', borderBottom: '1px solid var(--border)' }}>
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '18px' }}>
            <FileClock size={22} style={{ color: 'var(--primary)' }} />
            PF Updation <span style={{ color: 'var(--text-muted)', fontWeight: 400 }}>/ Constructions (242)</span>
          </h1>
        </div>
      </header>

      <div className="content-scroll" style={{ padding: '20px' }}>
        <div style={{ maxWidth: '1400px', margin: '0 auto', width: '100%' }}>
          
          <div className="md-card" style={{ 
            padding: '0', 
            overflow: 'hidden', 
            border: '1px solid rgba(11, 87, 208, 0.1)',
            boxShadow: '0 12px 32px rgba(0,0,0,0.04)',
            background: 'var(--surface)',
            borderRadius: '16px'
          }}>
            
            <div style={{ 
              padding: '16px 20px', 
              display: 'flex', 
              justifyContent: 'space-between', 
              alignItems: 'center', 
              borderBottom: '1px solid rgba(0,0,0,0.05)', 
              background: 'linear-gradient(to right, rgba(11, 87, 208, 0.02), transparent)',
              flexWrap: 'wrap', 
              gap: '16px' 
            }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <div style={{ position: 'relative' }}>
                  <Search size={16} style={{ position: 'absolute', left: '14px', top: '50%', transform: 'translateY(-50%)', color: 'var(--primary)' }} />
                  <input 
                    type="text" 
                    placeholder="Search by Name, SSIN..." 
                    value={searchTerm}
                    onChange={(e) => { setSearchTerm(e.target.value); setCurrentPage(1); }}
                    className="app-input"
                    style={{ 
                      paddingLeft: '40px', 
                      width: '320px', 
                      height: '38px',
                      borderRadius: '100px', 
                      background: 'var(--surface)',
                      color: 'var(--text-main)',
                      border: '1px solid var(--border)',
                      fontSize: '13px',
                      boxShadow: 'inset 0 2px 4px rgba(0,0,0,0.02)'
                    }}
                  />
                </div>
              </div>
              
              <button 
                onClick={loadData} 
                disabled={loading}
                style={{ 
                  borderRadius: '100px', padding: '8px 16px', background: 'var(--primary-container)', 
                  color: 'var(--primary)', border: 'none', cursor: 'pointer', fontWeight: 600,
                  display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px', transition: 'all 0.2s',
                  boxShadow: '0 2px 8px rgba(11, 87, 208, 0.15)'
                }}
              >
                <RefreshCw size={14} className={loading ? 'spinner' : ''} />
                Refresh List
              </button>
            </div>

            <div style={{ overflowX: 'auto', minHeight: '400px' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '13px' }}>
                <thead style={{ background: 'rgba(0,0,0,0.01)', color: 'var(--text-muted)', textTransform: 'uppercase', fontSize: '11px', letterSpacing: '0.5px' }}>
                  <tr>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid rgba(0,0,0,0.05)' }}>Beneficiary Name</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid rgba(0,0,0,0.05)' }}>SSIN Number</th>
                    <th style={{ padding: '12px 10px', fontWeight: 600, borderBottom: '1px solid rgba(0,0,0,0.05)', width: '150px' }}>Period From</th>
                    <th style={{ padding: '12px 10px', fontWeight: 600, borderBottom: '1px solid rgba(0,0,0,0.05)', width: '150px' }}>Period To</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, textAlign: 'right', borderBottom: '1px solid rgba(0,0,0,0.05)' }}>Actions</th>
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
                        <div style={{ fontSize: '13px', marginTop: '4px' }}>No pending PF updates found for this category.</div>
                      </td>
                    </tr>
                  ) : (
                    currentData.map((row) => (
                      <PFTableRow 
                        key={row.id} 
                        row={row} 
                        globalSettings={globalSettings} 
                        onAcceptSuccess={handleAcceptSuccess} 
                        openRejectModal={openRejectModal} 
                      />
                    ))
                  )}
                </tbody>
              </table>
            </div>

            {/* Pagination Controls */}
            {!loading && filteredData.length > 0 && (
              <div style={{ padding: '12px 20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: 'rgba(0,0,0,0.01)', borderTop: '1px solid rgba(0,0,0,0.03)' }}>
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

      {/* Reject Modal */}
      {isRejectModalOpen && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', backdropFilter: 'blur(6px)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '20px', animation: 'fadeIn 0.2s ease forwards' }}>
          <div style={{ background: 'var(--surface)', width: '100%', maxWidth: '420px', borderRadius: '20px', overflow: 'hidden', boxShadow: '0 24px 64px rgba(0,0,0,0.2)', animation: 'slideUp 0.3s cubic-bezier(0.2, 0, 0, 1) forwards' }}>
            <div style={{ padding: '20px 24px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: 'var(--error)' }}>
              <h2 style={{ fontSize: '16px', fontWeight: 700, color: '#fff', display: 'flex', alignItems: 'center', gap: '8px' }}>
                <AlertTriangle size={18} color="rgba(255,255,255,0.8)" /> Reject Beneficiary
              </h2>
              <button onClick={() => setIsRejectModalOpen(false)} style={{ background: 'rgba(255,255,255,0.2)', border: 'none', width: '28px', height: '28px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', color: '#fff' }}>
                <X size={16} />
              </button>
            </div>
            <form onSubmit={handleReject}>
              <div style={{ padding: '24px' }}>
                <div style={{ marginBottom: '16px', background: 'rgba(179, 38, 30, 0.05)', padding: '12px', borderRadius: '8px', border: '1px solid rgba(179, 38, 30, 0.1)' }}>
                  <div style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>{rejectForm.name}</div>
                  <div style={{ fontSize: '12px', fontFamily: 'monospace', color: 'var(--error)', marginTop: '4px' }}>{rejectForm.ssin}</div>
                </div>

                <div style={{ marginBottom: '8px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 600, marginBottom: '6px', color: 'var(--text-muted)' }}>REASON FOR REJECTION</label>
                  <select 
                    required 
                    value={rejectForm.reason} 
                    onChange={(e) => setRejectForm({...rejectForm, reason: e.target.value})} 
                    style={{ padding: '10px 14px', width: '100%', background: 'var(--background)', color: 'var(--text-main)', border: '1px solid var(--border)', borderRadius: '8px', fontSize: '13px', fontWeight: 600, outline: 'none' }}
                  >
                    <option value="" disabled>Select a reason...</option>
                    <option value="Duplicate SSIN">Duplicate SSIN</option>
                    <option value="Document Mismatch">Document Mismatch</option>
                    <option value="Fake Details">Fake Details</option>
                    <option value="Already Exists in System">Already Exists in System</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>
              <div style={{ padding: '16px 24px', background: 'var(--background)', borderTop: '1px solid var(--border)', display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
                <button type="button" onClick={() => setIsRejectModalOpen(false)} style={{ background: 'none', border: 'none', padding: '8px 16px', fontWeight: 600, color: 'var(--text-muted)', cursor: 'pointer', borderRadius: '100px', fontSize: '13px' }}>Cancel</button>
                <button type="submit" disabled={isRejecting || !rejectForm.reason} style={{ padding: '8px 20px', borderRadius: '100px', display: 'flex', alignItems: 'center', gap: '8px', background: 'var(--error)', color: '#fff', border: 'none', fontWeight: 600, cursor: (isRejecting || !rejectForm.reason) ? 'not-allowed' : 'pointer', opacity: (isRejecting || !rejectForm.reason) ? 0.7 : 1, fontSize: '13px', boxShadow: '0 4px 12px rgba(179, 38, 30, 0.2)' }}>
                  {isRejecting ? <><Loader2 size={16} className="spinner" /> Rejecting...</> : <><XCircle size={16} /> Confirm Reject</>}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      <style dangerouslySetInnerHTML={{__html: `
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .spinner { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
      `}} />
    </>
  );
}
