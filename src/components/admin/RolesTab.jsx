import React, { useEffect, useState } from 'react';
import { 
  getAllRoles, 
  createRole, 
  deleteRole, 
  updateRole 
} from '@/services/roleService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/use-toast';
import { Trash2, ShieldCheck } from 'lucide-react';

const RolesTab = () => {
    const [roles, setRoles] = useState([]);
    const [newRole, setNewRole] = useState({ name: '', description: '' });
    const [isOpen, setIsOpen] = useState(false);
    const { toast } = useToast();

    const load = async () => {
        const response = await getAllRoles();
        if (response.success) {
            setRoles(response.data || []);
        } else {
            toast({ title: "Error Loading Roles", description: response.error, variant: "destructive" });
        }
    };
    
    useEffect(() => { load(); }, []);

    const handleCreate = async () => {
        if (!newRole.name) return;
        const res = await createRole(newRole.name, newRole.description);
        if (res.success) {
            setIsOpen(false);
            setNewRole({ name: '', description: '' });
            load();
            toast({ title: "Role Created" });
        } else {
            toast({ title: "Error", description: res.error, variant: "destructive" });
        }
    };

    const handleDelete = async (id) => {
        try {
            const res = await deleteRole(id);
            if (res.success) {
                load();
                toast({ title: "Role Deleted" });
            } else {
                toast({ title: "Cannot Delete", description: res.error, variant: "destructive" });
            }
        } catch (e) {
            toast({ title: "Cannot Delete", description: e.message, variant: "destructive" });
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-end">
                <Dialog open={isOpen} onOpenChange={setIsOpen}>
                    <DialogTrigger asChild><Button>Create Role</Button></DialogTrigger>
                    <DialogContent>
                        <DialogHeader><DialogTitle>New Role</DialogTitle></DialogHeader>
                        <div className="space-y-4 py-4">
                            <Input placeholder="Role Name" value={newRole.name} onChange={e => setNewRole({...newRole, name: e.target.value})} />
                            <Input placeholder="Description" value={newRole.description} onChange={e => setNewRole({...newRole, description: e.target.value})} />
                            <Button onClick={handleCreate} className="w-full">Save Role</Button>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {roles.map(role => (
                    <Card key={role.id}>
                        <CardContent className="p-6 flex flex-col justify-between h-full">
                            <div>
                                <div className="flex items-center gap-2 mb-2">
                                    <ShieldCheck className="w-5 h-5 text-blue-600" />
                                    <h3 className="font-bold text-lg">{role.name}</h3>
                                </div>
                                <p className="text-gray-500 text-sm mb-4">{role.description}</p>
                            </div>
                            <div className="flex justify-between items-center mt-4 border-t pt-4">
                                <span className="text-xs text-gray-400">{role.is_default ? 'System Role' : 'Custom Role'}</span>
                                {!role.is_default && (
                                    <Button variant="ghost" size="sm" onClick={() => handleDelete(role.id)}>
                                        <Trash2 className="w-4 h-4 text-red-500" />
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
};

export default RolesTab;