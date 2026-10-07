import React, { useState, useEffect } from 'react';
import { getAllFeedback, deleteFeedback } from '@/services/feedbackService';
import { getTrainingCourses } from '@/services/coursesService';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Search, Trash2, MessageSquare, Filter } from 'lucide-react';
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
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";

const AdminFeedbackPage = () => {
    const [feedback, setFeedback] = useState([]);
    const [courses, setCourses] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [courseFilter, setCourseFilter] = useState('all');
    const [ratingFilter, setRatingFilter] = useState('all');
    const { toast } = useToast();

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        try {
            const [feedbackData, coursesData] = await Promise.all([
                getAllFeedback(),
                getTrainingCourses()
            ]);
            setFeedback(feedbackData);
            setCourses(coursesData);
        } catch (error) {
            toast({ title: "Error", description: "Failed to load feedback data.", variant: "destructive" });
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id) => {
        try {
            await deleteFeedback(id);
            toast({ title: "Deleted", description: "Feedback removed successfully." });
            setFeedback(prev => prev.filter(item => item.id !== id));
        } catch (error) {
            toast({ title: "Error", description: "Failed to delete feedback.", variant: "destructive" });
        }
    };

    const filteredFeedback = feedback.filter(item => {
        const matchesSearch = item.student_name?.toLowerCase().includes(search.toLowerCase()) || 
                              item.feedback_text?.toLowerCase().includes(search.toLowerCase());
        const matchesCourse = courseFilter === 'all' || item.course_id === courseFilter;
        const matchesRating = ratingFilter === 'all' || Math.round(item.rating) === parseInt(ratingFilter);
        
        return matchesSearch && matchesCourse && matchesRating;
    });

    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b pb-4">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82] flex items-center gap-2">
                        <MessageSquare className="w-8 h-8" /> Course Feedback
                    </h1>
                    <p className="text-gray-500">Review and manage student feedback.</p>
                </div>
            </div>

            <div className="flex flex-col md:flex-row gap-4 items-center bg-white p-4 rounded-lg border shadow-sm">
                <div className="relative w-full md:w-96">
                    <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                    <Input 
                        placeholder="Search student or feedback..." 
                        className="pl-9" 
                        value={search} 
                        onChange={e => setSearch(e.target.value)} 
                    />
                </div>
                
                <Select value={courseFilter} onValueChange={setCourseFilter}>
                    <SelectTrigger className="w-full md:w-[250px]">
                        <SelectValue placeholder="Filter by Course" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Courses</SelectItem>
                        {courses.map(course => (
                            <SelectItem key={course.id} value={course.id}>{course.name}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Select value={ratingFilter} onValueChange={setRatingFilter}>
                    <SelectTrigger className="w-full md:w-[180px]">
                        <SelectValue placeholder="Filter by Rating" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Ratings</SelectItem>
                        <SelectItem value="5">5 Stars</SelectItem>
                        <SelectItem value="4">4 Stars</SelectItem>
                        <SelectItem value="3">3 Stars</SelectItem>
                        <SelectItem value="2">2 Stars</SelectItem>
                        <SelectItem value="1">1 Star</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div className="bg-white rounded-lg border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-gray-50">
                            <TableHead>Date</TableHead>
                            <TableHead>Course</TableHead>
                            <TableHead>Student</TableHead>
                            <TableHead>Rating</TableHead>
                            <TableHead className="w-1/3">Feedback</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {loading ? (
                             <TableRow><TableCell colSpan={6} className="h-32 text-center"><Loader2 className="w-6 h-6 animate-spin mx-auto" /></TableCell></TableRow>
                        ) : filteredFeedback.length === 0 ? (
                             <TableRow><TableCell colSpan={6} className="h-32 text-center text-gray-500">No feedback found matching criteria.</TableCell></TableRow>
                        ) : (
                            filteredFeedback.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="text-sm text-gray-500">{new Date(item.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell className="font-medium">{item.courses?.name || 'Unknown'}</TableCell>
                                    <TableCell>{item.student_name}</TableCell>
                                    <TableCell>
                                        <StarRating rating={item.rating} readOnly size={16} />
                                    </TableCell>
                                    <TableCell className="text-sm text-gray-600 max-w-xs truncate" title={item.feedback_text}>
                                        {item.feedback_text || '-'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <AlertDialog>
                                            <AlertDialogTrigger asChild>
                                                <Button variant="ghost" size="sm" className="text-red-500 hover:text-red-700 hover:bg-red-50">
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Delete Feedback?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        This will permanently remove this feedback record. This action cannot be undone.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction onClick={() => handleDelete(item.id)} className="bg-red-600">Delete</AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
};

export default AdminFeedbackPage;