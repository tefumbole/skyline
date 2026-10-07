import React, { useState, useEffect } from 'react';
import { useToast } from '@/components/ui/use-toast';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { getTemplates, deleteTemplate } from '@/services/templateService';
import { format } from 'date-fns';
import { 
  Search, Loader2, FileText, Trash2, Edit
} from 'lucide-react';

const TemplateManagementPage = () => {
  const { toast } = useToast();
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    loadTemplates();
  }, []);

  const loadTemplates = async () => {
    setLoading(true);
    const result = await getTemplates();
    if (result.success) {
      setTemplates(result.data);
    } else {
      toast({
        title: "Error loading templates",
        description: result.error,
        variant: "destructive"
      });
    }
    setLoading(false);
  };

  const handleDelete = async (id) => {
    if (!confirm("Are you sure you want to delete this template?")) return;
    
    const result = await deleteTemplate(id);
    if (result.success) {
      toast({ title: "Template deleted successfully" });
      loadTemplates();
    } else {
      toast({
        title: "Failed to delete template",
        description: result.error,
        variant: "destructive"
      });
    }
  };

  const filteredTemplates = templates.filter(t => 
    t.name.toLowerCase().includes(search.toLowerCase()) || 
    t.subject?.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="max-w-7xl mx-auto space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Message Templates</h1>
          <p className="text-gray-500">Manage your saved communication templates.</p>
        </div>
      </div>

      <Card>
        <CardHeader className="pb-4 border-b">
          <div className="relative max-w-md">
            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
            <Input
              placeholder="Search templates..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-9 bg-white"
            />
          </div>
        </CardHeader>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex justify-center items-center py-16">
              <Loader2 className="w-8 h-8 animate-spin text-[#003D82]" />
            </div>
          ) : filteredTemplates.length === 0 ? (
            <div className="text-center py-16 text-gray-500">
              <FileText className="w-12 h-12 mx-auto text-gray-300 mb-3" />
              <p>No templates found.</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader className="bg-gray-50">
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Subject</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead>Created At</TableHead>
                    <TableHead className="text-right">Action</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredTemplates.map((template) => (
                    <TableRow key={template.id} className="hover:bg-gray-50/50">
                      <TableCell className="font-medium">{template.name}</TableCell>
                      <TableCell className="truncate max-w-[250px]">{template.subject || '-'}</TableCell>
                      <TableCell>{template.category || 'General'}</TableCell>
                      <TableCell className="text-sm text-gray-500">
                        {format(new Date(template.created_at), 'MMM dd, yyyy')}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-2">
                          <Button variant="ghost" size="sm" onClick={() => toast({ title: "Edit not implemented", description: "Use Compose Message page to overwrite templates for now." })}>
                            <Edit className="w-4 h-4 text-blue-500" />
                          </Button>
                          <Button variant="ghost" size="sm" onClick={() => handleDelete(template.id)}>
                            <Trash2 className="w-4 h-4 text-red-500" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default TemplateManagementPage;