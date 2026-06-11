'use client';

import { useState } from 'react';
import { checkSSINAction, submitSSINAction } from '@/app/actions/addNew';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { Search, PlusCircle, CheckCircle2, AlertCircle, FileCheck, RefreshCw, Smartphone, Calendar, User, Hash } from 'lucide-react';

const MySwal = withReactContent(Swal);

export default function AddNewPage() {
  const [ssin, setSsin] = useState('');
  const [isChecking, setIsChecking] = useState(false);
  
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
    
    // Show SweetAlert Loading Spinner for "Compiling time" feel
    MySwal.fire({
      title: 'Checking Database...',
      text: 'Please wait while we verify the SSIN.',
      allowOutsideClick: false,
      didOpen: () => {
        MySwal.showLoading();
      }
    });

    const result = await checkSSINAction(ssin);
    
    MySwal.close(); // Close loading spinner
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
    
    const formData = new FormData();
    formData.append('ssin', ssin);
    formData.append('name', name);
    formData.append('date', dateOf60);
    formData.append('phone', phone);

    MySwal.fire({
      title: 'Submitting Data...',
      text: 'Please wait...',
      allowOutsideClick: false,
      didOpen: () => {
        MySwal.showLoading();
      }
    });

    const result = await submitSSINAction(formData);
    
    MySwal.close();

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
      <div className="header">
        <div>
          <h1 className="page-title" style={{ fontSize: '1.5rem', fontWeight: 700, display: 'flex', alignItems: 'center', gap: '10px' }}>
            <PlusCircle size={28} style={{ color: 'var(--primary)' }} />
            Add New SSIN
          </h1>
        </div>
      </div>

      <div className="content-scroll">
        <div style={{ maxWidth: '800px', margin: '0 auto', width: '100%' }}>
          
          <div className="md-card">
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '24px', fontSize: '20px', fontWeight: 700, color: 'var(--primary)' }}>
              <Hash size={28} />
              ADD DATA <small style={{ color: 'var(--text-muted)', fontSize: '14px', fontWeight: 400 }}>SSIN</small>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="form-group" style={{ marginBottom: '20px' }}>
                <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }}>SSIN Number</label>
                <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                  <Hash size={20} style={{ position: 'absolute', left: '16px', color: 'var(--text-muted)' }} />
                  <input 
                    type="text" 
                    value={ssin}
                    onChange={(e) => setSsin(e.target.value.replace(/[^0-9]/g, '').slice(0, 12))}
                    placeholder="Enter 12-digit SSIN" 
                    readOnly={showNewData}
                    className="app-input"
                    style={{ paddingLeft: '48px', width: '100%', opacity: showNewData ? 0.6 : 1 }}
                  />
                </div>
              </div>

              {/* NEW DATA FORM */}
              {showNewData && (
                <div style={{ animation: 'fadeIn 0.4s ease forwards' }}>
                  <div className="form-group" style={{ marginBottom: '20px' }}>
                    <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }}>Name</label>
                    <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                      <User size={20} style={{ position: 'absolute', left: '16px', color: 'var(--text-muted)' }} />
                      <input 
                        type="text" 
                        required
                        value={name}
                        onChange={(e) => setName(e.target.value.toUpperCase())}
                        placeholder="ENTER NAME" 
                        className="app-input"
                        style={{ paddingLeft: '48px', width: '100%' }}
                      />
                    </div>
                  </div>

                  <div className="form-group" style={{ marginBottom: '20px' }}>
                    <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }}>Date of Attaining 60</label>
                    <div style={{ display: 'flex' }}>
                      <div style={{ position: 'relative', display: 'flex', alignItems: 'center', flex: 1 }}>
                        <Calendar size={20} style={{ position: 'absolute', left: '16px', color: 'var(--text-muted)' }} />
                        <input 
                          type="date" 
                          required
                          value={dateOf60}
                          onChange={(e) => setDateOf60(e.target.value)}
                          className="app-input"
                          style={{ paddingLeft: '48px', width: '100%', borderTopRightRadius: 0, borderBottomRightRadius: 0 }}
                        />
                      </div>
                      <button 
                        type="button" 
                        onClick={handleGenerateDate}
                        style={{ padding: '0 20px', background: 'var(--surface-hover)', border: '1px solid var(--border)', borderLeft: 'none', borderTopRightRadius: '16px', borderBottomRightRadius: '16px', cursor: 'pointer' }}
                      >
                        <RefreshCw size={20} color="var(--text-muted)" />
                      </button>
                    </div>
                  </div>

                  <div className="form-group" style={{ marginBottom: '20px' }}>
                    <label style={{ display: 'block', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }}>Phone Number</label>
                    <div style={{ display: 'flex' }}>
                      <div style={{ position: 'relative', display: 'flex', alignItems: 'center', flex: 1 }}>
                        <Smartphone size={20} style={{ position: 'absolute', left: '16px', color: 'var(--text-muted)' }} />
                        <input 
                          type="text" 
                          required
                          value={phone}
                          onChange={(e) => setPhone(e.target.value.replace(/[^0-9]/g, '').slice(0, 10))}
                          placeholder="Enter Phone Number"
                          className="app-input"
                          style={{ paddingLeft: '48px', width: '100%', borderTopRightRadius: 0, borderBottomRightRadius: 0 }}
                        />
                      </div>
                      <button 
                        type="button" 
                        onClick={handleGeneratePhone}
                        style={{ padding: '0 20px', background: 'var(--surface-hover)', border: '1px solid var(--border)', borderLeft: 'none', borderTopRightRadius: '16px', borderBottomRightRadius: '16px', cursor: 'pointer' }}
                      >
                        <RefreshCw size={20} color="var(--text-muted)" />
                      </button>
                    </div>
                  </div>
                </div>
              )}

              <div style={{ marginTop: '32px' }}>
                {!showNewData ? (
                  <button type="button" onClick={handleCheck} disabled={isChecking} className="btn-primary" style={{ width: '100%', padding: '16px' }}>
                    <Search size={20} />
                    Check SSIN
                  </button>
                ) : (
                  <button type="submit" className="btn-primary" style={{ width: '100%', padding: '16px', background: 'var(--color-success)' }}>
                    <CheckCircle2 size={20} />
                    Submit Data
                  </button>
                )}
              </div>
            </form>
          </div>

          {/* EXISTING DATA PROFILE */}
          {showExistingData && profile && (
            <div style={{ animation: 'fadeIn 0.4s ease forwards' }}>
              <div style={{
                background: profile.status.toLowerCase() === 'active' ? 'linear-gradient(135deg, #146c2e, #2e8b49)' : 
                            profile.status.toLowerCase() === 'rejected' ? 'linear-gradient(135deg, #b3261e, #dc362d)' : 
                            'linear-gradient(135deg, #0b57d0, #4285f4)',
                borderRadius: '24px', padding: '24px', color: '#fff', display: 'flex', gap: '20px', marginBottom: '24px'
              }}>
                <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: 'rgba(255,255,255,0.2)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                  <User size={32} color="#fff" />
                </div>
                <div style={{ flex: 1 }}>
                  <h4 style={{ fontSize: '20px', marginBottom: '16px', borderBottom: '1px solid rgba(255,255,255,0.3)', paddingBottom: '8px' }}>SSIN Found</h4>
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', fontSize: '14px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}><span><Hash size={16}/> SSIN:</span> <strong>{profile.ssin}</strong></div>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}><span><User size={16}/> Name:</span> <strong>{profile.name}</strong></div>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}><span><Calendar size={16}/> Date of 60:</span> <strong>{profile.date_of_attaining_60}</strong></div>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}><span><Smartphone size={16}/> Phone:</span> <strong>{profile.phone_no}</strong></div>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}><span><AlertCircle size={16}/> Status:</span> <strong style={{textTransform:'uppercase'}}>{profile.status}</strong></div>
                  </div>
                </div>
              </div>

              {/* PF TABLE */}
              {profile.pf_updates && profile.pf_updates.length > 0 && (
                <div className="md-card">
                  <div style={{ fontSize: '16px', fontWeight: 600, marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <FileCheck size={20} /> Provident Fund Updates
                  </div>
                  <div style={{ overflowX: 'auto', borderRadius: '12px', border: '1px solid var(--border)' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '14px' }}>
                      <thead style={{ background: 'var(--surface-hover)', textAlign: 'left' }}>
                        <tr>
                          <th style={{ padding: '12px' }}>#</th>
                          <th style={{ padding: '12px' }}>Period From</th>
                          <th style={{ padding: '12px' }}>Period To</th>
                          <th style={{ padding: '12px', textAlign: 'center' }}>Months</th>
                          <th style={{ padding: '12px', textAlign: 'right' }}>Amount (₹)</th>
                        </tr>
                      </thead>
                      <tbody>
                        {profile.pf_updates.map((pf: any, idx: number) => (
                          <tr key={idx} style={{ borderTop: '1px solid var(--border)' }}>
                            <td style={{ padding: '12px' }}>{idx + 1}</td>
                            <td style={{ padding: '12px' }}>{pf.period_from}</td>
                            <td style={{ padding: '12px' }}>{pf.period_to}</td>
                            <td style={{ padding: '12px', textAlign: 'center' }}>{pf.months}</td>
                            <td style={{ padding: '12px', textAlign: 'right', fontWeight: 600, fontFamily: 'monospace' }}>₹{pf.amount.toFixed(2)}</td>
                          </tr>
                        ))}
                      </tbody>
                      <tfoot>
                        <tr style={{ background: 'var(--surface-hover)', fontWeight: 700 }}>
                          <td colSpan={4} style={{ padding: '12px', textAlign: 'right' }}>TOTAL AMOUNT:</td>
                          <td style={{ padding: '12px', textAlign: 'right', color: 'var(--color-success)' }}>₹{profile.total_amount.toFixed(2)}</td>
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
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
      `}} />
    </>
  );
}
