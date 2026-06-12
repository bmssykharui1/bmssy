import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';
import LoginForm from './LoginForm';

export default async function LoginPage() {
  const cookieStore = await cookies();
  const session = cookieStore.get('agent_session');

  if (session) {
    redirect('/dashboard');
  }

  return <LoginForm />;
}
