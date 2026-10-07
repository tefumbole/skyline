import { PlusCircle, ListTodo, QrCode } from 'lucide-react';

export const INVITATION_NAV = [
  { label: 'Create Invitation', path: '/admin/invitations/create', icon: PlusCircle },
  { label: 'All Invitations', path: '/admin/invitations', icon: ListTodo },
  { label: 'QR Check-In', path: '/admin/check-in', icon: QrCode },
];
