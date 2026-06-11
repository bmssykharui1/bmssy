'use client';

import { useState, useEffect } from 'react';
import { getGlobalSettings, updateGlobalSettings } from '@/app/actions/pfupdate';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { Toast } from '@/lib/toast';
import { Settings, Save, Calendar, Loader2 } from 'lucide-react';

const MySwal = withReactContent(Swal);

export default function PFSettingsPage() {
  const [loading, setLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [settings, setSettings] = useState({ period_from: '', period_to: '' });

  useEffect(() => {
    async function load() {
      const data = await getGlobalSettings();
      setSettings(data);
      setLoading(false);
    }
    load();
  }, []);

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    
    const res = await updateGlobalSettings(settings.period_from, settings.period_to);
    setIsSaving(false);

    if (res.error) {
      MySwal.fire({ title: 'Error', text: res.error, icon: 'error' });
    } else {
      Toast.fire({ title: 'Global settings updated successfully.', icon: 'success' });
    }
  };

  return (
    <>
      <header className="app-topbar" style={{ background: 'var(--surface)', backdropFilter: 'blur(10px)', borderBottom: '1px solid var(--border)' }}>
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '18px' }}>
            <Settings size={22} style={{ color: 'var(--primary)' }} />
            PF Update Settings
          </h1>
        </div>
      </header>

      <div className="content-scroll" style={{ padding: '24px' }}>
        <div style={{ maxWidth: '600px', margin: '0 auto', width: '100%' }}>
          
          <div className="md-card" style={{ 
            padding: '32px', 
            background: 'var(--surface)',
            borderRadius: '24px',
            boxShadow: '0 12px 32px rgba(0,0,0,0.04)',
            border: '1px solid rgba(11, 87, 208, 0.1)'
          }}>
            
            <h2 style={{ fontSize: '20px', fontWeight: 700, color: 'var(--text-main)', marginBottom: '8px' }}>Global PF Period</h2>
            <p style={{ fontSize: '14px', color: 'var(--text-muted)', marginBottom: '32px' }}>
              Set the default date range for PF Updates. This will be automatically filled when accepting a beneficiary's PF Update.
            </p>

            {loading ? (
              <div style={{ display: 'flex', justifyContent: 'center', padding: '40px 0' }}>
                <Loader2 size={32} className="spinner" style={{ color: 'var(--primary)' }} />
              </div>
            ) : (
              <form onSubmit={handleSave}>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
                  
                  <div>
                    <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, marginBottom: '8px', color: 'var(--text-muted)' }}>PERIOD FROM</label>
                    <div style={{ position: 'relative' }}>
                      <Calendar size={18} style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', color: 'var(--primary)' }} />
                      <input 
                        type="date" 
                        required 
                        value={settings.period_from} 
                        onChange={(e) => setSettings({...settings, period_from: e.target.value})} 
                        style={{ 
                          padding: '12px 16px 12px 48px', width: '100%', background: 'var(--surface)', 
                          border: '2px solid transparent', borderRadius: '12px', fontSize: '15px', 
                          fontWeight: 600, outline: 'none', color: 'var(--text-main)', transition: 'all 0.2s',
                          boxShadow: 'inset 0 2px 4px rgba(0,0,0,0.02), 0 0 0 1px var(--border)'
                        }} 
                        onFocus={e=>e.currentTarget.style.borderColor='var(--primary)'} 
                        onBlur={e=>e.currentTarget.style.borderColor='transparent'} 
                      />
                    </div>
                  </div>

                  <div>
                    <label style={{ display: 'block', fontSize: '13px', fontWeight: 600, marginBottom: '8px', color: 'var(--text-muted)' }}>PERIOD TO</label>
                    <div style={{ position: 'relative' }}>
                      <Calendar size={18} style={{ position: 'absolute', left: '16px', top: '50%', transform: 'translateY(-50%)', color: 'var(--primary)' }} />
                      <input 
                        type="date" 
                        required 
                        value={settings.period_to} 
                        onChange={(e) => setSettings({...settings, period_to: e.target.value})} 
                        style={{ 
                          padding: '12px 16px 12px 48px', width: '100%', background: 'var(--surface)', 
                          border: '2px solid transparent', borderRadius: '12px', fontSize: '15px', 
                          fontWeight: 600, outline: 'none', color: 'var(--text-main)', transition: 'all 0.2s',
                          boxShadow: 'inset 0 2px 4px rgba(0,0,0,0.02), 0 0 0 1px var(--border)'
                        }} 
                        onFocus={e=>e.currentTarget.style.borderColor='var(--primary)'} 
                        onBlur={e=>e.currentTarget.style.borderColor='transparent'} 
                      />
                    </div>
                  </div>

                  <div style={{ marginTop: '16px' }}>
                    <button 
                      type="submit" 
                      disabled={isSaving} 
                      style={{ 
                        width: '100%', padding: '14px', borderRadius: '12px', display: 'flex', 
                        justifyContent: 'center', alignItems: 'center', gap: '8px', 
                        background: 'var(--primary)', color: '#fff', border: 'none', 
                        fontWeight: 600, fontSize: '15px', cursor: isSaving ? 'not-allowed' : 'pointer', 
                        opacity: isSaving ? 0.7 : 1, transition: '0.2s',
                        boxShadow: '0 8px 20px rgba(11, 87, 208, 0.2)'
                      }}
                      onMouseOver={e=>e.currentTarget.style.transform='translateY(-2px)'}
                      onMouseOut={e=>e.currentTarget.style.transform='none'}
                    >
                      {isSaving ? <><Loader2 size={20} className="spinner" /> Saving Settings...</> : <><Save size={20} /> Save Settings</>}
                    </button>
                  </div>
                  
                </div>
              </form>
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
