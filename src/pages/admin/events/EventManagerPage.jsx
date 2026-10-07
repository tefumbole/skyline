import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import AdminHorizontalNav from '@/components/admin/AdminHorizontalNav';
import { EVENT_NAV } from '@/config/eventNavConfig';
import { getAllEvents, deleteEvent } from '@/services/eventService';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { useToast } from '@/components/ui/use-toast';
import { PlusCircle, Calendar, MapPin, Clock, Trash2, Edit, Users, Eye } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';

const EventManagerPage = () => {
    const [events, setEvents] = useState([]);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();
    const { toast } = useToast();

    const loadEvents = async () => {
        setLoading(true);
        const res = await getAllEvents();
        if (res.success) {
            setEvents(res.data);
        }
        setLoading(false);
    };

    useEffect(() => {
        loadEvents();
    }, []);

    const handleDelete = async (id) => {
        if(!window.confirm("Are you sure you want to delete this event? All associated invitations will be lost.")) return;
        const res = await deleteEvent(id);
        if (res.success) {
            toast({ title: "Event deleted" });
            loadEvents();
        }
    };

    return (
        <div className="space-y-6">
            <AdminHorizontalNav items={EVENT_NAV} title="Event Management" description="Manage events, meals, and analytics." />
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82]">Event Manager</h1>
                    <p className="text-gray-500">Manage your digital events and track analytics.</p>
                </div>
                <Button onClick={() => navigate('/admin/events/create')} className="bg-[#003D82] hover:bg-[#002a5a]">
                    <PlusCircle className="w-4 h-4 mr-2" /> Create Event
                </Button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card className="bg-white shadow-sm border-t-4 border-t-blue-500">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Total Events</p>
                                <h3 className="text-3xl font-bold text-gray-900">{events.length}</h3>
                            </div>
                            <div className="p-3 bg-blue-50 rounded-full">
                                <Calendar className="w-6 h-6 text-blue-500" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {loading ? (
                <div className="text-center py-12">Loading events...</div>
            ) : events.length === 0 ? (
                <div className="text-center py-12 bg-white rounded-lg border border-dashed">
                    <Calendar className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                    <h3 className="text-lg font-medium">No events found</h3>
                    <p className="text-gray-500 mb-4">Create your first digital event to get started.</p>
                    <Button onClick={() => navigate('/admin/events/create')} variant="outline">Create Event</Button>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {events.map(event => (
                        <Card key={event.id} className="overflow-hidden hover:shadow-md transition-shadow">
                            <div className="h-40 w-full bg-gray-100 relative">
                                {event.banner_url ? (
                                    <img src={event.banner_url} alt={event.name} className="w-full h-full object-cover" />
                                ) : (
                                    <div className="flex items-center justify-center h-full text-gray-400">No Banner</div>
                                )}
                                <Badge className="absolute top-3 right-3 bg-white text-[#003D82] border-none shadow-sm">
                                    {new Date(event.date) > new Date() ? 'Upcoming' : 'Past'}
                                </Badge>
                            </div>
                            <CardContent className="p-5 space-y-3">
                                <h3 className="text-xl font-bold text-gray-900 line-clamp-1">{event.name}</h3>
                                <div className="space-y-1 text-sm text-gray-600">
                                    <div className="flex items-center">
                                        <Calendar className="w-4 h-4 mr-2 text-gray-400" />
                                        {format(new Date(event.date), 'MMMM dd, yyyy')}
                                    </div>
                                    <div className="flex items-center">
                                        <Clock className="w-4 h-4 mr-2 text-gray-400" />
                                        {event.time}
                                    </div>
                                    <div className="flex items-center">
                                        <MapPin className="w-4 h-4 mr-2 text-gray-400" />
                                        <span className="line-clamp-1">{event.location || 'TBA'}</span>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter className="p-4 pt-0 border-t bg-gray-50/50 flex justify-between gap-2">
                                <Button variant="outline" size="sm" className="flex-1" onClick={() => navigate(`/admin/invitations/create?event=${event.id}`)}>
                                    <Users className="w-4 h-4 mr-2" /> Invite
                                </Button>
                                <Button variant="ghost" size="sm" className="text-red-500 hover:text-red-700 hover:bg-red-50" onClick={() => handleDelete(event.id)}>
                                    <Trash2 className="w-4 h-4" />
                                </Button>
                            </CardFooter>
                        </Card>
                    ))}
                </div>
            )}
        </div>
    );
};

export default EventManagerPage;