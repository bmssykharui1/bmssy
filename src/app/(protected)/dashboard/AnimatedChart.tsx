'use client';

import { useEffect, useState } from 'react';

export default function AnimatedChart({
  pf142,
  pf242,
  totalRejected
}: {
  pf142: number;
  pf242: number;
  totalRejected: number;
}) {
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    // Small delay to allow the layout to render first, then trigger height transition
    const timer = setTimeout(() => setMounted(true), 100);
    return () => clearTimeout(timer);
  }, []);

  const maxVal = Math.max(pf142, pf242, totalRejected, 1);

  // Calculate percentages for height
  const h142 = `${(pf142 / maxVal) * 100}%`;
  const h242 = `${(pf242 / maxVal) * 100}%`;
  const hRej = `${(totalRejected / maxVal) * 100}%`;

  return (
    <div className="md-card animate-enter delay-1" style={{ padding: '24px 32px' }}>
      <div className="section-title" style={{ margin: '0 0 16px 0', fontSize: '18px', color: 'var(--text-main)' }}>Performance Graph</div>
      
      <div className="chart-container">
        
        {/* PF OTHERS Bar */}
        <div className="chart-bar-wrapper">
          <div className={`chart-value ${mounted ? 'show' : ''}`} style={{ color: 'var(--success)' }}>
            {pf142}
          </div>
          <div 
            className="chart-bar" 
            style={{ 
              height: mounted ? h142 : '0%', 
              background: 'linear-gradient(180deg, var(--success) 0%, rgba(20,108,46,0.6) 100%)' 
            }} 
          />
          <div className="chart-label">PF OTHERS</div>
        </div>

        {/* PF CONTRACTIONS Bar */}
        <div className="chart-bar-wrapper">
          <div className={`chart-value ${mounted ? 'show' : ''}`} style={{ color: '#b36b00' }}>
            {pf242}
          </div>
          <div 
            className="chart-bar" 
            style={{ 
              height: mounted ? h242 : '0%', 
              background: 'linear-gradient(180deg, #d97706 0%, rgba(217,119,6,0.6) 100%)' 
            }} 
          />
          <div className="chart-label">PF CONTRACTIONS</div>
        </div>

        {/* PF REJECTED Bar */}
        <div className="chart-bar-wrapper">
          <div className={`chart-value ${mounted ? 'show' : ''}`} style={{ color: 'var(--error)' }}>
            {totalRejected}
          </div>
          <div 
            className="chart-bar" 
            style={{ 
              height: mounted ? hRej : '0%', 
              background: 'linear-gradient(180deg, var(--error) 0%, rgba(186,26,26,0.6) 100%)' 
            }} 
          />
          <div className="chart-label">PF REJECTED</div>
        </div>

      </div>
    </div>
  );
}
