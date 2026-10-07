import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Loader2, Save, Plus, Trash2, Scale } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import { getSystemSettings, updateSystemSettings } from '@/services/settingsService';
import { DEFAULT_LICENSE_SECTIONS } from '@/data/defaultLicenseAgreement';

const LicenseAgreementTab = () => {
  const { toast } = useToast();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [sections, setSections] = useState(DEFAULT_LICENSE_SECTIONS);

  useEffect(() => {
    (async () => {
      try {
        const data = await getSystemSettings();
        const raw = data?.license_agreement_json;
        if (raw) {
          const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
          if (Array.isArray(parsed) && parsed.length) setSections(parsed);
        }
      } catch (err) {
        toast({ variant: 'destructive', title: 'Load failed', description: err.message });
      } finally {
        setLoading(false);
      }
    })();
  }, [toast]);

  const updateSection = (index, field, value) => {
    setSections((prev) => {
      const next = [...prev];
      next[index] = { ...next[index], [field]: value };
      return next;
    });
  };

  const updateParagraphs = (index, text) => {
    setSections((prev) => {
      const next = [...prev];
      next[index] = {
        ...next[index],
        paragraphs: text.split('\n').map((line) => line.trim()).filter(Boolean),
      };
      return next;
    });
  };

  const addSection = () => {
    setSections((prev) => [
      ...prev,
      { number: prev.length + 1, icon: 'Info', title: 'New Section', paragraphs: [''] },
    ]);
  };

  const removeSection = (index) => {
    setSections((prev) => prev.filter((_, i) => i !== index).map((s, i) => ({ ...s, number: i + 1 })));
  };

  const save = async () => {
    setSaving(true);
    try {
      await updateSystemSettings({ license_agreement_json: sections });
      toast({ title: 'License agreement saved', description: 'Changes appear on the public shareholders agreement page.' });
    } catch (err) {
      toast({ variant: 'destructive', title: 'Save failed', description: err.message });
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center p-12">
        <Loader2 className="w-8 h-8 animate-spin text-[#003D82]" />
      </div>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Scale className="w-5 h-5" /> License / Shareholder Agreement
        </CardTitle>
        <CardDescription>
          Edit the agreement text shown on the public Shareholders Agreement page. Use {'${price_per_share}'} in Share Price section.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {sections.map((section, index) => (
          <div key={index} className="border rounded-lg p-4 space-y-3 bg-gray-50">
            <div className="flex flex-wrap gap-3 items-center">
              <div className="w-16">
                <Label className="text-xs">No.</Label>
                <Input
                  type="number"
                  min="1"
                  value={section.number}
                  onChange={(e) => updateSection(index, 'number', Number(e.target.value))}
                />
              </div>
              <div className="flex-1 min-w-[180px]">
                <Label className="text-xs">Title</Label>
                <Input value={section.title} onChange={(e) => updateSection(index, 'title', e.target.value)} />
              </div>
              <div className="w-32">
                <Label className="text-xs">Icon</Label>
                <Input value={section.icon || 'Info'} onChange={(e) => updateSection(index, 'icon', e.target.value)} />
              </div>
              <Button type="button" variant="ghost" size="icon" onClick={() => removeSection(index)} className="self-end">
                <Trash2 className="w-4 h-4 text-red-500" />
              </Button>
            </div>
            <div>
              <Label className="text-xs">Content (one paragraph per line)</Label>
              <Textarea
                rows={4}
                value={(section.paragraphs || []).join('\n')}
                onChange={(e) => updateParagraphs(index, e.target.value)}
              />
            </div>
          </div>
        ))}

        <div className="flex flex-wrap gap-3">
          <Button type="button" variant="outline" onClick={addSection}>
            <Plus className="w-4 h-4 mr-1" /> Add Section
          </Button>
          <Button onClick={save} disabled={saving} className="bg-[#003D82]">
            {saving ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Save className="w-4 h-4 mr-2" />}
            Save License Agreement
          </Button>
        </div>
      </CardContent>
    </Card>
  );
};

export default LicenseAgreementTab;
