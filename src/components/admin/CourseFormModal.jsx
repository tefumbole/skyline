import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/components/ui/use-toast';
import { createCourse, updateCourse } from '@/services/coursesService';
import { Loader2, Save, X } from 'lucide-react';

const CourseFormModal = ({ course, onSuccess, onClose }) => {
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        price: '',
        category: 'Services'
    });
    const [loading, setLoading] = useState(false);
    const { toast } = useToast();

    useEffect(() => {
        if (course) {
            setFormData({
                name: course.name || '',
                description: course.description || '',
                price: course.price || '',
                category: course.category || 'Services'
            });
        }
    }, [course]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!formData.name.trim()) {
            toast({ title: "Required Field", description: "Course name is required.", variant: "destructive" });
            return;
        }

        if (!formData.price || isNaN(formData.price)) {
            toast({ title: "Invalid Price", description: "Please enter a valid numeric price.", variant: "destructive" });
            return;
        }

        setLoading(true);

        try {
            if (course?.id) {
                await updateCourse(course.id, formData);
                toast({ title: "Success", description: "Course updated successfully." });
            } else {
                await createCourse(formData);
                toast({ title: "Success", description: "New course created successfully." });
            }
            onSuccess();
        } catch (error) {
            console.error(error);
            toast({ 
                title: "Error", 
                description: error.message || "Failed to save course.", 
                variant: "destructive" 
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center border-b pb-2 mb-4">
                <h2 className="text-xl font-bold text-[#003D82]">
                    {course ? 'Edit Course' : 'Add New Course'}
                </h2>
                <Button variant="ghost" size="sm" onClick={onClose} className="h-8 w-8 p-0 rounded-full">
                    <X className="w-4 h-4" />
                </Button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="name">Course Name <span className="text-red-500">*</span></Label>
                    <Input
                        id="name"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        placeholder="e.g. IT Consultancy"
                        required
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="price">Price (USD) <span className="text-red-500">*</span></Label>
                    <Input
                        id="price"
                        name="price"
                        type="number"
                        min="0"
                        step="0.01"
                        value={formData.price}
                        onChange={handleChange}
                        placeholder="300"
                        required
                    />
                </div>
                
                 <div className="space-y-2">
                    <Label htmlFor="category">Category</Label>
                    <Input
                        id="category"
                        name="category"
                        value={formData.category}
                        onChange={handleChange}
                        placeholder="e.g. Services, Networking, Audio/Visual"
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="description">Description <span className="text-red-500">*</span></Label>
                    <Textarea
                        id="description"
                        name="description"
                        value={formData.description}
                        onChange={handleChange}
                        placeholder="Brief description of the course or service..."
                        className="h-24 resize-none"
                        required
                    />
                </div>

                <div className="pt-4 flex justify-end gap-3">
                    <Button type="button" variant="outline" onClick={onClose} disabled={loading}>
                        Cancel
                    </Button>
                    <Button type="submit" disabled={loading} className="bg-[#003D82] hover:bg-[#002d62]">
                        {loading ? (
                            <><Loader2 className="w-4 h-4 mr-2 animate-spin" /> Saving...</>
                        ) : (
                            <><Save className="w-4 h-4 mr-2" /> {course ? 'Update Course' : 'Create Course'}</>
                        )}
                    </Button>
                </div>
            </form>
        </div>
    );
};

export default CourseFormModal;