import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/context/AuthContext';
import { fetchEventMealSelections, calculateMealSummary, exportToCSV } from '@/services/mealSelectionsService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Utensils, Download, Printer, PieChart, Users } from 'lucide-react';
import { format } from 'date-fns';

const MealSelectionsPage = () => {
  const { user } = useAuth();
  const { toast } = useToast();
  
  const [events, setEvents] = useState([]);
  const [selectedEvent, setSelectedEvent] = useState('');
  const [selections, setSelections] = useState([]);
  const [summary, setSummary] = useState(null);
  
  const [loadingEvents, setLoadingEvents] = useState(true);
  const [loadingData, setLoadingData] = useState(false);

  useEffect(() => {
    const fetchEvents = async () => {
      if (!user) return;
      try {
        const { data, error } = await supabase
          .from('events')
          .select('id, event_name, event_date')
          .eq('created_by', user.id)
          .order('event_date', { ascending: false });

        if (error) throw error;
        setEvents(data || []);
        if (data && data.length > 0) {
          setSelectedEvent(data[0].id);
        }
      } catch (err) {
        toast({ title: 'Error loading events', description: err.message, variant: 'destructive' });
      } finally {
        setLoadingEvents(false);
      }
    };
    fetchEvents();
  }, [user]);

  useEffect(() => {
    const loadSelections = async () => {
      if (!selectedEvent) return;
      setLoadingData(true);
      
      const res = await fetchEventMealSelections(selectedEvent);
      if (res.success) {
        setSelections(res.data);
        setSummary(calculateMealSummary(res.data));
      } else {
        toast({ title: 'Error', description: res.error, variant: 'destructive' });
      }
      setLoadingData(false);
    };
    
    loadSelections();
  }, [selectedEvent]);

  const handlePrint = () => {
    window.print();
  };

  const handleExport = () => {
    const eventName = events.find(e => e.id === selectedEvent)?.event_name || 'Event';
    exportToCSV(selections, eventName);
  };

  const SummaryStat = ({ title, obj = {} }) => (
    <div className="space-y-2">
      <h4 className="font-semibold text-sm text-gray-500 uppercase tracking-wider">{title}</h4>
      {Object.entries(obj).length === 0 ? (
        <p className="text-sm text-gray-400">No data</p>
      ) : (
        <ul className="space-y-1">
          {Object.entries(obj).map(([key, count]) => (
            <li key={key} className="flex justify-between items-center bg-gray-50 px-3 py-2 rounded-md border border-gray-100">
              <span className="font-medium text-gray-700">{key}</span>
              <span className="bg-[#003D82] text-white px-2 py-0.5 rounded-full text-xs font-bold">{count}</span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );

  return (
    <div className="space-y-6 pb-10">
      
      {/* Non-Printable Header */}
      <div className="print:hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-[#003D82] flex items-center">
            <Utensils className="w-6 h-6 mr-2 text-[#D4AF37]" />
            Meal Selections Dashboard
          </h1>
          <p className="text-gray-500 mt-1">Manage and track guest menu preferences.</p>
        </div>
        
        <div className="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
          {events.length > 0 && (
            <Select value={selectedEvent} onValueChange={setSelectedEvent}>
              <SelectTrigger className="w-full sm:w-[250px] bg-white">
                <SelectValue placeholder="Select Event" />
              </SelectTrigger>
              <SelectContent>
                {events.map(ev => (
                  <SelectItem key={ev.id} value={ev.id}>
                    {ev.event_name} ({format(new Date(ev.event_date), 'MMM d')})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}
          
          <Button variant="outline" onClick={handlePrint} disabled={!selections.length} className="bg-white">
            <Printer className="w-4 h-4 mr-2" /> Print Report
          </Button>
          <Button onClick={handleExport} disabled={!selections.length} className="bg-[#003D82] text-white hover:bg-blue-800">
            <Download className="w-4 h-4 mr-2" /> Export CSV
          </Button>
        </div>
      </div>

      {/* Printable Header */}
      <div className="hidden print:block mb-8 text-center">
        <h1 className="text-2xl font-bold">Meal Selections Report</h1>
        <h2 className="text-xl mt-2">{events.find(e => e.id === selectedEvent)?.event_name}</h2>
        <p className="text-gray-500">Generated on {format(new Date(), 'PPP p')}</p>
      </div>

      {loadingEvents ? (
        <div className="flex justify-center py-10"><Loader2 className="w-8 h-8 animate-spin text-gray-400" /></div>
      ) : events.length === 0 ? (
        <Card className="print:hidden border-dashed border-2 py-12 bg-gray-50 text-center">
          <CardContent>
            <p className="text-gray-500">You haven't created any events yet.</p>
          </CardContent>
        </Card>
      ) : loadingData ? (
         <div className="flex justify-center py-20"><Loader2 className="w-8 h-8 animate-spin text-[#003D82]" /></div>
      ) : (
        <>
          {/* Summary Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card className="bg-[#003D82] text-white">
              <CardContent className="p-6 flex items-center justify-between">
                <div>
                  <p className="text-blue-100 text-sm font-medium">Total Confirmed</p>
                  <h3 className="text-3xl font-bold mt-1">{summary?.total || 0}</h3>
                </div>
                <div className="p-3 bg-white/10 rounded-full"><Users className="w-6 h-6 text-[#D4AF37]" /></div>
              </CardContent>
            </Card>
            <Card className="bg-white">
              <CardContent className="p-6 flex items-center justify-between border-l-4 border-indigo-500">
                <div>
                  <p className="text-gray-500 text-sm font-medium">Unique Main Courses</p>
                  <h3 className="text-2xl font-bold text-gray-800 mt-1">{Object.keys(summary?.mainCourses || {}).length}</h3>
                </div>
                <div className="p-3 bg-indigo-50 rounded-full"><Utensils className="w-6 h-6 text-indigo-500" /></div>
              </CardContent>
            </Card>
            <Card className="bg-white">
              <CardContent className="p-6 flex items-center justify-between border-l-4 border-emerald-500">
                <div>
                  <p className="text-gray-500 text-sm font-medium">Unique Side Dishes</p>
                  <h3 className="text-2xl font-bold text-gray-800 mt-1">{Object.keys(summary?.sideDishes || {}).length}</h3>
                </div>
                <div className="p-3 bg-emerald-50 rounded-full"><PieChart className="w-6 h-6 text-emerald-500" /></div>
              </CardContent>
            </Card>
            <Card className="bg-white">
              <CardContent className="p-6 flex items-center justify-between border-l-4 border-orange-500">
                <div>
                  <p className="text-gray-500 text-sm font-medium">Unique Starters</p>
                  <h3 className="text-2xl font-bold text-gray-800 mt-1">{Object.keys(summary?.starters || {}).length}</h3>
                </div>
                <div className="p-3 bg-orange-50 rounded-full"><PieChart className="w-6 h-6 text-orange-500" /></div>
              </CardContent>
            </Card>
          </div>

          {/* Breakdown Section */}
          <Card className="bg-white shadow-sm border-gray-200">
            <CardHeader className="border-b border-gray-100 bg-gray-50/50 pb-4">
              <CardTitle className="text-lg">Kitchen Breakdown</CardTitle>
            </CardHeader>
            <CardContent className="pt-6">
               <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                  <SummaryStat title="Main Courses" obj={summary?.mainCourses} />
                  <SummaryStat title="Side Dishes" obj={summary?.sideDishes} />
                  <SummaryStat title="Starters" obj={summary?.starters} />
               </div>
            </CardContent>
          </Card>

          {/* Detailed Table */}
          <Card className="bg-white shadow-sm border-gray-200 overflow-hidden">
            <CardHeader className="border-b border-gray-100 bg-gray-50/50 pb-4">
              <CardTitle className="text-lg">Guest List & Details</CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              {selections.length === 0 ? (
                <div className="py-16 text-center text-gray-500">
                  <Utensils className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                  <p>No meal selections recorded yet for this event.</p>
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader className="bg-gray-50">
                      <TableRow>
                        <TableHead className="font-semibold text-gray-700">Name</TableHead>
                        <TableHead className="font-semibold text-gray-700">Phone</TableHead>
                        <TableHead className="font-semibold text-gray-700">Starter</TableHead>
                        <TableHead className="font-semibold text-gray-700">Main Course</TableHead>
                        <TableHead className="font-semibold text-gray-700">Side Dish</TableHead>
                        <TableHead className="font-semibold text-gray-700">Date Confirmed</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {selections.map(sel => (
                        <TableRow key={sel.id} className="hover:bg-gray-50/50">
                          <TableCell className="font-medium">{sel.name}</TableCell>
                          <TableCell className="text-gray-600">{sel.phone}</TableCell>
                          <TableCell>{sel.starter}</TableCell>
                          <TableCell className="font-medium text-[#003D82]">{sel.main_course}</TableCell>
                          <TableCell>{sel.side_dish}</TableCell>
                          <TableCell className="text-sm text-gray-500">{format(new Date(sel.created_at), 'MMM d, p')}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
};

export default MealSelectionsPage;