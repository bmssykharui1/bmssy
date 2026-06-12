'use client';

import { Loader2 } from 'lucide-react';

export default function Loading() {
  return (
    <div style={{
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      height: '100%',
      minHeight: '60vh',
      width: '100%',
      gap: '16px'
    }}>
      <div style={{
        position: 'relative',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        width: '80px',
        height: '80px',
        borderRadius: '50%',
        background: 'var(--surface)',
        boxShadow: 'var(--shadow-lg)',
        border: '1px solid var(--border)'
      }}>
        <div style={{
          position: 'absolute',
          top: 0, left: 0, right: 0, bottom: 0,
          borderRadius: '50%',
          border: '3px solid transparent',
          borderTopColor: 'var(--primary)',
          borderRightColor: 'var(--primary)',
          animation: 'spin 1s linear infinite',
          opacity: 0.8
        }}></div>
        <Loader2 size={32} color="var(--primary)" style={{ animation: 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite' }} />
      </div>
      <h3 style={{
        color: 'var(--text-main)',
        fontSize: '18px',
        fontWeight: 600,
        margin: 0,
        letterSpacing: '0.5px'
      }}>Loading Data...</h3>
      <p style={{
        color: 'var(--text-muted)',
        fontSize: '14px',
        margin: 0
      }}>Please wait while we prepare your dashboard.</p>
      
      <style dangerouslySetInnerHTML={{__html: `
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
        @keyframes pulse {
          0%, 100% { opacity: 1; }
          50% { opacity: .5; }
        }
      `}} />
    </div>
  );
}
