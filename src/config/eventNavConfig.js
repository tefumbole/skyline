import { CalendarDays, PlusCircle, Utensils, LineChart } from 'lucide-react';

export const EVENT_NAV = [
  { label: 'Event Manager', path: '/admin/events', icon: CalendarDays },
  { label: 'Create Event', path: '/admin/events/create', icon: PlusCircle },
  { label: 'Meal List', path: '/admin/events/meals', icon: Utensils },
  { label: 'Create Meal', path: '/admin/events/meals/create', icon: PlusCircle },
  { label: 'Analytics', path: '/admin/events/analytics', icon: LineChart },
];
