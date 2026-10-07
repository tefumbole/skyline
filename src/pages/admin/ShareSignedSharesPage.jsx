import React, { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FileCheck, Search, Download, Eye, FileText, Loader2 } from 'lucide-react';
import { supabase } from '@/lib/customSupabaseClient';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';

const ShareSignedSharesPage = () => {
    const [agreements, setAgreements] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');

    useEffect(() => {
        fetchSignedAgreements();
    }, []);

    const fetchSignedAgreements = async () => {
        setLoading(true);
        try {
            // Fetch shareholders who have signed
            const { data, error } = await supabase
                .from('shareholders')
                .select('*')
                .not('agreement_signed_at', 'is', null)
                .order('agreement_signed_at', { ascending: false });

            if (error) throw error;
            setAgreements(data || []);
        } catch (error) {
            console.error("Error fetching signed agreements:", error);
        } finally {
            setLoading(false);
        }
    };

    const filteredAgreements = agreements.filter(agreement => 
        (agreement.name && agreement.name.toLowerCase().includes(searchTerm.toLowerCase())) ||
        (agreement.email && agreement.email.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    const handleDownloadPDF = (url) => {
        if (!url) return;
        window.open(url, '_blank');
    };

    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82]">Signed Agreements</h1>
                    <p className="text-gray-500">Manage and view signed shareholder contracts.</p>
                </div>
                <div className="relative w-full md:w-64">
                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-gray-400" />
                    <Input
                        placeholder="Search shareholders..."
                        className="pl-8"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                </div>
            </div>

            <Card className="border-t-4 border-t-[#D4AF37] shadow-md">
                <CardHeader>
                    <div className="flex items-center gap-2">
                        <FileCheck className="w-5 h-5 text-blue-600" />
                        <CardTitle>Agreement Registry</CardTitle>
                    </div>
                    <CardDescription>List of all digitally signed investor agreements.</CardDescription>
                </CardHeader>
                <CardContent>
                    {loading ? (
                        <div className="flex justify-center py-12 text-gray-500">
                             <div className="flex flex-col items-center gap-2">
                                <Loader2 className="w-8 h-8 animate-spin text-[#003D82]" /> 
                                <span className="text-sm font-medium">Loading records...</span>
                             </div>
                        </div>
                    ) : filteredAgreements.length === 0 ? (
                        <div className="text-center py-12 text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                            <FileText className="w-12 h-12 mx-auto mb-3 opacity-20" />
                            <p className="font-medium">No signed agreements found.</p>
                            {searchTerm && <p className="text-sm mt-1">Try adjusting your search criteria.</p>}
                        </div>
                    ) : (
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-gray-50">
                                        <TableHead className="font-semibold">Shareholder</TableHead>
                                        <TableHead className="font-semibold">Date Signed</TableHead>
                                        <TableHead className="font-semibold">Status</TableHead>
                                        <TableHead className="text-right font-semibold">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredAgreements.map((agreement) => (
                                        <TableRow key={agreement.id} className="hover:bg-gray-50 transition-colors">
                                            <TableCell>
                                                <div className="font-medium text-gray-900">{agreement.name}</div>
                                                <div className="text-sm text-gray-500">{agreement.email}</div>
                                            </TableCell>
                                            <TableCell>
                                                {agreement.agreement_signed_at 
                                                    ? format(new Date(agreement.agreement_signed_at), 'MMM dd, yyyy HH:mm') 
                                                    : 'N/A'
                                                }
                                            </TableCell>
                                            <TableCell>
                                                <Badge className="bg-green-100 text-green-800 border-green-200 hover:bg-green-100">
                                                    Signed
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    {agreement.pdf_url ? (
                                                        <Button 
                                                            variant="outline" 
                                                            size="sm" 
                                                            onClick={() => handleDownloadPDF(agreement.pdf_url)}
                                                            className="h-8 gap-1 text-blue-600 border-blue-200 hover:bg-blue-50"
                                                        >
                                                            <Download className="w-3 h-3" /> PDF
                                                        </Button>
                                                    ) : (
                                                        <span className="text-xs text-gray-400 italic px-2 py-1">No PDF</span>
                                                    )}
                                                    
                                                    {agreement.signature_image_url && (
                                                         <Button 
                                                            variant="ghost" 
                                                            size="sm" 
                                                            onClick={() => window.open(agreement.signature_image_url, '_blank')}
                                                            className="h-8 w-8 p-0 hover:text-blue-600"
                                                            title="View Signature"
                                                         >
                                                            <Eye className="w-4 h-4" />
                                                         </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default ShareSignedSharesPage;