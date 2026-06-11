'use server'

import { cookies } from 'next/headers';
import { verifyAgent } from '@/lib/db';
import { redirect } from 'next/navigation';

export async function loginAgent(formData: FormData) {
  const agentId = formData.get('agentId') as string;

  if (!agentId) {
    return { error: 'Agent ID is required' };
  }

  try {
    const agent = await verifyAgent(agentId);

    if (agent) {
      // Set session cookie
      const cookieStore = await cookies();
      cookieStore.set('agent_session', JSON.stringify({ id: agent.id, name: agent.name, area: agent.area }), {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'lax',
        maxAge: 60 * 60 * 24 * 30, // 30 days
        path: '/',
      });

      return { success: true };
    } else {
      return { error: 'Invalid Agent ID' };
    }
  } catch (error) {
    console.error('Login error:', error);
    return { error: 'An error occurred during login. Please try again.' };
  }
}

export async function logoutAgent() {
  const cookieStore = await cookies();
  cookieStore.delete('agent_session');
  redirect('/');
}
