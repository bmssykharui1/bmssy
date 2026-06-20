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
              <ShieldAlert size={28} className="brand-icon" />
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
                  id="agentId"
                  name="agentId"
                  type="text"
                  placeholder="e.g., 4207112"
                  required
                  className="modern-input"
                  autoComplete="off"
                />
              </div>

              <button type="submit" disabled={loading} className="modern-btn">
                {loading ? (
                  <><Loader2 className="animate-spin" size={20} /> Authenticating...</>
                ) : (
                  <>Secure Access <ChevronRight size={20} /></>
                )}
              </button>
            </form>
          </div>
        </div>
      </div>

      <style dangerouslySetInnerHTML={{__html: `
        :root {
          --bg-color: #f1f5f9;
          --surface: #ffffff;
          --text-main: #0f172a;
          --text-muted: #64748b;
          --primary: #0b57d0;
          --primary-hover: #0842a0;
          --border: #e2e8f0;
          --shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        [data-theme='dark'] {
          --bg-color: #020617;
          --surface: #0f172a;
          --text-main: #f8fafc;
          --text-muted: #94a3b8;
          --primary: #3b82f6;
          --primary-hover: #60a5fa;
          --border: #1e293b;
          --shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .login-wrapper {
          min-height: 100vh;
          background: var(--bg-color);
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 24px;
          transition: background 0.3s ease;
        }

        .split-layout {
          display: flex;
          width: 100%;
          max-width: 1000px;
          height: 600px;
          background: var(--surface);
          border-radius: 24px;
          box-shadow: var(--shadow);
          overflow: hidden;
          transition: all 0.3s ease;
        }

        .left-panel {
          flex: 1;
          background: linear-gradient(135deg, #0b57d0 0%, #063073 100%);
          position: relative;
          color: white;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          padding: 48px;
        }

        .left-bg-pattern {
          position: absolute;
          inset: 0;
          opacity: 0.1;
          pointer-events: none;
        }

        .left-content {
          position: relative;
          z-index: 2;
          height: 100%;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
        }

        .brand-logo {
          display: flex;
          align-items: center;
          gap: 12px;
          font-weight: 700;
          font-size: 20px;
        }

        .brand-icon {
          color: #93c5fd;
        }

        .hero-text-container {
          margin-top: -60px;
        }

        .hero-title {
          font-size: 44px;
          font-weight: 800;
          line-height: 1.1;
          margin-bottom: 16px;
        }

        .hero-subtitle {
          color: #bfdbfe;
          font-size: 16px;
          line-height: 1.5;
        }

        .version-tag {
          display: inline-flex;
          align-items: center;
          gap: 8px;
          background: rgba(255, 255, 255, 0.15);
          padding: 8px 16px;
          border-radius: 20px;
          font-size: 13px;
          font-weight: 600;
          backdrop-filter: blur(8px);
          width: fit-content;
        }

        .pulse-dot {
          width: 8px;
          height: 8px;
          background: #4ade80;
          border-radius: 50%;
          animation: pulse 2s infinite;
        }

        @keyframes pulse {
          0% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.6); }
          70% { box-shadow: 0 0 0 8px rgba(74, 222, 128, 0); }
          100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
        }

        .right-panel {
          flex: 1;
          display: flex;
          flex-direction: column;
          justify-content: center;
          padding: 48px;
          position: relative;
        }

        .form-container {
          max-width: 360px;
          width: 100%;
          margin: 0 auto;
        }

        .mobile-brand {
          display: none;
          align-items: center;
          gap: 12px;
          margin-bottom: 40px;
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
          margin: 0;
          font-size: 15px;
        }

        .premium-form {
          display: flex;
          flex-direction: column;
          gap: 24px;
        }

        .input-box {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .input-box label {
          font-size: 13px;
          font-weight: 600;
          color: var(--text-muted);
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .modern-input {
          width: 100%;
          background: var(--bg-color);
          border: 1px solid var(--border);
          padding: 14px 16px;
          border-radius: 12px;
          font-size: 16px;
          color: var(--text-main);
          transition: all 0.2s ease;
          outline: none;
        }

        .modern-input:focus {
          border-color: var(--primary);
          box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.1);
        }

        .modern-btn {
          background: var(--primary);
          color: white;
          border: none;
          padding: 16px;
          border-radius: 12px;
          font-size: 16px;
          font-weight: 600;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          transition: all 0.2s ease;
          margin-top: 8px;
        }

        .modern-btn:hover:not(:disabled) {
          background: var(--primary-hover);
          transform: translateY(-1px);
          box-shadow: 0 4px 12px rgba(11, 87, 208, 0.2);
        }

        .modern-btn:disabled {
          opacity: 0.7;
          cursor: not-allowed;
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
