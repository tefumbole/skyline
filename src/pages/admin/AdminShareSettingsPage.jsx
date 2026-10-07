import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/customSupabaseClient';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { useToast } from '@/components/ui/use-toast';
import { useAuth } from '@/context/AuthContext';
import { Save, Loader2, Settings2, AlertCircle, TrendingUp } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

const AdminShareSettingsPage = () => {
  const [settings, setSettings] = useState({
    total_shares_available: 0,
    price_per_share: 0,
    currency: 'USD',
    total_sold_admin_override: 0,
    total_available: 0
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [settingsId, setSettingsId] = useState(null);

  const { toast } = useToast();
  const { user } = useAuth();

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    setLoading(true);
    setError(null);
    
    try {
      console.log('AdminShareSettings: Fetching system_settings...');
      
      const { data, error: fetchError } = await supabase
        .from('system_settings')
        .select('*')
        .single();

      if (fetchError) {
        console.error('AdminShareSettings: Fetch error:', fetchError);
        throw new Error(fetchError.message || 'Failed to load settings');
      }

      if (!data) {
        throw new Error('No system settings found. Please contact support.');
      }

      console.log('AdminShareSettings: Loaded settings:', data);

      // Calculate total_available
      const totalAvailable = (data.total_shares_available || 0) - (data.total_sold_admin_override || 0);

      setSettings({
        total_shares_available: data.total_shares_available || 0,
        price_per_share: data.price_per_share || 0,
        currency: data.currency || 'USD',
        total_sold_admin_override: data.total_sold_admin_override || 0,
        total_available: totalAvailable
      });

      setSettingsId(data.id);

    } catch (err) {
      console.error('AdminShareSettings: Load error:', err);
      setError(err.message || 'Failed to load settings');
      toast({ 
        title: "Error Loading Settings", 
        description: err.message || 'Failed to load settings',
        variant: "destructive" 
      });
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setSettings(prev => {
      const newSettings = { ...prev, [name]: value };
      
      // Recalculate total_available client-side for immediate feedback
      if (name === 'total_shares_available' || name === 'total_sold_admin_override') {
        const totalShares = parseInt(newSettings.total_shares_available) || 0;
        const totalSold = parseInt(newSettings.total_sold_admin_override) || 0;
        newSettings.total_available = totalShares - totalSold;
      }
      
      return newSettings;
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!settingsId) {
      toast({
        title: "Error",
        description: "Settings ID not found. Please refresh the page.",
        variant: "destructive"
      });
      return;
    }

    setSaving(true);
    setError(null);

    try {
      console.log('AdminShareSettings: Saving settings...', settings);

      // Prepare update payload with only the fields we want to update
      const updatePayload = {
        total_shares_available: parseInt(settings.total_shares_available) || 0,
        price_per_share: parseFloat(settings.price_per_share) || 0,
        total_sold_admin_override: parseInt(settings.total_sold_admin_override) || 0,
        currency: settings.currency || 'USD',
        updated_at: new Date().toISOString()
      };

      console.log('AdminShareSettings: Update payload:', updatePayload);

      const { data, error: updateError } = await supabase
        .from('system_settings')
        .update(updatePayload)
        .eq('id', settingsId)
        .select()
        .single();

      if (updateError) {
        console.error('AdminShareSettings: Update error:', updateError);
        throw new Error(updateError.message || 'Failed to save settings');
      }

      console.log('AdminShareSettings: Settings saved successfully:', data);

      toast({ 
        title: "Settings Saved", 
        description: "Share configuration updated successfully.",
        className: "bg-green-600 text-white"
      });

      // Reload settings to ensure consistency
      await loadSettings();

    } catch (err) {
      console.error('AdminShareSettings: Save error:', err);
      setError(err.message || 'Failed to save settings');
      toast({ 
        title: "Error Saving Settings", 
        description: err.message || 'Failed to save settings',
        variant: "destructive" 
      });
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px]">
        <Loader2 className="w-12 h-12 animate-spin text-[#003D82] mb-4" />
        <p className="text-gray-600 text-lg">Loading share settings...</p>
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto space-y-6 animate-in fade-in duration-500">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82] flex items-center gap-2">
          <Settings2 className="w-8 h-8" />
          Share Settings
        </h1>
        <p className="text-gray-500 mt-1">Configure core parameters for shareholder equity.</p>
      </div>

      {error && (
        <Alert variant="destructive" className="bg-red-50 border-red-200">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription className="ml-2">{error}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <TrendingUp className="w-5 h-5 text-[#D4AF37]" /> 
            Configuration
          </CardTitle>
          <CardDescription>
            Update global share values and limits. Changes are reflected system-wide.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-2">
              <Label htmlFor="total_shares_available">
                Total Shares Available <span className="text-red-500">*</span>
              </Label>
              <Input 
                id="total_shares_available" 
                name="total_shares_available" 
                type="number" 
                min="0"
                value={settings.total_shares_available} 
                onChange={handleChange} 
                required 
                className="bg-white text-gray-900"
                disabled={saving}
              />
              <p className="text-xs text-gray-500">The total number of shares that can be issued.</p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="price_per_share">
                Price Per Share <span className="text-red-500">*</span>
              </Label>
              <Input 
                id="price_per_share" 
                name="price_per_share" 
                type="number" 
                step="0.01"
                min="0"
                value={settings.price_per_share} 
                onChange={handleChange} 
                required 
                className="bg-white text-gray-900"
                disabled={saving}
              />
              <p className="text-xs text-gray-500">The price charged per individual share.</p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="total_sold_admin_override">
                Total Sold (Admin Override)
              </Label>
              <Input 
                id="total_sold_admin_override" 
                name="total_sold_admin_override" 
                type="number" 
                min="0"
                value={settings.total_sold_admin_override} 
                onChange={handleChange} 
                className="bg-white text-gray-900"
                disabled={saving}
              />
              <p className="text-xs text-gray-500">
                Manually override the number of shares sold. Use with caution.
              </p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="total_available" className="text-gray-600">
                Total Available (Calculated)
              </Label>
              <Input 
                id="total_available" 
                name="total_available" 
                type="number" 
                value={settings.total_available} 
                readOnly 
                className="bg-gray-100 cursor-not-allowed text-gray-700"
                disabled
              />
              <p className="text-xs text-gray-500">
                Automatically calculated as: Total Shares - Total Sold
              </p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="currency">
                Currency
              </Label>
              <Input 
                id="currency" 
                name="currency" 
                maxLength="10"
                value={settings.currency} 
                onChange={handleChange} 
                placeholder="USD"
                className="bg-white text-gray-900"
                disabled={saving}
              />
              <p className="text-xs text-gray-500">Currency code (e.g., USD, EUR, XAF)</p>
            </div>

            <Button 
              type="submit" 
              className="w-full bg-[#003D82] hover:bg-[#002855] font-bold" 
              disabled={saving || loading}
            >
              {saving ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin mr-2" /> 
                  Saving Configuration...
                </>
              ) : (
                <>
                  <Save className="w-4 h-4 mr-2" />
                  Save Configuration
                </>
              )}
            </Button>
          </form>
        </CardContent>
      </Card>

      <Card className="bg-blue-50 border-blue-200">
        <CardContent className="pt-6">
          <h3 className="font-semibold text-blue-900 mb-2">ℹ️ Important Notes</h3>
          <ul className="text-sm text-blue-800 space-y-1 list-disc list-inside">
            <li>Changes to share settings affect all shareholders system-wide</li>
            <li>Total Available is automatically calculated and cannot be edited directly</li>
            <li>Use "Total Sold Override" only when correcting data discrepancies</li>
            <li>Price changes do not retroactively affect existing shareholders</li>
          </ul>
        </CardContent>
      </Card>
    </div>
  );
};

export default AdminShareSettingsPage;