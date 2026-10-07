import React from 'react';
import SendLettersTab from '@/components/admin/SendLettersTab';

const AdminSendLettersPage = () => {
    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div>
                <h1 className="text-3xl font-bold text-[#003D82]">Send Letters</h1>
                <p className="text-gray-500">Bulk send official documents via WhatsApp</p>
            </div>
            
            <SendLettersTab />
        </div>
    );
};

export default AdminSendLettersPage;