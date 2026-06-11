'use client';

import { useState } from 'react';
import { checkSSINAction, submitSSINAction } from '@/app/actions/addNew';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { Search, PlusCircle, CheckCircle2, AlertCircle, FileCheck, RefreshCw, Smartphone, Calendar, User, Hash, Loader2, Sparkles } from 'lucide-react';

const MySwal = withReactContent(Swal);

export default function AddNewPage() {
  const [ssin, setSsin] = useState('');
  const [isChecking, setIsChecking] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const [showNewData, setShowNewData] = useState(false);
  const [showExistingData, setShowExistingData] = useState(false);
  const [profile, setProfile] = useState<any>(null);

  // Form Fields
  const [name, setName] = useState('');
  const [dateOf60, setDateOf60] = useState('');
  const [phone, setPhone] = useState('');

  const Toast = MySwal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
  });

  const handleCheck = async () => {
    if (ssin.length !== 12) {
      Toast.fire({ icon: 'error', title: 'SSIN must be exactly 12 digits' });
      return;
    }

    setIsChecking(true);
    
    // Optional: keeping the SweetAlert minimal if they still want it, but the button loader is now primary
    const result = await checkSSINAction(ssin) as any;
    
    setIsChecking(false);

    if (result.error) {
      Toast.fire({ icon: 'error', title: result.error });
      return;
    }

    if (result.exists) {
      setProfile(result);
      setShowExistingData(true);
      setShowNewData(false);
      Toast.fire({ icon: 'success', title: 'SSIN Found in Database' });
    } else {
      setShowExistingData(false);
      setShowNewData(true);
      Toast.fire({ icon: 'info', title: 'New SSIN. Please fill the details.' });
    }
  };

  const handleGenerateDate = () => {
    const start = new Date(2043, 0, 1).getTime();
    const end = new Date(2052, 0, 1).getTime();
    const randomDate = new Date(start + Math.random() * (end - start));
    setDateOf60(randomDate.toISOString().split('T')[0]);
  };

  const handleGeneratePhone = () => {
    const randomPhone = '9' + Math.floor(100000000 + Math.random() * 900000000);
    setPhone(randomPhone);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    setIsSubmitting(true);

    const formData = new FormData();
    formData.append('ssin', ssin);
    formData.append('name', name);
    formData.append('date', dateOf60);
    formData.append('phone', phone);

    const result = await submitSSINAction(formData) as any;
    
    setIsSubmitting(false);

    if (result.error) {
      Toast.fire({ icon: 'error', title: result.error });
    } else {
      Toast.fire({ icon: 'success', title: 'Data Saved Successfully!' });
      // Reset Form
      setSsin('');
      setName('');
      setDateOf60('');
      setPhone('');
      setShowNewData(false);
    }
  };

  return (
    <>
      <header className="app-topbar">
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <PlusCircle size={28} style={{ color: 'var(--primary)' }} />
            Add New SSIN
          </h1>
        </div>
      </header>

      <div className="content-scroll">
        <div style={{ maxWidth: '800px', margin: '0 auto', width: '100%' }}>
          
          <div className="md-card" style={{ 
            background: 'var(--surface)', 
            backdropFilter: 'blur(10px)', 
            border: '1px solid rgba(11, 87, 208, 0.1)', 
            boxShadow: '0 8px 32px rgba(0,0,0,0.05)',
            position: 'relative'
          }}>
            {/* Futuristic Glow Orbs */}
            <div style={{ position: 'absolute', top: '-50px', right: '-50px', width: '150px', height: '150px', background: 'var(--primary)', filter: 'blur(80px)', opacity: 0.1, borderRadius: '50%', pointerEvents: 'none' }}></div>
            <div style={{ position: 'absolute', bottom: '-50px', left: '-50px', width: '150px', height: '150px', background: 'var(--color-success)', filter: 'blur(80px)', opacity: 0.1, borderRadius: '50%', pointerEvents: 'none' }}></div>

            <div style={{ position: 'relative', zIndex: 1 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '24px', fontSize: '20px', fontWeight: 700, color: 'var(--primary)' }}>
                <Sparkles size={28} />
                SSIN PORTAL <small style={{ color: 'var(--text-muted)', fontSize: '14px', fontWeight: 400 }}>DATA ENTRY</small>
              </div>

              <form onSubmit={handleSubmit}>
                <div className="form-group" style={{ marginBottom: '20px' }}>
                  <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px', color: 'var(--text-main)' }}>SSIN Number</label>
                  <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                    <Hash size={20} style={{ position: 'absolute', left: '16px', color: 'var(--primary)' }} />
                    <input 
                      type="text" 
                      value={ssin}
                      onChange={(e) => setSsin(e.target.value.replace(/[^0-9]/g, '').slice(0, 12))}
                      placeholder="Enter 12-digit SSIN" 
                      readOnly={showNewData || isChecking || isSubmitting}
                      className="app-input"
                      style={{ 
                        paddingLeft: '48px', 
                        width: '100%', 
                        opacity: (showNewData || isChecking || isSubmitting) ? 0.6 : 1,
                        background: 'rgba(0,0,0,0.02)',
                        border: '2px solid rgba(11, 87, 208, 0.15)',
                        transition: 'all 0.3s ease'
                      }}
                      onFocus={(e) => e.target.style.borderColor = 'var(--primary)'}
                      onBlur={(e) => e.target.style.borderColor = 'rgba(11, 87, 208, 0.15)'}
                    />
                  </div>
                </div>

                {/* NEW DATA FORM */}
                {showNewData && (
                  <div style={{ animation: 'fadeIn 0.4s ease forwards' }}>
                    <div className="form-group" style={{ marginBottom: '20px' }}>
                      <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px', color: 'var(--text-main)' }}>Beneficiary Name</label>
                      <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                        <User size={20} style={{ position: 'absolute', left: '16px', color: 'var(--primary)' }} />
                        <input 
                          type="text" 
                          required
                          value={name}
                          onChange={(e) => setName(e.target.value.toUpperCase())}
                          placeholder="ENTER NAME" 
                          className="app-input"
                          disabled={isSubmitting}
                          style={{ 
                            paddingLeft: '48px', width: '100%', background: 'rgba(0,0,0,0.02)', border: '2px solid rgba(11, 87, 208, 0.15)'
                          }}
                          onFocus={(e) => e.target.style.borderColor = 'var(--primary)'}
                          onBlur={(e) => e.target.style.borderColor = 'rgba(11, 87, 208, 0.15)'}
                        />
                      </div>
                    </div>

                    <div className="form-group" style={{ marginBottom: '20px' }}>
                      <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px', color: 'var(--text-main)' }}>Date of Attaining 60</label>
                      <div style={{ display: 'flex', boxShadow: '0 2px 8px rgba(0,0,0,0.04)', borderRadius: '16px' }}>
                        <div style={{ position: 'relative', display: 'flex', alignItems: 'center', flex: 1 }}>
                          <Calendar size={20} style={{ position: 'absolute', left: '16px', color: 'var(--primary)' }} />
                          <input 
                            type="date" 
                            required
                            value={dateOf60}
                            onChange={(e) => setDateOf60(e.target.value)}
                            className="app-input"
                            disabled={isSubmitting}
                            style={{ paddingLeft: '48px', width: '100%', borderTopRightRadius: 0, borderBottomRightRadius: 0, background: 'rgba(0,0,0,0.02)', border: '2px solid rgba(11, 87, 208, 0.15)', borderRight: 'none' }}
                          />
                        </div>
                        <button 
                          type="button" 
                          onClick={handleGenerateDate}
                          disabled={isSubmitting}
                          style={{ padding: '0 20px', background: 'rgba(11, 87, 208, 0.05)', border: '2px solid rgba(11, 87, 208, 0.15)', borderLeft: '1px solid rgba(11, 87, 208, 0.1)', borderTopRightRadius: '16px', borderBottomRightRadius: '16px', cursor: 'pointer', transition: 'background 0.2s' }}
                          onMouseOver={(e) => e.currentTarget.style.background = 'rgba(11, 87, 208, 0.1)'}
                          onMouseOut={(e) => e.currentTarget.style.background = 'rgba(11, 87, 208, 0.05)'}
                        >
                          <RefreshCw size={20} color="var(--primary)" />
                        </button>
                      </div>
                    </div>

                    <div className="form-group" style={{ marginBottom: '20px' }}>
                      <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px', color: 'var(--text-main)' }}>Phone Number</label>
                      <div style={{ display: 'flex', boxShadow: '0 2px 8px rgba(0,0,0,0.04)', borderRadius: '16px' }}>
                        <div style={{ position: 'relative', display: 'flex', alignItems: 'center', flex: 1 }}>
                          <Smartphone size={20} style={{ position: 'absolute', left: '16px', color: 'var(--primary)' }} />
                          <input 
                            type="text" 
                            required
                            value={phone}
                            onChange={(e) => setPhone(e.target.value.replace(/[^0-9]/g, '').slice(0, 10))}
                            placeholder="Enter Phone Number"
                            className="app-input"
                            disabled={isSubmitting}
                            style={{ paddingLeft: '48px', width: '100%', borderTopRightRadius: 0, borderBottomRightRadius: 0, background: 'rgba(0,0,0,0.02)', border: '2px solid rgba(11, 87, 208, 0.15)', borderRight: 'none' }}
                          />
                        </div>
                        <button 
                          type="button" 
                          onClick={handleGeneratePhone}
                          disabled={isSubmitting}
                          style={{ padding: '0 20px', background: 'rgba(11, 87, 208, 0.05)', border: '2px solid rgba(11, 87, 208, 0.15)', borderLeft: '1px solid rgba(11, 87, 208, 0.1)', borderTopRightRadius: '16px', borderBottomRightRadius: '16px', cursor: 'pointer', transition: 'background 0.2s' }}
                          onMouseOver={(e) => e.currentTarget.style.background = 'rgba(11, 87, 208, 0.1)'}
                          onMouseOut={(e) => e.currentTarget.style.background = 'rgba(11, 87, 208, 0.05)'}
                        >
                          <RefreshCw size={20} color="var(--primary)" />
                        </button>
                      </div>
                    </div>
                  </div>
                )}

                <div style={{ marginTop: '32px' }}>
                  {!showNewData ? (
                    <button 
                      type="button" 
                      onClick={handleCheck} 
                      disabled={isChecking || ssin.length !== 12} 
                      className="btn-primary" 
                      style={{ 
                        width: '100%', padding: '16px',
                        background: 'linear-gradient(90deg, #0b57d0, #2b70e4)',
                        boxShadow: '0 8px 24px rgba(11, 87, 208, 0.3)',
                        opacity: (isChecking || ssin.length !== 12) ? 0.7 : 1
                      }}
                    >
                      {isChecking ? (
                        <><Loader2 className="spinner" size={20} /> Checking SSIN...</>
                      ) : (
                        <><Search size={20} /> Verify Database</>
                      )}
                    </button>
                  ) : (
                    <button 
                      type="submit" 
                      disabled={isSubmitting}
                      className="btn-primary" 
                      style={{ 
                        width: '100%', padding: '16px', 
                        background: 'linear-gradient(90deg, #146c2e, #2e8b49)',
                        boxShadow: '0 8px 24px rgba(20, 108, 46, 0.3)',
                        opacity: isSubmitting ? 0.7 : 1
                      }}
                    >
                      {isSubmitting ? (
                        <><Loader2 className="spinner" size={20} /> Processing Data...</>
                      ) : (
                        <><CheckCircle2 size={20} /> Submit & Create Entry</>
                      )}
                    </button>
                  )}
                </div>
              </form>
            </div>
          </div>

          {/* EXISTING DATA PROFILE */}
          {showExistingData && profile && (
            <div style={{ animation: 'fadeIn 0.5s cubic-bezier(0.2, 0, 0, 1) forwards' }}>
              <div style={{
                background: profile.status.toLowerCase() === 'active' ? 'linear-gradient(135deg, #146c2e, #2e8b49)' : 
                            profile.status.toLowerCase() === 'rejected' ? 'linear-gradient(135deg, #b3261e, #dc362d)' : 
                            'linear-gradient(135deg, #0b57d0, #4285f4)',
                borderRadius: '24px', padding: '24px', color: '#fff', display: 'flex', gap: '20px', marginBottom: '24px',
                boxShadow: '0 12px 32px rgba(0,0,0,0.15)'
              }}>
                <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: 'rgba(255,255,255,0.2)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, backdropFilter: 'blur(5px)' }}>
                  <User size={32} color="#fff" />
                </div>
                <div style={{ flex: 1 }}>
                  <h4 style={{ fontSize: '20px', marginBottom: '16px', borderBottom: '1px solid rgba(255,255,255,0.3)', paddingBottom: '8px', display: 'flex', justifyContent: 'space-between' }}>
                    SSIN Profile
                    <span style={{ fontSize: '12px', background: 'rgba(255,255,255,0.2)', padding: '4px 10px', borderRadius: '100px' }}>VERIFIED</span>
                  </h4>
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '12px', fontSize: '14px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}><span><Hash size={16} style={{display:'inline', verticalAlign:'middle', marginRight:'4px'}}/> SSIN:</span> <strong style={{ letterSpacing: '1px' }}>{profile.ssin}</strong></div>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}><span><User size={16} style={{display:'inline', verticalAlign:'middle', marginRight:'4px'}}/> Name:</span> <strong>{profile.name}</strong></div>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}><span><Calendar size={16} style={{display:'inline', verticalAlign:'middle', marginRight:'4px'}}/> Age 60 Date:</span> <strong>{profile.date_of_attaining_60}</strong></div>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}><span><Smartphone size={16} style={{display:'inline', verticalAlign:'middle', marginRight:'4px'}}/> Mobile:</span> <strong>{profile.phone_no}</strong></div>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}><span><AlertCircle size={16} style={{display:'inline', verticalAlign:'middle', marginRight:'4px'}}/> Status:</span> <strong style={{textTransform:'uppercase', background: 'rgba(255,255,255,0.2)', padding: '2px 8px', borderRadius: '6px'}}>{profile.status}</strong></div>
                  </div>
                </div>
              </div>

              {/* PF TABLE */}
              {profile.pf_updates && profile.pf_updates.length > 0 && (
                <div className="md-card" style={{ border: 'none', boxShadow: '0 12px 32px rgba(0,0,0,0.08)' }}>
                  <div style={{ fontSize: '16px', fontWeight: 600, marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px', color: 'var(--primary)' }}>
                    <FileCheck size={20} /> PF Updates Ledger
                  </div>
                  <div style={{ overflowX: 'auto', borderRadius: '16px', border: '1px solid rgba(11, 87, 208, 0.1)' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '14px' }}>
                      <thead style={{ background: 'rgba(11, 87, 208, 0.05)', textAlign: 'left', color: 'var(--primary)' }}>
                        <tr>
                          <th style={{ padding: '16px' }}>#</th>
                          <th style={{ padding: '16px' }}>Period From</th>
                          <th style={{ padding: '16px' }}>Period To</th>
                          <th style={{ padding: '16px', textAlign: 'center' }}>Months</th>
                          <th style={{ padding: '16px', textAlign: 'right' }}>Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        {profile.pf_updates.map((pf: any, idx: number) => (
                          <tr key={idx} style={{ borderTop: '1px solid rgba(11, 87, 208, 0.1)', transition: 'background 0.2s' }} onMouseOver={(e) => e.currentTarget.style.background = 'rgba(0,0,0,0.01)'} onMouseOut={(e) => e.currentTarget.style.background = 'transparent'}>
                            <td style={{ padding: '16px' }}>{idx + 1}</td>
                            <td style={{ padding: '16px', fontWeight: 500 }}>{pf.period_from}</td>
                            <td style={{ padding: '16px', fontWeight: 500 }}>{pf.period_to}</td>
                            <td style={{ padding: '16px', textAlign: 'center' }}>
                              <span style={{ background: 'rgba(11, 87, 208, 0.1)', color: 'var(--primary)', padding: '4px 10px', borderRadius: '100px', fontWeight: 600 }}>{pf.months}</span>
                            </td>
                            <td style={{ padding: '16px', textAlign: 'right', fontWeight: 700, color: 'var(--text-main)' }}>₹{pf.amount.toFixed(2)}</td>
                          </tr>
                        ))}
                      </tbody>
                      <tfoot>
                        <tr style={{ background: 'rgba(20, 108, 46, 0.05)', fontWeight: 700 }}>
                          <td colSpan={4} style={{ padding: '16px', textAlign: 'right', color: 'var(--color-success)' }}>TOTAL AMOUNT:</td>
                          <td style={{ padding: '16px', textAlign: 'right', color: 'var(--color-success)', fontSize: '16px' }}>₹{profile.total_amount.toFixed(2)}</td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              )}

            </div>
          )}

        </div>
        <div style={{ height: '40px' }}></div>
      </div>
      <style dangerouslySetInnerHTML={{__html: `
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .spinner { animation: spin 1s linear infinite; }
      `}} />
    </>
  );
}
