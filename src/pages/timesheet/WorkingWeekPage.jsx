import React, { useState, useEffect } from 'react';
import { useTimeSheet } from '@/context/TimeSheetContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Save, Loader2, Info, Clock, CalendarDays } from 'lucide-react';
import { calculateDailyHours } from '@/services/workingWeekService';

const WorkingWeekPage = () => {
  const { workingWeek, saveWorkingWeek, loading: ctxLoading } = useTimeSheet();
  const [settings, setSettings] = useState(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (workingWeek) {
      setSettings(workingWeek);
    }
  }, [workingWeek]);

  const handleDayToggle = (day) => {
    setSettings(prev => ({
      ...prev,
      [day]: !prev[day]
    }));
  };

  const handleTimeChange = (day, field, value) => {
    setSettings(prev => ({
      ...prev,
      [`${day}_${field}`]: value
    }));
  };

  const handleGlobalChange = (e) => {
      const { name, value } = e.target;
      setSettings(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    await saveWorkingWeek(settings);
    setSaving(false);
  };

  if (!settings && ctxLoading) return <div className="p-8 text-center"><Loader2 className="w-8 h-8 animate-spin mx-auto mb-2"/>Loading schedule...</div>;
  if (!settings) return null;

  const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
  
  // Calculate total
  let totalWeeklyHours = 0;
  days.forEach(day => {
      if (settings[day]) {
          const daily = calculateDailyHours(
              settings[`${day}_start`] || '08:00', 
              settings[`${day}_end`] || '17:00', 
              settings.lunch_break_minutes || 0
          );
          totalWeeklyHours += daily;
      }
  });

  return (
    <div className="max-w-7xl mx-auto p-4 md:p-8 space-y-6 animate-in fade-in duration-500">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
         <div>
            <h1 className="text-3xl font-bold text-[#003D82]">Working Week Configuration</h1>
            <p className="text-gray-500">Set your weekly schedule and working hours.</p>
         </div>
         <Button type="submit" form="working-week-form" className="bg-[#003D82] font-bold shadow-lg" disabled={saving}>
            {saving ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Save className="w-4 h-4 mr-2" />}
            Save Changes
         </Button>
      </div>

      <div className="grid lg:grid-cols-3 gap-8">
        {/* Main Form */}
        <Card className="lg:col-span-2 border-t-4 border-t-[#003D82] shadow-lg">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
                <CalendarDays className="w-5 h-5 text-[#D4AF37]" /> Weekly Schedule
            </CardTitle>
            <CardDescription>Define working hours for each day of the week.</CardDescription>
          </CardHeader>
          <CardContent>
            <form id="working-week-form" onSubmit={handleSubmit} className="space-y-6">
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-blue-50/50 p-4 rounded-lg border border-blue-100">
                  <div className="space-y-2">
                      <Label htmlFor="lunch_break_minutes" className="text-[#003D82] font-semibold">Lunch Break (Minutes)</Label>
                      <Input 
                        id="lunch_break_minutes" 
                        name="lunch_break_minutes"
                        type="number" 
                        min="0"
                        value={settings.lunch_break_minutes} 
                        onChange={handleGlobalChange}
                        className="bg-white"
                      />
                      <p className="text-xs text-gray-500">Deducted from daily total.</p>
                  </div>
              </div>

              <div className="space-y-4">
                  {days.map(day => {
                      const isActive = settings[day];
                      const start = settings[`${day}_start`] || '08:00';
                      const end = settings[`${day}_end`] || '17:00';
                      const dailyHours = isActive ? calculateDailyHours(start, end, settings.lunch_break_minutes || 0) : 0;

                      return (
                        <div key={day} className={`p-4 rounded-lg border transition-all ${isActive ? 'bg-white border-gray-200 shadow-sm' : 'bg-gray-50 border-transparent opacity-60'}`}>
                            <div className="flex flex-col md:flex-row gap-4 items-center">
                                <div className="flex items-center space-x-3 w-32 shrink-0">
                                    <Checkbox 
                                        id={day} 
                                        checked={isActive} 
                                        onCheckedChange={() => handleDayToggle(day)} 
                                        className="border-gray-400 data-[state=checked]:bg-[#003D82] data-[state=checked]:border-[#003D82]"
                                    />
                                    <Label htmlFor={day} className="capitalize font-bold text-gray-700 cursor-pointer">{day}</Label>
                                </div>

                                {isActive && (
                                    <>
                                        <div className="flex items-center gap-2">
                                            <div className="grid gap-1">
                                                <Label className="text-xs text-gray-500">From</Label>
                                                <div className="relative">
                                                    <Clock className="absolute left-2 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                                                    <Input 
                                                        type="time" 
                                                        value={start}
                                                        onChange={(e) => handleTimeChange(day, 'start', e.target.value)}
                                                        className="pl-8 w-32"
                                                    />
                                                </div>
                                            </div>
                                            <span className="text-gray-400 mt-5">-</span>
                                            <div className="grid gap-1">
                                                <Label className="text-xs text-gray-500">To</Label>
                                                <div className="relative">
                                                    <Clock className="absolute left-2 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                                                    <Input 
                                                        type="time" 
                                                        value={end}
                                                        onChange={(e) => handleTimeChange(day, 'end', e.target.value)}
                                                        className="pl-8 w-32"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div className="ml-auto flex items-center gap-2 bg-blue-50 px-3 py-1 rounded text-[#003D82] font-medium text-sm">
                                            <span>{dailyHours.toFixed(2)}h</span>
                                        </div>
                                    </>
                                )}
                                {!isActive && <span className="text-sm text-gray-400 italic ml-2">Day Off</span>}
                            </div>
                        </div>
                      );
                  })}
              </div>
            </form>
          </CardContent>
        </Card>

        {/* Sidebar Summary */}
        <div className="space-y-6">
           <Card className="bg-gradient-to-br from-[#003D82] to-[#00509d] text-white shadow-xl border-none sticky top-24">
             <CardHeader>
               <CardTitle className="text-white flex items-center gap-2">
                   <Info className="w-5 h-5 text-[#D4AF37]" /> Summary
               </CardTitle>
             </CardHeader>
             <CardContent className="space-y-6">
               <div className="flex items-center justify-between border-b border-white/20 pb-4">
                 <span className="opacity-80">Working Days</span>
                 <span className="text-xl font-bold">{days.filter(d => settings[d]).length}</span>
               </div>
               
               <div>
                 <span className="block opacity-80 text-sm mb-1 uppercase tracking-wider">Total Expected Hours</span>
                 <span className="text-5xl font-bold text-[#D4AF37]">{totalWeeklyHours.toFixed(2)}<span className="text-2xl text-white/60 ml-1">h</span></span>
                 <p className="text-xs text-white/50 mt-2">Per week based on current configuration.</p>
               </div>

               <div className="bg-black/20 rounded p-3 text-sm">
                   <p className="opacity-90">Lunch break of <strong>{settings.lunch_break_minutes} min</strong> is deducted daily.</p>
               </div>
             </CardContent>
           </Card>
        </div>
      </div>
    </div>
  );
};

export default WorkingWeekPage;