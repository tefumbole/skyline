import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import AdminHorizontalNav from '@/components/admin/AdminHorizontalNav';
import { INVITATION_NAV } from '@/config/invitationNavConfig';
import { getAllInvitations, deleteInvitation, resendInvitation } from '@/services/invitationService';
import { getAllEvents } from '@/services/eventService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/use-toast';
import { Search, Eye, Trash2, Send, QrCode, Loader2, Plus } from 'lucide-react';

const InvitationListPage = () => {
    const [invitations, setInvitations] = useState([]);
    const [events, setEvents] = useState({});
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [categoryFilter, setCategoryFilter] = useState('all');
    const navigate = useNavigate();
    const { toast } = useToast();

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        try {
            const [invRes, evtRes] = await Promise.all([
                getAllInvitations(),
                getAllEvents()
            ]);

            if (invRes.success) setInvitations(invRes.data);
            if (evtRes.success) {
                const evtMap = {};
                evtRes.data.forEach(e => evtMap[e.id] = e.name);
                setEvents(evtMap);
            }
        } catch (error) {
            toast({ title: "Error loading data", variant: "destructive" });
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm("Are you sure you want to delete this invitation?")) return;
        const res = await deleteInvitation(id);
        if (res.success) {
            toast({ title: "Deleted successfully" });
            loadData();
        }
    };

    const handleResend = async (id) => {
        toast({ title: "Resending invitation..." });
        const res = await resendInvitation(id);
        if (res.success) {
            toast({ title: "Invitation resent successfully" });
            loadData();
        } else {
            toast({ title: "Failed to resend", variant: "destructive" });
        }
    };

    const filteredInvitations = invitations.filter(inv => {
        const matchesSearch = inv.guest_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                              inv.invitation_code?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                              inv.phone?.includes(searchTerm);
        const matchesStatus = statusFilter === 'all' || inv.delivery_status === statusFilter || inv.status === statusFilter;
        const matchesCategory = categoryFilter === 'all' || inv.category === categoryFilter;
        return matchesSearch && matchesStatus && matchesCategory;
    });

    return (
        <div className="p-6 space-y-6">
            <AdminHorizontalNav items={INVITATION_NAV} title="Digital Invitations" description="Manage event invitations and entry." />
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82]">Invitations</h1>
                    <p className="text-gray-500">Manage all event invitations</p>
                </div>
                <Button onClick={() => navigate('/admin/invitations/create')} className="bg-[#003D82]">
                    <Plus className="w-4 h-4 mr-2" /> Create Invitation
                </Button>
            </div>

            <Card>
                <CardHeader className="bg-gray-50 border-b">
                    <div className="flex flex-col md:flex-row gap-4 justify-between items-center">
                        <div className="relative w-full md:w-1/3">
                            <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                            <Input 
                                placeholder="Search by name, ID, or phone..." 
                                className="pl-9"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>
                        <div className="flex w-full md:w-2/3 gap-4 justify-end">
                            <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                                <SelectTrigger className="w-full md:w-48 bg-white">
                                    <SelectValue placeholder="Filter by Category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Categories</SelectItem>
                                    <SelectItem value="VIP">VIP</SelectItem>
                                    <SelectItem value="Standard">Standard</SelectItem>
                                    <SelectItem value="Government">Government</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={statusFilter} onValueChange={setStatusFilter}>
                                <SelectTrigger className="w-full md:w-48 bg-white">
                                    <SelectValue placeholder="Filter by Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="Pending">Pending</SelectItem>
                                    <SelectItem value="Sent">Sent</SelectItem>
                                    <SelectItem value="Delivered">Delivered</SelectItem>
                                    <SelectItem value="Failed">Failed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    {loading ? (
                        <div className="flex justify-center p-8"><Loader2 className="w-8 h-8 animate-spin text-[#003D82]" /></div>
                    ) : filteredInvitations.length === 0 ? (
                        <div className="text-center p-8 text-gray-500">No invitations found.</div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Guest Name</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead>Invitation ID</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Check-in</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredInvitations.map((inv) => (
                                    <TableRow key={inv.id}>
                                        <TableCell className="font-medium">
                                            {inv.guest_name}
                                            <div className="text-xs text-gray-500">{inv.phone}</div>
                                        </TableCell>
                                        <TableCell>{events[inv.event_id] || 'Unknown Event'}</TableCell>
                                        <TableCell>
                                            <Badge variant={inv.category === 'VIP' ? 'default' : 'outline'} className={inv.category === 'VIP' ? 'bg-yellow-500 hover:bg-yellow-600' : ''}>
                                                {inv.category || 'Standard'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="font-mono text-xs">{inv.invitation_code}</TableCell>
                                        <TableCell>
                                            <Badge className={
                                                inv.status === 'Sent' || inv.delivery_status === 'Sent' ? 'bg-blue-100 text-blue-800' :
                                                inv.status === 'Delivered' ? 'bg-green-100 text-green-800' :
                                                inv.status === 'Failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                                            }>
                                                {inv.status || inv.delivery_status || 'Pending'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {inv.checked_in ? (
                                                <Badge className="bg-green-500">Checked In</Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-gray-400">Waiting</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button size="icon" variant="ghost" onClick={() => navigate(`/admin/invitations/${inv.id}`)}>
                                                    <Eye className="w-4 h-4 text-blue-600" />
                                                </Button>
                                                <Button size="icon" variant="ghost" onClick={() => handleResend(inv.id)}>
                                                    <Send className="w-4 h-4 text-green-600" />
                                                </Button>
                                                <Button size="icon" variant="ghost" onClick={() => handleDelete(inv.id)}>
                                                    <Trash2 className="w-4 h-4 text-red-600" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default InvitationListPage;