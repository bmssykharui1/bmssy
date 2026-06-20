import { Smartphone, Download, ShieldAlert, Lock, AlertTriangle } from 'lucide-react';

export default function MobileBlocker() {
  return (
    <div className="mobile-blocker-overlay">
      <div className="mobile-blocker-content">
        <div className="glowing-orb orb-1"></div>
        <div className="glowing-orb orb-2"></div>
        
        <div className="blocker-header">
          <div className="icon-wrapper">
            <Smartphone size={48} className="pulse-icon" />
            <Lock size={20} className="lock-badge" />
          </div>
          <h1>Mobile Access Restricted</h1>
          <p className="subtitle">
            The web dashboard is not supported on mobile devices. Please download the official Android application to access the system.
          </p>
        </div>

        <a href="/api/download-app" className="download-btn">
          <Download size={24} />
          <div className="btn-text">
            <strong>Download Android App</strong>
            <span>Secure Direct Download</span>
          </div>
        </a>

        <div className="security-card">
          <div className="security-header">
            <AlertTriangle size={20} color="var(--error)" />
            <span>PRIVATE SYSTEM</span>
          </div>
          <p>
            This is a Private Self-Management System exclusively for <strong>KHARUI I</strong>. It is strictly not for public use and has <strong>no affiliation or connection with any Government entity</strong>.
          </p>
          <div className="security-footer">
            <ShieldAlert size={14} /> Private & Confidential
          </div>
        </div>
      </div>
    </div>
  );
}
