import React from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { cn } from '@/lib/utils';
import {
  Users, Tags, Briefcase, Calendar, Gift, MinusCircle,
  Wallet, FileText, CheckCircle, Landmark, BarChart3,
} from 'lucide-react';

const STAFF_TABS = [
  { label: 'Staff Management', path: '/admin/hr/staff', icon: Users, color: 'blue' },
  { label: 'Staff Categories', path: '/admin/hr/categories', icon: Tags, color: 'purple' },
];

const PAYROLL_TABS = [
  { label: 'Job / Event Payroll', path: '/admin/hr/jobs', icon: Briefcase, color: 'pink' },
  { label: 'Monthly Payroll', path: '/admin/hr/monthly-payroll', icon: Calendar, color: 'green' },
  { label: 'Allowances', path: '/admin/hr/allowances', icon: Gift, color: 'teal' },
  { label: 'Deductions', path: '/admin/hr/deductions', icon: MinusCircle, color: 'orange' },
  { label: 'Advance Payments', path: '/admin/hr/advances', icon: Wallet, color: 'indigo' },
  { label: 'Payslips', path: '/admin/hr/payslips', icon: FileText, color: 'cyan' },
  { label: 'Payroll Approvals', path: '/admin/hr/approvals', icon: CheckCircle, color: 'amber' },
  { label: 'Finance Status', path: '/admin/hr/finance', icon: Landmark, color: 'rose' },
  { label: 'Reports', path: '/admin/hr/reports', icon: BarChart3, color: 'lime' },
];

const COLOR_MAP = {
  blue: {
    idle: 'border-blue-400 bg-blue-50 text-blue-700 hover:bg-blue-100',
    active: 'border-blue-600 bg-blue-600 text-white shadow-md shadow-blue-200',
  },
  purple: {
    idle: 'border-purple-400 bg-purple-50 text-purple-700 hover:bg-purple-100',
    active: 'border-purple-600 bg-purple-600 text-white shadow-md shadow-purple-200',
  },
  pink: {
    idle: 'border-pink-400 bg-pink-50 text-pink-700 hover:bg-pink-100',
    active: 'border-pink-600 bg-pink-600 text-white shadow-md shadow-pink-200',
  },
  green: {
    idle: 'border-green-400 bg-green-50 text-green-700 hover:bg-green-100',
    active: 'border-green-600 bg-green-600 text-white shadow-md shadow-green-200',
  },
  teal: {
    idle: 'border-teal-400 bg-teal-50 text-teal-700 hover:bg-teal-100',
    active: 'border-teal-600 bg-teal-600 text-white shadow-md shadow-teal-200',
  },
  orange: {
    idle: 'border-orange-400 bg-orange-50 text-orange-700 hover:bg-orange-100',
    active: 'border-orange-600 bg-orange-600 text-white shadow-md shadow-orange-200',
  },
  indigo: {
    idle: 'border-indigo-400 bg-indigo-50 text-indigo-700 hover:bg-indigo-100',
    active: 'border-indigo-600 bg-indigo-600 text-white shadow-md shadow-indigo-200',
  },
  cyan: {
    idle: 'border-cyan-400 bg-cyan-50 text-cyan-700 hover:bg-cyan-100',
    active: 'border-cyan-600 bg-cyan-600 text-white shadow-md shadow-cyan-200',
  },
  amber: {
    idle: 'border-amber-400 bg-amber-50 text-amber-800 hover:bg-amber-100',
    active: 'border-amber-500 bg-amber-500 text-white shadow-md shadow-amber-200',
  },
  rose: {
    idle: 'border-rose-400 bg-rose-50 text-rose-700 hover:bg-rose-100',
    active: 'border-rose-600 bg-rose-600 text-white shadow-md shadow-rose-200',
  },
  lime: {
    idle: 'border-lime-500 bg-lime-50 text-lime-800 hover:bg-lime-100',
    active: 'border-lime-600 bg-lime-600 text-white shadow-md shadow-lime-200',
  },
};

function TabPill({ tab }) {
  const location = useLocation();
  const base = tab.path.split('?')[0];
  const isActive = location.pathname === base || location.pathname.startsWith(`${base}/`);
  const theme = COLOR_MAP[tab.color] || COLOR_MAP.blue;
  const Icon = tab.icon;

  return (
    <NavLink
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
}

function TabSection({ title, tabs }) {
  return (
    <div className="space-y-2">
      <div className="text-[10px] font-bold uppercase tracking-widest text-slate-500 px-1">{title}</div>
      <div className="flex flex-wrap gap-2">
        {tabs.map((tab) => (
          <TabPill key={tab.path} tab={tab} />
        ))}
      </div>
    </div>
  );
}

export default function HrTopNav() {
  return (
    <div className="mb-6 space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <TabSection title="Staff" tabs={STAFF_TABS} />
      <TabSection title="Payroll" tabs={PAYROLL_TABS} />
    </div>
  );
}

export { STAFF_TABS, PAYROLL_TABS };
