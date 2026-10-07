import React, { useEffect, useState } from 'react';
import { getAllPermissions, createPermission } from '@/services/permissionService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Lock } from 'lucide-react';

const PermissionsTab = () => {
    const [perms, setPerms] = useState([]);
    
    useEffect(() => {
        getAllPermissions().then(setPerms);
    }, []);

    return (
        <div className="space-y-4">
            <Card>
                <CardContent className="p-0">
                     <table className="w-full text-sm">
                        <thead className="bg-gray-100 text-left">
                            <tr>
                                <th className="p-4">Permission Name</th>
                                <th className="p-4">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            {perms.map(p => (
                                <tr key={p.id} className="border-t">
                                    <td className="p-4 font-mono text-xs flex items-center gap-2">
                                        <Lock className="w-3 h-3 text-gray-400" />
                                        {p.name}
                                    </td>
                                    <td className="p-4 text-gray-600">{p.description}</td>
                                </tr>
                            ))}
                        </tbody>
                     </table>
                </CardContent>
            </Card>
        </div>
    );
};

export default PermissionsTab;