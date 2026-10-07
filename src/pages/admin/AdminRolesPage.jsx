import React from 'react';
import RolesPermissionsTab from '@/components/admin/RolesPermissionsTab';

const AdminRolesPage = () => {
    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div>
                <h1 className="text-3xl font-bold text-[#003D82]">Role & Permission Management</h1>
                <p className="text-gray-500">Configure system access levels and role capabilities.</p>
            </div>
            
            <RolesPermissionsTab />
        </div>
    );
};

export default AdminRolesPage;