import React, { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { getStatusHistory } from '@/services/applicationStatusService';
import { Loader2, History, CheckCircle, XCircle, Clock, Circle } from 'lucide-react';

const ApplicationStatusHistoryModal = ({ isOpen, onClose, applicationId }) => {
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (isOpen && applicationId) {
      setLoading(true);
      getStatusHistory(applicationId)
        .then(setHistory)
        .catch(console.error)
        .finally(() => setLoading(false));
    }
  }, [isOpen, applicationId]);

  const getIcon = (status) => {
    switch (status) {
        case 'shortlisted': return <CheckCircle className="w-5 h-5 text-green-600" />;
        case 'rejected': return <XCircle className="w-5 h-5 text-red-600" />;
        case 'pending': return <Clock className="w-5 h-5 text-yellow-600" />;
        default: return <Circle className="w-5 h-5 text-gray-400" />;
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-[#003D82]">
             <History className="w-5 h-5" /> Status Timeline
          </DialogTitle>
        </DialogHeader>
        
        <div className="mt-4 max-h-[60vh] overflow-y-auto pr-2">
            {loading ? (
                <div className="flex justify-center p-4"><Loader2 className="w-6 h-6 animate-spin text-blue-600" /></div>
            ) : history.length === 0 ? (
                <div className="text-center text-gray-500 py-4">No history records found.</div>
            ) : (
                <div className="space-y-6 relative before:absolute before:inset-0 before:ml-2.5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-300 before:to-transparent">
                    {history.map((record) => (
                        <div key={record.id} className="relative flex items-start group">
                            <div className="absolute left-0 top-0 bg-white rounded-full p-0.5 border-2 border-slate-300 z-10 group-hover:border-[#003D82] transition-colors">
                                {getIcon(record.new_status)}
                            </div>
                            <div className="ml-8 w-full bg-slate-50 p-3 rounded-lg border text-sm hover:shadow-sm transition-shadow">
                                <div className="flex justify-between items-start mb-1">
                                    <span className="font-semibold text-gray-900 capitalize">
                                        {record.new_status}
                                    </span>
                                    <span className="text-xs text-gray-400 whitespace-nowrap">
                                        {new Date(record.changed_at).toLocaleDateString()}
                                    </span>
                                </div>
                                <p className="text-gray-600 mb-2">{record.reason || 'Status updated'}</p>
                                <div className="text-xs text-gray-400 border-t pt-2 mt-2">
                                    Changed at {new Date(record.changed_at).toLocaleTimeString()}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default ApplicationStatusHistoryModal;