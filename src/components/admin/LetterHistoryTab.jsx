import React, { useEffect, useState } from 'react';
import { getLetterHistory, deleteLetterRecord } from '@/services/letterService';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Eye, Trash2, RefreshCw } from 'lucide-react';
import { format } from 'date-fns';

const LetterHistoryTab = () => {
    const [history, setHistory] = useState([]);
    const [selectedLetter, setSelectedLetter] = useState(null);

    const load = async () => setHistory(await getLetterHistory());
    useEffect(() => { load(); }, []);

    const handleDelete = async (id) => {
        if (confirm("Delete this history record?")) {
            await deleteLetterRecord(id);
            load();
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <h3 className="text-lg font-semibold">Sent Letters History</h3>
                <Button variant="outline" size="sm" onClick={load}><RefreshCw className="w-4 h-4 mr-2" /> Refresh</Button>
            </div>

            <Card>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-100 text-left">
                            <tr>
                                <th className="p-4">Date</th>
                                <th className="p-4">Recipient</th>
                                <th className="p-4">Template</th>
                                <th className="p-4">Status</th>
                                <th className="p-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {history.map(item => (
                                <tr key={item.id} className="border-t hover:bg-gray-50">
                                    <td className="p-4 text-gray-500">{format(new Date(item.sent_at), 'MMM d, yyyy HH:mm')}</td>
                                    <td className="p-4">
                                        <div className="font-medium">{item.recipient_name}</div>
                                        <div className="text-xs text-gray-500">{item.recipient_phone}</div>
                                    </td>
                                    <td className="p-4">{item.template_name}</td>
                                    <td className="p-4">
                                        <span className={`px-2 py-1 rounded-full text-xs ${item.status === 'Sent' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                            {item.status}
                                        </span>
                                    </td>
                                    <td className="p-4 flex gap-2">
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button variant="ghost" size="sm" onClick={() => setSelectedLetter(item)}><Eye className="w-4 h-4" /></Button>
                                            </DialogTrigger>
                                            <DialogContent>
                                                <DialogHeader><DialogTitle>Letter Details</DialogTitle></DialogHeader>
                                                <div className="space-y-4 pt-2">
                                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                                        <div><span className="font-bold">To:</span> {item.recipient_name}</div>
                                                        <div><span className="font-bold">Sent:</span> {new Date(item.sent_at).toLocaleString()}</div>
                                                    </div>
                                                    <div className="bg-gray-50 p-4 rounded text-sm font-mono whitespace-pre-wrap border">
                                                        {item.letter_content}
                                                    </div>
                                                    {item.error_message && (
                                                        <div className="text-red-500 text-sm">Error: {item.error_message}</div>
                                                    )}
                                                </div>
                                            </DialogContent>
                                        </Dialog>
                                        <Button variant="ghost" size="sm" onClick={() => handleDelete(item.id)}><Trash2 className="w-4 h-4 text-red-500" /></Button>
                                    </td>
                                </tr>
                            ))}
                            {history.length === 0 && (
                                <tr><td colSpan="5" className="p-8 text-center text-gray-500">No letters sent yet.</td></tr>
                            )}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>
    );
};

export default LetterHistoryTab;