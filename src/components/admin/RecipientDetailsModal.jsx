import React, { useState, useEffect } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getRecipientDetails } from '@/services/mailListingService';
import { Loader2, Download, CheckCircle, AlertCircle, Clock, FileText } from 'lucide-react';
import { format } from 'date-fns';
import { useToast } from '@/components/ui/use-toast';

const RecipientDetailsModal = ({ isOpen, onClose, recipientId }) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    if (isOpen && recipientId) {
      loadData();
    }
  }, [isOpen, recipientId]);

  const loadData = async () => {
    setLoading(true);
    try {
      const details = await getRecipientDetails(recipientId);
      setData(details);
    } catch (error) {
      toast({
        title: "Error loading recipient details",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'completed': return <Badge className="bg-green-100 text-green-800">Completed</Badge>;
      case 'failed': return <Badge className="bg-red-100 text-red-800">Failed</Badge>;
      case 'pending': return <Badge className="bg-blue-100 text-blue-800">Pending</Badge>;
      default: return <Badge variant="outline">{status}</Badge>;
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-3xl max-h-[80vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Recipient Details</DialogTitle>
        </DialogHeader>

        {loading ? (
          <div className="flex justify-center py-8">
            <Loader2 className="w-8 h-8 animate-spin text-[#003D82]" />
          </div>
        ) : data ? (
          <div className="space-y-6">
            <div className="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg border">
              <div>
                <p className="text-sm text-gray-500">Name</p>
                <p className="font-medium">{data.recipient_name}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Type</p>
                <p className="font-medium capitalize">{data.recipient_type}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Email</p>
                <p className="font-medium">{data.recipient_email || 'N/A'}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Phone</p>
                <p className="font-medium">{data.recipient_phone || 'N/A'}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Reference Code</p>
                <p className="font-medium font-mono">{data.reference_code}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Status</p>
                {getStatusBadge(data.status)}
              </div>
            </div>

            {data.pdf_url && (
              <div className="flex justify-end">
                <Button variant="outline" onClick={() => window.open(data.pdf_url, '_blank')}>
                  <Download className="w-4 h-4 mr-2" /> Download Generated PDF
                </Button>
              </div>
            )}

            <div>
              <h3 className="font-semibold text-lg mb-3">Delivery Queue Jobs</h3>
              {data.queueJobs?.length > 0 ? (
                <div className="border rounded-md divide-y">
                  {data.queueJobs.map(job => (
                    <div key={job.id} className="p-3 flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        {job.channel === 'email' ? <FileText className="w-4 h-4 text-blue-500"/> : <Clock className="w-4 h-4 text-green-500"/>}
                        <div>
                          <p className="font-medium capitalize">{job.channel} Delivery</p>
                          <p className="text-xs text-gray-500">Attempts: {job.attempts || 0}</p>
                        </div>
                      </div>
                      <div className="text-right">
                        {getStatusBadge(job.status)}
                        {job.last_error && <p className="text-xs text-red-500 mt-1 max-w-[200px] truncate" title={job.last_error}>{job.last_error}</p>}
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-gray-500 text-sm">No queue jobs found.</p>
              )}
            </div>

            <div>
              <h3 className="font-semibold text-lg mb-3">Activity Logs</h3>
              {data.logs?.length > 0 ? (
                <div className="space-y-2 border-l-2 border-gray-200 ml-3 pl-4">
                  {data.logs.map(log => (
                    <div key={log.id} className="relative">
                      <div className="absolute -left-[21px] top-1 w-2 h-2 rounded-full bg-blue-500"></div>
                      <p className="text-sm font-medium">{log.event_type.replace(/_/g, ' ')}</p>
                      <p className="text-xs text-gray-500">{format(new Date(log.created_at), 'MMM dd, yyyy HH:mm:ss')}</p>
                      {log.details && log.details.error && (
                        <p className="text-xs text-red-500 mt-1">{log.details.error}</p>
                      )}
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-gray-500 text-sm">No activity logs found.</p>
              )}
            </div>
          </div>
        ) : (
          <p className="text-center text-gray-500 py-8">Recipient not found.</p>
        )}
      </DialogContent>
    </Dialog>
  );
};

export default RecipientDetailsModal;