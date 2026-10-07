import React, { useState, useEffect } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { getAllEvents } from '@/services/eventService';
import { getAllUsers } from '@/services/userService';
import { createInvitation, updateInvitationStatus } from '@/services/invitationService';
import { sendInvitationViaWhatsApp } from '@/services/whatsappInvitationService';
import { getAllTemplates } from '@/services/templateService';
import { isValidUUID, checkEventExists } from '@/utils/uuidValidator';
import AdminHorizontalNav from '@/components/admin/AdminHorizontalNav';
import { INVITATION_NAV } from '@/config/invitationNavConfig';
import PremiumInvitationRenderer, { drawPremiumInvitation } from '@/components/PremiumInvitationRenderer';
import QRCode from 'qrcode';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent } from '@/components/ui/card';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Ticket, UserCheck, Search, UserPlus, AlertCircle, CheckCircle2 } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

const CreateInvitationPage = () => {
  const { user } = useAuth();
  const [searchParams] = useSearchParams();
  const eventIdParam = searchParams.get('event');
  const navigate = useNavigate();
  const { toast } = useToast();

  const [events, setEvents] = useState([]);
  const [systemUsers, setSystemUsers] = useState([]);
  const [templates, setTemplates] = useState([]);
  
  const [loading, setLoading] = useState(false);
  const [isManualEntry, setIsManualEntry] = useState(true);
  const [qrDataUrl, setQrDataUrl] = useState('');
  const [isGenerated, setIsGenerated] = useState(false);
  const [validationError, setValidationError] = useState('');
  
  const [formData, setFormData] = useState({
    event_id: (eventIdParam && isValidUUID(eventIdParam)) ? eventIdParam : '',
    guest_name: '',
    phone: '',
    email: '',
    category: 'Standard',
    invitationType: 'VIP Guest',
    template_id: '',
    rsvpUrl: 'https://example.com/rsvp',
    customMessage: '',
    auto_send: false
  });

  useEffect(() => {
    const loadInitialData = async () => {
      try {
        const [evRes, userRes] = await Promise.all([
          getAllEvents(),
          getAllUsers()
        ]);
        
        if (evRes.success) {
          // Filter out any mock/placeholder events that might have slipped into the DB
          const validEvents = evRes.data.filter(ev => isValidUUID(ev.id));
          setEvents(validEvents);
        }
        if (userRes.success) {
          setSystemUsers(userRes.data);
        }
      } catch (err) {
        console.error("Error loading initial data:", err);
      }
    };
    
    loadInitialData();
    generatePreviewQR();
  }, []);

  useEffect(() => {
    if (user?.id) {
      const loadTemplates = async () => {
        const res = await getAllTemplates(user.id);
        if (res.success) {
          const activeTemplates = res.data.filter(t => t.status === 'active' && isValidUUID(t.id));
          setTemplates(activeTemplates);
          
          if (activeTemplates.length > 0 && !formData.template_id) {
             setFormData(prev => ({ ...prev, template_id: activeTemplates[0].id }));
          }
        }
      };
      loadTemplates();
    }
  }, [user]);

  const generatePreviewQR = async () => {
    try {
      const qr = await QRCode.toDataURL("PREVIEW-DATA", {
         color: { dark: '#D4AF37', light: '#FFFFFF' },
         margin: 1, width: 150
      });
      setQrDataUrl(qr);
    } catch (err) {
      console.error("Preview QR error:", err);
    }
  };

  const handleUserSelect = (userId) => {
    if (!isValidUUID(userId)) return;
    
    const sysUser = systemUsers.find(u => u.id === userId);
    if (sysUser) {
      setFormData(prev => ({
        ...prev,
        guest_name: sysUser.name || sysUser.full_name || '',
        phone: sysUser.phone || '',
        email: sysUser.email || ''
      }));
      setIsManualEntry(false);
      toast({ title: "User data populated" });
    }
  };

  const handleEventSelect = (value) => {
    if (isValidUUID(value)) {
       setFormData({...formData, event_id: value});
       setValidationError('');
    } else {
       setValidationError('Invalid event selected. Please select a valid event.');
    }
  };

  const validateForm = () => {
    if (!formData.event_id || !isValidUUID(formData.event_id)) {
      setValidationError('Please select a valid event.');
      return false;
    }
    if (!formData.guest_name.trim()) {
      setValidationError('Guest name is required.');
      return false;
    }
    if (formData.template_id && formData.template_id !== 'none' && !isValidUUID(formData.template_id)) {
      setValidationError('Selected template is invalid.');
      return false;
    }
    if (formData.rsvpUrl && !formData.rsvpUrl.startsWith('http')) {
      setValidationError('RSVP URL must start with http:// or https://');
      return false;
    }
    setValidationError('');
    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
        toast({ title: "Validation Error", description: validationError || "Please check form fields.", variant: "destructive" });
        return;
    }

    setLoading(true);
    setIsGenerated(false);
    
    try {
      // 1. Verify Event Exists in DB safely
      const eventExists = await checkEventExists(formData.event_id);
      if (!eventExists) {
         throw new Error("The selected event does not exist in the database or has an invalid ID.");
      }

      // 2. Create Invitation Record
      const invRes = await createInvitation(formData);
      if (!invRes.success) throw new Error(invRes.error);
      
      // 3. Generate Real QR Code with Dynamic URL parameters
      const eventData = events.find(ev => ev.id === formData.event_id);
      const rsvpLinkWithParams = formData.rsvpUrl
          ? `${formData.rsvpUrl}?event_id=${formData.event_id}&guest_name=${encodeURIComponent(formData.guest_name)}&invitation_id=${invRes.data.id}`
          : `https://example.com/rsvp?invitation_id=${invRes.data.id}`;

      const realQr = await QRCode.toDataURL(rsvpLinkWithParams, {
          color: { dark: '#D4AF37', light: '#FFFFFF' },
          margin: 1,
          width: 150
      });
      setQrDataUrl(realQr);

      // 4. Handle WhatsApp auto-send if checked
      if (formData.auto_send && formData.phone) {
        toast({ title: "Sending WhatsApp message..." });
        
        // Generate an offscreen canvas specifically for the WhatsApp payload
        const offscreenCanvas = document.createElement('canvas');
        offscreenCanvas.width = 1200;
        offscreenCanvas.height = 800;
        
        const cardImageUrl = await drawPremiumInvitation(offscreenCanvas, {
            event: eventData,
            guestName: formData.guest_name,
            template: templates.find(t => t.id === formData.template_id),
            qrCodeUrl: realQr,
            invitationType: formData.invitationType || formData.category,
            rsvpUrl: formData.rsvpUrl,
            customMessage: formData.customMessage
        });

        const waRes = await sendInvitationViaWhatsApp(
            formData.phone, 
            formData.guest_name, 
            eventData?.name || "Your Event", 
            cardImageUrl,
            invRes.data.id
        );
        
        await updateInvitationStatus(invRes.data.id, waRes.success ? 'Sent' : 'Failed');
        
        if (!waRes.success) {
            toast({ title: "WhatsApp Failed", description: waRes.error, variant: "destructive" });
        } else {
            toast({ title: "Invitation Sent!", description: `Successfully delivered to ${formData.phone}` });
        }
      }

      setIsGenerated(true);
      toast({ title: "Success!", description: `Invitation created with code: ${invRes.data.invitation_code}` });

    } catch (error) {
      console.error("Submission Error:", error);
      toast({ title: "Error", description: error.message, variant: "destructive" });
      setValidationError(error.message);
    } finally {
      setLoading(false);
    }
  };

  const selectedTemplateData = templates.find(t => t.id === formData.template_id);
  const selectedEventData = events.find(ev => ev.id === formData.event_id);

  return (
    <div className="max-w-7xl mx-auto p-4 lg:p-6 space-y-6">
      <AdminHorizontalNav
        items={INVITATION_NAV}
        title="Digital Invitations"
        description="Design and generate high-quality digital event invitations."
      />
      <div className="flex flex-col sm:flex-row justify-end items-start sm:items-end gap-4">
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => setIsManualEntry(!isManualEntry)}>
            {isManualEntry ? <><UserCheck className="w-4 h-4 mr-2"/> Select System User</> : <><UserPlus className="w-4 h-4 mr-2"/> Manual Entry</>}
          </Button>
        </div>
      </div>

      {validationError && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{validationError}</AlertDescription>
        </Alert>
      )}

      {isGenerated && (
        <Alert className="bg-green-50 border-green-200">
          <CheckCircle2 className="h-4 w-4 text-green-600" />
          <AlertDescription className="text-green-800">
            Invitation generated successfully! You can download or print it from the preview panel.
          </AlertDescription>
        </Alert>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {/* FORM PANEL */}
        <Card className="shadow-md border-gray-200 lg:col-span-5 h-fit">
          <CardContent className="p-6 space-y-6">
            {!isManualEntry && (
              <div className="space-y-2 pb-4 border-b border-gray-100">
                <Label className="flex items-center"><Search className="w-4 h-4 mr-2"/> Search System Users</Label>
                <Select onValueChange={handleUserSelect}>
                  <SelectTrigger className="bg-white"><SelectValue placeholder="Search by name..." /></SelectTrigger>
                  <SelectContent>
                    {systemUsers.map(u => (
                      <SelectItem key={u.id} value={u.id}>{u.name || u.full_name} ({u.phone})</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-5">
              <div className="space-y-2">
                <Label className="text-gray-900 font-semibold">Select Event *</Label>
                <Select value={formData.event_id} onValueChange={handleEventSelect}>
                  <SelectTrigger className={`bg-white ${!formData.event_id ? 'border-red-300' : ''}`}>
                    <SelectValue placeholder="Select a valid event" />
                  </SelectTrigger>
                  <SelectContent>
                    {events.map(e => <SelectItem key={e.id} value={e.id}>{e.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label className="text-gray-900 font-semibold">Guest Full Name *</Label>
                <Input 
                   value={formData.guest_name} 
                   onChange={(e) => setFormData({...formData, guest_name: e.target.value})} 
                   required 
                   placeholder="e.g. John Doe" 
                   className="bg-white text-gray-900"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label className="text-gray-900">Phone</Label>
                  <Input 
                     value={formData.phone} 
                     onChange={(e) => setFormData({...formData, phone: e.target.value})} 
                     placeholder="+250..." 
                     className="bg-white text-gray-900"
                  />
                </div>
                <div className="space-y-2">
                  <Label className="text-gray-900">Email Address</Label>
                  <Input 
                     type="email"
                     value={formData.email} 
                     onChange={(e) => setFormData({...formData, email: e.target.value})} 
                     placeholder="guest@example.com" 
                     className="bg-white text-gray-900"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label className="text-gray-900">Invitation Type</Label>
                  <Input 
                     value={formData.invitationType} 
                     onChange={(e) => setFormData({...formData, invitationType: e.target.value})} 
                     placeholder="VIP, General, etc." 
                     className="bg-white text-gray-900"
                  />
                </div>
                <div className="space-y-2">
                  <Label className="text-gray-900">Design Template</Label>
                  <Select value={formData.template_id} onValueChange={(v) => setFormData({...formData, template_id: v})}>
                    <SelectTrigger className="bg-white text-gray-900">
                      <SelectValue placeholder="Select a template" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">Blank Dark Theme</SelectItem>
                      {templates.map(t => (
                        <SelectItem key={t.id} value={t.id}>{t.name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="space-y-2">
                <Label className="text-gray-900">RSVP URL Link</Label>
                <Input 
                   type="url"
                   value={formData.rsvpUrl} 
                   onChange={(e) => setFormData({...formData, rsvpUrl: e.target.value})} 
                   placeholder="https://..." 
                   className="bg-white text-gray-900"
                />
              </div>

              <div className="space-y-2">
                <Label className="text-gray-900">Custom Message (Optional)</Label>
                <Textarea 
                   value={formData.customMessage} 
                   onChange={(e) => setFormData({...formData, customMessage: e.target.value})} 
                   placeholder="Add a personalized note..." 
                   className="bg-white text-gray-900 resize-none"
                   rows={3}
                />
              </div>

              <div className="flex items-center space-x-2 py-2 border-t border-gray-100">
                <Checkbox 
                   id="send" 
                   checked={formData.auto_send} 
                   onCheckedChange={(c) => setFormData({...formData, auto_send: c})} 
                />
                <Label htmlFor="send" className="font-normal cursor-pointer text-gray-700">Send WhatsApp notification immediately upon generation</Label>
              </div>

              <Button type="submit" className="w-full bg-[#003D82] hover:bg-blue-800 text-white py-6 text-lg font-medium shadow-lg" disabled={loading}>
                {loading ? <Loader2 className="animate-spin mr-2"/> : <Ticket className="mr-2"/>}
                {isGenerated ? 'Generate Another' : 'Generate Premium Ticket'}
              </Button>
            </form>
          </CardContent>
        </Card>

        {/* PREVIEW PANEL */}
        <div className="lg:col-span-7 bg-gray-50 rounded-xl p-4 sm:p-8 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 relative min-h-[500px]">
          <div className="absolute top-4 left-6 px-3 py-1 bg-white border border-gray-200 shadow-sm rounded-full text-xs font-bold text-gray-500 uppercase tracking-widest z-10">
            {isGenerated ? 'Final Output' : 'Live Canvas Preview'}
          </div>
          
          <div className="w-full mt-4 flex justify-center items-center">
            <PremiumInvitationRenderer 
                event={selectedEventData}
                guestName={formData.guest_name}
                template={selectedTemplateData}
                qrCodeUrl={qrDataUrl}
                invitationType={formData.invitationType}
                rsvpUrl={formData.rsvpUrl}
                customMessage={formData.customMessage}
            />
          </div>
        </div>
        
      </div>
    </div>
  );
};

export default CreateInvitationPage;