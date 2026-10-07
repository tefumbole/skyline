import React, { useState, useEffect } from 'react';
import { getSettings, updateSettings } from '@/services/CommunicationSettingsService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/use-toast';
import { Save, Loader2, Settings } from 'lucide-react';

const AdminCommunicationSettingsPage = () => {
  const [settings, setSettings] = useState(null);
  const [loading, setLoading] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    const data = await getSettings();
    setSettings(data);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setLoading(true);
    await updateSettings(settings);
    setLoading(false);
    toast({ title: "Settings Saved", description: "Communication preferences updated." });
  };

  if (!settings) return <div className="p-8 text-center">Loading settings...</div>;

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-in fade-in duration-500">
        <div>
            <h1 className="text-3xl font-bold text-[#003D82]">Communication Settings</h1>
            <p className="text-gray-500">Configure global preferences for system messaging.</p>
        </div>

        <form onSubmit={handleSave}>
            <div className="grid gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><Settings className="w-5 h-5" /> General Configuration</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="flex items-center justify-between p-4 border rounded-lg">
                            <div className="space-y-0.5">
                                <Label className="text-base">Email Notifications</Label>
                                <p className="text-sm text-gray-500">Send email copies of notifications automatically.</p>
                            </div>
                            <Switch 
                                checked={settings.email_notifications}
                                onCheckedChange={(c) => setSettings({...settings, email_notifications: c})}
                            />
                        </div>
                        <div className="flex items-center justify-between p-4 border rounded-lg">
                            <div className="space-y-0.5">
                                <Label className="text-base">SMS Notifications</Label>
                                <p className="text-sm text-gray-500">Send SMS alerts for high priority messages.</p>
                            </div>
                            <Switch 
                                checked={settings.sms_notifications}
                                onCheckedChange={(c) => setSettings({...settings, sms_notifications: c})}
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Data Retention</CardTitle>
                        <CardDescription>Manage how long messages are kept active.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                         <div className="grid md:grid-cols-2 gap-6">
                            <div className="space-y-2">
                                <Label>Default Retention (Days)</Label>
                                <Input 
                                    type="number" 
                                    value={settings.default_retention_days}
                                    onChange={(e) => setSettings({...settings, default_retention_days: e.target.value})}
                                />
                            </div>
                            <div className="flex items-center space-x-2 pt-8">
                                <Switch 
                                    id="archive"
                                    checked={settings.auto_archive}
                                    onCheckedChange={(c) => setSettings({...settings, auto_archive: c})}
                                />
                                <Label htmlFor="archive">Auto-Archive Old Messages</Label>
                            </div>
                         </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Defaults</CardTitle>
                    </CardHeader>
                    <CardContent className="grid md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label>Default Letter Template</Label>
                            <Select 
                                value={settings.default_letter_template}
                                onValueChange={(v) => setSettings({...settings, default_letter_template: v})}
                            >
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Standard Business">Standard Business</SelectItem>
                                    <SelectItem value="Modern Minimal">Modern Minimal</SelectItem>
                                    <SelectItem value="Formal Legal">Formal Legal</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                         <div className="space-y-2">
                            <Label>Default Notification Type</Label>
                            <Select 
                                value={settings.default_notification_template}
                                onValueChange={(v) => setSettings({...settings, default_notification_template: v})}
                            >
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Simple Alert">Simple Alert</SelectItem>
                                    <SelectItem value="Action Required">Action Required</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button type="submit" className="bg-[#003D82] min-w-[150px]" disabled={loading}>
                        {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Save className="w-4 h-4 mr-2" />}
                        Save Changes
                    </Button>
                </div>
            </div>
        </form>
    </div>
  );
};

export default AdminCommunicationSettingsPage;