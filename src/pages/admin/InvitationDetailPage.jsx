import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { getInvitationById, resendInvitation, deleteInvitation, generateQRCode } from '@/services/invitationService';
import { getEventById } from '@/services/eventService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';
import { ArrowLeft, Send, Trash2, Download, CheckCircle, Clock, User, Calendar, MapPin } from 'lucide-react';

const InvitationDetailPage = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const { toast } = useToast();
    
    const [invitation, setInvitation] = useState(null);
    const [event, setEvent] = useState(null);
    const [qrCode, setQrCode] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const loadData = async () => {
            setLoading(true);
            try {
                const invRes = await getInvitationById(id);
                if (invRes.success) {
                    setInvitation(invRes.data);
                    const qr = await generateQRCode(invRes.data.invitation_code);
                    setQrCode(qr);
                    
                    if (invRes.data.event_id) {
                        const evtRes = await getEventById(invRes.data.event_id);
                        if (evtRes.success) setEvent(evtRes.data);
                    }
                } else {
                    toast({ title: "Invitation not found", variant: "destructive" });
                    navigate('/admin/invitations');
                }
            } catch (error) {
                console.error(error);
            } finally {
                setLoading(false);
            }
        };
        loadData();
    }, [id, navigate, toast]);

    if (loading) return <div className="p-8 text-center">Loading...</div>;
    if (!invitation) return <div className="p-8 text-center">Not found</div>;

    return (
        <div className="max-w-4xl mx-auto space-y-6 p-6">
            <Button variant="ghost" onClick={() => navigate(-1)} className="mb-4">
                <ArrowLeft className="w-4 h-4 mr-2" /> Back to List
            </Button>

            <div className="flex justify-between items-start">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82]">Invitation Details</h1>
                    <p className="text-gray-500 font-mono mt-1">ID: {invitation.invitation_code}</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" className="border-blue-200 text-blue-700 hover:bg-blue-50" onClick={() => resendInvitation(id).then(() => toast({title: "Resent!"}))}>
                        <Send className="w-4 h-4 mr-2" /> Resend
                    </Button>
                    <Button variant="destructive" onClick={() => {
                        if(window.confirm('Delete?')) {
                            deleteInvitation(id).then(() => navigate('/admin/invitations'));
                        }
                    }}>
                        <Trash2 className="w-4 h-4 mr-2" /> Delete
                    </Button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card className="md:col-span-2">
                    <CardHeader className="bg-gray-50 border-b">
                        <CardTitle>Guest Information</CardTitle>
                    </CardHeader>
                    <CardContent className="p-6 space-y-6">
                        <div className="flex items-start gap-4">
                            <div className="p-4 bg-blue-100 rounded-full text-blue-600">
                                <User className="w-8 h-8" />
                            </div>
                            <div>
                                <h2 className="text-2xl font-bold">{invitation.guest_name}</h2>
                                <p className="text-gray-500">{invitation.phone} {invitation.email && `• ${invitation.email}`}</p>
                                <div className="mt-2 flex gap-2">
                                    <Badge variant="outline">{invitation.category || 'Standard'}</Badge>
                                    <Badge className={invitation.checked_in ? 'bg-green-500' : 'bg-yellow-500'}>
                                        {invitation.checked_in ? 'Checked In' : 'Not Checked In'}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4 pt-6 border-t">
                            <div>
                                <p className="text-sm text-gray-500 mb-1">Event</p>
                                <p className="font-medium flex items-center"><Calendar className="w-4 h-4 mr-2 text-gray-400" /> {event?.name || 'Unknown'}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 mb-1">Date</p>
                                <p className="font-medium flex items-center"><Clock className="w-4 h-4 mr-2 text-gray-400" /> {event?.date || 'TBA'}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="bg-gray-50 border-b text-center">
                        <CardTitle>QR Code</CardTitle>
                    </CardHeader>
                    <CardContent className="p-6 flex flex-col items-center justify-center space-y-4">
                        {qrCode ? (
                            <img src={qrCode} alt="QR Code" className="w-48 h-48 border rounded p-2" />
                        ) : (
                            <div className="w-48 h-48 bg-gray-100 flex items-center justify-center rounded">No QR</div>
                        )}
                        <p className="font-mono text-sm text-center bg-gray-100 px-3 py-1 rounded w-full">
                            {invitation.invitation_code}
                        </p>
                        <Button variant="outline" className="w-full" onClick={() => {
                            const link = document.createElement('a');
                            link.href = qrCode;
                            link.download = `QR-${invitation.invitation_code}.png`;
                            link.click();
                        }}>
                            <Download className="w-4 h-4 mr-2" /> Download QR
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default InvitationDetailPage;