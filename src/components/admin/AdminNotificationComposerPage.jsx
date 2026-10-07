import React, { useState, useEffect } from 'react';
import { createNotification } from '@/services/NotificationService';
import { getAllCategories } from '@/services/CommunicationCategoryService';
import { getAllShareholders } from '@/services/shareholdersService';
import { getAllMembers } from '@/services/membersService';
import { getAllUsers } from '@/services/userService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/components/ui/use-toast';
import { Send, Loader2, Users, CheckCircle, MessageSquare, AlertCircle } from 'lucide-react';

const AdminNotificationComposerPage = () => {
  const [categories, setCategories] = useState([]);
  const [formData, setFormData] = useState({ title: '', message: '', category_id: '' });
  const [recipientType, setRecipientType] = useState('shareholders'); 
  const [recipients, setRecipients] = useState([]);
  const [selectedRecipients, setSelectedRecipients] = useState([]);
  const [loading, setLoading] = useState(false);
  const [sendViaWhatsApp, setSendViaWhatsApp] = useState(false); 
  const [deliveryResults, setDeliveryResults] = useState([]);
  const { toast } = useToast();

  useEffect(() => {
    loadInitialData();
  }, []);

  useEffect(() => {
    loadRecipients();
  }, [recipientType]);

  const loadInitialData = async () => {
    const cats = await getAllCategories();
    setCategories(cats);
  };

  const loadRecipients = async () => {
    setLoading(true);
    let data = [];
    if (recipientType === 'shareholders') data = await getAllShareholders();
    else if (recipientType === 'members') data = await getAllMembers();
    else if (recipientType === 'users') data = await getAllUsers();
    
    // Normalize data structure for selection
    setRecipients(data.map(r => ({
        id: r.id,
        name: r.name || r.full_name,
        phone: r.phone || r.phone_number, 
        type: recipientType === 'shareholders' ? 'Shareholder' : recipientType === 'members' ? 'Member' : 'User'
    })));
    setSelectedRecipients([]);
    setDeliveryResults([]);
    setLoading(false);
  };

  const handleSelectAll = (checked) => {
    if (checked) setSelectedRecipients(recipients.map(r => r.id));
    else setSelectedRecipients([]);
  };

  const handleSelectOne = (id, checked) => {
    if (checked) setSelectedRecipients([...selectedRecipients, id]);
    else setSelectedRecipients(selectedRecipients.filter(rid => rid !== id));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setDeliveryResults([]);

    if (!formData.message || formData.message.trim() === '') {
        toast({ title: "Validation Error", description: "Message content cannot be empty.", variant: "destructive" });
        return;
    }

    if (selectedRecipients.length === 0) {
        toast({ title: "No Recipients", description: "Please select at least one recipient.", variant: "destructive" });
        return;
    }

    if (!confirm(`Send this notification to ${selectedRecipients.length} recipients?`)) return;

    setLoading(true);
    try {
        const finalRecipients = recipients.filter(r => selectedRecipients.includes(r.id));
        
        if (sendViaWhatsApp) {
            toast({ title: "Sending Notifications", description: "Dispatching messages, please wait..." });
        }

        const response = await createNotification(formData, finalRecipients, sendViaWhatsApp);
        
        let successMsg = `Notification saved in system for ${finalRecipients.length} recipients.`;

        if (sendViaWhatsApp && response.results) {
            setDeliveryResults(response.results);
            const successCount = response.results.filter(r => r.success).length;
            const failCount = response.results.filter(r => !r.success).length;
            successMsg += ` WhatsApp: ${successCount} sent, ${failCount} failed. Check below for details.`;
            console.log("Delivery Results:", response.results);
        }

        toast({ title: "Operation Completed", description: successMsg });
        
        // Reset form
        setFormData({ title: '', message: '', category_id: '' });
        setSelectedRecipients([]);
        setSendViaWhatsApp(false);
    } catch (error) {
        toast({ title: "Error", description: error.message, variant: "destructive" });
    } finally {
        setLoading(false);
    }
  };

  return (
    <div className="max-w-5xl mx-auto space-y-6 animate-in fade-in duration-500 pb-12">
        <div>
            <h1 className="text-3xl font-bold text-[#003D82]">Compose Notification</h1>
            <p className="text-gray-500">Send alerts and updates to system users.</p>
        </div>

        <div className="grid md:grid-cols-3 gap-6">
            <div className="md:col-span-2 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Message Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form id="notif-form" onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label>Category</Label>
                                <Select 
                                    value={formData.category_id} 
                                    onValueChange={(val) => setFormData({...formData, category_id: val})}
                                >
                                    <SelectTrigger><SelectValue placeholder="Select category..." /></SelectTrigger>
                                    <SelectContent>
                                        {categories.map(c => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Title</Label>
                                <Input 
                                    value={formData.title} 
                                    onChange={e => setFormData({...formData, title: e.target.value})} 
                                    placeholder="e.g. System Maintenance"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Message Content</Label>
                                <Textarea 
                                    value={formData.message} 
                                    onChange={e => setFormData({...formData, message: e.target.value})} 
                                    placeholder="Type your message here. You can use {name} to personalize."
                                    rows={5}
                                    required
                                />
                                <p className="text-xs text-gray-400">Supported placeholders: &#123;name&#125;</p>
                            </div>
                            
                            <div className="pt-4 border-t mt-4 flex items-center space-x-2">
                                <Checkbox 
                                    id="wa-send" 
                                    checked={sendViaWhatsApp} 
                                    onCheckedChange={setSendViaWhatsApp}
                                />
                                <Label htmlFor="wa-send" className="flex items-center gap-2 cursor-pointer font-medium text-green-700">
                                    <MessageSquare className="w-4 h-4" /> Send via WhatsApp
                                </Label>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {deliveryResults.length > 0 && (
                    <Card className="border-t-4 border-t-[#003D82]">
                        <CardHeader>
                            <CardTitle>Delivery Report</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {deliveryResults.map((result, idx) => (
                                    <div key={idx} className="flex items-center justify-between p-3 bg-gray-50 rounded-md border border-gray-100">
                                        <div className="flex flex-col">
                                            <span className="font-semibold text-sm text-gray-800">{result.recipientName}</span>
                                            <span className="text-xs text-gray-500">{result.phone || "No phone provided"}</span>
                                        </div>
                                        <div>
                                            {result.success ? (
                                                <span className="flex items-center text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">
                                                    <CheckCircle className="w-3 h-3 mr-1" /> Sent
                                                </span>
                                            ) : (
                                                <span className="flex items-center text-xs font-medium text-red-600 bg-red-50 px-2 py-1 rounded-full">
                                                    <AlertCircle className="w-3 h-3 mr-1" /> {result.error || "Failed"}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            <div className="space-y-6">
                <Card className="h-full flex flex-col">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><Users className="w-4 h-4" /> Recipients</CardTitle>
                        <CardDescription>Target Audience Selection</CardDescription>
                    </CardHeader>
                    <CardContent className="flex-1 flex flex-col">
                        <div className="mb-4">
                             <Select value={recipientType} onValueChange={setRecipientType}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="shareholders">Shareholders</SelectItem>
                                    <SelectItem value="members">Team Members</SelectItem>
                                    <SelectItem value="users">System Users</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <div className="flex items-center space-x-2 mb-4 p-2 bg-gray-50 rounded">
                            <Checkbox 
                                id="select-all" 
                                checked={recipients.length > 0 && selectedRecipients.length === recipients.length}
                                onCheckedChange={handleSelectAll}
                            />
                            <Label htmlFor="select-all" className="font-semibold cursor-pointer">Select All ({recipients.length})</Label>
                        </div>

                        <div className="flex-1 overflow-y-auto max-h-[400px] space-y-2 pr-2">
                            {loading ? <div className="text-center py-4"><Loader2 className="w-6 h-6 animate-spin mx-auto text-gray-400" /></div> : 
                                recipients.length === 0 ? <p className="text-center text-sm text-gray-400">No recipients found.</p> :
                                recipients.map(r => (
                                    <div key={r.id} className="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded border border-transparent hover:border-gray-100 transition-colors">
                                        <Checkbox 
                                            id={`r-${r.id}`} 
                                            checked={selectedRecipients.includes(r.id)}
                                            onCheckedChange={(c) => handleSelectOne(r.id, c)}
                                        />
                                        <div className="overflow-hidden">
                                            <Label htmlFor={`r-${r.id}`} className="cursor-pointer block truncate">{r.name}</Label>
                                            <span className="text-xs text-gray-400 block truncate">{r.phone || 'No phone'}</span>
                                        </div>
                                    </div>
                                ))
                            }
                        </div>
                    </CardContent>
                    <div className="p-6 border-t bg-gray-50 rounded-b-lg">
                        <Button type="submit" form="notif-form" className="w-full bg-[#003D82]" disabled={loading || selectedRecipients.length === 0}>
                            {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Send className="w-4 h-4 mr-2" />}
                            Send Notification ({selectedRecipients.length})
                        </Button>
                    </div>
                </Card>
            </div>
        </div>
    </div>
  );
};

export default AdminNotificationComposerPage;