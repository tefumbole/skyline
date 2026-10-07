import React, { useState, useEffect } from 'react';
import { getContactMessages, deleteContactMessage } from '@/services/contactService';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Trash2, Mail, MessageSquare, Calendar } from 'lucide-react';
import { format } from 'date-fns';
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
import { Badge } from '@/components/ui/badge';

const ContactMessagesPage = () => {
    const [messages, setMessages] = useState([]);
    const [loading, setLoading] = useState(true);
    const [deletingId, setDeletingId] = useState(null);
    const { toast } = useToast();

    useEffect(() => {
        fetchMessages();
    }, []);

    const fetchMessages = async () => {
        setLoading(true);
        const res = await getContactMessages();
        if (res.success) {
            setMessages(res.data || []);
        } else {
            toast({
                title: "Error Loading Messages",
                description: res.error,
                variant: "destructive"
            });
        }
        setLoading(false);
    };

    const handleDelete = async (id) => {
        setDeletingId(id);
        const res = await deleteContactMessage(id);
        
        if (res.success) {
            toast({ title: "Message deleted successfully" });
            setMessages(messages.filter(m => m.id !== id));
        } else {
            toast({
                title: "Error deleting message",
                description: res.error,
                variant: "destructive"
            });
        }
        setDeletingId(null);
    };

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-3xl font-bold text-[#003D82] flex items-center gap-2">
                    <MessageSquare className="w-8 h-8 text-[#D4AF37]" /> Contact Form Messages
                </h1>
                <p className="text-gray-500">View and manage messages submitted through the public website contact form.</p>
            </div>

            <Card className="shadow-md border-0 rounded-xl overflow-hidden">
                <CardHeader className="bg-gray-50 border-b">
                    <CardTitle className="text-xl">Inbox</CardTitle>
                    <CardDescription>Recent inquiries and messages</CardDescription>
                </CardHeader>
                <CardContent className="p-0">
                    {loading ? (
                        <div className="flex flex-col items-center justify-center p-12">
                            <Loader2 className="w-8 h-8 animate-spin text-[#003D82] mb-4" />
                            <p className="text-gray-500">Loading messages...</p>
                        </div>
                    ) : messages.length === 0 ? (
                        <div className="text-center p-12">
                            <div className="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <MessageSquare className="w-8 h-8 text-gray-400" />
                            </div>
                            <h3 className="text-lg font-medium text-gray-900 mb-1">No messages found</h3>
                            <p className="text-gray-500">When users submit the contact form, their messages will appear here.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader className="bg-gray-50/50">
                                    <TableRow>
                                        <TableHead className="w-[200px]">Sender Details</TableHead>
                                        <TableHead>Message Info</TableHead>
                                        <TableHead className="w-[150px]">Date</TableHead>
                                        <TableHead className="w-[150px] text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {messages.map((msg) => (
                                        <TableRow key={msg.id} className="hover:bg-gray-50/50 transition-colors">
                                            <TableCell className="align-top">
                                                <p className="font-semibold text-gray-900">{msg.name}</p>
                                                <a href={`mailto:${msg.email}`} className="text-sm text-[#003D82] hover:underline flex items-center gap-1 mt-1">
                                                    <Mail className="w-3 h-3" /> {msg.email}
                                                </a>
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <p className="font-bold text-gray-900 mb-1">{msg.subject}</p>
                                                <p className="text-sm text-gray-600 whitespace-pre-wrap">{msg.message}</p>
                                                <Badge variant="outline" className="mt-2 text-xs bg-green-50 text-green-700 border-green-200">
                                                    Sent via {msg.sent_via}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <div className="flex items-center gap-1.5 text-sm text-gray-500">
                                                    <Calendar className="w-4 h-4" />
                                                    {format(new Date(msg.created_at), 'MMM dd, yyyy')}
                                                </div>
                                                <p className="text-xs text-gray-400 ml-5">{format(new Date(msg.created_at), 'hh:mm a')}</p>
                                            </TableCell>
                                            <TableCell className="align-top text-right space-x-2">
                                                <Button variant="outline" size="sm" asChild className="h-8">
                                                    <a href={`mailto:${msg.email}?subject=Re: ${msg.subject}`}>
                                                        <Mail className="w-4 h-4 mr-1" /> Reply
                                                    </a>
                                                </Button>
                                                
                                                <AlertDialog>
                                                    <AlertDialogTrigger asChild>
                                                        <Button variant="ghost" size="sm" className="h-8 text-red-500 hover:text-red-700 hover:bg-red-50">
                                                            {deletingId === msg.id ? <Loader2 className="w-4 h-4 animate-spin" /> : <Trash2 className="w-4 h-4" />}
                                                        </Button>
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                This will permanently delete the message from <b>{msg.name}</b> regarding "{msg.subject}". This action cannot be undone.
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter>
                                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                            <AlertDialogAction 
                                                                onClick={() => handleDelete(msg.id)}
                                                                className="bg-red-600 hover:bg-red-700 text-white"
                                                            >
                                                                Delete Message
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
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

export default ContactMessagesPage;