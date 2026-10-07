import React, { useState, useEffect } from 'react';
import { getAllRegistrations } from '@/services/registrationsService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/use-toast';
import { Search, Filter, Loader2, Eye, DollarSign, Users, CheckCircle, Calendar } from 'lucide-react';
import RegistrationDetailsModal from '@/components/admin/RegistrationDetailsModal';
import { Badge } from '@/components/ui/badge';

const AdminRegistrationsPage = () => {
    const [registrations, setRegistrations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [selectedRegistration, setSelectedRegistration] = useState(null);
    const { toast } = useToast();

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        try {
            const data = await getAllRegistrations();
            setRegistrations(data);
        } catch (error) {
            toast({ title: "Error", description: "Failed to load registrations.", variant: "destructive" });
        } finally {
            setLoading(false);
        }
    };

    const filteredRegistrations = registrations.filter(reg => {
        const matchesSearch = 
            reg.client_name?.toLowerCase().includes(search.toLowerCase()) ||
            reg.client_email?.toLowerCase().includes(search.toLowerCase());
        const matchesStatus = statusFilter === 'all' || reg.status === statusFilter;
        return matchesSearch && matchesStatus;
    });

    // Statistics
    const totalRevenue = registrations.reduce((sum, r) => sum + Number(r.total_price || 0), 0);
    const statusCounts = registrations.reduce((acc, r) => {
        acc[r.status] = (acc[r.status] || 0) + 1;
        return acc;
    }, {});

    const getStatusColor = (status) => {
        switch(status) {
            case 'confirmed': return 'bg-green-100 text-green-800 hover:bg-green-200';
            case 'completed': return 'bg-blue-100 text-blue-800 hover:bg-blue-200';
            case 'cancelled': return 'bg-red-100 text-red-800 hover:bg-red-200';
            default: return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200';
        }
    };

    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82]">Registration Management</h1>
                    <p className="text-gray-500">Monitor and manage course enrollments.</p>
                </div>
                <Button onClick={loadData} variant="outline" size="sm" disabled={loading}>
                    <Calendar className="w-4 h-4 mr-2" /> Refresh Data
                </Button>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Total Registrations</CardTitle>
                        <Users className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{registrations.length}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
                        <DollarSign className="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold text-green-700">${totalRevenue.toLocaleString()}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Confirmed</CardTitle>
                        <CheckCircle className="h-4 w-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{statusCounts['confirmed'] || 0}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Pending</CardTitle>
                        <Loader2 className="h-4 w-4 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{statusCounts['pending'] || 0}</div>
                    </CardContent>
                </Card>
            </div>

            {/* Filters */}
            <div className="flex flex-col md:flex-row gap-4 items-center bg-white p-4 rounded-lg border shadow-sm">
                <div className="relative w-full md:w-96">
                    <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                    <Input 
                        placeholder="Search client name, email..." 
                        className="pl-9" 
                        value={search} 
                        onChange={e => setSearch(e.target.value)} 
                    />
                </div>
                <Select value={statusFilter} onValueChange={setStatusFilter}>
                    <SelectTrigger className="w-full md:w-[200px]">
                        <div className="flex items-center gap-2">
                            <Filter className="w-4 h-4 text-gray-500" />
                            <SelectValue placeholder="Filter Status" />
                        </div>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="confirmed">Confirmed</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {/* Table */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-gray-50">
                            <TableHead>Client Name</TableHead>
                            <TableHead>Contact</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Courses</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {loading ? (
                             <TableRow><TableCell colSpan={7} className="text-center h-32"><Loader2 className="w-6 h-6 animate-spin mx-auto text-[#003D82]" /></TableCell></TableRow>
                        ) : filteredRegistrations.length === 0 ? (
                             <TableRow><TableCell colSpan={7} className="text-center h-32 text-gray-500">No registrations found.</TableCell></TableRow>
                        ) : (
                            filteredRegistrations.map((reg) => (
                                <TableRow key={reg.id} className="hover:bg-gray-50">
                                    <TableCell className="font-medium">{reg.client_name}</TableCell>
                                    <TableCell className="text-sm">
                                        <div className="flex flex-col">
                                            <span>{reg.client_email}</span>
                                            <span className="text-gray-400 text-xs">{reg.client_phone}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>{new Date(reg.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{Array.isArray(reg.course_ids) ? reg.course_ids.length : 0} Items</Badge>
                                    </TableCell>
                                    <TableCell className="font-semibold text-[#003D82]">${Number(reg.total_price).toLocaleString()}</TableCell>
                                    <TableCell>
                                        <Badge className={getStatusColor(reg.status)} variant="secondary">{reg.status}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button size="sm" variant="ghost" onClick={() => setSelectedRegistration(reg)}>
                                            <Eye className="w-4 h-4 text-blue-600" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            <RegistrationDetailsModal 
                registration={selectedRegistration} 
                open={!!selectedRegistration} 
                onClose={() => setSelectedRegistration(null)}
                onUpdate={loadData}
            />
        </div>
    );
};

export default AdminRegistrationsPage;