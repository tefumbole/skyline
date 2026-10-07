import React from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { cn } from '@/lib/utils';
import { CalendarOff, ShieldCheck, Briefcase, FileCheck, LayoutTemplate } from 'lucide-react';

const LETTER_TABS = [
  { label: 'Leave of Absence', path: '/admin/hr/letters/leave', icon: CalendarOff, color: 'blue' },
  { label: 'Permission', path: '/admin/hr/letters/permission', icon: ShieldCheck, color: 'purple' },
  { label: 'Employment Letter', path: '/admin/hr/letters/employment', icon: Briefcase, color: 'green' },
  { label: 'Attestation of Work', path: '/admin/hr/letters/attestation', icon: FileCheck, color: 'teal' },
  { label: 'Templates', path: '/admin/hr/letters/templates', icon: LayoutTemplate, color: 'orange' },
];

const COLOR_MAP = {
  blue: { idle: 'border-blue-400 bg-blue-50 text-blue-700 hover:bg-blue-100', active: 'border-blue-600 bg-blue-600 text-white shadow-md shadow-blue-200' },
  purple: { idle: 'border-purple-400 bg-purple-50 text-purple-700 hover:bg-purple-100', active: 'border-purple-600 bg-purple-600 text-white shadow-md shadow-purple-200' },
  green: { idle: 'border-green-400 bg-green-50 text-green-700 hover:bg-green-100', active: 'border-green-600 bg-green-600 text-white shadow-md shadow-green-200' },
  teal: { idle: 'border-teal-400 bg-teal-50 text-teal-700 hover:bg-teal-100', active: 'border-teal-600 bg-teal-600 text-white shadow-md shadow-teal-200' },
  orange: { idle: 'border-orange-400 bg-orange-50 text-orange-700 hover:bg-orange-100', active: 'border-orange-600 bg-orange-600 text-white shadow-md shadow-orange-200' },
};

export default function HrLettersTopNav() {
  const location = useLocation();
  return (
    <div className="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div className="text-[10px] font-bold uppercase tracking-widest text-slate-500 px-1 mb-2">HR Letters</div>
      <div className="flex flex-wrap gap-2">
        {LETTER_TABS.map((tab) => {
          const isActive = location.pathname === tab.path || location.pathname.startsWith(`${tab.path}/`);
          const theme = COLOR_MAP[tab.color];
          const Icon = tab.icon;
          return (
            <NavLink
              key={tab.path}
              to={tab.path}
              className={cn(
                'inline-flex items-center gap-1.5 px-3 py-2 rounded-full text-xs sm:text-sm font-semibold border-2 transition-all whitespace-nowrap',
                isActive ? theme.active : theme.idle
              )}
            >
              <Icon className="w-3.5 h-3.5 shrink-0" />
              {tab.label}
            </NavLink>
          );
        })}
      </div>
    </div>
  );
}
