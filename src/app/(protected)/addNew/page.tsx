'use client';

import { useState } from 'react';
import { checkSSINAction, submitSSINAction } from '@/app/actions/addNew';
import { Search, PlusCircle, CheckCircle2, AlertCircle, FileCheck, RefreshCw, Smartphone, Calendar, User, Hash, Loader2, Sparkles, ChevronDown, Check } from 'lucide-react';
import { toast } from 'sonner';

export default function AddNewPage() {
  const [ssin, setSsin] = useState('');
  const [isChecking, setIsChecking] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  // Accordion Step: 1 for Search, 2 for Data Entry
  const [activeStep, setActiveStep] = useState<1 | 2>(1);

  const [showNewData, setShowNewData] = useState(false);
  const [showExistingData, setShowExistingData] = useState(false);
  const [profile, setProfile] = useState<any>(null);

  // Form Fields
  const [name, setName] = useState('');
  const [dateOf60, setDateOf60] = useState('');
  const [phone, setPhone] = useState('');

  const handleCheck = async () => {
    if (ssin.length !== 12) {
      toast.error('SSIN must be exactly 12 digits');
      return;
    }

    setIsChecking(true);
    const result = await checkSSINAction(ssin) as any;
    setIsChecking(false);

    if (result.error) {
      toast.error(result.error);
      return;
    }

    if (result.exists) {
      setProfile(result);
      setShowExistingData(true);
      setShowNewData(false);
      toast.success('SSIN Found in Database');
      setActiveStep(2); // Move to step 2
    } else {
      setShowExistingData(false);
      setShowNewData(true);
      toast.info('New SSIN. Please fill the details.');
      setActiveStep(2); // Move to step 2
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
      toast.error(result.error);
    } else {
      toast.success('Data Saved Successfully!');
      // Reset Form and go back to step 1
      setSsin('');
      setName('');
      setDateOf60('');
      setPhone('');
      setShowNewData(false);
      setActiveStep(1);
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
        <div style={{ maxWidth: '1200px', margin: '0 auto', width: '100%' }}>
          
          <div className="stepper-container">
            {/* STEP 1: IDENTITY VERIFICATION */}
            <div className={`accordion-panel ${activeStep === 1 ? 'active' : 'completed'}`}>
              <div 
                className="accordion-header" 
                onClick={() => activeStep === 2 && setActiveStep(1)}
                style={{ cursor: activeStep === 2 ? 'pointer' : 'default' }}
              >
                <div className="step-badge">
                  {activeStep === 2 ? <Check size={18} /> : '1'}
                </div>
                <div className="step-title">
                  <h3>Identity Verification</h3>
                  <p>{activeStep === 2 ? `Verified SSIN: ${ssin}` : 'Enter 12-digit SSIN number to verify status'}</p>
                </div>
              </div>

              <div className="accordion-body">
                <div style={{ padding: '0 24px 24px 24px' }}>
                  <div className="form-group">
                    <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                      <Hash size={24} style={{ position: 'absolute', left: '20px', color: 'var(--primary)' }} />
                      <input 
                        type="text" 
                        value={ssin}
                        onChange={(e) => setSsin(e.target.value.replace(/[^0-9]/g, '').slice(0, 12))}
                        placeholder="e.g., 420711200001" 
                        className="cool-input"
                        autoComplete="off"
                        autoFocus
                      />
                    </div>
                  </div>

                  <button 
                    type="button" 
                    onClick={handleCheck} 
                    disabled={isChecking || ssin.length !== 12} 
                    className="cool-btn verify-btn"
                  >
                    {isChecking ? (
                      <><Loader2 className="spinner" size={20} /> Querying Database...</>
                    ) : (
                      <><Search size={20} /> Verify Database</>
                    )}
                  </button>
                </div>
              </div>
            </div>

            {/* STEP 2: DATA ENTRY / RECORD VIEW */}
            <div className={`accordion-panel ${activeStep === 2 ? 'active' : 'disabled'}`}>
              <div className="accordion-header">
                <div className="step-badge">2</div>
                <div className="step-title">
                  <h3>Database Record</h3>
                  <p>{showNewData ? 'New Entry Required' : showExistingData ? 'Existing Profile Found' : 'Awaiting SSIN Verification'}</p>
                </div>
              </div>

              <div className="accordion-body">
                <div style={{ padding: '0 24px 24px 24px' }}>
                  
                  {/* EXISTING DATA PROFILE */}
                  {showExistingData && profile && (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
                      <div className="profile-card">
                        <div className="profile-header">
                          <div className="profile-avatar">
                            <User size={32} color="#fff" />
                          </div>
                          <div style={{ flex: 1 }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                              <h4 style={{ fontSize: '20px', margin: 0 }}>SSIN Profile</h4>
                              <span className="status-badge">{profile.status}</span>
                            </div>
                            <div className="profile-grid">
                              <div><span>SSIN</span><strong>{profile.ssin}</strong></div>
                              <div><span>Name</span><strong>{profile.name}</strong></div>
                              <div><span>Age 60 Date</span><strong>{profile.date_of_attaining_60}</strong></div>
                              <div><span>Mobile</span><strong>{profile.phone_no}</strong></div>
                            </div>
                          </div>
                        </div>
                      </div>

                      {/* PF TABLE */}
                      {profile.pf_updates && profile.pf_updates.length > 0 && (
                        <div className="pf-ledger">
                          <div className="pf-ledger-title">
                            <FileCheck size={18} /> PF Updates Ledger
                          </div>
                          <div className="table-wrapper">
                            <table>
                              <thead>
                                <tr>
                                  <th>#</th>
                                  <th>Period From</th>
                                  <th>Period To</th>
                                  <th style={{ textAlign: 'center' }}>Months</th>
                                  <th style={{ textAlign: 'right' }}>Amount</th>
                                </tr>
                              </thead>
                              <tbody>
                                {profile.pf_updates.map((pf: any, idx: number) => (
                                  <tr key={idx}>
                                    <td>{idx + 1}</td>
                                    <td>{pf.period_from}</td>
                                    <td>{pf.period_to}</td>
                                    <td style={{ textAlign: 'center' }}><span className="month-chip">{pf.months}</span></td>
                                    <td style={{ textAlign: 'right', fontWeight: 700, color: 'var(--text-main)' }}>₹{pf.amount.toFixed(2)}</td>
                                  </tr>
                                ))}
                              </tbody>
                              <tfoot>
                                <tr>
                                  <td colSpan={4} style={{ textAlign: 'right', color: 'var(--color-success)' }}>TOTAL AMOUNT:</td>
                                  <td style={{ textAlign: 'right', color: 'var(--color-success)', fontSize: '16px' }}>₹{profile.total_amount.toFixed(2)}</td>
                                </tr>
                              </tfoot>
                            </table>
                          </div>
                        </div>
                      )}
                    </div>
                  )}

                  {/* NEW DATA FORM */}
                  {showNewData && (
                    <form onSubmit={handleSubmit} className="new-data-form">
                      <div className="form-group">
                        <label>Beneficiary Name</label>
                        <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                          <User size={20} style={{ position: 'absolute', left: '20px', color: 'var(--primary)' }} />
                          <input 
                            type="text" 
                            required
                            value={name}
                            onChange={(e) => setName(e.target.value.toUpperCase())}
                            placeholder="Enter full name" 
                            className="cool-input"
                            disabled={isSubmitting}
                          />
                        </div>
                      </div>

                      <div className="form-row">
                        <div className="form-group" style={{ flex: 1 }}>
                          <label>Date of Attaining 60</label>
                          <div className="input-with-btn">
                            <div style={{ position: 'relative', display: 'flex', alignItems: 'center', flex: 1 }}>
                              <Calendar size={20} style={{ position: 'absolute', left: '20px', color: 'var(--primary)' }} />
                              <input 
                                type="date" 
                                required
                                value={dateOf60}
                                onChange={(e) => setDateOf60(e.target.value)}
                                className="cool-input"
                                disabled={isSubmitting}
                              />
                            </div>
                            <button type="button" onClick={handleGenerateDate} disabled={isSubmitting} className="action-btn">
                              <RefreshCw size={20} />
                            </button>
                          </div>
                        </div>

                        <div className="form-group" style={{ flex: 1 }}>
                          <label>Phone Number</label>
                          <div className="input-with-btn">
                            <div style={{ position: 'relative', display: 'flex', alignItems: 'center', flex: 1 }}>
                              <Smartphone size={20} style={{ position: 'absolute', left: '20px', color: 'var(--primary)' }} />
                              <input 
                                type="text" 
                                required
                                value={phone}
                                onChange={(e) => setPhone(e.target.value.replace(/[^0-9]/g, '').slice(0, 10))}
                                placeholder="Enter phone"
                                className="cool-input"
                                disabled={isSubmitting}
                              />
                            </div>
                            <button type="button" onClick={handleGeneratePhone} disabled={isSubmitting} className="action-btn">
                              <RefreshCw size={20} />
                            </button>
                          </div>
                        </div>
                      </div>

                      <button 
                        type="submit" 
                        disabled={isSubmitting}
                        className="cool-btn submit-btn" 
                      >
                        {isSubmitting ? (
                          <><Loader2 className="spinner" size={20} /> Creating Entry...</>
                        ) : (
                          <><CheckCircle2 size={20} /> Submit & Register</>
                        )}
                      </button>
                    </form>
                  )}

                </div>
              </div>
            </div>

          </div>

        </div>
        <div style={{ height: '40px' }}></div>
      </div>

      <style dangerouslySetInnerHTML={{__html: `
        .stepper-container {
          display: flex;
          flex-direction: column;
          gap: 20px;
          margin-top: 20px;
        }

        .accordion-panel {
          background: var(--surface);
          border-radius: 24px;
          box-shadow: var(--shadow);
          border: 1px solid var(--border);
          overflow: hidden;
          transition: all 0.5s cubic-bezier(0.2, 0, 0, 1);
        }

        /* Desktop: Horizontal Split */
        @media (min-width: 1024px) {
          .stepper-container {
            flex-direction: row;
            align-items: flex-start;
          }
          
          .accordion-panel {
            flex: 1;
            min-width: 0; /* Prevent flex blowout */
          }

          .accordion-panel.completed {
            flex: 0 0 400px; /* Left panel shrinks to 400px when right is open */
          }

          .accordion-panel.disabled {
            flex: 0 0 0;
            opacity: 0;
            margin-left: -20px; /* pull to hide gap */
            border: none;
            visibility: hidden;
          }
        }

        /* Mobile: Vertical Split */
        @media (max-width: 1023px) {
          .accordion-panel.disabled {
            display: none;
          }
          .accordion-panel.completed .accordion-body {
            display: none;
          }
        }

        .accordion-panel.active {
          box-shadow: 0 12px 32px rgba(11, 87, 208, 0.1);
          border-color: rgba(11, 87, 208, 0.3);
          transform: translateY(-2px);
        }

        .accordion-header {
          display: flex;
          align-items: center;
          padding: 24px;
          gap: 16px;
        }

        .step-badge {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: 700;
          font-size: 16px;
          flex-shrink: 0;
          transition: all 0.3s;
        }

        .accordion-panel.active .step-badge {
          background: var(--primary);
          color: white;
          box-shadow: 0 4px 12px rgba(11, 87, 208, 0.3);
        }

        .accordion-panel.completed .step-badge {
          background: var(--color-success, #146c2e);
          color: white;
        }

        .accordion-panel.disabled .step-badge {
          background: var(--border);
          color: var(--text-muted);
        }

        .step-title {
          flex: 1;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }

        .step-title h3 {
          margin: 0 0 4px 0;
          font-size: 18px;
          color: var(--text-main);
        }

        .step-title p {
          margin: 0;
          font-size: 14px;
          color: var(--text-muted);
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }

        .cool-input {
          width: 100%;
          padding: 20px 20px 20px 56px;
          font-size: 16px;
          font-weight: 500;
          color: var(--text-main);
          background: var(--background);
          border: 2px solid transparent;
          border-radius: 16px;
          outline: none;
          transition: all 0.3s;
          box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .cool-input:focus {
          border-color: var(--primary);
          box-shadow: 0 0 0 4px rgba(11, 87, 208, 0.1), inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .cool-btn {
          width: 100%;
          padding: 20px;
          border: none;
          border-radius: 16px;
          font-size: 16px;
          font-weight: 600;
          color: white;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 10px;
          transition: all 0.3s;
          margin-top: 16px;
        }

        .verify-btn {
          background: linear-gradient(135deg, #0b57d0, #2b70e4);
          box-shadow: 0 8px 24px rgba(11, 87, 208, 0.25);
        }

        .verify-btn:hover:not(:disabled) {
          transform: translateY(-2px);
          box-shadow: 0 12px 32px rgba(11, 87, 208, 0.35);
        }

        .submit-btn {
          background: linear-gradient(135deg, #146c2e, #2e8b49);
          box-shadow: 0 8px 24px rgba(20, 108, 46, 0.25);
          margin-top: 32px;
        }

        .submit-btn:hover:not(:disabled) {
          transform: translateY(-2px);
          box-shadow: 0 12px 32px rgba(20, 108, 46, 0.35);
        }

        .cool-btn:disabled {
          opacity: 0.7;
          cursor: not-allowed;
          transform: none !important;
        }

        .form-row {
          display: flex;
          gap: 20px;
          margin-top: 20px;
        }

        .form-group label {
          display: block;
          font-size: 14px;
          font-weight: 600;
          color: var(--text-muted);
          margin-bottom: 10px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .input-with-btn {
          display: flex;
          background: var(--background);
          border-radius: 16px;
          overflow: hidden;
          box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .input-with-btn .cool-input {
          background: transparent;
          box-shadow: none;
          border-radius: 0;
        }

        .input-with-btn .cool-input:focus {
          box-shadow: none;
        }

        .input-with-btn:focus-within {
          box-shadow: 0 0 0 2px var(--primary);
        }

        .action-btn {
          padding: 0 24px;
          background: rgba(11, 87, 208, 0.05);
          border: none;
          border-left: 1px solid var(--border);
          color: var(--primary);
          cursor: pointer;
          transition: all 0.2s;
        }

        .action-btn:hover:not(:disabled) {
          background: rgba(11, 87, 208, 0.15);
        }

        /* Profile Card Styles */
        .profile-card {
          background: linear-gradient(135deg, #0f172a, #1e293b);
          border-radius: 20px;
          color: white;
          overflow: hidden;
          box-shadow: 0 12px 32px rgba(0,0,0,0.2);
          animation: slideUp 0.5s cubic-bezier(0.2, 0, 0, 1) forwards;
        }

        [data-theme='light'] .profile-card {
          background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        }

        .profile-header {
          padding: 24px;
          display: flex;
          gap: 20px;
        }

        .profile-avatar {
          width: 72px; height: 72px;
          border-radius: 50%;
          background: rgba(255,255,255,0.15);
          display: flex; align-items: center; justify-content: center;
          backdrop-filter: blur(10px);
          flex-shrink: 0;
          box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        .status-badge {
          background: rgba(255,255,255,0.2);
          padding: 6px 12px;
          border-radius: 100px;
          font-size: 12px;
          font-weight: 700;
          letter-spacing: 1px;
          text-transform: uppercase;
          backdrop-filter: blur(5px);
        }

        .profile-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 16px;
          margin-top: 16px;
          padding-top: 16px;
          border-top: 1px solid rgba(255,255,255,0.15);
        }

        .profile-grid span {
          display: block;
          font-size: 12px;
          color: rgba(255,255,255,0.7);
          text-transform: uppercase;
          margin-bottom: 4px;
        }

        .profile-grid strong {
          font-size: 15px;
          letter-spacing: 0.5px;
        }

        .pf-ledger {
          background: var(--surface);
          border-radius: 20px;
          border: 1px solid var(--border);
          box-shadow: var(--shadow);
          color: var(--text-main);
          overflow: hidden;
          animation: slideUp 0.6s cubic-bezier(0.2, 0, 0, 1) forwards;
        }

        .pf-ledger-title {
          padding: 16px 20px;
          font-weight: 600;
          display: flex;
          align-items: center;
          gap: 10px;
          color: var(--primary);
          border-bottom: 1px solid var(--border);
        }

        .table-wrapper {
          overflow-x: auto;
        }

        .table-wrapper table {
          width: 100%;
          border-collapse: collapse;
          font-size: 14px;
        }

        .table-wrapper th, .table-wrapper td {
          padding: 16px 20px;
          text-align: left;
        }

        .table-wrapper th {
          background: var(--background);
          color: var(--text-muted);
          font-weight: 600;
        }

        .table-wrapper tbody tr {
          border-bottom: 1px solid var(--border);
        }

        .month-chip {
          background: rgba(11, 87, 208, 0.1);
          color: var(--primary);
          padding: 4px 12px;
          border-radius: 100px;
          font-weight: 600;
          font-size: 13px;
        }

        .table-wrapper tfoot td {
          font-weight: 700;
          background: rgba(20, 108, 46, 0.05);
        }

        .new-data-form {
          animation: slideUp 0.5s cubic-bezier(0.2, 0, 0, 1) forwards;
        }

        @keyframes slideUp { 
          from { opacity: 0; transform: translateY(20px); } 
          to { opacity: 1; transform: translateY(0); } 
        }
        @keyframes spin { 
          from { transform: rotate(0deg); } 
          to { transform: rotate(360deg); } 
        }
        .spinner { animation: spin 1s linear infinite; }

        @media (max-width: 768px) {
          .form-row { flex-direction: column; gap: 20px; }
          .profile-grid { grid-template-columns: 1fr; }
        }
      `}} />
    </>
  );
}
