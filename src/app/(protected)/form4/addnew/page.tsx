'use client';

import { useState, useEffect, FormEvent } from 'react';
import { 
  initForm4Table, 
  saveForm4Entry, 
  getLatestForm4Entry, 
  searchForm4Entry, 
  updateForm4Entry 
} from '@/app/actions/form4';
import { 
  Save, Edit2, Search, X, Loader2, IndianRupee, Hash, User, BookOpen, Receipt, Calendar 
} from 'lucide-react';
import { Toast } from '@/lib/toast';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';

const MySwal = withReactContent(Swal);

export default function Form4AddNewPage() {
  const [loading, setLoading] = useState(false);
  const [latestEntry, setLatestEntry] = useState<any>(null);
  
  // Add Form State
  const [formData, setFormData] = useState({
    reg_no: '',
    beneficiary_name: '',
    book_no: '',
    receipt_no: '',
    for_month_from: '',
    for_month_to: '',
    date_of_collection: new Date().toISOString().split('T')[0],
    amount: ''
  });

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [searchReg, setSearchReg] = useState('');
  const [searchBook, setSearchBook] = useState('');
  const [searching, setSearching] = useState(false);
  
  const [editData, setEditData] = useState<any>(null);
  const [updating, setUpdating] = useState(false);

  useEffect(() => {
    // Initialize DB table
    initForm4Table().then(() => fetchLatest());
  }, []);

  const fetchLatest = async () => {
    const latest = await getLatestForm4Entry();
    setLatestEntry(latest);
  };

  const handleSave = async (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);

    const res = await saveForm4Entry({
      ...formData,
      amount: parseFloat(formData.amount)
    });

    setLoading(false);

    if (res.error) {
      Toast.fire({ title: res.error, icon: 'error' });
    } else {
      Toast.fire({ title: 'Entry saved successfully!', icon: 'success' });
      setFormData({
        ...formData,
        reg_no: '',
        beneficiary_name: '',
        book_no: '',
        receipt_no: '',
        amount: ''
      });
      // Focus reg_no
      document.getElementById('reg_no')?.focus();
      fetchLatest();
    }
  };

  const handleSearchEdit = async () => {
    if (!searchReg || !searchBook) {
      Toast.fire({ title: 'Enter Reg No and Book No', icon: 'warning' });
      return;
    }
    setSearching(true);
    const result = await searchForm4Entry(searchReg, searchBook);
    setSearching(false);

    if (result) {
      setEditData(result);
    } else {
      setEditData(null);
      Toast.fire({ title: 'No entry found', icon: 'error' });
    }
  };

  const handleUpdate = async (e: FormEvent) => {
    e.preventDefault();
    if (!editData) return;
    
    setUpdating(true);
    const res = await updateForm4Entry(editData.id, {
      beneficiary_name: editData.beneficiary_name,
      amount: parseFloat(editData.amount)
    });
    setUpdating(false);

    if (res.error) {
      Toast.fire({ title: res.error, icon: 'error' });
    } else {
      Toast.fire({ title: 'Updated Successfully!', icon: 'success' });
      setIsModalOpen(false);
      setEditData(null);
      fetchLatest();
    }
  };

  const formatDate = (dateStr: string) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  };

  return (
    <>
      <header className="app-topbar" style={{ background: 'var(--surface)', backdropFilter: 'blur(10px)', borderBottom: '1px solid rgba(0,0,0,0.05)' }}>
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '18px' }}>
            <span style={{ background: 'rgba(13, 148, 136, 0.1)', color: '#0d9488', padding: '6px 12px', borderRadius: '8px', fontWeight: 700 }}>Form 4</span>
            Add New Entry
          </h1>
        </div>
      </header>

      <div className="content-scroll" style={{ padding: '20px' }}>
        <div style={{ maxWidth: '900px', margin: '0 auto', width: '100%' }}>
          
          {/* Ticker Card */}
          <div className="md-card" style={{ padding: '16px 24px', borderLeft: '4px solid #0d9488', marginBottom: '24px', background: 'var(--surface)', borderRadius: '16px', boxShadow: '0 4px 12px rgba(0,0,0,0.03)' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
              <div style={{ fontSize: '12px', fontWeight: 700, textTransform: 'uppercase', color: '#0d9488', letterSpacing: '0.5px' }}>Latest Saved Entry</div>
              <button 
                onClick={() => {
                  if (latestEntry) {
                    setSearchReg(latestEntry.reg_no);
                    setSearchBook(latestEntry.book_no);
                  }
                  setIsModalOpen(true);
                }} 
                style={{ background: 'rgba(13, 148, 136, 0.1)', color: '#0d9488', border: 'none', width: '32px', height: '32px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', transition: '0.2s' }}
                onMouseOver={(e) => e.currentTarget.style.transform = 'scale(1.05)'}
                onMouseOut={(e) => e.currentTarget.style.transform = 'scale(1)'}
              >
                <Edit2 size={16} />
              </button>
            </div>
            
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px', fontSize: '15px', fontWeight: 500, flexWrap: 'wrap' }}>
              {latestEntry ? (
                <>
                  <div style={{ width: '10px', height: '10px', borderRadius: '50%', background: '#0d9488', animation: 'pulse 1.5s infinite' }}></div>
                  <span style={{ fontFamily: 'monospace', color: '#0d9488', background: 'rgba(13, 148, 136, 0.1)', padding: '2px 8px', borderRadius: '4px' }}>{latestEntry.reg_no}</span>
                  <strong style={{ color: 'var(--text-main)' }}>{latestEntry.beneficiary_name}</strong>
                  <span style={{ opacity: 0.5 }}>|</span>
                  <span>Book: <strong style={{ color: 'var(--text-main)' }}>{latestEntry.book_no}</strong></span>
                  <span style={{ opacity: 0.5 }}>|</span>
                  <span>Rec: <strong style={{ color: 'var(--text-main)' }}>{latestEntry.receipt_no}</strong></span>
                  <span style={{ opacity: 0.5 }}>|</span>
                  <span style={{ color: 'var(--success)', fontWeight: 700 }}>₹{parseFloat(latestEntry.amount).toFixed(2)}</span>
                  <span style={{ fontSize: '12px', color: 'var(--text-muted)', width: '100%', marginTop: '4px' }}>
                    For Period: <strong style={{ color: 'var(--text-main)' }}>{latestEntry.for_month}</strong> • Collected: {formatDate(latestEntry.date_of_collection)}
                  </span>
                </>
              ) : (
                <span style={{ color: 'var(--text-muted)' }}>No entries saved yet. Fill the form below to start.</span>
              )}
            </div>
          </div>

          {/* Form Card */}
          <div className="md-card" style={{ padding: '32px', background: 'var(--surface)', borderRadius: '16px', boxShadow: '0 4px 12px rgba(0,0,0,0.03)' }}>
            <form onSubmit={handleSave}>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px' }}>
                
                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>Registration No.</label>
                  <div style={{ position: 'relative' }}>
                    <Hash size={18} style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input type="text" id="reg_no" required value={formData.reg_no} onChange={(e) => setFormData({...formData, reg_no: e.target.value})} style={{ width: '100%', background: 'var(--background)', border: '2px solid transparent', borderRadius: '12px', padding: '14px 16px 14px 44px', fontSize: '15px', color: 'var(--text-main)', outline: 'none', fontFamily: 'monospace', transition: '0.2s' }} onFocus={e => e.currentTarget.style.borderColor = '#0d9488'} onBlur={e => e.currentTarget.style.borderColor = 'transparent'} placeholder="Enter Reg No" autoFocus />
                  </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>Name of Beneficiary</label>
                  <div style={{ position: 'relative' }}>
                    <User size={18} style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input type="text" required value={formData.beneficiary_name} onChange={(e) => setFormData({...formData, beneficiary_name: e.target.value.toUpperCase()})} style={{ width: '100%', background: 'var(--background)', border: '2px solid transparent', borderRadius: '12px', padding: '14px 16px 14px 44px', fontSize: '15px', color: 'var(--text-main)', outline: 'none', textTransform: 'uppercase', transition: '0.2s' }} onFocus={e => e.currentTarget.style.borderColor = '#0d9488'} onBlur={e => e.currentTarget.style.borderColor = 'transparent'} placeholder="Full Name" />
                  </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>Book No.</label>
                  <div style={{ position: 'relative' }}>
                    <BookOpen size={18} style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input type="number" required value={formData.book_no} onChange={(e) => setFormData({...formData, book_no: e.target.value})} style={{ width: '100%', background: 'var(--background)', border: '2px solid transparent', borderRadius: '12px', padding: '14px 16px 14px 44px', fontSize: '15px', color: 'var(--text-main)', outline: 'none', transition: '0.2s' }} onFocus={e => e.currentTarget.style.borderColor = '#0d9488'} onBlur={e => e.currentTarget.style.borderColor = 'transparent'} placeholder="e.g. 1024" />
                  </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>Receipt No.</label>
                  <div style={{ position: 'relative' }}>
                    <Receipt size={18} style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input type="number" required value={formData.receipt_no} onChange={(e) => setFormData({...formData, receipt_no: e.target.value})} style={{ width: '100%', background: 'var(--background)', border: '2px solid transparent', borderRadius: '12px', padding: '14px 16px 14px 44px', fontSize: '15px', color: 'var(--text-main)', outline: 'none', transition: '0.2s' }} onFocus={e => e.currentTarget.style.borderColor = '#0d9488'} onBlur={e => e.currentTarget.style.borderColor = 'transparent'} placeholder="e.g. 549" />
                  </div>
                </div>

                <div style={{ gridColumn: '1 / -1', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>For the Period (Date to Date)</label>
                  <div style={{ display: 'flex', gap: '16px', alignItems: 'center', flexWrap: 'wrap' }}>
                    <div style={{ position: 'relative', flex: 1, minWidth: '200px' }}>
                      <Calendar size={18} style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                      <input type="date" required value={formData.for_month_from} onChange={(e) => setFormData({...formData, for_month_from: e.target.value})} style={{ width: '100%', background: 'var(--background)', border: '2px solid transparent', borderRadius: '12px', padding: '14px 16px 14px 44px', fontSize: '15px', color: 'var(--text-main)', outline: 'none', transition: '0.2s' }} onFocus={e => e.currentTarget.style.borderColor = '#0d9488'} onBlur={e => e.currentTarget.style.borderColor = 'transparent'} />
                    </div>
                    <div style={{ fontWeight: 700, color: 'var(--text-muted)', fontSize: '14px' }}>TO</div>
                    <div style={{ position: 'relative', flex: 1, minWidth: '200px' }}>
                      <Calendar size={18} style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                      <input type="date" required value={formData.for_month_to} onChange={(e) => setFormData({...formData, for_month_to: e.target.value})} style={{ width: '100%', background: 'var(--background)', border: '2px solid transparent', borderRadius: '12px', padding: '14px 16px 14px 44px', fontSize: '15px', color: 'var(--text-main)', outline: 'none', transition: '0.2s' }} onFocus={e => e.currentTarget.style.borderColor = '#0d9488'} onBlur={e => e.currentTarget.style.borderColor = 'transparent'} />
                    </div>
                  </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>Date of Collection</label>
                  <div style={{ position: 'relative' }}>
                    <Calendar size={18} style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input type="date" required value={formData.date_of_collection} onChange={(e) => setFormData({...formData, date_of_collection: e.target.value})} style={{ width: '100%', background: 'var(--background)', border: '2px solid transparent', borderRadius: '12px', padding: '14px 16px 14px 44px', fontSize: '15px', color: 'var(--text-main)', outline: 'none', transition: '0.2s' }} onFocus={e => e.currentTarget.style.borderColor = '#0d9488'} onBlur={e => e.currentTarget.style.borderColor = 'transparent'} />
                  </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)' }}>Amount</label>
                  <div style={{ position: 'relative' }}>
                    <IndianRupee size={18} style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input type="number" step="0.01" required value={formData.amount} onChange={(e) => setFormData({...formData, amount: e.target.value})} style={{ width: '100%', background: 'var(--background)', border: '2px solid transparent', borderRadius: '12px', padding: '14px 16px 14px 44px', fontSize: '18px', fontWeight: 700, color: 'var(--text-main)', outline: 'none', fontFamily: 'monospace', transition: '0.2s' }} onFocus={e => e.currentTarget.style.borderColor = '#0d9488'} onBlur={e => e.currentTarget.style.borderColor = 'transparent'} placeholder="0.00" />
                  </div>
                </div>

                <div style={{ gridColumn: '1 / -1', marginTop: '16px' }}>
                  <button type="submit" disabled={loading} style={{ width: '100%', background: '#0d9488', color: '#fff', padding: '16px', borderRadius: '100px', fontSize: '16px', fontWeight: 600, border: 'none', cursor: loading ? 'not-allowed' : 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', boxShadow: '0 4px 12px rgba(13, 148, 136, 0.2)', transition: '0.2s', opacity: loading ? 0.7 : 1 }} onMouseOver={e => e.currentTarget.style.transform = loading ? 'none' : 'scale(0.99)'} onMouseOut={e => e.currentTarget.style.transform = 'scale(1)'}>
                    {loading ? <><Loader2 size={20} className="spinner" /> Saving Entry...</> : <><Save size={20} /> Save Entry</>}
                  </button>
                </div>

              </div>
            </form>
          </div>
        </div>
        <div style={{ height: '40px' }}></div>
      </div>

      {/* Edit Modal */}
      {isModalOpen && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', backdropFilter: 'blur(4px)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '24px', animation: 'fadeIn 0.2s ease forwards' }}>
          <div style={{ background: 'var(--surface)', width: '100%', maxWidth: '500px', borderRadius: '24px', padding: '24px', boxShadow: '0 10px 40px rgba(0,0,0,0.2)', animation: 'slideUp 0.3s cubic-bezier(0.2, 0, 0, 1) forwards' }}>
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px', borderBottom: '1px solid var(--border)', paddingBottom: '16px' }}>
              <div style={{ fontSize: '18px', fontWeight: 700, color: 'var(--text-main)' }}>Search & Edit Entry</div>
              <button onClick={() => { setIsModalOpen(false); setEditData(null); }} style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text-muted)' }}>
                <X size={24} />
              </button>
            </div>
            
            <div style={{ display: 'flex', gap: '12px', marginBottom: '24px', flexWrap: 'wrap' }}>
              <input type="text" value={searchReg} onChange={(e) => setSearchReg(e.target.value)} placeholder="Reg No" style={{ flex: 1, padding: '12px 16px', background: 'var(--background)', border: '1px solid var(--border)', borderRadius: '12px', fontFamily: 'monospace', outline: 'none', color: 'var(--text-main)' }} />
              <input type="text" value={searchBook} onChange={(e) => setSearchBook(e.target.value)} placeholder="Book No" style={{ flex: 1, padding: '12px 16px', background: 'var(--background)', border: '1px solid var(--border)', borderRadius: '12px', outline: 'none', color: 'var(--text-main)' }} />
              <button onClick={handleSearchEdit} disabled={searching} style={{ background: '#0d9488', color: '#fff', border: 'none', borderRadius: '12px', padding: '0 20px', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                {searching ? <Loader2 size={20} className="spinner" /> : <Search size={20} />}
              </button>
            </div>

            {editData && (
              <form onSubmit={handleUpdate} style={{ animation: 'fadeIn 0.3s' }}>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                  <div>
                    <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)', marginBottom: '8px', display: 'block' }}>Beneficiary Name</label>
                    <input type="text" required value={editData.beneficiary_name} onChange={(e) => setEditData({...editData, beneficiary_name: e.target.value.toUpperCase()})} style={{ width: '100%', padding: '14px 16px', background: 'var(--background)', border: '1px solid var(--border)', borderRadius: '12px', textTransform: 'uppercase', outline: 'none', color: 'var(--text-main)' }} />
                  </div>
                  <div>
                    <label style={{ fontSize: '13px', fontWeight: 600, color: 'var(--text-main)', marginBottom: '8px', display: 'block' }}>Amount</label>
                    <div style={{ position: 'relative' }}>
                      <span style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', fontWeight: 700, color: 'var(--text-muted)' }}>₹</span>
                      <input type="number" step="0.01" required value={editData.amount} onChange={(e) => setEditData({...editData, amount: e.target.value})} style={{ width: '100%', padding: '14px 16px 14px 40px', background: 'var(--background)', border: '1px solid var(--border)', borderRadius: '12px', outline: 'none', color: 'var(--text-main)', fontFamily: 'monospace', fontWeight: 700, fontSize: '16px' }} />
                    </div>
                  </div>
                </div>

                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '24px', borderTop: '1px solid var(--border)', paddingTop: '16px' }}>
                  <button type="button" onClick={() => setEditData(null)} style={{ background: 'transparent', color: 'var(--text-muted)', border: 'none', fontWeight: 600, padding: '10px 16px', cursor: 'pointer', borderRadius: '100px' }}>Cancel</button>
                  <button type="submit" disabled={updating} style={{ background: '#0d9488', color: '#fff', border: 'none', borderRadius: '100px', padding: '10px 24px', fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '8px' }}>
                    {updating ? <><Loader2 size={16} className="spinner" /> Updating...</> : 'Update Entry'}
                  </button>
                </div>
              </form>
            )}

          </div>
        </div>
      )}

      <style dangerouslySetInnerHTML={{__html: `
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(13, 148, 136, 0.4); } 70% { box-shadow: 0 0 0 8px rgba(13, 148, 136, 0); } 100% { box-shadow: 0 0 0 0 rgba(13, 148, 136, 0); } }
        .spinner { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
      `}} />
    </>
  );
}
