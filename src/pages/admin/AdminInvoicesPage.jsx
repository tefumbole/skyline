import React, { useState, useEffect } from 'react';
import { getInvoices, createInvoicePDF, sendInvoiceEmail } from '@/services/invoicesService';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Download, Mail, Search, FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const AdminInvoicesPage = () => {
    const [invoices, setInvoices] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const { toast } = useToast();

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        try {
            const data = await getInvoices();
            setInvoices(data);
        } catch (error) {
            toast({ title: "Error", description: "Failed to load invoices.", variant: "destructive" });
        } finally {
            setLoading(false);
        }
    };

    const handleDownload = (invoice) => {
        const doc = createInvoicePDF(invoice);
        doc.save(`${invoice.invoice_number}.pdf`);
        toast({ title: "Downloaded", description: "Invoice PDF downloaded successfully." });
    };

    const handleEmail = async (id) => {
        try {
            await sendInvoiceEmail(id);
            toast({ title: "Sent", description: "Invoice email sent to client." });
        } catch (error) {
            toast({ title: "Error", description: "Failed to send email.", variant: "destructive" });
        }
    };

    const filtered = invoices.filter(inv => 
        inv.client_name?.toLowerCase().includes(search.toLowerCase()) || 
        inv.invoice_number?.toLowerCase().includes(search.toLowerCase())
    );

    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div className="flex justify-between items-center border-b pb-4">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82] flex items-center gap-2">
                        <FileText className="w-8 h-8" /> Invoices
                    </h1>
                    <p className="text-gray-500">Manage client billing and invoices.</p>
                </div>
                <div className="relative w-64">
                    <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                    <Input 
                        placeholder="Search client or invoice #" 
                        className="pl-9" 
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                    />
                </div>
            </div>

            <div className="bg-white rounded-lg border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-gray-50">
                            <TableHead>Invoice #</TableHead>
                            <TableHead>Client</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {loading ? (
                             <TableRow><TableCell colSpan={6} className="h-32 text-center"><Loader2 className="w-6 h-6 animate-spin mx-auto" /></TableCell></TableRow>
                        ) : filtered.length === 0 ? (
                             <TableRow><TableCell colSpan={6} className="h-32 text-center text-gray-500">No invoices found.</TableCell></TableRow>
                        ) : (
                            filtered.map((inv) => (
                                <TableRow key={inv.id}>
                                    <TableCell className="font-medium">{inv.invoice_number}</TableCell>
                                    <TableCell>{inv.client_name}</TableCell>
                                    <TableCell>{new Date(inv.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell className="font-bold text-[#003D82]">${inv.total?.toFixed(2)}</TableCell>
                                    <TableCell>
                                        <Badge variant={inv.payment_status === 'paid' ? 'success' : 'secondary'} className={inv.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}>
                                            {inv.payment_status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="ghost" size="sm" onClick={() => handleDownload(inv)}>
                                                <Download className="w-4 h-4 text-blue-600" />
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => handleEmail(inv.id)}>
                                                <Mail className="w-4 h-4 text-gray-600" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
};

export default AdminInvoicesPage;