import React, { useState, useEffect, useMemo } from 'react';
import { useToast } from '@/components/ui/use-toast';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { 
  fetchShareholders, 
  fetchStudents, 
  fetchStaff 
} from '@/services/recipientService';
import { 
  uploadMessageAttachment, 
  deleteMessageAttachment, 
  createMessageWithRecipientsAndPDFs 
} from '@/services/messageCompositionService';
import { getTemplates } from '@/services/templateService';
import { autoStartWorker } from '@/services/queueWorkerService';
import PDFPreviewModal from '@/components/admin/PDFPreviewModal';
import SaveTemplateModal from '@/components/admin/SaveTemplateModal';
import { supabase } from '@/lib/customSupabaseClient';
import { 
  Loader2, Send, Paperclip, X, Calendar as CalendarIcon, 
  Users, UserCheck, AlertCircle, Info, Eye, Save, FileText, Search
} from 'lucide-react';

const ComposeMessagePage = () => {
  const { toast } = useToast();
  
  // Recipients State
  const [loadingRecipients, setLoadingRecipients] = useState(true);
  const [shareholders, setShareholders] = useState([]);
  const [students, setStudents] = useState([]);
  const [staff, setStaff] = useState([]);
  const [selectedRecipients, setSelectedRecipients] = useState(new Set());
  const [recipientSearch, setRecipientSearch] = useState('');
  
  // Message State
  const [subject, setSubject] = useState('');
  const [category, setCategory] = useState('General');
  const [body, setBody] = useState('');
  
  // Options State
  const [options, setOptions] = useState({
    sendWhatsapp: true,
    sendEmail: true,
    generatePdf: true,
    isScheduled: false
  });
  const [scheduledDate, setScheduledDate] = useState('');
  const [scheduledTime, setScheduledTime] = useState('');
  
  // Attachments & Templates State
  const [attachments, setAttachments] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [templates, setTemplates] = useState([]);
  const [selectedTemplateId, setSelectedTemplateId] = useState('none');
  
  // Submission State
  const [sending, setSending] = useState(false);

  // Modals State
  const [isPreviewOpen, setIsPreviewOpen] = useState(false);
  const [isSaveTemplateOpen, setIsSaveTemplateOpen] = useState(false);
  const [messageSettings, setMessageSettings] = useState({});

  useEffect(() => {
    loadRecipients();
    loadMessageSettings();
    loadTemplates();
  }, []);

  const loadMessageSettings = async () => {
    try {
      const { data, error } = await supabase
        .from('message_settings')
        .select('*')
        .limit(1)
        .single();
      if (!error && data) {
        setMessageSettings(data);
      }
    } catch (e) {
      console.warn("Could not load message settings", e);
    }
  };

  const loadRecipients = async () => {
    setLoadingRecipients(true);
    try {
      const [shData, stData, stfData] = await Promise.all([
        fetchShareholders(),
        fetchStudents(),
        fetchStaff()
      ]);
      setShareholders(shData);
      setStudents(stData);
      setStaff(stfData);
    } catch (error) {
      toast({
        title: "Error loading recipients",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setLoadingRecipients(false);
    }
  };

  const loadTemplates = async () => {
    const result = await getTemplates();
    if (result.success) {
      setTemplates(result.data);
    }
  };

  const handleTemplateChange = (val) => {
    setSelectedTemplateId(val);
    if (val !== 'none') {
      const t = templates.find(temp => temp.id === val);
      if (t) {
        setSubject(t.subject || '');
        setBody(t.body || '');
        setCategory(t.category || 'General');
        setOptions(prev => ({
          ...prev,
          sendWhatsapp: !!t.send_whatsapp,
          sendEmail: !!t.send_email,
          generatePdf: !!t.generate_pdf
        }));
      }
    }
  };

  const handleRecipientToggle = (recipient) => {
    const newSelected = new Set(selectedRecipients);
    const exists = Array.from(newSelected).find(r => r.id === recipient.id && r.type === recipient.type);
    
    if (exists) {
      newSelected.delete(exists);
    } else {
      newSelected.add(recipient);
    }
    setSelectedRecipients(newSelected);
  };

  const filterRecipients = (list) => {
    if (!recipientSearch) return list;
    const lowerQuery = recipientSearch.toLowerCase();
    return list.filter(r => 
      (r.name && r.name.toLowerCase().includes(lowerQuery)) ||
      (r.email && r.email.toLowerCase().includes(lowerQuery)) ||
      (r.phone && r.phone.toLowerCase().includes(lowerQuery))
    );
  };

  const handleSelectAll = (group, type) => {
    const filteredGroup = filterRecipients(group);
    const newSelected = new Set(selectedRecipients);
    
    const allSelected = filteredGroup.every(r => 
      Array.from(newSelected).some(s => s.id === r.id && s.type === type)
    );

    if (allSelected) {
      filteredGroup.forEach(r => {
        const exists = Array.from(newSelected).find(s => s.id === r.id && s.type === type);
        if (exists) newSelected.delete(exists);
      });
    } else {
      filteredGroup.forEach(r => {
        const exists = Array.from(newSelected).find(s => s.id === r.id && s.type === type);
        if (!exists) newSelected.add(r);
      });
    }
    setSelectedRecipients(newSelected);
  };

  const handleFileUpload = async (e) => {
    const files = Array.from(e.target.files);
    if (!files.length) return;

    setUploading(true);
    try {
      const newAttachments = [];
      for (const file of files) {
        const uploaded = await uploadMessageAttachment(file);
        newAttachments.push(uploaded);
      }
      setAttachments(prev => [...prev, ...newAttachments]);
      toast({ title: "Attachments uploaded successfully" });
    } catch (error) {
      toast({
        title: "Upload Failed",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setUploading(false);
      e.target.value = null;
    }
  };

  const removeAttachment = async (index) => {
    const file = attachments[index];
    try {
      await deleteMessageAttachment(file.url);
      setAttachments(prev => prev.filter((_, i) => i !== index));
    } catch (error) {
      console.error("Failed to remove from storage", error);
      setAttachments(prev => prev.filter((_, i) => i !== index));
    }
  };

  const handleSend = async () => {
    if (!subject.trim()) return toast({ title: "Validation Error", description: "Subject is required", variant: "destructive" });
    if (!body.trim()) return toast({ title: "Validation Error", description: "Message body is required", variant: "destructive" });
    if (selectedRecipients.size === 0) return toast({ title: "Validation Error", description: "Select at least one recipient", variant: "destructive" });
    if (!options.sendWhatsapp && !options.sendEmail) return toast({ title: "Validation Error", description: "Select at least one delivery channel (Email or WhatsApp)", variant: "destructive" });

    let scheduledFor = null;
    if (options.isScheduled) {
      if (!scheduledDate || !scheduledTime) return toast({ title: "Validation Error", description: "Please select a schedule date and time", variant: "destructive" });
      scheduledFor = new Date(`${scheduledDate}T${scheduledTime}`).toISOString();
    }

    setSending(true);
    try {
      const messageData = {
        subject,
        category,
        body,
        sendEmail: options.sendEmail,
        sendWhatsapp: options.sendWhatsapp,
        generatePdf: options.generatePdf,
        isScheduled: options.isScheduled,
        scheduledFor
      };

      const result = await createMessageWithRecipientsAndPDFs(
        messageData, 
        Array.from(selectedRecipients), 
        attachments
      );

      let description = `Message processed for ${selectedRecipients.size} recipients.`;
      if (result.pdfStats) {
        description += ` PDFs Generated: ${result.pdfStats.successCount} Successful, ${result.pdfStats.failCount} Failed.`;
      }

      toast({
        title: options.isScheduled ? "Message Scheduled Successfully" : "Message Sent Successfully",
        description: description,
      });

      // Auto start worker if it's not scheduled for later
      if (!options.isScheduled) {
        autoStartWorker();
      }

      // Reset Form
      setSubject('');
      setBody('');
      setAttachments([]);
      setSelectedRecipients(new Set());
      setOptions({
        sendWhatsapp: true,
        sendEmail: true,
        generatePdf: true,
        isScheduled: false
      });
      setScheduledDate('');
      setScheduledTime('');
      setSelectedTemplateId('none');
    } catch (error) {
      toast({
        title: "Failed to send message",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setSending(false);
    }
  };

  const insertVariable = (variable) => {
    setBody(prev => prev + variable);
  };

  const renderRecipientList = (group, type) => {
    const filteredGroup = filterRecipients(group);
    
    if (group.length === 0) return <p className="text-gray-500 p-4 text-sm">No {type}s found.</p>;
    if (filteredGroup.length === 0) return <p className="text-gray-500 p-4 text-sm">No {type}s match your search.</p>;
    
    const allSelected = filteredGroup.length > 0 && filteredGroup.every(r => 
      Array.from(selectedRecipients).some(s => s.id === r.id && s.type === type)
    );

    return (
      <div className="space-y-2">
        <div className="flex items-center justify-between pb-2 border-b mt-2">
          <span className="text-sm font-medium text-gray-700">Showing {filteredGroup.length} of {group.length}</span>
          <Button 
            variant="outline" 
            size="sm" 
            onClick={() => handleSelectAll(group, type)}
            className="h-8 text-xs"
          >
            {allSelected ? 'Deselect All Visible' : 'Select All Visible'}
          </Button>
        </div>
        <div className="max-h-[300px] overflow-y-auto space-y-1 pr-2">
          {filteredGroup.map(person => {
            const isSelected = Array.from(selectedRecipients).some(s => s.id === person.id && s.type === type);
            return (
              <div 
                key={person.id} 
                className={`flex items-center space-x-3 p-2 rounded-md hover:bg-gray-50 cursor-pointer border transition-colors ${isSelected ? 'bg-blue-50 border-[#003D82]/30' : 'border-transparent'}`}
                onClick={() => handleRecipientToggle(person)}
              >
                <Checkbox 
                  checked={isSelected}
                  onCheckedChange={() => handleRecipientToggle(person)}
                />
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 truncate">{person.name}</p>
                  <p className="text-xs text-gray-500 truncate">
                    {person.email || person.phone || 'No contact info'}
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    );
  };

  return (
    <div className="max-w-6xl mx-auto space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Compose Message</h1>
          <p className="text-gray-500">Send personalized emails, WhatsApps, and generated documents.</p>
        </div>
        <div className="flex items-center gap-4 bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-100">
          <Users className="w-5 h-5 text-[#003D82]" />
          <div>
            <p className="text-xs text-gray-500 font-medium">Selected Recipients</p>
            <p className="text-lg font-bold text-[#003D82] leading-none">{selectedRecipients.size}</p>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column - Form */}
        <div className="lg:col-span-2 space-y-6">
          <Card className="border-t-4 border-t-[#003D82]">
            <CardHeader className="flex flex-row justify-between items-center bg-gray-50/50">
              <CardTitle>Message Content</CardTitle>
              <div className="flex gap-2">
                {options.generatePdf && body.trim() && subject.trim() && (
                  <Button variant="outline" size="sm" onClick={() => setIsPreviewOpen(true)} className="flex items-center bg-white">
                    <Eye className="w-4 h-4 mr-2" />
                    Preview Document
                  </Button>
                )}
                <Select value={selectedTemplateId} onValueChange={handleTemplateChange}>
                  <SelectTrigger className="w-[180px] bg-white text-sm">
                    <SelectValue placeholder="Load Template..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">Blank Message</SelectItem>
                    {templates.map(t => (
                      <SelectItem key={t.id} value={t.id}>{t.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </CardHeader>
            <CardContent className="space-y-4 pt-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Category</Label>
                  <Input 
                    value={category} 
                    onChange={(e) => setCategory(e.target.value)} 
                    placeholder="e.g. Announcement, Invoice, Update"
                    className="bg-white text-gray-900"
                  />
                </div>
                <div className="space-y-2">
                  <Label>Subject</Label>
                  <Input 
                    value={subject} 
                    onChange={(e) => setSubject(e.target.value)} 
                    placeholder="Enter message subject"
                    className="bg-white text-gray-900"
                  />
                </div>
              </div>

              <div className="space-y-2 pt-2">
                <div className="flex justify-between items-center">
                  <Label>Message Body</Label>
                  <div className="flex gap-2">
                    <span className="text-xs text-gray-500 flex items-center mr-2">
                      <Info className="w-3 h-3 mr-1" /> Insert Variables:
                    </span>
                    {['{name}', '{email}', '{phone}', '{date}'].map(v => (
                      <button 
                        key={v}
                        onClick={() => insertVariable(v)}
                        className="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded transition-colors"
                      >
                        {v}
                      </button>
                    ))}
                  </div>
                </div>
                <Textarea 
                  value={body} 
                  onChange={(e) => setBody(e.target.value)} 
                  placeholder="Type your message here..."
                  className="min-h-[250px] font-sans bg-white text-gray-900 resize-y"
                />
              </div>

              {/* Attachments Section */}
              <div className="space-y-3 pt-4 border-t">
                <Label className="flex items-center">
                  <Paperclip className="w-4 h-4 mr-2" />
                  Attachments
                </Label>
                
                <div className="border-2 border-dashed rounded-lg p-6 text-center hover:bg-gray-50 transition-colors">
                  <div className="flex flex-col items-center justify-center space-y-2">
                    <Paperclip className="w-8 h-8 text-gray-400" />
                    <p className="text-sm text-gray-500">Attach PDFs, Documents or Images (Max 10MB)</p>
                    <Label htmlFor="file-upload" className="cursor-pointer">
                      <div className="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors flex items-center mt-2">
                        {uploading ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Paperclip className="w-4 h-4 mr-2" />}
                        Browse Files
                      </div>
                    </Label>
                    <Input 
                      id="file-upload" 
                      type="file" 
                      multiple 
                      className="hidden" 
                      onChange={handleFileUpload}
                      disabled={uploading}
                    />
                  </div>
                </div>

                {attachments.length > 0 && (
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4">
                    {attachments.map((file, index) => (
                      <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded border text-sm">
                        <div className="flex items-center space-x-2 overflow-hidden">
                          <Paperclip className="w-4 h-4 text-gray-400 shrink-0" />
                          <span className="truncate text-gray-700">{file.name}</span>
                        </div>
                        <button 
                          onClick={() => removeAttachment(index)}
                          className="text-red-500 hover:text-red-700 p-1 shrink-0"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Right Column - Options & Recipients */}
        <div className="space-y-6">
          <Card>
            <CardHeader className="pb-4">
              <CardTitle className="text-lg flex items-center">
                <AlertCircle className="w-5 h-5 mr-2 text-[#003D82]" />
                Sending Options
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-3 p-4 bg-gray-50 rounded-lg border">
                <div className="flex items-center space-x-2">
                  <Checkbox 
                    id="sendWhatsapp" 
                    checked={options.sendWhatsapp} 
                    onCheckedChange={(c) => setOptions({...options, sendWhatsapp: c})} 
                  />
                  <Label htmlFor="sendWhatsapp" className="font-medium cursor-pointer text-gray-900">Send via WhatsApp</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Checkbox 
                    id="sendEmail" 
                    checked={options.sendEmail} 
                    onCheckedChange={(c) => setOptions({...options, sendEmail: c})} 
                  />
                  <Label htmlFor="sendEmail" className="font-medium cursor-pointer text-gray-900">Send via Email</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Checkbox 
                    id="generatePdf" 
                    checked={options.generatePdf} 
                    onCheckedChange={(c) => setOptions({...options, generatePdf: c})} 
                  />
                  <Label htmlFor="generatePdf" className="font-medium cursor-pointer text-gray-900">Generate PDF Document</Label>
                </div>
              </div>

              <div className="pt-2">
                <div className="flex items-center space-x-2 mb-3">
                  <Checkbox 
                    id="isScheduled" 
                    checked={options.isScheduled} 
                    onCheckedChange={(c) => setOptions({...options, isScheduled: c})} 
                  />
                  <Label htmlFor="isScheduled" className="font-medium flex items-center cursor-pointer text-gray-900">
                    <CalendarIcon className="w-4 h-4 mr-2" />
                    Schedule for later
                  </Label>
                </div>
                
                {options.isScheduled && (
                  <div className="grid grid-cols-2 gap-2 p-3 bg-blue-50/50 rounded border border-blue-100">
                    <div className="space-y-1">
                      <Label className="text-xs text-gray-900">Date</Label>
                      <Input 
                        type="date" 
                        value={scheduledDate}
                        onChange={(e) => setScheduledDate(e.target.value)}
                        className="bg-white text-sm text-gray-900"
                        min={new Date().toISOString().split('T')[0]}
                      />
                    </div>
                    <div className="space-y-1">
                      <Label className="text-xs text-gray-900">Time</Label>
                      <Input 
                        type="time" 
                        value={scheduledTime}
                        onChange={(e) => setScheduledTime(e.target.value)}
                        className="bg-white text-sm text-gray-900"
                      />
                    </div>
                  </div>
                )}
              </div>

              <div className="flex gap-2 mt-4">
                <Button 
                  onClick={() => setIsSaveTemplateOpen(true)} 
                  variant="outline"
                  className="flex-1"
                >
                  <Save className="w-4 h-4 mr-2" /> Save Template
                </Button>
                <Button 
                  onClick={handleSend} 
                  disabled={sending || uploading} 
                  className="flex-[2] bg-[#003D82] hover:bg-[#002a5a] text-white"
                >
                  {sending ? (
                    <>
                      <Loader2 className="w-4 h-4 mr-2 animate-spin" /> Sending...
                    </>
                  ) : (
                    <>
                      <Send className="w-4 h-4 mr-2" /> {options.isScheduled ? 'Schedule' : 'Send Now'}
                    </>
                  )}
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card className="flex-1 flex flex-col min-h-[400px]">
            <CardHeader className="pb-2">
              <CardTitle className="text-lg flex items-center">
                <UserCheck className="w-5 h-5 mr-2 text-[#003D82]" />
                Select Recipients
              </CardTitle>
              <CardDescription>Choose who will receive this message.</CardDescription>
              <div className="relative mt-2">
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
                <Input
                  placeholder="Search name, email, phone..."
                  value={recipientSearch}
                  onChange={(e) => setRecipientSearch(e.target.value)}
                  className="pl-9 h-9 text-sm bg-white"
                />
              </div>
            </CardHeader>
            <CardContent className="p-0 flex-1">
              {loadingRecipients ? (
                <div className="flex justify-center items-center h-48">
                  <Loader2 className="w-8 h-8 animate-spin text-[#003D82]" />
                </div>
              ) : (
                <Tabs defaultValue="shareholders" className="w-full">
                  <div className="px-4 mt-2">
                    <TabsList className="grid w-full grid-cols-3 bg-gray-100">
                      <TabsTrigger value="shareholders">Shareholders</TabsTrigger>
                      <TabsTrigger value="students">Students</TabsTrigger>
                      <TabsTrigger value="staff">Staff</TabsTrigger>
                    </TabsList>
                  </div>
                  <div className="p-4 pt-2">
                    <TabsContent value="shareholders" className="mt-0">
                      {renderRecipientList(shareholders, 'shareholder')}
                    </TabsContent>
                    <TabsContent value="students" className="mt-0">
                      {renderRecipientList(students, 'student')}
                    </TabsContent>
                    <TabsContent value="staff" className="mt-0">
                      {renderRecipientList(staff, 'staff')}
                    </TabsContent>
                  </div>
                </Tabs>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
      
      {/* Preview Modal */}
      <PDFPreviewModal 
        isOpen={isPreviewOpen} 
        onClose={() => setIsPreviewOpen(false)} 
        messageData={{ subject, body }} 
        settings={messageSettings}
      />

      {/* Save Template Modal */}
      <SaveTemplateModal
        isOpen={isSaveTemplateOpen}
        onClose={() => setIsSaveTemplateOpen(false)}
        messageData={{ subject, body, category, sendEmail: options.sendEmail, sendWhatsapp: options.sendWhatsapp, generatePdf: options.generatePdf }}
        onSaveSuccess={loadTemplates}
      />
    </div>
  );
};

export default ComposeMessagePage;