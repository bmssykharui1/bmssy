'use client';

import { useState, useEffect, useMemo } from 'react';
import { getAllBeneficiaries, updateBeneficiary } from '@/app/actions/list';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { Users, Search, RefreshCw, Edit2, X, Save, ChevronLeft, ChevronRight, Hash, Calendar, Smartphone, User, Loader2, Database } from 'lucide-react';

const MySwal = withReactContent(Swal);

export default function AllDataPage() {
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  
  // Pagination & Search
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const rowsPerPage = 20; // Show slightly less rows but in a more compact format

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isUpdating, setIsUpdating] = useState(false);
  const [updateForm, setUpdateForm] = useState({ id: 0, name: '', ssin: '', dob: '', phone: '' });

  const loadData = async () => {
    setLoading(true);
    const result = await getAllBeneficiaries();
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
        (item.approved_ssin && item.approved_ssin.includes(s)) ||
        (item.phone_no && item.phone_no.includes(s))
      );
    });
  }, [data, searchTerm]);

  const totalPages = Math.ceil(filteredData.length / rowsPerPage);
  const currentData = filteredData.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);

  const openUpdateModal = (row: any) => {
    setUpdateForm({
      id: row.id,
      name: row.beneficiary_name || '',
      ssin: row.approved_ssin || '',
      dob: row.date_of_attaining_60 || '',
      phone: row.phone_no || ''
    });
    setIsModalOpen(true);
  };

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsUpdating(true);
    
    const res = await updateBeneficiary(updateForm.id, updateForm);
    setIsUpdating(false);

    if (res.error) {
      MySwal.fire({ title: 'Error', text: res.error, icon: 'error', confirmButtonColor: 'var(--primary)' });
    } else {
      MySwal.fire({ title: 'Updated!', text: 'Beneficiary updated successfully.', icon: 'success', timer: 2000, showConfirmButton: false });
      setIsModalOpen(false);
      loadData(); // Reload to get fresh data
    }
  };

  return (
    <>
      <header className="app-topbar" style={{ background: 'var(--surface)', backdropFilter: 'blur(10px)', borderBottom: '1px solid var(--border)' }}>
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '18px' }}>
            <Database size={22} style={{ color: 'var(--primary)' }} />
            Beneficiary Database
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
            
            {/* Sleek Toolbar */}
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
                    placeholder="Search by Name, SSIN, Phone..." 
                    value={searchTerm}
                    onChange={(e) => { setSearchTerm(e.target.value); setCurrentPage(1); }}
                    className="app-input"
                    style={{ 
                      paddingLeft: '40px', 
                      width: '320px', 
                      height: '38px',
                      borderRadius: '100px', 
                      background: 'var(--surface)',
                      border: '1px solid rgba(11, 87, 208, 0.2)',
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
                onMouseOver={(e) => e.currentTarget.style.transform = 'translateY(-1px)'}
                onMouseOut={(e) => e.currentTarget.style.transform = 'none'}
              >
                <RefreshCw size={14} className={loading ? 'spinner' : ''} />
                Refresh List
              </button>
            </div>

            {/* Compact Table */}
            <div style={{ overflowX: 'auto', minHeight: '400px' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '13px' }}>
                <thead style={{ background: 'rgba(0,0,0,0.01)', color: 'var(--text-muted)', textTransform: 'uppercase', fontSize: '11px', letterSpacing: '0.5px' }}>
                  <tr>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid rgba(0,0,0,0.05)' }}>Beneficiary Name</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid rgba(0,0,0,0.05)' }}>SSIN Number</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid rgba(0,0,0,0.05)' }}>Date of 60</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid rgba(0,0,0,0.05)' }}>Phone No</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, borderBottom: '1px solid rgba(0,0,0,0.05)' }}>Last Update</th>
                    <th style={{ padding: '12px 20px', fontWeight: 600, textAlign: 'right', borderBottom: '1px solid rgba(0,0,0,0.05)' }}>Action</th>
                  </tr>
                </thead>
                <tbody>
                  {loading ? (
                    <tr>
                      <td colSpan={6} style={{ textAlign: 'center', padding: '80px 20px', color: 'var(--text-muted)' }}>
                        <Loader2 size={32} className="spinner" style={{ margin: '0 auto 16px auto', color: 'var(--primary)' }} />
                        <div style={{ fontSize: '14px', fontWeight: 500 }}>Syncing Database Records...</div>
                      </td>
                    </tr>
                  ) : currentData.length === 0 ? (
                    <tr>
                      <td colSpan={6} style={{ textAlign: 'center', padding: '80px 20px', color: 'var(--text-muted)' }}>
                        <div style={{ background: 'rgba(0,0,0,0.02)', width: '64px', height: '64px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 16px auto' }}>
                          <Search size={24} color="var(--text-muted)" />
                        </div>
                        <div style={{ fontSize: '14px', fontWeight: 500 }}>No records found matching your criteria.</div>
                      </td>
                    </tr>
                  ) : (
                    currentData.map((row, idx) => (
                      <tr key={row.id} style={{ borderBottom: '1px solid rgba(0,0,0,0.03)', transition: 'background 0.2s' }} onMouseOver={(e) => e.currentTarget.style.background = 'rgba(11, 87, 208, 0.02)'} onMouseOut={(e) => e.currentTarget.style.background = 'transparent'}>
                        <td style={{ padding: '12px 20px', fontWeight: 600, color: 'var(--text-main)' }}>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <div style={{ width: '28px', height: '28px', borderRadius: '50%', background: 'var(--primary-container)', color: 'var(--primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '12px', fontWeight: 700 }}>
                              {row.beneficiary_name.charAt(0).toUpperCase()}
                            </div>
                            {row.beneficiary_name}
                          </div>
                        </td>
                        <td style={{ padding: '12px 20px', fontFamily: 'monospace', fontSize: '14px', color: 'var(--primary)', fontWeight: 600, letterSpacing: '0.5px' }}>{row.approved_ssin}</td>
                        <td style={{ padding: '12px 20px', color: 'var(--text-main)' }}>{row.date_of_attaining_60}</td>
                        <td style={{ padding: '12px 20px', color: 'var(--text-main)' }}>{row.phone_no}</td>
                        <td style={{ padding: '12px 20px', fontSize: '12px', color: 'var(--text-muted)' }}>{row.last_update}</td>
                        <td style={{ padding: '12px 20px', textAlign: 'right' }}>
                          <button 
                            onClick={() => openUpdateModal(row)}
                            style={{ 
                              background: 'var(--surface)', color: 'var(--primary)', border: '1px solid rgba(11, 87, 208, 0.2)', 
                              padding: '6px 14px', borderRadius: '100px', cursor: 'pointer', fontWeight: 600, 
                              display: 'inline-flex', alignItems: 'center', gap: '6px', fontSize: '12px', transition: '0.2s',
                              boxShadow: '0 2px 4px rgba(0,0,0,0.02)'
                            }}
                            onMouseOver={(e) => { e.currentTarget.style.background = 'var(--primary)'; e.currentTarget.style.color = '#fff'; }}
                            onMouseOut={(e) => { e.currentTarget.style.background = 'var(--surface)'; e.currentTarget.style.color = 'var(--primary)'; }}
                          >
                            <Edit2 size={12} /> Edit
                          </button>
                        </td>
                      </tr>
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
                  <button 
                    disabled={currentPage === 1} 
                    onClick={() => setCurrentPage(p => p - 1)}
                    style={{ padding: '6px 12px', border: '1px solid rgba(0,0,0,0.1)', background: 'var(--surface)', borderRadius: '6px', cursor: currentPage === 1 ? 'not-allowed' : 'pointer', opacity: currentPage === 1 ? 0.5 : 1, transition: '0.2s' }}
                    onMouseOver={e=>e.currentTarget.style.background=currentPage===1?'var(--surface)':'var(--surface-hover)'}
                    onMouseOut={e=>e.currentTarget.style.background='var(--surface)'}
                  ><ChevronLeft size={16} color="var(--text-main)" /></button>
                  <button 
                    disabled={currentPage === totalPages} 
                    onClick={() => setCurrentPage(p => p + 1)}
                    style={{ padding: '6px 12px', border: '1px solid rgba(0,0,0,0.1)', background: 'var(--surface)', borderRadius: '6px', cursor: currentPage === totalPages ? 'not-allowed' : 'pointer', opacity: currentPage === totalPages ? 0.5 : 1, transition: '0.2s' }}
                    onMouseOver={e=>e.currentTarget.style.background=currentPage===totalPages?'var(--surface)':'var(--surface-hover)'}
                    onMouseOut={e=>e.currentTarget.style.background='var(--surface)'}
                  ><ChevronRight size={16} color="var(--text-main)" /></button>
                </div>
              </div>
            )}
            
          </div>
        </div>
        <div style={{ height: '40px' }}></div>
      </div>

      {/* Sleek Update Modal */}
      {isModalOpen && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', backdropFilter: 'blur(6px)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '20px', animation: 'fadeIn 0.2s ease forwards' }}>
          <div style={{ background: 'var(--surface)', width: '100%', maxWidth: '420px', borderRadius: '20px', overflow: 'hidden', boxShadow: '0 24px 64px rgba(0,0,0,0.2)', animation: 'slideUp 0.3s cubic-bezier(0.2, 0, 0, 1) forwards' }}>
            
            <div style={{ padding: '20px 24px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: 'var(--primary)' }}>
              <h2 style={{ fontSize: '16px', fontWeight: 700, color: '#fff', display: 'flex', alignItems: 'center', gap: '8px' }}>
                <Edit2 size={18} color="rgba(255,255,255,0.8)" /> Modify Record
              </h2>
              <button onClick={() => setIsModalOpen(false)} style={{ background: 'rgba(255,255,255,0.2)', border: 'none', width: '28px', height: '28px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', color: '#fff', transition: '0.2s' }} onMouseOver={e=>e.currentTarget.style.background='rgba(255,255,255,0.3)'} onMouseOut={e=>e.currentTarget.style.background='rgba(255,255,255,0.2)'}>
                <X size={16} />
              </button>
            </div>

            <form onSubmit={handleUpdate}>
              <div style={{ padding: '24px' }}>
                
                <div style={{ marginBottom: '16px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 600, marginBottom: '6px', color: 'var(--text-muted)' }}>BENEFICIARY NAME</label>
                  <div style={{ position: 'relative' }}>
                    <User size={16} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input type="text" required value={updateForm.name} onChange={(e) => setUpdateForm({...updateForm, name: e.target.value.toUpperCase()})} style={{ padding: '10px 10px 10px 36px', width: '100%', background: 'var(--background)', border: '1px solid var(--border)', borderRadius: '8px', fontSize: '13px', fontWeight: 600, outline: 'none', color: 'var(--text-main)', transition: '0.2s' }} onFocus={e=>e.currentTarget.style.borderColor='var(--primary)'} onBlur={e=>e.currentTarget.style.borderColor='var(--border)'} />
                  </div>
                </div>

                <div style={{ marginBottom: '16px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 600, marginBottom: '6px', color: 'var(--text-muted)' }}>SSIN NUMBER</label>
                  <div style={{ position: 'relative' }}>
                    <Hash size={16} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input type="text" required value={updateForm.ssin} onChange={(e) => setUpdateForm({...updateForm, ssin: e.target.value.replace(/[^0-9]/g, '').slice(0,12)})} style={{ padding: '10px 10px 10px 36px', width: '100%', background: 'var(--background)', border: '1px solid var(--border)', borderRadius: '8px', fontSize: '13px', fontWeight: 600, outline: 'none', color: 'var(--text-main)', transition: '0.2s', fontFamily: 'monospace' }} onFocus={e=>e.currentTarget.style.borderColor='var(--primary)'} onBlur={e=>e.currentTarget.style.borderColor='var(--border)'} />
                  </div>
                </div>

                <div style={{ marginBottom: '16px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 600, marginBottom: '6px', color: 'var(--text-muted)' }}>DATE OF ATTAINING 60</label>
                  <div style={{ position: 'relative' }}>
                    <Calendar size={16} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input type="date" required value={updateForm.dob} onChange={(e) => setUpdateForm({...updateForm, dob: e.target.value})} style={{ padding: '10px 10px 10px 36px', width: '100%', background: 'var(--background)', border: '1px solid var(--border)', borderRadius: '8px', fontSize: '13px', fontWeight: 600, outline: 'none', color: 'var(--text-main)', transition: '0.2s' }} onFocus={e=>e.currentTarget.style.borderColor='var(--primary)'} onBlur={e=>e.currentTarget.style.borderColor='var(--border)'} />
                  </div>
                </div>

                <div style={{ marginBottom: '8px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 600, marginBottom: '6px', color: 'var(--text-muted)' }}>PHONE NUMBER</label>
                  <div style={{ position: 'relative' }}>
                    <Smartphone size={16} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input type="text" required value={updateForm.phone} onChange={(e) => setUpdateForm({...updateForm, phone: e.target.value.replace(/[^0-9]/g, '').slice(0,10)})} style={{ padding: '10px 10px 10px 36px', width: '100%', background: 'var(--background)', border: '1px solid var(--border)', borderRadius: '8px', fontSize: '13px', fontWeight: 600, outline: 'none', color: 'var(--text-main)', transition: '0.2s' }} onFocus={e=>e.currentTarget.style.borderColor='var(--primary)'} onBlur={e=>e.currentTarget.style.borderColor='var(--border)'} />
                  </div>
                </div>

              </div>

              <div style={{ padding: '16px 24px', background: 'var(--background)', borderTop: '1px solid var(--border)', display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
                <button type="button" onClick={() => setIsModalOpen(false)} style={{ background: 'none', border: 'none', padding: '8px 16px', fontWeight: 600, color: 'var(--text-muted)', cursor: 'pointer', borderRadius: '100px', fontSize: '13px' }} onMouseOver={e=>e.currentTarget.style.background='rgba(0,0,0,0.05)'} onMouseOut={e=>e.currentTarget.style.background='none'}>Cancel</button>
                <button type="submit" disabled={isUpdating} style={{ padding: '8px 20px', borderRadius: '100px', display: 'flex', alignItems: 'center', gap: '8px', background: 'var(--primary)', color: '#fff', border: 'none', fontWeight: 600, cursor: isUpdating ? 'not-allowed' : 'pointer', opacity: isUpdating ? 0.7 : 1, fontSize: '13px', boxShadow: '0 4px 12px rgba(11, 87, 208, 0.2)' }}>
                  {isUpdating ? <><Loader2 size={16} className="spinner" /> Saving...</> : <><Save size={16} /> Save Changes</>}
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
