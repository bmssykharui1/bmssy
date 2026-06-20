import type { Metadata } from 'next';
import './globals.css';
import { Providers } from './providers';
import ThemeSwitcher from '@/components/ThemeSwitcher';
import MobileBlocker from '@/components/MobileBlocker';

export const metadata: Metadata = {
  title: 'BMSSY KHARUI I | Dashboard',
  description: 'Modern Node.js Dashboard',
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body suppressHydrationWarning>
        <Providers>
          <ThemeSwitcher />
          <div className="desktop-view">
            {children}
          </div>
          <MobileBlocker />
        </Providers>
      </body>
    </html>
  );
}
