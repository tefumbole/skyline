import React, { useState, useEffect } from 'react';
import { approveEventRegistration, rejectEventRegistration, getEventRegistrations } from '@/services/eventService';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Check, X, Clock, Loader2 } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';

const AdminEventApprovalPanel = ({ eventId }) => {
    const [registrations, setRegistrations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [processing, setProcessing] = useState(null);
    const { toast } = useToast();

    const loadRegistrations = async () => {
        setLoading(true);
        try {
            const { data, error } = await getEventRegistrations(eventId);
            if (error) throw error;
            setRegistrations(data || []);
        } catch (error) {
            console.error(error);
            toast({ title: "Error", description: "Failed to load registrations.", variant: "destructive" });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadRegistrations();
    }, [eventId]);

    const handleApprove = async (id) => {
        setProcessing(id);
        try {
            const { data, error } = await approveEventRegistration(id);
            if (error) throw error;
            toast({ title: "Approved", description: "Registration approved and QR code sent." });
            loadRegistrations();
        } catch (error) {
            console.error(error);
            toast({ title: "Error", description: error.message || "Failed to approve.", variant: "destructive" });
        } finally {
            setProcessing(null);
        }
    };

    const handleReject = async (id) => {
        setProcessing(id);
        try {
            const { data, error } = await rejectEventRegistration(id, 'Admin Rejected');
            if (error) throw error;
            toast({ title: "Rejected", description: "Registration rejected and user notified." });
            loadRegistrations();
        } catch (error) {
            console.error(error);
            toast({ title: "Error", description: error.message || "Failed to reject.", variant: "destructive" });
        } finally {
            setProcessing(null);
        }
    };

    const pendingRegistrations = registrations.filter(r => r.status === 'Pending' || r.approval_status === 'pending');

    if (loading) {
        return (
            <div className="flex justify-center p-8 bg-gray-50 rounded-lg border">
                <Loader2 className="w-6 h-6 animate-spin text-[#003D82]" />
            </div>
        );
    }

    if (pendingRegistrations.length === 0) {
        return (
            <div className="text-center p-8 bg-gray-50 rounded-lg border border-dashed">
                <p className="text-gray-500">No pending registrations to review.</p>
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-lg border bg-white shadow">
            <div className="p-4 bg-gray-50 border-b">
                <h3 className="font-semibold text-gray-800">Pending Approvals ({pendingRegistrations.length})</h3>
            </div>
            <div className="divide-y">
                {pendingRegistrations.map((reg) => (
                    <div key={reg.id} className="p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <p className="font-bold text-gray-900">{reg.full_name || reg.guest_name || 'Unknown Guest'}</p>
                            <p className="text-sm text-gray-500">{reg.email} • {reg.phone}</p>
                            <div className="flex items-center gap-2 mt-1">
                                <Badge variant="outline" className="text-xs">{reg.company_name || 'Individual'}</Badge>
                                <span className="text-xs text-gray-400 flex items-center gap-1">
                                    <Clock className="w-3 h-3" /> {new Date(reg.created_at).toLocaleString()}
                                </span>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Button 
                                size="sm" 
                                variant="outline" 
                                className="text-green-600 hover:text-green-700 hover:bg-green-50 border-green-200"
                                onClick={() => handleApprove(reg.id)}
                                disabled={processing === reg.id}
                            >
                                {processing === reg.id ? <Loader2 className="w-4 h-4 mr-1 animate-spin" /> : <Check className="w-4 h-4 mr-1" />} Approve
                            </Button>
                            <Button 
                                size="sm" 
                                variant="outline" 
                                className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                                onClick={() => handleReject(reg.id)}
                                disabled={processing === reg.id}
                            >
                                {processing === reg.id ? <Loader2 className="w-4 h-4 mr-1 animate-spin" /> : <X className="w-4 h-4 mr-1" />} Reject
                            </Button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default AdminEventApprovalPanel;