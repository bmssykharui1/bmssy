import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';
import Sidebar from '@/components/Sidebar';

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
    <div className="app-scaffold">
      <Sidebar agent={agent} />
      <main className="app-main">
        {children}
      </main>
    </div>
  );
}
