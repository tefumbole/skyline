import React, { useState, useEffect } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { getMessageDetails, resendToFailedRecipients } from '@/services/mailListingService';
import { Loader2, RefreshCw, Paperclip, Eye, Mail, MessageSquare } from 'lucide-react';
import { format } from 'date-fns';
import { useToast } from '@/components/ui/use-toast';
import RecipientDetailsModal from './RecipientDetailsModal';

const MessageDetailsModal = ({ isOpen, onClose, messageId }) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const [selectedRecipientId, setSelectedRecipientId] = useState(null);
  const { toast } = useToast();

  useEffect(() => {
    if (isOpen && messageId) {
      loadData();
    }
  }, [isOpen, messageId]);

  const loadData = async () => {
    setLoading(true);
    try {
      const details = await getMessageDetails(messageId);
      setData(details);
    } catch (error) {
      toast({
        title: "Error loading message details",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const handleResendFailed = async () => {
    setResending(true);
    try {
      const count = await resendToFailedRecipients(messageId);
      toast({
        title: "Resend Initiated",
        description: `Queued ${count} failed messages for retry.`
      });
    } catch (error) {
      toast({
        title: "Failed to resend",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setResending(false);
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'sent': case 'completed': return <Badge className="bg-green-100 text-green-800">Completed</Badge>;
      case 'failed': return <Badge className="bg-red-100 text-red-800">Failed</Badge>;
      case 'sending': case 'processing': return <Badge className="bg-yellow-100 text-yellow-800">Processing</Badge>;
      case 'scheduled': return <Badge className="bg-purple-100 text-purple-800">Scheduled</Badge>;
      default: return <Badge variant="outline">{status}</Badge>;
    }
  };

  return (
    <>
      <Dialog open={isOpen} onOpenChange={onClose}>
        <DialogContent className="max-w-4xl max-h-[90vh] flex flex-col p-0">
          <DialogHeader className="p-6 border-b shrink-0">
            <DialogTitle className="flex justify-between items-center">
              <span>Message Details: {data?.reference || 'Loading...'}</span>
              {data?.status === 'failed' || data?.recipients?.some(r => r.status === 'failed') ? (
                <Button size="sm" variant="outline" onClick={handleResendFailed} disabled={resending}>
                  {resending ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <RefreshCw className="w-4 h-4 mr-2" />}
                  Retry Failed
                </Button>
              ) : null}
            </DialogTitle>
          </DialogHeader>

          <div className="flex-1 overflow-y-auto p-6">
            {loading ? (
              <div className="flex justify-center py-12">
                <Loader2 className="w-8 h-8 animate-spin text-[#003D82]" />
              </div>
            ) : data ? (
              <div className="space-y-6">
                {/* Meta Info */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-lg border">
                  <div>
                    <p className="text-sm text-gray-500">Subject</p>
                    <p className="font-medium truncate" title={data.subject}>{data.subject}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-500">Category</p>
                    <p className="font-medium">{data.category || 'General'}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-500">Status</p>
                    {getStatusBadge(data.status)}
                  </div>
                  <div>
                    <p className="text-sm text-gray-500">Created</p>
                    <p className="font-medium">{format(new Date(data.created_at), 'MMM dd, yyyy')}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-500">Channels</p>
                    <div className="flex gap-2 mt-1">
                      {data.send_email && <Mail className="w-4 h-4 text-blue-500" title="Email" />}
                      {data.send_whatsapp && <MessageSquare className="w-4 h-4 text-green-500" title="WhatsApp" />}
                    </div>
                  </div>
                </div>

                {/* Body */}
                <div>
                  <h3 className="text-sm font-semibold text-gray-700 uppercase mb-2">Message Body</h3>
                  <div className="bg-white border rounded-md p-4 min-h-[100px] whitespace-pre-wrap text-sm">
                    {data.body}
                  </div>
                </div>

                {/* Attachments */}
                {data.attachments?.length > 0 && (
                  <div>
                    <h3 className="text-sm font-semibold text-gray-700 uppercase mb-2">Attachments</h3>
                    <div className="flex flex-wrap gap-2">
                      {data.attachments.map(att => (
                        <a 
                          key={att.id} 
                          href={att.file_url} 
                          target="_blank" 
                          rel="noopener noreferrer"
                          className="flex items-center gap-2 px-3 py-2 bg-gray-100 border rounded hover:bg-gray-200 transition-colors text-sm"
                        >
                          <Paperclip className="w-4 h-4 text-gray-500" />
                          <span className="truncate max-w-[200px]">{att.file_name}</span>
                        </a>
                      ))}
                    </div>
                  </div>
                )}

                {/* Recipients Table */}
                <div>
                  <h3 className="text-sm font-semibold text-gray-700 uppercase mb-2">
                    Recipients ({data.recipients?.length || 0})
                  </h3>
                  <div className="border rounded-md overflow-x-auto">
                    <Table>
                      <TableHeader className="bg-gray-50">
                        <TableRow>
                          <TableHead>Name</TableHead>
                          <TableHead>Contact</TableHead>
                          <TableHead>Ref Code</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead className="text-right">Action</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {data.recipients?.map(rec => (
                          <TableRow key={rec.id}>
                            <TableCell className="font-medium">{rec.recipient_name}</TableCell>
                            <TableCell className="text-xs text-gray-500">
                              <div>{rec.recipient_email}</div>
                              <div>{rec.recipient_phone}</div>
                            </TableCell>
                            <TableCell className="font-mono text-xs">{rec.reference_code}</TableCell>
                            <TableCell>{getStatusBadge(rec.status)}</TableCell>
                            <TableCell className="text-right">
                              <Button variant="ghost" size="sm" onClick={() => setSelectedRecipientId(rec.id)}>
                                <Eye className="w-4 h-4" />
                              </Button>
                            </TableCell>
                          </TableRow>
                        ))}
                        {data.recipients?.length === 0 && (
                          <TableRow>
                            <TableCell colSpan={5} className="text-center py-4 text-gray-500">No recipients found</TableCell>
                          </TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </div>
                </div>

              </div>
            ) : (
              <p className="text-center text-gray-500">Message not found.</p>
            )}
          </div>
          <div className="p-4 border-t shrink-0 flex justify-end bg-gray-50">
            <Button variant="outline" onClick={onClose}>Close</Button>
          </div>
        </DialogContent>
      </Dialog>

      <RecipientDetailsModal 
        isOpen={!!selectedRecipientId} 
        onClose={() => setSelectedRecipientId(null)} 
        recipientId={selectedRecipientId} 
      />
    </>
  );
};

export default MessageDetailsModal;