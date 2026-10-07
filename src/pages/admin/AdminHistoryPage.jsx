import React from 'react';
import LetterHistoryTab from '@/components/admin/LetterHistoryTab';

const AdminHistoryPage = () => {
    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div>
                <h1 className="text-3xl font-bold text-[#003D82]">Letter History</h1>
                <p className="text-gray-500">Audit log of all sent correspondence</p>
            </div>
            
            <LetterHistoryTab />
        </div>
    );
};

export default AdminHistoryPage;