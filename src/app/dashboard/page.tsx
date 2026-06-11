export default function DashboardPage() {
  return (
    <>
      <header className="header">
        <div>
          <h1 style={{ fontSize: '1.5rem', fontWeight: 700 }}>Overview</h1>
          <p style={{ color: 'var(--text-muted)' }}>Welcome to your new modern dashboard</p>
        </div>
      </header>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '1.5rem' }}>
        <div className="card">
          <h3 style={{ fontSize: '1.1rem', marginBottom: '1rem', color: 'var(--text-muted)' }}>Total Records</h3>
          <p style={{ fontSize: '2.5rem', fontWeight: 700, color: 'var(--primary)' }}>0</p>
        </div>
        
        <div className="card">
          <h3 style={{ fontSize: '1.1rem', marginBottom: '1rem', color: 'var(--text-muted)' }}>Active Status</h3>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            <div style={{ width: 12, height: 12, borderRadius: '50%', background: '#10b981' }}></div>
            <p style={{ fontWeight: 500 }}>System Online</p>
          </div>
        </div>
      </div>
      
      <div className="card" style={{ marginTop: '2rem' }}>
        <h2 style={{ fontSize: '1.25rem', fontWeight: 600, marginBottom: '1rem' }}>Recent Activity</h2>
        <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)', border: '1px dashed var(--border)', borderRadius: '0.5rem' }}>
          No recent activity to show.
        </div>
      </div>
    </>
  );
}
