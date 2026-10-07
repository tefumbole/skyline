import React from 'react';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Eye, Trash2, Power, PowerOff } from 'lucide-react';
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

const TemplateList = ({ templates, onPreview, onToggleStatus, onDelete }) => {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {templates.map((template) => (
        <Card key={template.id} className="overflow-hidden shadow-sm hover:shadow-md transition-shadow">
          <div className="relative h-48 bg-gray-100 group">
            <img 
              src={template.preview_thumbnail_url || template.background_image_url} 
              alt={template.name} 
              className="w-full h-full object-cover"
            />
            <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
              <Button variant="secondary" size="sm" onClick={() => onPreview(template)} className="mr-2">
                <Eye className="w-4 h-4 mr-2" /> Preview
              </Button>
            </div>
            <div className="absolute top-2 right-2 flex gap-2">
              <Badge variant={template.status === 'active' ? "default" : "secondary"} className="shadow">
                {template.status === 'active' ? 'Active' : 'Inactive'}
              </Badge>
            </div>
          </div>
          <CardContent className="p-4">
            <h3 className="font-bold text-lg text-gray-900 truncate" title={template.name}>
              {template.name}
            </h3>
            <div className="flex items-center justify-between mt-1 mb-2">
              <span className="text-xs font-medium text-[#003D82] bg-blue-50 px-2 py-0.5 rounded-full">
                {template.category || 'General'}
              </span>
            </div>
            <p className="text-sm text-gray-500 line-clamp-2 min-h-[40px]">
              {template.description || 'No description provided.'}
            </p>
          </CardContent>
          <CardFooter className="p-4 pt-0 border-t bg-gray-50 flex justify-between gap-2 mt-auto">
            <Button 
              variant="outline" 
              size="sm" 
              className="flex-1"
              onClick={() => onToggleStatus(template)}
            >
              {template.status === 'active' ? (
                <><PowerOff className="w-3.5 h-3.5 mr-2 text-gray-500" /> Deactivate</>
              ) : (
                <><Power className="w-3.5 h-3.5 mr-2 text-green-600" /> Activate</>
              )}
            </Button>
            
            <AlertDialog>
              <AlertDialogTrigger asChild>
                <Button variant="outline" size="sm" className="text-red-600 hover:text-red-700 hover:bg-red-50 flex-1">
                  <Trash2 className="w-3.5 h-3.5 mr-2" /> Delete
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Delete Template?</AlertDialogTitle>
                  <AlertDialogDescription>
                    Are you sure you want to delete "{template.name}"? This action cannot be undone and will remove the image from storage.
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>Cancel</AlertDialogCancel>
                  <AlertDialogAction onClick={() => onDelete(template)} className="bg-red-600 text-white hover:bg-red-700">
                    Yes, delete it
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </CardFooter>
        </Card>
      ))}
    </div>
  );
};

export default TemplateList;