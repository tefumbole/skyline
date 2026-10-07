import React, { useState, useEffect } from 'react';
import { getCertificates, createCertificatePDF, revokeCertificate, sendCertificateEmail } from '@/services/certificatesService';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Download, Mail, Search, Award, Ban } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
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
} from "@/components/ui/alert-dialog";

const AdminCertificatesPage = () => {
    const [certificates, setCertificates] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const { toast } = useToast();

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        try {
            const data = await getCertificates();
            setCertificates(data);
        } catch (error) {
            toast({ title: "Error", description: "Failed to load certificates.", variant: "destructive" });
        } finally {
            setLoading(false);
        }
    };

    const handleDownload = (cert) => {
        const doc = createCertificatePDF(cert);
        doc.save(`${cert.certificate_number}.pdf`);
    };

    const handleEmail = async (id) => {
        try {
            await sendCertificateEmail(id);
            toast({ title: "Sent", description: "Certificate notification sent." });
        } catch (error) {
            toast({ title: "Error", description: "Failed to send email.", variant: "destructive" });
        }
    };

    const handleRevoke = async (id) => {
        try {
            await revokeCertificate(id);
            toast({ title: "Revoked", description: "Certificate has been revoked." });
            loadData();
        } catch (error) {
             toast({ title: "Error", description: "Failed to revoke certificate.", variant: "destructive" });
        }
    };

    const filtered = certificates.filter(c => 
        c.student_name?.toLowerCase().includes(search.toLowerCase()) || 
        c.certificate_number?.toLowerCase().includes(search.toLowerCase())
    );

    return (
        <div className="space-y-6 animate-in fade-in duration-500">
             <div className="flex justify-between items-center border-b pb-4">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82] flex items-center gap-2">
                        <Award className="w-8 h-8" /> Certificates
                    </h1>
                    <p className="text-gray-500">Manage issued course certificates.</p>
                </div>
                <div className="relative w-64">
                    <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                    <Input 
                        placeholder="Search student or cert #" 
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
                            <TableHead>Cert #</TableHead>
                            <TableHead>Student</TableHead>
                            <TableHead>Course</TableHead>
                            <TableHead>Issued Date</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {loading ? (
                             <TableRow><TableCell colSpan={6} className="h-32 text-center"><Loader2 className="w-6 h-6 animate-spin mx-auto" /></TableCell></TableRow>
                        ) : filtered.length === 0 ? (
                             <TableRow><TableCell colSpan={6} className="h-32 text-center text-gray-500">No certificates found.</TableCell></TableRow>
                        ) : (
                            filtered.map((cert) => (
                                <TableRow key={cert.id} className={cert.status === 'revoked' ? 'bg-red-50 opacity-75' : ''}>
                                    <TableCell className="font-mono text-xs">{cert.certificate_number}</TableCell>
                                    <TableCell className="font-medium">{cert.student_name}</TableCell>
                                    <TableCell>{cert.course_name}</TableCell>
                                    <TableCell>{new Date(cert.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell>
                                        <Badge variant={cert.status === 'active' ? 'success' : 'destructive'} className={cert.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}>
                                            {cert.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {cert.status === 'active' && (
                                                <>
                                                    <Button variant="ghost" size="sm" onClick={() => handleDownload(cert)}>
                                                        <Download className="w-4 h-4 text-blue-600" />
                                                    </Button>
                                                    <Button variant="ghost" size="sm" onClick={() => handleEmail(cert.id)}>
                                                        <Mail className="w-4 h-4 text-gray-600" />
                                                    </Button>
                                                    <AlertDialog>
                                                        <AlertDialogTrigger asChild>
                                                            <Button variant="ghost" size="sm" className="text-red-500 hover:text-red-700 hover:bg-red-50">
                                                                <Ban className="w-4 h-4" />
                                                            </Button>
                                                        </AlertDialogTrigger>
                                                        <AlertDialogContent>
                                                            <AlertDialogHeader>
                                                                <AlertDialogTitle>Revoke Certificate</AlertDialogTitle>
                                                                <AlertDialogDescription>
                                                                    Are you sure you want to revoke {cert.certificate_number}? This action indicates the certification is no longer valid.
                                                                </AlertDialogDescription>
                                                            </AlertDialogHeader>
                                                            <AlertDialogFooter>
                                                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                                <AlertDialogAction onClick={() => handleRevoke(cert.id)} className="bg-red-600">Revoke</AlertDialogAction>
                                                            </AlertDialogFooter>
                                                        </AlertDialogContent>
                                                    </AlertDialog>
                                                </>
                                            )}
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

export default AdminCertificatesPage;