import React, { useState, useEffect } from 'react';
import { getAllEvents, deleteEvent } from '@/services/eventsService';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';
import EventForm from '@/components/admin/EventForm';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle
} from '@/components/ui/alert-dialog';
import { Calendar, MapPin, Edit, Trash2, Plus, Loader2, AlertCircle } from 'lucide-react';
import { format } from 'date-fns';

const EventManagementPage = () => {
  const { toast } = useToast();

  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editingEvent, setEditingEvent] = useState(null);
  const [deleteConfirmId, setDeleteConfirmId] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchEvents = async () => {
    setLoading(true);
    setError(null);

    try {
      const data = await getAllEvents();
      setEvents(data || []);
    } catch (err) {
      console.error('EventManagementPage: Error fetching events:', err);
      setError('Failed to load events');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEvents();
  }, []);

  const handleCreate = () => {
    setEditingEvent(null);
    setFormOpen(true);
  };

  const handleEdit = (event) => {
    setEditingEvent(event);
    setFormOpen(true);
  };

  const handleFormClose = (shouldRefresh) => {
    setFormOpen(false);
    setEditingEvent(null);
    if (shouldRefresh) {
      fetchEvents();
    }
  };

  const handleDelete = async (eventId) => {
    setDeleting(true);

    try {
      await deleteEvent(eventId);

      toast({
        title: 'Event Deleted',
        description: 'The event has been successfully deleted.',
        className: 'bg-green-600 text-white'
      });

      setDeleteConfirmId(null);
      await fetchEvents();
    } catch (err) {
      console.error('EventManagementPage: Error deleting event:', err);
      toast({
        title: 'Delete Failed',
        description: err.message || 'Failed to delete event',
        variant: 'destructive'
      });
    } finally {
      setDeleting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-center">
          <Loader2 className="w-12 h-12 animate-spin text-[#003D82] mx-auto mb-4" />
          <p className="text-gray-600">Loading events...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Events Management</h1>
          <p className="text-gray-600 mt-1">Manage events and highlights</p>
        </div>
        <Button
          onClick={handleCreate}
          className="bg-[#003D82] hover:bg-[#002855] text-white"
        >
          <Plus className="w-4 h-4 mr-2" />
          Create New Event
        </Button>
      </div>

      {/* Error Alert */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-600" />
          <p className="text-red-800">{error}</p>
        </div>
      )}

      {/* Events Table */}
      {events.length === 0 ? (
        <div className="text-center py-12 bg-white rounded-lg border">
          <Calendar className="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <h3 className="text-xl font-semibold text-gray-800 mb-2">No Events Yet</h3>
          <p className="text-gray-600 mb-4">Get started by creating your first event</p>
          <Button
            onClick={handleCreate}
            className="bg-[#003D82] hover:bg-[#002855] text-white"
          >
            <Plus className="w-4 h-4 mr-2" />
            Create Event
          </Button>
        </div>
      ) : (
        <div className="bg-white rounded-lg border shadow-sm overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Title</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Location</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {events.map((event) => (
                <TableRow key={event.id}>
                  <TableCell className="font-medium">{event.title}</TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2 text-sm">
                      <Calendar className="w-4 h-4 text-gray-400" />
                      {format(new Date(event.event_date), 'MMM dd, yyyy')}
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge
                      variant="outline"
                      className={
                        event.status === 'published'
                          ? 'bg-green-50 text-green-700 border-green-300'
                          : 'bg-gray-50 text-gray-700 border-gray-300'
                      }
                    >
                      {event.status || 'draft'}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    {event.location ? (
                      <div className="flex items-center gap-2 text-sm text-gray-600">
                        <MapPin className="w-4 h-4" />
                        <span className="line-clamp-1">{event.location}</span>
                      </div>
                    ) : (
                      <span className="text-gray-400 text-sm">No location</span>
                    )}
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex items-center justify-end gap-2">
                      <Button
                        onClick={() => handleEdit(event)}
                        size="sm"
                        variant="outline"
                        className="hover:bg-blue-50 hover:border-blue-300"
                      >
                        <Edit className="w-4 h-4" />
                      </Button>
                      <Button
                        onClick={() => setDeleteConfirmId(event.id)}
                        size="sm"
                        variant="destructive"
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}

      {/* Event Form Modal */}
      {formOpen && (
        <EventForm
          event={editingEvent}
          onClose={handleFormClose}
        />
      )}

      {/* Delete Confirmation Dialog */}
      <AlertDialog
        open={!!deleteConfirmId}
        onOpenChange={() => !deleting && setDeleteConfirmId(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Event?</AlertDialogTitle>
            <AlertDialogDescription>
              This will permanently delete this event and all its images. This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={deleting}>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteConfirmId && handleDelete(deleteConfirmId)}
              disabled={deleting}
              className="bg-red-600 hover:bg-red-700"
            >
              {deleting ? (
                <>
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  Deleting...
                </>
              ) : (
                'Delete'
              )}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default EventManagementPage;