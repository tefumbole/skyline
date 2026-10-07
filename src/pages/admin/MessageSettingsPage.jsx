import React, { useState, useEffect } from 'react';
import { 
  getMessageSettings, 
  updateMessageSettings, 
  uploadMessageAttachment, 
  deleteMessageAttachment 
} from '@/services/messageSettingsService';
import { useToast } from '@/components/ui/use-toast';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Loader2, Save, Upload, Trash2, Image as ImageIcon } from 'lucide-react';

const MessageSettingsPage = () => {
  const { toast } = useToast();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploadingHeader, setUploadingHeader] = useState(false);
  const [uploadingFooter, setUploadingFooter] = useState(false);

  const [settings, setSettings] = useState({
    org_name: '',
    reference_prefix: 'MSG-',
    sequence_padding: 6,
    verification_base_url: '',
    default_send_whatsapp: false,
    default_send_email: false,
    default_generate_pdf: true,
    header_url: '',
    footer_url: ''
  });

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      setLoading(true);
      const data = await getMessageSettings();
      if (data) {
        setSettings(data);
      }
    } catch (error) {
      toast({
        title: "Error loading settings",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setSettings(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleCheckboxChange = (name, checked) => {
    setSettings(prev => ({ ...prev, [name]: checked }));
  };

  const handleSave = async () => {
    try {
      setSaving(true);
      await updateMessageSettings(settings);
      toast({
        title: "Settings Saved",
        description: "Messaging settings have been updated successfully."
      });
    } catch (error) {
      toast({
        title: "Error saving settings",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setSaving(false);
    }
  };

  const handleFileUpload = async (e, type) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const setUploading = type === 'header' ? setUploadingHeader : setUploadingFooter;
    const urlKey = type === 'header' ? 'header_url' : 'footer_url';

    try {
      setUploading(true);
      const url = await uploadMessageAttachment(file, type);
      
      const newSettings = { ...settings, [urlKey]: url };
      setSettings(newSettings);
      
      // Auto-save the new URL
      await updateMessageSettings({ [urlKey]: url });
      
      toast({
        title: "Upload Successful",
        description: `${type === 'header' ? 'Header' : 'Footer'} image has been uploaded.`
      });
    } catch (error) {
      toast({
        title: "Upload Failed",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setUploading(false);
      // Reset input
      e.target.value = null;
    }
  };

  const handleRemoveImage = async (type) => {
    const urlKey = type === 'header' ? 'header_url' : 'footer_url';
    const currentUrl = settings[urlKey];
    
    if (!currentUrl) return;

    try {
      setSaving(true);
      await deleteMessageAttachment(currentUrl);
      
      const newSettings = { ...settings, [urlKey]: null };
      setSettings(newSettings);
      await updateMessageSettings({ [urlKey]: null });
      
      toast({
        title: "Image Removed",
        description: `${type === 'header' ? 'Header' : 'Footer'} image has been deleted.`
      });
    } catch (error) {
      toast({
        title: "Error Removing Image",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setSaving(false);
    }
  };

  const renderPreview = () => {
    const prefix = settings.reference_prefix || '';
    const padding = parseInt(settings.sequence_padding) || 6;
    const paddingStr = '0'.repeat(padding - 1) + '1';
    return `${prefix}${paddingStr}`;
  };

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-[#003D82]" />
      </div>
    );
  }

  return (
    <div className="max-w-5xl mx-auto space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Messaging Settings</h1>
          <p className="text-gray-500">Configure default options for the messaging system.</p>
        </div>
        <Button onClick={handleSave} disabled={saving} className="bg-[#003D82]">
          {saving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
          Save Settings
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Organization & Reference Config */}
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Organization Settings</CardTitle>
              <CardDescription>Basic details used in messaging headers and templates.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="org_name">Organization Name</Label>
                <Input 
                  id="org_name" 
                  name="org_name" 
                  value={settings.org_name || ''} 
                  onChange={handleChange} 
                  placeholder="e.g. Acme Corp" 
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Reference Configuration</CardTitle>
              <CardDescription>Format for automatically generated message reference numbers.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="reference_prefix">Prefix</Label>
                  <Input 
                    id="reference_prefix" 
                    name="reference_prefix" 
                    value={settings.reference_prefix || ''} 
                    onChange={handleChange} 
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="sequence_padding">Sequence Padding (Digits)</Label>
                  <Input 
                    id="sequence_padding" 
                    name="sequence_padding" 
                    type="number"
                    min="3"
                    max="10"
                    value={settings.sequence_padding || 6} 
                    onChange={handleChange} 
                  />
                </div>
              </div>
              <div className="mt-2 p-3 bg-gray-50 rounded-md border border-gray-100">
                <p className="text-sm text-gray-500 mb-1">Live Preview:</p>
                <p className="font-mono font-bold text-[#003D82] text-lg">{renderPreview()}</p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Verification Settings</CardTitle>
              <CardDescription>Base URL for document and message verification links.</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                <Label htmlFor="verification_base_url">Verification Base URL</Label>
                <Input 
                  id="verification_base_url" 
                  name="verification_base_url" 
                  value={settings.verification_base_url || ''} 
                  onChange={handleChange} 
                  placeholder="https://example.com/verify" 
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Default Sending Options</CardTitle>
              <CardDescription>Default channels selected when composing a new message.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center space-x-2">
                <Checkbox 
                  id="default_send_whatsapp" 
                  checked={settings.default_send_whatsapp} 
                  onCheckedChange={(c) => handleCheckboxChange('default_send_whatsapp', c)} 
                />
                <Label htmlFor="default_send_whatsapp">Send via WhatsApp by default</Label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox 
                  id="default_send_email" 
                  checked={settings.default_send_email} 
                  onCheckedChange={(c) => handleCheckboxChange('default_send_email', c)} 
                />
                <Label htmlFor="default_send_email">Send via Email by default</Label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox 
                  id="default_generate_pdf" 
                  checked={settings.default_generate_pdf} 
                  onCheckedChange={(c) => handleCheckboxChange('default_generate_pdf', c)} 
                />
                <Label htmlFor="default_generate_pdf">Generate PDF attachment by default</Label>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Assets / Attachments */}
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Letterhead Assets</CardTitle>
              <CardDescription>Images used for PDF generation and formal emails.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-8">
              
              {/* Header Upload */}
              <div className="space-y-3">
                <Label className="text-base font-semibold">Header Image</Label>
                <div className="border-2 border-dashed rounded-lg p-4 text-center hover:bg-gray-50 transition-colors relative">
                  {settings.header_url ? (
                    <div className="space-y-4">
                      <img src={settings.header_url} alt="Header Preview" className="max-h-32 mx-auto object-contain" />
                      <Button 
                        variant="destructive" 
                        size="sm" 
                        onClick={() => handleRemoveImage('header')}
                        disabled={saving}
                      >
                        <Trash2 className="w-4 h-4 mr-2" /> Remove Header
                      </Button>
                    </div>
                  ) : (
                    <div className="py-6">
                      <ImageIcon className="w-12 h-12 text-gray-300 mx-auto mb-2" />
                      <p className="text-sm text-gray-500 mb-4">Upload a high-quality header image (Max 5MB)</p>
                      <div className="flex justify-center">
                        <Label htmlFor="header-upload" className="cursor-pointer">
                          <div className="bg-[#003D82] text-white px-4 py-2 rounded-md hover:bg-blue-800 transition-colors flex items-center">
                            {uploadingHeader ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Upload className="w-4 h-4 mr-2" />}
                            Browse File
                          </div>
                        </Label>
                        <Input 
                          id="header-upload" 
                          type="file" 
                          accept=".png,.jpg,.jpeg,.webp" 
                          className="hidden" 
                          onChange={(e) => handleFileUpload(e, 'header')}
                          disabled={uploadingHeader || saving}
                        />
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {/* Footer Upload */}
              <div className="space-y-3 pt-4 border-t border-gray-100">
                <Label className="text-base font-semibold">Footer Image</Label>
                <div className="border-2 border-dashed rounded-lg p-4 text-center hover:bg-gray-50 transition-colors relative">
                  {settings.footer_url ? (
                    <div className="space-y-4">
                      <img src={settings.footer_url} alt="Footer Preview" className="max-h-32 mx-auto object-contain" />
                      <Button 
                        variant="destructive" 
                        size="sm" 
                        onClick={() => handleRemoveImage('footer')}
                        disabled={saving}
                      >
                        <Trash2 className="w-4 h-4 mr-2" /> Remove Footer
                      </Button>
                    </div>
                  ) : (
                    <div className="py-6">
                      <ImageIcon className="w-12 h-12 text-gray-300 mx-auto mb-2" />
                      <p className="text-sm text-gray-500 mb-4">Upload a high-quality footer image (Max 5MB)</p>
                      <div className="flex justify-center">
                        <Label htmlFor="footer-upload" className="cursor-pointer">
                          <div className="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 transition-colors flex items-center">
                            {uploadingFooter ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Upload className="w-4 h-4 mr-2" />}
                            Browse File
                          </div>
                        </Label>
                        <Input 
                          id="footer-upload" 
                          type="file" 
                          accept=".png,.jpg,.jpeg,.webp" 
                          className="hidden" 
                          onChange={(e) => handleFileUpload(e, 'footer')}
                          disabled={uploadingFooter || saving}
                        />
                      </div>
                    </div>
                  )}
                </div>
              </div>

            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
};

export default MessageSettingsPage;