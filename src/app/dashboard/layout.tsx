import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';
import { LogOut, LayoutDashboard, Users, FileText } from 'lucide-react';
import { logoutAgent } from '../actions/auth';

export default async function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const cookieStore = await cookies();
  const sessionData = cookieStore.get('agent_session');

  if (!sessionData) {
    redirect('/');
  }

  const agent = JSON.parse(sessionData.value);

  return (
    <div className="dashboard-layout">
      <aside className="sidebar">
        <div style={{ marginBottom: '2rem' }}>
          <h2 style={{ fontSize: '1.25rem', fontWeight: 700, color: 'var(--primary)' }}>DEEPGYA</h2>
          <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>SERVICE PORTAL</p>
        </div>

        <nav style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem', flex: 1 }}>
          <a href="/dashboard" style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', padding: '0.75rem', borderRadius: '0.5rem', background: 'var(--surface-hover)', color: 'var(--text-main)', textDecoration: 'none', fontWeight: 500 }}>
            <LayoutDashboard size={20} />
            Dashboard
          </a>
          {/* Add more links here later */}
        </nav>

        <div style={{ marginTop: 'auto', paddingTop: '1rem', borderTop: '1px solid var(--border)' }}>
          <div style={{ marginBottom: '1rem' }}>
            <p style={{ fontWeight: 600, fontSize: '0.9rem' }}>{agent.name}</p>
            <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>ID: {agent.id} | {agent.area}</p>
          </div>
          <form action={logoutAgent}>
            <button type="submit" style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', width: '100%', padding: '0.75rem', borderRadius: '0.5rem', border: '1px solid var(--border)', background: 'transparent', color: 'var(--error)', cursor: 'pointer', fontWeight: 500 }}>
              <LogOut size={18} />
              Logout
            </button>
          </form>
        </div>
      </aside>

      <main className="main-content">
        {children}
      </main>
    </div>
  );
}
