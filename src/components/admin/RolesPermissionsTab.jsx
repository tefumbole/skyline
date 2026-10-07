import React, { useEffect, useState } from 'react';
import { getAllRolePermissions, createRoleWithPermissions, updateRolePermissions, PERMISSION_KEYS } from '@/services/rolePermissionService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/components/ui/use-toast';
import { Shield, Save, Plus, Check } from 'lucide-react';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';

const RolesPermissionsTab = () => {
    const [roles, setRoles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [newRole, setNewRole] = useState({ name: '', description: '' });
    const [createDialogOpen, setCreateDialogOpen] = useState(false);
    const { toast } = useToast();

    // State to track permissions being edited { roleId: [perms] }
    const [editBuffer, setEditBuffer] = useState({});

    const loadRoles = async () => {
        setLoading(true);
        const data = await getAllRolePermissions();
        setRoles(data);
        
        // Initialize edit buffer
        const initialBuffer = {};
        data.forEach(r => {
            initialBuffer[r.id] = [...r.permissions];
        });
        setEditBuffer(initialBuffer);
        
        setLoading(false);
    };

    useEffect(() => { loadRoles(); }, []);

    const handleCreateRole = async () => {
        if (!newRole.name) return;
        try {
            await createRoleWithPermissions(newRole.name, newRole.description, []);
            toast({ title: "Role Created", description: `${newRole.name} added successfully.` });
            setCreateDialogOpen(false);
            setNewRole({ name: '', description: '' });
            loadRoles();
        } catch (error) {
            toast({ title: "Error", description: error.message, variant: "destructive" });
        }
    };

    const togglePermission = (roleId, permissionKey) => {
        setEditBuffer(prev => {
            const currentPerms = prev[roleId] || [];
            const hasPerm = currentPerms.includes(permissionKey);
            
            let newPerms;
            if (hasPerm) {
                newPerms = currentPerms.filter(p => p !== permissionKey);
            } else {
                newPerms = [...currentPerms, permissionKey];
            }
            return { ...prev, [roleId]: newPerms };
        });
    };

    const savePermissions = async (role) => {
        try {
            await updateRolePermissions(role.id, editBuffer[role.id]);
            toast({ title: "Success", description: `Permissions for ${role.name} updated.` });
            loadRoles(); // Refresh to confirm persistence
        } catch (error) {
            toast({ title: "Save Failed", description: error.message, variant: "destructive" });
        }
    };

    // Group permissions for UI
    const groupedPermissions = PERMISSION_KEYS.reduce((acc, key) => {
        const group = key.split('_')[0]; // e.g., USERS_VIEW -> USERS
        if (!acc[group]) acc[group] = [];
        acc[group].push(key);
        return acc;
    }, {});

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <h2 className="text-xl font-semibold text-[#003D82]">Role Configuration</h2>
                <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
                    <DialogTrigger asChild>
                        <Button className="bg-[#D4AF37] text-[#003D82] hover:bg-[#b5952f] font-bold">
                            <Plus className="w-4 h-4 mr-2" /> New Role
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader><DialogTitle>Create New Role</DialogTitle></DialogHeader>
                        <div className="space-y-4 py-4">
                            <Input 
                                placeholder="Role Name (e.g. Finance Officer)" 
                                value={newRole.name} 
                                onChange={e => setNewRole({...newRole, name: e.target.value})} 
                            />
                            <Input 
                                placeholder="Description" 
                                value={newRole.description} 
                                onChange={e => setNewRole({...newRole, description: e.target.value})} 
                            />
                        </div>
                        <DialogFooter>
                            <Button onClick={handleCreateRole}>Create Role</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="grid gap-6">
                {roles.map(role => (
                    <Card key={role.id} className="border-t-4 border-t-[#003D82]">
                        <CardHeader className="pb-2">
                            <div className="flex justify-between items-start">
                                <div>
                                    <CardTitle className="flex items-center gap-2">
                                        <Shield className="w-5 h-5 text-[#D4AF37]" />
                                        {role.name}
                                        {role.is_default && <span className="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-normal">System Default</span>}
                                    </CardTitle>
                                    <p className="text-sm text-gray-500 mt-1">{role.description}</p>
                                </div>
                                <Button 
                                    size="sm" 
                                    onClick={() => savePermissions(role)}
                                    className="bg-green-600 hover:bg-green-700 text-white"
                                >
                                    <Save className="w-4 h-4 mr-2" /> Save Permissions
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Accordion type="single" collapsible className="w-full">
                                <AccordionItem value="permissions">
                                    <AccordionTrigger className="text-sm font-medium text-gray-600">
                                        Manage Access Rights ({editBuffer[role.id]?.length || 0} selected)
                                    </AccordionTrigger>
                                    <AccordionContent>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
                                            {Object.entries(groupedPermissions).map(([group, keys]) => (
                                                <div key={group} className="space-y-2 border p-3 rounded-lg bg-gray-50/50">
                                                    <h4 className="font-bold text-xs uppercase text-[#003D82] mb-2 border-b pb-1">{group}</h4>
                                                    <div className="space-y-2">
                                                        {keys.map(permKey => (
                                                            <div key={permKey} className="flex items-center space-x-2">
                                                                <Checkbox 
                                                                    id={`${role.id}-${permKey}`} 
                                                                    checked={editBuffer[role.id]?.includes(permKey)}
                                                                    onCheckedChange={() => togglePermission(role.id, permKey)}
                                                                    disabled={role.id === 'role-super-admin'} // Super Admin always full access
                                                                />
                                                                <label 
                                                                    htmlFor={`${role.id}-${permKey}`}
                                                                    className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                                                                >
                                                                    {permKey.split('_').slice(1).join(' ')} 
                                                                </label>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </AccordionContent>
                                </AccordionItem>
                            </Accordion>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
};

export default RolesPermissionsTab;