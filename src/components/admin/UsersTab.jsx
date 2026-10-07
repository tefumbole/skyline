import React, { useEffect, useState } from 'react';
import { getAllUsers, createUser, updateUser, deleteUser } from '@/services/userService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useToast } from '@/components/ui/use-toast';
import { Plus, Search, Trash2, Edit, Loader2, User, Mail, Phone, Shield, Lock, CheckCircle2, XCircle } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from '@/components/ui/badge';
import { supabase } from '@/lib/customSupabaseClient';

const UsersTab = () => {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    
    // Modal State
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    
    // Form State
    const [formData, setFormData] = useState({
        full_name: '',
        username: '',
        email: '',
        phone: '',
        role: 'Staff',
        password: '',
        confirmPassword: ''
    });

    const { toast } = useToast();

    const loadData = async () => {
        setLoading(true);
        try {
            const data = await getAllUsers();
            setUsers(data);
        } catch (e) {
            console.error("Failed to load users", e);
            toast({ title: "Error", description: "Failed to load user list.", variant: "destructive" });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { loadData(); }, []);

    const filteredUsers = users.filter(u => 
        (u.full_name?.toLowerCase() || '').includes(search.toLowerCase()) ||
        (u.email?.toLowerCase() || '').includes(search.toLowerCase()) ||
        (u.username?.toLowerCase() || '').includes(search.toLowerCase())
    );

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleRoleChange = (val) => {
        setFormData(prev => ({ ...prev, role: val }));
    };

    const validateForm = () => {
        if (!formData.email || !formData.full_name || !formData.username) {
            toast({ title: "Validation Error", description: "Please fill in all required fields.", variant: "destructive" });
            return false;
        }
        if (formData.password && formData.password !== formData.confirmPassword) {
            toast({ title: "Validation Error", description: "Passwords do not match.", variant: "destructive" });
            return false;
        }
        if (formData.password && formData.password.length < 6) {
             toast({ title: "Weak Password", description: "Password must be at least 6 characters.", variant: "destructive" });
             return false;
        }
        return true;
    };

    const handleCreate = async () => {
        if (!validateForm()) return;
        
        setIsSubmitting(true);
        try {
            // Note: In a real app, creating the AUTH user requires Supabase Admin API 
            // which isn't available in client-side JS safely.
            // We will attempt to sign up (which might log us out if not careful) 
            // OR just create the profile if the auth user is created separately.
            
            // For this specific implementation request, we will create the Profile entry directly.
            // If Supabase Auth is strictly required, please use the Invite User feature in Supabase Dashboard.
            
            // We will mock the "Auth Creation" by just adding the profile to the DB
            // assuming an external process or trigger handles the auth.
            
            await createUser({
                full_name: formData.full_name,
                email: formData.email,
                phone: formData.phone,
                role: formData.role,
                username: formData.username
            });

            toast({ title: "User Created", description: "User profile has been added successfully." });
            setIsCreateOpen(false);
            setFormData({ full_name: '', username: '', email: '', phone: '', role: 'Staff', password: '', confirmPassword: '' });
            loadData();
        } catch (e) {
            toast({ title: "Error", description: e.message || "Failed to create user.", variant: "destructive" });
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm("Are you sure you want to delete this user? This action cannot be undone.")) return;
        
        try {
            await deleteUser(id);
            toast({ title: "Deleted", description: "User removed successfully." });
            loadData();
        } catch (e) {
            toast({ title: "Error", description: "Failed to delete user.", variant: "destructive" });
        }
    };

    const getRoleBadgeColor = (role) => {
        switch((role || '').toLowerCase()) {
            case 'super admin': return 'bg-purple-100 text-purple-800 border-purple-200';
            case 'admin': return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'manager': return 'bg-orange-100 text-orange-800 border-orange-200';
            default: return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div className="relative w-full sm:w-72">
                    <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                    <Input 
                        placeholder="Search users by name, email..." 
                        className="pl-9" 
                        value={search} 
                        onChange={e => setSearch(e.target.value)} 
                    />
                </div>
                
                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button className="bg-[#003D82] hover:bg-[#002e63] shadow-sm w-full sm:w-auto">
                            <Plus className="mr-2 h-4 w-4" /> Add New User
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle className="text-xl font-bold text-[#003D82] flex items-center gap-2">
                                <User className="w-5 h-5" /> Create New User
                            </DialogTitle>
                        </DialogHeader>
                        
                        <div className="grid gap-4 py-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Full Name <span className="text-red-500">*</span></Label>
                                    <Input 
                                        name="full_name" 
                                        value={formData.full_name} 
                                        onChange={handleInputChange} 
                                        placeholder="John Doe" 
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Username <span className="text-red-500">*</span></Label>
                                    <Input 
                                        name="username" 
                                        value={formData.username} 
                                        onChange={handleInputChange} 
                                        placeholder="jdoe" 
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Email Address <span className="text-red-500">*</span></Label>
                                <div className="relative">
                                    <Mail className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                                    <Input 
                                        name="email" 
                                        type="email"
                                        className="pl-9"
                                        value={formData.email} 
                                        onChange={handleInputChange} 
                                        placeholder="john@example.com" 
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Phone Number</Label>
                                    <div className="relative">
                                        <Phone className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                                        <Input 
                                            name="phone" 
                                            className="pl-9"
                                            value={formData.phone} 
                                            onChange={handleInputChange} 
                                            placeholder="+1 234..." 
                                        />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Role</Label>
                                    <Select value={formData.role} onValueChange={handleRoleChange}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Staff">Staff</SelectItem>
                                            <SelectItem value="Manager">Manager</SelectItem>
                                            <SelectItem value="Admin">Admin</SelectItem>
                                            <SelectItem value="Super Admin">Super Admin</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="border-t pt-4 mt-2">
                                <Label className="text-gray-900 font-semibold mb-3 block">Set Password</Label>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Input 
                                            name="password" 
                                            type="password"
                                            value={formData.password} 
                                            onChange={handleInputChange} 
                                            placeholder="Password" 
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Input 
                                            name="confirmPassword" 
                                            type="password"
                                            value={formData.confirmPassword} 
                                            onChange={handleInputChange} 
                                            placeholder="Confirm Password" 
                                        />
                                    </div>
                                </div>
                                <p className="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                    <Lock className="w-3 h-3" /> Min 6 chars. (Note: Auth creation handled separately)
                                </p>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsCreateOpen(false)}>Cancel</Button>
                            <Button onClick={handleCreate} disabled={isSubmitting} className="bg-[#003D82]">
                                {isSubmitting ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" /> Creating...</> : 'Create User'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <Card className="border-gray-200 shadow-sm overflow-hidden">
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left">
                            <thead className="bg-gray-50 text-gray-600 border-b">
                                <tr>
                                    <th className="p-4 font-semibold">User Details</th>
                                    <th className="p-4 font-semibold">Contact Info</th>
                                    <th className="p-4 font-semibold">Role</th>
                                    <th className="p-4 font-semibold">Status</th>
                                    <th className="p-4 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {loading ? (
                                    <tr>
                                        <td colSpan="5" className="p-8 text-center text-gray-500">
                                            <Loader2 className="w-6 h-6 animate-spin mx-auto mb-2" />
                                            Loading users...
                                        </td>
                                    </tr>
                                ) : filteredUsers.length === 0 ? (
                                    <tr>
                                        <td colSpan="5" className="p-8 text-center text-gray-500">
                                            No users found matching your search.
                                        </td>
                                    </tr>
                                ) : (
                                    filteredUsers.map(u => (
                                        <tr key={u.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="p-4">
                                                <div className="flex flex-col">
                                                    <span className="font-semibold text-gray-900">{u.full_name || 'No Name'}</span>
                                                    <span className="text-xs text-gray-500">@{u.username || 'unknown'}</span>
                                                </div>
                                            </td>
                                            <td className="p-4">
                                                <div className="flex flex-col gap-1">
                                                    <div className="flex items-center gap-1.5 text-gray-600">
                                                        <Mail className="w-3.5 h-3.5" />
                                                        <span>{u.email}</span>
                                                    </div>
                                                    {u.phone && (
                                                        <div className="flex items-center gap-1.5 text-gray-500 text-xs">
                                                            <Phone className="w-3 h-3" />
                                                            <span>{u.phone}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="p-4">
                                                <Badge variant="outline" className={`${getRoleBadgeColor(u.role)} font-medium`}>
                                                    {u.role || 'Staff'}
                                                </Badge>
                                            </td>
                                            <td className="p-4">
                                                <div className="flex items-center gap-1.5">
                                                    {u.status === 'Active' ? (
                                                        <CheckCircle2 className="w-4 h-4 text-green-500" />
                                                    ) : (
                                                        <XCircle className="w-4 h-4 text-gray-400" />
                                                    )}
                                                    <span className={u.status === 'Active' ? 'text-green-700' : 'text-gray-500'}>
                                                        {u.status || 'Active'}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="p-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button variant="ghost" size="icon" className="h-8 w-8 text-gray-500 hover:text-[#003D82]">
                                                        <Edit className="w-4 h-4" />
                                                    </Button>
                                                    <Button 
                                                        variant="ghost" 
                                                        size="icon" 
                                                        onClick={() => handleDelete(u.id)}
                                                        className="h-8 w-8 text-gray-500 hover:text-red-600 hover:bg-red-50"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default UsersTab;