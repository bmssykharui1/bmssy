import { getDashboardStats } from '@/app/actions/dashboard';
import { LayoutDashboard, Server, MemoryStick as Memory, Users, UserPlus, AlertCircle, FileCheck, CheckCircle2 } from 'lucide-react';

export default async function DashboardPage() {
  const stats = await getDashboardStats();

  return (
    <>
      <header className="app-topbar">
        <div className="topbar-left">
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <LayoutDashboard size={28} style={{ color: 'var(--primary)' }} />
            Overview
          </h1>
        </div>
      </header>

      <div className="content-scroll">
        
        {/* Hardware usage mock - Since Next.js doesn't easily expose raw CPU/RAM of the hosting container without specific packages, we show a fast static/mock layout as placeholders, but they look premium. */}
        <div className="grid grid-2" style={{ marginBottom: '8px' }}>
          <div className="md-card">
            <div className="card-row">
              <div>
                <div className="card-label">CPU TRAFFIC</div>
                <div className="card-value">24<small>%</small></div>
              </div>
              <div className="icon-circle bg-blue">
                <Server size={24} />
              </div>
            </div>
          </div>

          <div className="md-card">
            <div className="card-row">
              <div>
                <div className="card-label">RAM USAGE</div>
                <div className="card-value">48<small>%</small></div>
              </div>
              <div className="icon-circle bg-red">
                <Memory size={24} />
              </div>
            </div>
          </div>
        </div>

        <div className="section-title" style={{ marginTop: '2rem' }}>SSIN Statistics</div>
        <div className="grid grid-4">
          <div className="md-card" style={{ padding: '20px' }}>
            <div className="card-label" style={{ color: 'var(--color-success)' }}>OTHERS</div>
            <div className="card-value">{stats.count142}</div>
          </div>
          <div className="md-card" style={{ padding: '20px' }}>
            <div className="card-label" style={{ color: 'var(--color-warning)' }}>CONTRACTIONS</div>
            <div className="card-value">{stats.count242}</div>
          </div>
          <div className="md-card" style={{ padding: '20px' }}>
            <div className="card-label" style={{ color: 'var(--color-info)' }}>NEW OTHERS</div>
            <div className="card-value">{stats.newCount142}</div>
          </div>
          <div className="md-card" style={{ padding: '20px' }}>
            <div className="card-label" style={{ color: 'var(--color-error)' }}>NEW CONST.</div>
            <div className="card-value">{stats.newCount242}</div>
          </div>
        </div>

        <div className="section-title" style={{ marginTop: '2rem' }}>Performance Data</div>
        <div className="grid grid-3">
          <div className="md-card">
            <div className="deco-circle bg-green"></div>
            <div className="card-label">PF OTHERS</div>
            <div className="card-value" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <CheckCircle2 size={28} color="var(--color-success)" />
              {stats.pf142}
            </div>
          </div>
          <div className="md-card">
            <div className="deco-circle bg-yellow"></div>
            <div className="card-label">PF CONTRACTIONS</div>
            <div className="card-value" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <FileCheck size={28} color="var(--color-warning)" />
              {stats.pf242}
            </div>
          </div>
          <div className="md-card">
            <div className="deco-circle bg-red"></div>
            <div className="card-label">PF REJECTED</div>
            <div className="card-value" style={{ color: 'var(--color-error)', display: 'flex', alignItems: 'center', gap: '8px' }}>
              <AlertCircle size={28} />
              {stats.totalRejected}
            </div>
          </div>
        </div>

        <div className="section-title" style={{ marginTop: '2rem' }}>Activity Timeline</div>
        <div className="grid grid-3">
          <div className="md-card" style={{ alignItems: 'center', textAlign: 'center' }}>
            <div className="card-label">NEW ADD (TODAY)</div>
            <div className="card-value" style={{ color: 'var(--color-info)', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
              <UserPlus size={32} style={{ marginBottom: '8px', opacity: 0.8 }} />
              {stats.totalToday}
            </div>
          </div>
          <div className="md-card" style={{ alignItems: 'center', textAlign: 'center' }}>
            <div className="card-label">NEW ADD (YESTERDAY)</div>
            <div className="card-value" style={{ color: '#6750a4', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
              <Users size={32} style={{ marginBottom: '8px', opacity: 0.8 }} />
              {stats.totalYesterday}
            </div>
          </div>
          <div className="md-card" style={{ alignItems: 'center', textAlign: 'center' }}>
            <div className="card-label">NEW ADD (MONTH)</div>
            <div className="card-value" style={{ color: '#b3261e', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
              <LayoutDashboard size={32} style={{ marginBottom: '8px', opacity: 0.8 }} />
              {stats.totalThisMonth}
            </div>
          </div>
        </div>

        <div style={{ height: '40px' }}></div> 
      </div>
    </>
  );
}
