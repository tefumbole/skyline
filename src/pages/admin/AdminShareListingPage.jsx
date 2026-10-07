import React, { useState, useEffect } from 'react';
import { getShareBookings, updateShareBooking, deleteShareBooking } from '@/services/shareBookingService';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Edit, Trash2, Search, PlusCircle, PieChart, RefreshCw } from 'lucide-react';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

const AdminShareListingPage = () => {
    const [bookings, setBookings] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const { toast } = useToast();

    useEffect(() => {
        fetchBookings();
    }, []);

    const fetchBookings = async () => {
        setLoading(true);
        try {
            const data = await getShareBookings();
            setBookings(data);
        } catch (error) {
            toast({ title: "Error", description: "Failed to fetch share bookings.", variant: "destructive" });
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id) => {
        try {
            await deleteShareBooking(id);
            toast({ title: "Success", description: "Booking deleted successfully." });
            fetchBookings();
        } catch (error) {
            toast({ title: "Error", description: error.message, variant: "destructive" });
        }
    };
    
    // Placeholder for edit functionality
    const handleEdit = (booking) => {
        toast({
            title: "Edit Feature 🚧",
            description: "This feature isn't implemented yet—but you can request it!",
        });
    }
    
    // Placeholder for add functionality
    const handleAdd = () => {
        toast({
            title: "Add Feature 🚧",
            description: "This feature isn't implemented yet—but you can request it!",
        });
    }

    const filteredBookings = bookings.filter(booking =>
        booking.shareholders?.name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82] flex items-center gap-2">
                        <PieChart className="w-8 h-8" /> Share Bookings
                    </h1>
                    <p className="text-gray-500">Monitor and manage all shareholder investment bookings.</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={fetchBookings} disabled={loading}>
                        <RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                        Refresh
                    </Button>
                    <Button onClick={handleAdd}>
                        <PlusCircle className="w-4 h-4 mr-2" /> Add New Booking
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>All Bookings</CardTitle>
                    <div className="relative w-64">
                        <Search className="absolute left-2 top-2.5 h-4 w-4 text-gray-400" />
                        <Input placeholder="Search by name..." className="pl-8" value={searchTerm} onChange={e => setSearchTerm(e.target.value)} />
                    </div>
                </CardHeader>
                <CardContent>
                    {loading ? (
                        <div className="flex justify-center py-8"><Loader2 className="w-8 h-8 animate-spin text-[#003D82]" /></div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Member Name</TableHead>
                                    <TableHead>Shares Booked</TableHead>
                                    <TableHead>Total Amount</TableHead>
                                    <TableHead>Booking Date</TableHead>
                                    <TableHead>Payment Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredBookings.length > 0 ? filteredBookings.map(booking => (
                                    <TableRow key={booking.id}>
                                        <TableCell className="font-medium">{booking.shareholders?.name || 'N/A'}</TableCell>
                                        <TableCell>{booking.shares_booked}</TableCell>
                                        <TableCell>${booking.total_amount?.toLocaleString()}</TableCell>
                                        <TableCell>{new Date(booking.booking_date).toLocaleDateString()}</TableCell>
                                        <TableCell>
                                            <Badge variant={booking.payment_status === 'completed' ? 'default' : 'secondary'}>
                                                {booking.payment_status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right space-x-1">
                                            <Button variant="ghost" size="sm" onClick={() => handleEdit(booking)}><Edit className="w-4 h-4 text-blue-600" /></Button>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button variant="ghost" size="sm"><Trash2 className="w-4 h-4 text-red-600" /></Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Delete Booking?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            This will permanently delete the booking for {booking.shareholders?.name}. This action cannot be undone.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction onClick={() => handleDelete(booking.id)} className="bg-red-600">Delete</AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </TableCell>
                                    </TableRow>
                                )) : (
                                     <TableRow><TableCell colSpan="6" className="text-center py-8 text-gray-500">No bookings found.</TableCell></TableRow>
                                )}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default AdminShareListingPage;