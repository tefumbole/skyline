import React, { useState, useEffect } from 'react';
import { getTrainingCourses, deleteCourse, reorderCourses, syncTrainingCourses } from '@/services/coursesService';
import { getFeedbackByCourse, deleteFeedback } from '@/services/feedbackService';
import CourseFormModal from '@/components/admin/CourseFormModal';
import EditCourseForm from '@/components/admin/EditCourseForm';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/use-toast';
import { Plus, Edit, Trash2, Search, BookOpen, Loader2, AlertCircle, MessageCircle, ChevronUp, ChevronDown, ChevronsUp, ChevronsDown } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import StarRating from '@/components/StarRating';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";

const AdminCoursesPage = ({ defaultTab }) => {
    const [courses, setCourses] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    
    // Modal & Action states
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [editingCourse, setEditingCourse] = useState(null);
    const [deleteId, setDeleteId] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    // Feedback Modal State
    const [feedbackModalOpen, setFeedbackModalOpen] = useState(false);
    const [selectedCourseFeedback, setSelectedCourseFeedback] = useState([]);
    const [selectedCourseName, setSelectedCourseName] = useState('');
    const [loadingFeedback, setLoadingFeedback] = useState(false);
    const [syncing, setSyncing] = useState(false);

    const { toast } = useToast();

    useEffect(() => {
        loadCourses();
        if (defaultTab === 'add') {
            handleCreate();
        }
    }, [defaultTab]);

    useEffect(() => {
        (async () => {
            try {
                setSyncing(true);
                const result = await syncTrainingCourses();
                await loadCourses();
                if (result.created || result.updated || result.removed || result.archived) {
                    toast({
                        title: 'Training programs synced',
                        description: `${result.updated} updated, ${result.created} created, ${result.removed} removed${result.archived ? `, ${result.archived} archived` : ''}.`,
                    });
                }
            } catch (error) {
                console.warn('Training sync skipped:', error.message);
            } finally {
                setSyncing(false);
            }
        })();
    }, []);

    const loadCourses = async () => {
        setLoading(true);
        try {
            const data = await getTrainingCourses();
            setCourses(data);
        } catch (error) {
            toast({ title: "Fetch Error", description: error.message, variant: "destructive" });
        } finally {
            setLoading(false);
        }
    };

    const handleCreate = () => {
        setEditingCourse(null);
        setIsCreateModalOpen(true);
    };

    const handleEdit = (course) => {
        setEditingCourse(course);
        setIsEditModalOpen(true);
    };

    const handleDelete = async () => {
        if (!deleteId) return;
        
        setIsDeleting(true);
        try {
            await deleteCourse(deleteId);
            toast({ title: "Deleted", description: "Course removed successfully." });
            setDeleteId(null);
            loadCourses();
        } catch (error) {
            toast({ title: "Delete Failed", description: error.message, variant: "destructive" });
        } finally {
            setIsDeleting(false);
        }
    };

    const handleViewFeedback = async (course) => {
        setFeedbackModalOpen(true);
        setSelectedCourseName(course.name);
        setLoadingFeedback(true);
        try {
            const data = await getFeedbackByCourse(course.id);
            setSelectedCourseFeedback(data);
        } catch (error) {
            toast({ title: "Error", description: "Failed to load feedback.", variant: "destructive" });
        } finally {
            setLoadingFeedback(false);
        }
    };

    const handleDeleteFeedback = async (feedbackId) => {
        try {
            await deleteFeedback(feedbackId);
            setSelectedCourseFeedback(prev => prev.filter(f => f.id !== feedbackId));
            toast({ title: "Feedback Deleted", description: "Review removed successfully." });
        } catch (error) {
            toast({ title: "Error", description: "Failed to delete feedback.", variant: "destructive" });
        }
    };

    const filteredCourses = courses.filter(c => 
        c.name.toLowerCase().includes(search.toLowerCase()) || 
        (c.description || '').toLowerCase().includes(search.toLowerCase())
    );

    const moveCourse = async (index, direction) => {
        const list = [...filteredCourses];
        const target = index + direction;
        if (target < 0 || target >= list.length) return;
        const swapped = [...list];
        [swapped[index], swapped[target]] = [swapped[target], swapped[index]];
        try {
            await reorderCourses(swapped.map((c) => c.id));
            toast({ title: 'Order updated', description: 'Course display order saved.' });
            loadCourses();
        } catch (error) {
            toast({ title: 'Reorder failed', description: error.message, variant: 'destructive' });
        }
    };

    const moveCourseToEdge = async (index, edge) => {
        const list = [...filteredCourses];
        if (index < 0 || index >= list.length) return;
        const reordered = [...list];
        const [item] = reordered.splice(index, 1);
        if (edge === 'first') {
            reordered.unshift(item);
        } else {
            reordered.push(item);
        }
        try {
            await reorderCourses(reordered.map((c) => c.id));
            toast({ title: 'Order updated', description: edge === 'first' ? 'Course moved to first.' : 'Course moved to last.' });
            loadCourses();
        } catch (error) {
            toast({ title: 'Reorder failed', description: error.message, variant: 'destructive' });
        }
    };

    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b pb-6">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82] flex items-center gap-3">
                        <BookOpen className="w-8 h-8" /> Course Management
                    </h1>
                    <p className="text-gray-500 mt-1">Manage educational courses and training programs.</p>
                </div>
                <Button onClick={handleCreate} className="bg-[#D4AF37] text-[#003D82] hover:bg-[#b5952f] font-bold shadow-md">
                    <Plus className="w-4 h-4 mr-2" /> Add Course
                </Button>
            </div>

            <div className="relative w-full max-w-sm">
                <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                <Input 
                    placeholder="Search courses..." 
                    className="pl-9 bg-white border-gray-200" 
                    value={search} 
                    onChange={e => setSearch(e.target.value)} 
                />
            </div>

            <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-gray-50 hover:bg-gray-50">
                            <TableHead className="font-bold text-[#003D82] w-20">Order</TableHead>
                            <TableHead className="font-bold text-[#003D82]">Course Name</TableHead>
                            <TableHead className="font-bold text-[#003D82] w-1/3">Description</TableHead>
                            <TableHead className="font-bold text-[#003D82]">Category</TableHead>
                            <TableHead className="font-bold text-[#003D82]">Price</TableHead>
                            <TableHead className="text-right font-bold text-[#003D82]">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {loading ? (
                            <TableRow>
                                <TableCell colSpan={5} className="h-32 text-center">
                                    <div className="flex justify-center items-center gap-2 text-gray-400">
                                        <Loader2 className="w-6 h-6 animate-spin" /> Loading courses...
                                    </div>
                                </TableCell>
                            </TableRow>
                        ) : filteredCourses.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={5} className="h-32 text-center text-gray-500">
                                    {search ? "No courses found matching your search." : "No courses available. Create one to get started."}
                                </TableCell>
                            </TableRow>
                        ) : (
                            filteredCourses.map((course, index) => (
                                <TableRow key={course.id} className="hover:bg-blue-50/50 transition-colors">
                                    <TableCell>
                                        <div className="flex flex-col gap-1">
                                            <Button variant="ghost" size="icon" className="h-6 w-6" disabled={index === 0} onClick={() => moveCourseToEdge(index, 'first')} title="Move to first">
                                                <ChevronsUp className="w-4 h-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="h-6 w-6" disabled={index === 0} onClick={() => moveCourse(index, -1)} title="Move up">
                                                <ChevronUp className="w-4 h-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="h-6 w-6" disabled={index === filteredCourses.length - 1} onClick={() => moveCourse(index, 1)} title="Move down">
                                                <ChevronDown className="w-4 h-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="h-6 w-6" disabled={index === filteredCourses.length - 1} onClick={() => moveCourseToEdge(index, 'last')} title="Move to last">
                                                <ChevronsDown className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                    <TableCell className="font-medium text-gray-900">{course.name}</TableCell>
                                    <TableCell className="text-gray-600 truncate max-w-md" title={course.description}>
                                        {course.description}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{course.category || 'General'}</Badge>
                                    </TableCell>
                                    <TableCell className="font-semibold text-green-700">
                                        ${Number(course.price).toLocaleString()}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button 
                                                variant="outline" 
                                                size="sm" 
                                                onClick={() => handleViewFeedback(course)}
                                                className="text-gray-600"
                                            >
                                                <MessageCircle className="w-4 h-4 mr-2" /> Feedback
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => handleEdit(course)} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-800 hover:bg-blue-100">
                                                <Edit className="w-4 h-4" />
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => setDeleteId(course.id)} className="h-8 w-8 p-0 text-red-600 hover:text-red-800 hover:bg-red-100">
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Create Dialog */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent className="max-w-md">
                    <CourseFormModal 
                        course={null} 
                        onSuccess={() => { setIsCreateModalOpen(false); loadCourses(); }} 
                        onClose={() => setIsCreateModalOpen(false)}
                    />
                </DialogContent>
            </Dialog>

            {/* Edit Dialog */}
            <EditCourseForm 
                course={editingCourse}
                isOpen={isEditModalOpen}
                onClose={() => setIsEditModalOpen(false)}
                onSuccess={loadCourses}
            />

            {/* Feedback View Dialog */}
            <Dialog open={feedbackModalOpen} onOpenChange={setFeedbackModalOpen}>
                <DialogContent className="max-w-3xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Feedback for {selectedCourseName}</DialogTitle>
                    </DialogHeader>
                    
                    {loadingFeedback ? (
                        <div className="flex justify-center py-12"><Loader2 className="w-8 h-8 animate-spin text-gray-400" /></div>
                    ) : selectedCourseFeedback.length === 0 ? (
                        <div className="text-center py-12 text-gray-500">No feedback submitted for this course yet.</div>
                    ) : (
                        <div className="space-y-4 mt-4">
                             {selectedCourseFeedback.map(fb => (
                                <div key={fb.id} className="border p-4 rounded-lg bg-gray-50 flex justify-between gap-4">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-semibold text-gray-800">{fb.student_name}</span>
                                            <span className="text-xs text-gray-400">• {new Date(fb.created_at).toLocaleDateString()}</span>
                                        </div>
                                        <StarRating rating={fb.rating} readOnly size={14} />
                                        <p className="text-sm text-gray-600 mt-2">{fb.feedback_text || <span className="italic text-gray-400">No written feedback</span>}</p>
                                    </div>
                                    <Button 
                                        variant="ghost" 
                                        size="sm" 
                                        className="text-red-500 hover:bg-red-100 h-8 w-8 p-0 self-start"
                                        onClick={() => handleDeleteFeedback(fb.id)}
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                </div>
                             ))}
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation */}
            <AlertDialog open={!!deleteId} onOpenChange={(open) => !open && setDeleteId(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle className="flex items-center gap-2 text-red-600">
                            <AlertCircle className="w-5 h-5" /> Delete Course
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete this course? This will remove it from future registrations. Existing registrations will not be affected.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={isDeleting}>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDelete} className="bg-red-600 hover:bg-red-700" disabled={isDeleting}>
                            {isDeleting ? "Deleting..." : "Delete"}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
};

export default AdminCoursesPage;