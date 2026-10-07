import React from 'react';
import WhatsAppNotificationPanel from '@/components/WhatsAppNotificationPanel';

const AdminNotificationsPage = () => {
    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div>
                <h1 className="text-3xl font-bold text-[#003D82]">WhatsApp Notifications</h1>
                <p className="text-gray-500">Monitor and retry automated system messages</p>
            </div>
            
            <WhatsAppNotificationPanel />
        </div>
    );
};

export default AdminNotificationsPage;