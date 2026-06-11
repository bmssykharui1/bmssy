'use client';

import { useState } from 'react';
import { loginAgent } from './actions/auth';
import { UserCircle2, Loader2 } from 'lucide-react';
import { useRouter } from 'next/navigation';

export default function LoginPage() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setLoading(true);
    setError(null);

    const formData = new FormData(e.currentTarget);
    const result = await loginAgent(formData);

    if (result?.error) {
      setError(result.error);
      setLoading(false);
    } else {
      router.push('/dashboard');
    }
  }

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <div style={{ display: 'flex', justifyContent: 'center', marginBottom: '1rem', color: 'var(--primary)' }}>
            <UserCircle2 size={64} />
          </div>
          <h1>DEEPGYA SERVICE</h1>
          <p>Login with your Agent ID to continue</p>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="input-group">
            <label htmlFor="agentId" className="input-label">Agent ID</label>
            <input
              type="text"
              id="agentId"
              name="agentId"
              className="app-input"
              placeholder="e.g. 4207112"
              required
              disabled={loading}
              autoComplete="off"
            />
          </div>

          {error && <div className="error-message">{error}</div>}

          <button type="submit" className="btn-primary" disabled={loading} style={{ marginTop: '1.5rem' }}>
            {loading ? (
              <>
                <Loader2 className="animate-spin" size={20} style={{ animation: 'spin 1s linear infinite' }} />
                Authenticating...
              </>
            ) : (
              'Login'
            )}
          </button>
        </form>
      </div>
      
      <style dangerouslySetInnerHTML={{__html: `
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}} />
    </div>
  );
}
