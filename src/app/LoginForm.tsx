'use client';

import { useState, useEffect } from 'react';
import { loginAgent } from './actions/auth';
import { ShieldAlert, Loader2, ChevronRight } from 'lucide-react';
import { useRouter } from 'next/navigation';
import { toast } from 'sonner';

export default function LoginForm() {
  const [loading, setLoading] = useState(false);
  const [theme, setTheme] = useState('light');
  const router = useRouter();

  useEffect(() => {
    // Check if theme was saved or system preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      setTheme(savedTheme);
      document.documentElement.setAttribute('data-theme', savedTheme);
    } else {
      document.documentElement.setAttribute('data-theme', 'light');
    }
  }, []);

  const toggleTheme = () => {
    const newTheme = theme === 'light' ? 'dark' : 'light';
    setTheme(newTheme);
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
  };

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setLoading(true);

    const formData = new FormData(e.currentTarget);
    const result = await loginAgent(formData);

    if (result?.error) {
      setLoading(false);
      toast.error(result.error);
    } else {
      toast.success('Authentication Successful');
      router.push('/dashboard');
    }
  }

  return (
    <div className="login-wrapper">
      <div className="split-layout">
        
        {/* Left Side: Premium Content */}
        <div className="left-panel">
          <div className="left-bg-pattern">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <pattern id="dotGrid" width="30" height="30" patternUnits="userSpaceOnUse">
                  <circle cx="2" cy="2" r="1.5" fill="currentColor" opacity="0.15" />
                </pattern>
              </defs>
              <rect width="100%" height="100%" fill="url(#dotGrid)" />
            </svg>
          </div>
          <div className="left-content">
            <div className="brand-logo">
              <ShieldAlert size={40} className="brand-icon" />
              <span>BMSSY KHARUI I</span>
            </div>
            
            <div className="hero-text-container">
              <h1 className="hero-title">Welcome to<br/>BMSSY KHARUI I</h1>
              <p className="hero-subtitle">
                Secure access terminal for the centralized management dashboard. Ensure authorized access only.
              </p>
            </div>

            <div className="version-tag">
              <div className="pulse-dot"></div>
              System Version 1.0.0
            </div>
          </div>
        </div>

        {/* Right Side: Login Form */}
        <div className="right-panel">
          <div className="form-container">
            <div className="mobile-brand">
              <ShieldAlert size={32} className="brand-icon" />
              <h2>BMSSY KHARUI I</h2>
            </div>
            
            <div className="form-header">
              <h2>Sign In</h2>
              <p>Enter your Agent ID to access your dashboard</p>
            </div>

            <form onSubmit={handleSubmit} className="premium-form">
              <div className="input-box">
                <label htmlFor="agentId">Agent Identification</label>
                <input
                  type="text"
                  id="agentId"
                  name="agentId"
                  className="modern-input"
                  placeholder="e.g. 4207112"
                  required
                  disabled={loading}
                  autoComplete="off"
                />
              </div>

              <button type="submit" className="modern-btn" disabled={loading}>
                <span className="btn-text">
                  {loading ? 'Authenticating...' : 'Sign In'}
                </span>
                {loading ? (
                  <Loader2 className="animate-spin" size={18} />
                ) : (
                  <ChevronRight size={18} className="btn-icon" />
                )}
              </button>
            </form>
          </div>
        </div>

      </div>

      <style dangerouslySetInnerHTML={{__html: `
        .login-wrapper {
          min-height: 100vh;
          width: 100%;
          background-color: var(--background);
          color: var(--text-main);
          font-family: 'Inter', sans-serif;
          position: relative;
        }

        .theme-toggle-btn {
          position: absolute;
          top: 24px;
          right: 24px;
          width: 44px; height: 44px;
          border-radius: 50%;
          background: var(--surface);
          border: 1px solid var(--border);
          color: var(--text-main);
          display: flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          z-index: 50;
          box-shadow: 0 4px 12px rgba(0,0,0,0.05);
          transition: all 0.2s ease;
        }

        .theme-toggle-btn:hover {
          background: var(--surface-hover);
          transform: scale(1.05);
        }

        .split-layout {
          display: flex;
          min-height: 100vh;
          width: 100%;
        }

        /* --- LEFT PANEL --- */
        .left-panel {
          flex: 1;
          background: linear-gradient(135deg, var(--primary) 0%, #063073 100%);
          position: relative;
          color: white;
          display: flex;
          flex-direction: column;
          padding: 40px;
          overflow: hidden;
        }

        .left-bg-pattern {
          position: absolute;
          top: 0; left: 0; width: 100%; height: 100%;
          color: white;
          z-index: 0;
        }

        .left-content {
          position: relative;
          z-index: 1;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          height: 100%;
        }

        .brand-logo {
          display: flex;
          align-items: center;
          gap: 12px;
          font-size: 18px;
          font-weight: 700;
          letter-spacing: 1px;
        }

        .brand-icon {
          color: #60a5fa;
        }

        .hero-text-container {
          margin-top: -60px; /* Adjust vertical center slightly */
        }

        .hero-title {
          font-size: 48px;
          font-weight: 800;
          line-height: 1.1;
          margin-bottom: 24px;
          letter-spacing: -1px;
        }

        .hero-subtitle {
          font-size: 18px;
          line-height: 1.6;
          color: rgba(255, 255, 255, 0.8);
          max-width: 400px;
        }

        .version-tag {
          display: flex;
          align-items: center;
          gap: 8px;
          font-size: 13px;
          font-weight: 500;
          color: rgba(255, 255, 255, 0.7);
          background: rgba(0, 0, 0, 0.2);
          padding: 8px 16px;
          border-radius: 100px;
          width: fit-content;
          border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .pulse-dot {
          width: 8px; height: 8px;
          border-radius: 50%;
          background-color: #4ade80;
          box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7);
          animation: pulse 2s infinite;
        }

        @keyframes pulse {
          0% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
          70% { box-shadow: 0 0 0 10px rgba(74, 222, 128, 0); }
          100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
        }

        /* --- RIGHT PANEL --- */
        .right-panel {
          flex: 1;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 40px;
          background-color: var(--background);
        }

        .form-container {
          width: 100%;
          max-width: 400px;
        }

        .mobile-brand {
          display: none;
          align-items: center;
          justify-content: center;
          gap: 12px;
          margin-bottom: 40px;
          color: var(--primary);
        }
        
        .mobile-brand h2 {
          font-size: 20px;
          font-weight: 700;
          margin: 0;
          color: var(--text-main);
        }

        .form-header {
          margin-bottom: 32px;
        }

        .form-header h2 {
          font-size: 32px;
          font-weight: 700;
          margin: 0 0 8px 0;
          color: var(--text-main);
          letter-spacing: -0.5px;
        }

        .form-header p {
          color: var(--text-muted);
          font-size: 15px;
          margin: 0;
        }

        .premium-form .input-box {
          margin-bottom: 24px;
        }

        .premium-form label {
          display: block;
          font-size: 13px;
          font-weight: 600;
          color: var(--text-muted);
          margin-bottom: 8px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .modern-input {
          width: 100%;
          background: var(--surface);
          border: 1px solid var(--border);
          border-radius: 12px;
          padding: 16px 20px;
          font-size: 16px;
          color: var(--text-main);
          transition: all 0.2s ease;
          outline: none;
          box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .modern-input:focus {
          border-color: var(--primary);
          box-shadow: 0 0 0 4px rgba(11, 87, 208, 0.1);
        }

        .modern-btn {
          width: 100%;
          background: var(--primary);
          color: white;
          border: none;
          border-radius: 12px;
          padding: 16px;
          font-size: 16px;
          font-weight: 600;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 12px;
          transition: all 0.2s ease;
          margin-top: 16px;
          box-shadow: 0 4px 12px rgba(11, 87, 208, 0.2);
        }

        .modern-btn:hover:not(:disabled) {
          background: var(--primary-hover);
          transform: translateY(-2px);
          box-shadow: 0 6px 16px rgba(11, 87, 208, 0.3);
        }

        .modern-btn:active:not(:disabled) {
          transform: translateY(0);
        }

        .modern-btn:disabled {
          opacity: 0.7;
          cursor: not-allowed;
        }

        .btn-icon {
          transition: transform 0.2s ease;
        }

        .modern-btn:hover:not(:disabled) .btn-icon {
          transform: translateX(4px);
        }

        .animate-spin {
          animation: spin 1s linear infinite;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 900px) {
          .left-panel {
            display: none; /* Hide left panel on smaller screens */
          }
          .mobile-brand {
            display: flex;
          }
          .form-container {
            background: var(--surface);
            padding: 40px;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
          }
        }
        
        @media (max-width: 480px) {
          .form-container {
            padding: 24px;
            box-shadow: none;
            border: none;
            background: transparent;
          }
          .right-panel {
            padding: 16px;
          }
        }
      `}} />
    </div>
  );
}
