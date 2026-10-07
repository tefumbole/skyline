import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { cn } from '@/lib/utils';

/**
 * Horizontal sub-navigation shown on admin section pages (events, invitations, etc.)
 */
const AdminHorizontalNav = ({ items = [], title, description }) => {
  const location = useLocation();

  const isActive = (path) => {
    if (path.includes('?')) {
      return location.pathname + location.search === path;
    }
    if (location.pathname === path) return true;
    const hasMoreSpecificNav = items.some(
      (item) => item.path !== path && item.path.startsWith(`${path}/`)
    );
    if (hasMoreSpecificNav) return false;
    return location.pathname.startsWith(`${path}/`);
  };

  return (
    <div className="mb-6 space-y-3">
      {(title || description) && (
        <div>
          {title && <h1 className="text-2xl font-bold text-[#003D82]">{title}</h1>}
          {description && <p className="text-sm text-gray-500 mt-1">{description}</p>}
        </div>
      )}
      <nav className="flex flex-wrap gap-2 border-b border-gray-200 pb-3">
        {items.map((item) => (
          <Link
            key={item.path}
            to={item.path}
            className={cn(
              'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-colors border',
              isActive(item.path)
                ? 'bg-[#003D82] text-white border-[#003D82]'
                : 'bg-white text-gray-700 border-gray-200 hover:border-[#003D82] hover:text-[#003D82]'
            )}
          >
            {item.icon && <item.icon className="w-4 h-4" />}
            {item.label}
          </Link>
        ))}
      </nav>
    </div>
  );
};

export default AdminHorizontalNav;
