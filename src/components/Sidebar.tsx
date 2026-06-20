'use client';

import { useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { 
  LayoutDashboard, PlusSquare, FileCheck, FileText, 
  List, ShieldCheck, ChevronRight, LogOut, Settings, 
  Download, Users, FilePlus
} from 'lucide-react';
import { logoutAgent } from '@/app/actions/auth';

export default function Sidebar({ agent }: { agent: { id: string, name: string, area: string } }) {
  const pathname = usePathname();
  
  // Manage open state for accordions
  const [openMenus, setOpenMenus] = useState<Record<string, boolean>>({
    pfupdate: pathname.startsWith('/pfupdate'),
    ds: pathname.startsWith('/ds'),
    list: pathname.startsWith('/list'),
    form4: pathname.startsWith('/form4')
  });

  const toggleMenu = (menu: string) => {
    setOpenMenus(prev => ({
      pfupdate: menu === 'pfupdate' ? !prev.pfupdate : false,
      ds: menu === 'ds' ? !prev.ds : false,
      list: menu === 'list' ? !prev.list : false,
      form4: menu === 'form4' ? !prev.form4 : false
    }));
  };

  const closeAllMenus = () => {
    setOpenMenus({ pfupdate: false, ds: false, list: false, form4: false });
  };

  const isActive = (path: string) => pathname === path;

  return (
    <aside className="sidebar">
      <div style={{ marginBottom: '2rem', display: 'flex', alignItems: 'center', gap: '12px' }}>
        <div style={{ background: 'var(--primary)', color: '#fff', padding: '8px', borderRadius: '12px' }}>
          <ShieldCheck size={28} />
        </div>
        <div>
          <h2 style={{ fontSize: '1.1rem', fontWeight: 700, color: 'var(--text-main)', letterSpacing: '0.5px' }}>BMSSY KHARUI I</h2>
        </div>
      </div>

      <nav className="sb-nav">
        {/* Dashboard */}
        <Link href="/dashboard" className={`sb-item ${isActive('/dashboard') ? 'active' : ''}`} onClick={closeAllMenus}>
          <div className="sb-item-left" style={{ color: 'var(--icon-dash)' }}>
            <LayoutDashboard size={20} />
            <span>Dashboard</span>
          </div>
        </Link>

        {/* Add New */}
        <Link href="/addNew" className={`sb-item ${isActive('/addNew') ? 'active' : ''}`} onClick={closeAllMenus}>
          <div className="sb-item-left" style={{ color: 'var(--icon-add)' }}>
            <PlusSquare size={20} />
            <span>Add New</span>
          </div>
          <span className="sb-badge">New</span>
        </Link>

        {/* PF Updation */}
        <div>
          <button className={`sb-item ${pathname.startsWith('/pfupdate') ? 'active' : ''}`} onClick={() => toggleMenu('pfupdate')}>
            <div className="sb-item-left" style={{ color: 'var(--icon-pf)' }}>
              <FileCheck size={20} />
              <span>PF Updation</span>
            </div>
            <ChevronRight size={18} className={`sb-chevron ${openMenus.pfupdate ? 'rotate' : ''}`} />
          </button>
          <div className={`sb-submenu ${openMenus.pfupdate ? 'open' : ''}`}>
            <Link href="/pfupdate/others" className={`sb-subitem ${isActive('/pfupdate/others') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> Others
            </Link>
            <Link href="/pfupdate/constractions" className={`sb-subitem ${isActive('/pfupdate/constractions') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> Contractions
            </Link>
            <Link href="/pfupdate/settings" className={`sb-subitem ${isActive('/pfupdate/settings') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> Settings
            </Link>
          </div>
        </div>

        {/* Duare Sorkar */}
        <div>
          <button className={`sb-item ${pathname.startsWith('/ds') ? 'active' : ''}`} onClick={() => toggleMenu('ds')}>
            <div className="sb-item-left" style={{ color: 'var(--icon-ds)' }}>
              <Users size={20} />
              <span>Duare Sorkar</span>
            </div>
            <ChevronRight size={18} className={`sb-chevron ${openMenus.ds ? 'rotate' : ''}`} />
          </button>
          <div className={`sb-submenu ${openMenus.ds ? 'open' : ''}`}>
            <Link href="/ds/entry" className={`sb-subitem ${isActive('/ds/entry') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> Entry
            </Link>
            <Link href="/ds/pf_update" className={`sb-subitem ${isActive('/ds/pf_update') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> PF Update
            </Link>
            <Link href="/ds/ds_list" className={`sb-subitem ${isActive('/ds/ds_list') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> DS List
            </Link>
          </div>
        </div>

        {/* Lists */}
        <div>
          <button className={`sb-item ${pathname.startsWith('/list') ? 'active' : ''}`} onClick={() => toggleMenu('list')}>
            <div className="sb-item-left" style={{ color: 'var(--icon-list)' }}>
              <List size={20} />
              <span>Lists</span>
            </div>
            <ChevronRight size={18} className={`sb-chevron ${openMenus.list ? 'rotate' : ''}`} />
          </button>
          <div className={`sb-submenu ${openMenus.list ? 'open' : ''}`}>
            <Link href="/list/alldata" className={`sb-subitem ${isActive('/list/alldata') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> All Data
            </Link>
            <Link href="/list/pfupdate" className={`sb-subitem ${isActive('/list/pfupdate') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> PF Update
            </Link>
            <Link href="/list/newdata" className={`sb-subitem ${isActive('/list/newdata') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> New Data
            </Link>
            <Link href="/list/inactivedata" className={`sb-subitem ${isActive('/list/inactivedata') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> Inactive Data
            </Link>
          </div>
        </div>

        {/* Form 4 */}
        <div>
          <button className={`sb-item ${pathname.startsWith('/form4') ? 'active' : ''}`} onClick={() => toggleMenu('form4')}>
            <div className="sb-item-left" style={{ color: 'var(--icon-form)' }}>
              <FilePlus size={20} />
              <span>Form 4</span>
            </div>
            <ChevronRight size={18} className={`sb-chevron ${openMenus.form4 ? 'rotate' : ''}`} />
          </button>
          <div className={`sb-submenu ${openMenus.form4 ? 'open' : ''}`}>
            <Link href="/form4/addnew" className={`sb-subitem ${isActive('/form4/addnew') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> Add New
            </Link>
            <Link href="/form4/download" className={`sb-subitem ${isActive('/form4/download') ? 'active' : ''}`}>
              <span className="sb-subicon"></span> Download PDF
            </Link>
          </div>
        </div>
      </nav>

      <div className="sb-footer">
        <div style={{ marginBottom: '1rem', padding: '0 8px' }}>
          <p style={{ fontWeight: 600, fontSize: '0.9rem', color: 'var(--text-main)' }}>{agent.name}</p>
          <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>ID: {agent.id} | {agent.area}</p>
        </div>
        <form action={logoutAgent}>
          <button type="submit" className="sb-logout-btn">
            <LogOut size={18} />
            Logout
          </button>
        </form>
      </div>
    </aside>
  );
}
