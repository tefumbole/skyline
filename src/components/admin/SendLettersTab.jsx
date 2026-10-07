import React, { useState, useEffect } from 'react';
import { getAllTemplates, previewTemplate } from '@/services/letterTemplateService';
import { getAllUsers } from '@/services/userService';
import { sendLetters } from '@/services/letterService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/components/ui/use-toast';
import { ArrowRight, ArrowLeft, Send, CheckCircle, Search, Users } from 'lucide-react';
import { motion } from 'framer-motion';

const SendLettersTab = () => {
    const [step, setStep] = useState(1);
    const [templates, setTemplates] = useState([]);
    const [users, setUsers] = useState([]);
    const [selectedTemplateId, setSelectedTemplateId] = useState('');
    const [selectedUserIds, setSelectedUserIds] = useState([]);
    const [searchUser, setSearchUser] = useState('');
    const [sending, setSending] = useState(false);
    const { toast } = useToast();

    useEffect(() => {
        getAllTemplates().then(setTemplates);
        getAllUsers().then(setUsers);
    }, []);

    const selectedTemplate = templates.find(t => t.id === selectedTemplateId);
    
    const filteredUsers = users.filter(u => 
        u.full_name.toLowerCase().includes(searchUser.toLowerCase()) || 
        u.email.toLowerCase().includes(searchUser.toLowerCase())
    );

    const toggleUser = (id) => {
        if (selectedUserIds.includes(id)) setSelectedUserIds(selectedUserIds.filter(uid => uid !== id));
        else setSelectedUserIds([...selectedUserIds, id]);
    };

    const toggleAll = () => {
        if (selectedUserIds.length === filteredUsers.length) setSelectedUserIds([]);
        else setSelectedUserIds(filteredUsers.map(u => u.id));
    };

    const handleSend = async () => {
        setSending(true);
        try {
            const result = await sendLetters(selectedTemplate, selectedUserIds, "Admin");
            toast({
                title: "Sending Complete",
                description: `Sent: ${result.sent}, Failed: ${result.failed}`,
                variant: result.failed > 0 ? "destructive" : "default"
            });
            if (result.sent > 0) {
                setStep(1);
                setSelectedUserIds([]);
                setSelectedTemplateId('');
            }
        } catch (e) {
            toast({ title: "Error", description: e.message, variant: "destructive" });
        } finally {
            setSending(false);
        }
    };

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            {/* Steps Indicator */}
            <div className="flex justify-center gap-4 mb-8">
                {[1, 2, 3].map(s => (
                    <div key={s} className={`flex items-center gap-2 ${step >= s ? 'text-blue-600 font-bold' : 'text-gray-400'}`}>
                        <div className={`w-8 h-8 rounded-full flex items-center justify-center border-2 ${step >= s ? 'border-blue-600 bg-blue-50' : 'border-gray-300'}`}>
                            {s}
                        </div>
                        <span className="text-sm hidden md:inline">{s === 1 ? 'Select Template' : s === 2 ? 'Select Recipients' : 'Review & Send'}</span>
                        {s < 3 && <div className="w-8 h-px bg-gray-300 mx-2" />}
                    </div>
                ))}
            </div>

            <Card className="min-h-[400px]">
                <CardContent className="p-6">
                    {step === 1 && (
                        <motion.div initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }}>
                            <h3 className="text-lg font-semibold mb-4">Choose a Letter Template</h3>
                            <div className="grid gap-4 md:grid-cols-2">
                                {templates.map(t => (
                                    <div 
                                        key={t.id} 
                                        className={`p-4 border rounded cursor-pointer transition-colors ${selectedTemplateId === t.id ? 'border-blue-500 bg-blue-50' : 'hover:border-blue-300'}`}
                                        onClick={() => setSelectedTemplateId(t.id)}
                                    >
                                        <h4 className="font-bold">{t.name}</h4>
                                        <p className="text-sm text-gray-500 truncate">{t.subject}</p>
                                    </div>
                                ))}
                            </div>
                            <div className="mt-6 flex justify-end">
                                <Button disabled={!selectedTemplateId} onClick={() => setStep(2)}>
                                    Next: Select Users <ArrowRight className="ml-2 w-4 h-4" />
                                </Button>
                            </div>
                        </motion.div>
                    )}

                    {step === 2 && (
                        <motion.div initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }}>
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-semibold">Select Recipients ({selectedUserIds.length} selected)</h3>
                                <div className="w-64 relative">
                                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-gray-400" />
                                    <Input placeholder="Search users..." className="pl-8" value={searchUser} onChange={e => setSearchUser(e.target.value)} />
                                </div>
                            </div>

                            <div className="border rounded-lg overflow-hidden max-h-[400px] overflow-y-auto mb-6">
                                <div className="p-3 bg-gray-50 border-b flex items-center gap-3">
                                    <Checkbox checked={selectedUserIds.length === filteredUsers.length && filteredUsers.length > 0} onCheckedChange={toggleAll} />
                                    <span className="text-sm font-medium">Select All</span>
                                </div>
                                {filteredUsers.map(u => (
                                    <div key={u.id} className="p-3 border-b flex items-center gap-3 hover:bg-gray-50">
                                        <Checkbox checked={selectedUserIds.includes(u.id)} onCheckedChange={() => toggleUser(u.id)} />
                                        <div>
                                            <div className="font-medium text-sm">{u.full_name}</div>
                                            <div className="text-xs text-gray-500">{u.email} • {u.phone}</div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="flex justify-between">
                                <Button variant="outline" onClick={() => setStep(1)}><ArrowLeft className="mr-2 w-4 h-4" /> Back</Button>
                                <Button disabled={selectedUserIds.length === 0} onClick={() => setStep(3)}>
                                    Next: Review <ArrowRight className="ml-2 w-4 h-4" />
                                </Button>
                            </div>
                        </motion.div>
                    )}

                    {step === 3 && (
                        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
                            <h3 className="text-lg font-semibold mb-4">Review & Send</h3>
                            <div className="bg-yellow-50 p-4 rounded-lg mb-6 border border-yellow-200">
                                <div className="flex items-start gap-3">
                                    <Send className="w-5 h-5 text-yellow-700 mt-1" />
                                    <div>
                                        <h4 className="font-bold text-yellow-800">Ready to Send</h4>
                                        <p className="text-sm text-yellow-700">
                                            You are about to send <strong>{selectedUserIds.length}</strong> letters using template <strong>"{selectedTemplate?.name}"</strong>.
                                            The letters will be sent via WhatsApp.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="bg-gray-50 p-4 rounded border mb-6">
                                <h5 className="text-xs font-bold text-gray-500 uppercase mb-2">Sample Preview (First User)</h5>
                                <div className="whitespace-pre-wrap text-sm font-mono bg-white p-4 border rounded">
                                    {previewTemplate(selectedTemplate, {
                                        USER_NAME: users.find(u => u.id === selectedUserIds[0])?.full_name || 'John Doe',
                                        ADMIN_NAME: 'Admin'
                                    })}
                                </div>
                            </div>

                            <div className="flex justify-between">
                                <Button variant="outline" onClick={() => setStep(2)} disabled={sending}><ArrowLeft className="mr-2 w-4 h-4" /> Back</Button>
                                <Button onClick={handleSend} disabled={sending} className="bg-green-600 hover:bg-green-700">
                                    {sending ? "Sending..." : "Confirm & Send Letters"} <Send className="ml-2 w-4 h-4" />
                                </Button>
                            </div>
                        </motion.div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default SendLettersTab;