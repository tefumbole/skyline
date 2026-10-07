import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useToast } from '@/components/ui/use-toast';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from '@/components/ui/badge';
import { Loader2, DownloadCloud, UploadCloud, Code2, Trash2, FileCode, AlertTriangle } from 'lucide-react';
import { createCodeBackup, downloadBackup, logCodeBackup, getCodeBackupHistory, deleteCodeBackupRecord } from '@/services/CodeBackupService';
import { validateZipFile, restoreCodeFiles } from '@/services/CodeRestoreService';
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

const CodeBackupTab = () => {
  const [loading, setLoading] = useState(false);
  const [historyLoading, setHistoryLoading] = useState(true);
  const [backups, setBackups] = useState([]);
  const [restoreFile, setRestoreFile] = useState(null);
  const { toast } = useToast();

  useEffect(() => {
    loadHistory();
  }, []);

  const loadHistory = async () => {
    try {
      setHistoryLoading(true);
      const data = await getCodeBackupHistory();
      setBackups(data || []);
    } catch (error) {
      console.error("Failed to load code backup history", error);
    } finally {
      setHistoryLoading(false);
    }
  };

  const handleCreateBackup = async () => {
    setLoading(true);
    try {
      const { blob, fileName, sizeStr, fileCount } = await createCodeBackup();
      downloadBackup(blob, fileName);
      
      // Log to database
      await logCodeBackup(fileName, sizeStr);
      
      toast({
        title: "Code Backup Created",
        description: `Successfully backed up ${fileCount} files (${sizeStr}).`,
        className: "bg-green-50 border-green-200 text-green-900"
      });
      
      loadHistory();
    } catch (error) {
      toast({
        title: "Backup Failed",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const handleRestore = async () => {
    if (!restoreFile) return;
    
    setLoading(true);
    try {
      const { valid, zip } = await validateZipFile(restoreFile);
      if(valid) {
          // This is a simulation in this environment as we cannot write to disk
          const result = await restoreCodeFiles(zip);
          toast({
              title: "Code Restoration Simulated",
              description: `Successfully processed ${result.fileCount} files. In a real environment, files would be overwritten.`,
              className: "bg-blue-50 border-blue-200 text-blue-900"
          });
      }
      setRestoreFile(null);
    } catch (error) {
       toast({
        title: "Restore Failed",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
      try {
          await deleteCodeBackupRecord(id);
          toast({ title: "Record Deleted", description: "Backup history record removed." });
          loadHistory();
      } catch (error) {
          toast({ title: "Delete Failed", variant: "destructive" });
      }
  };

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Create Code Backup */}
        <Card className="border-t-4 border-t-purple-600 shadow-md">
          <CardHeader className="pb-4">
            <CardTitle className="text-purple-800 flex items-center gap-2">
               <DownloadCloud className="w-5 h-5" /> Backup Source Code
            </CardTitle>
            <CardDescription>
              Create a ZIP archive of critical source files (JS, JSX, CSS, Config).
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="bg-purple-50 p-4 rounded-lg flex flex-col items-center justify-center gap-3 border border-purple-100">
               <Code2 className="w-10 h-10 text-purple-400" />
               <p className="text-xs text-center text-purple-700 max-w-xs">
                 Backs up: src/ components, pages, services, config files.
               </p>
               <Button onClick={handleCreateBackup} disabled={loading} className="w-full bg-purple-600 hover:bg-purple-700">
                 {loading ? <Loader2 className="animate-spin w-4 h-4 mr-2" /> : <DownloadCloud className="w-4 h-4 mr-2" />}
                 Download Code ZIP
               </Button>
            </div>
          </CardContent>
        </Card>

        {/* Restore Code */}
        <Card className="border-t-4 border-t-orange-500 shadow-md">
           <CardHeader className="pb-4">
            <CardTitle className="text-orange-700 flex items-center gap-2">
               <UploadCloud className="w-5 h-5" /> Restore Source Code
            </CardTitle>
            <CardDescription>
              Upload a previously created ZIP file to restore code files.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
                <Input 
                    type="file" 
                    accept=".zip"
                    onChange={(e) => setRestoreFile(e.target.files[0])}
                    className="cursor-pointer"
                />
                
                {restoreFile && (
                    <div className="flex items-center gap-2 text-xs text-orange-800 bg-orange-50 p-2 rounded border border-orange-100">
                        <AlertTriangle className="w-4 h-4" />
                        <span>Warning: This will overwrite existing files.</span>
                    </div>
                )}

                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button disabled={!restoreFile || loading} variant="outline" className="w-full border-orange-500 text-orange-600 hover:bg-orange-50">
                            {loading ? <Loader2 className="animate-spin w-4 h-4 mr-2" /> : <UploadCloud className="w-4 h-4 mr-2" />}
                            Restore from ZIP
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Confirm Code Restoration</AlertDialogTitle>
                            <AlertDialogDescription>
                                Are you sure you want to restore code files from <strong>{restoreFile?.name}</strong>?
                                This process will overwrite current source files. Ensure you have a current backup before proceeding.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction onClick={handleRestore} className="bg-orange-600 hover:bg-orange-700">Proceed</AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
          <CardHeader>
              <CardTitle className="flex items-center gap-2 text-sm uppercase tracking-wider text-gray-500">
                  <FileCode className="w-4 h-4" /> Code Backup History
              </CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Date & Time</TableHead>
                        <TableHead>Filename</TableHead>
                        <TableHead>Size</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {historyLoading ? (
                        <TableRow>
                             <TableCell colSpan={4} className="text-center py-6">
                                 <Loader2 className="w-6 h-6 animate-spin mx-auto text-gray-400" />
                             </TableCell>
                        </TableRow>
                    ) : backups.length === 0 ? (
                        <TableRow>
                             <TableCell colSpan={4} className="text-center py-6 text-gray-400 text-sm">
                                 No code backups recorded.
                             </TableCell>
                        </TableRow>
                    ) : (
                        backups.map(backup => (
                            <TableRow key={backup.id}>
                                <TableCell className="text-sm">{new Date(backup.created_at).toLocaleString()}</TableCell>
                                <TableCell className="font-mono text-xs">{backup.name}</TableCell>
                                <TableCell><Badge variant="outline">{backup.size}</Badge></TableCell>
                                <TableCell className="text-right">
                                     <Button size="sm" variant="ghost" onClick={() => handleDelete(backup.id)} className="text-red-500 hover:bg-red-50 h-8 w-8 p-0">
                                         <Trash2 className="w-4 h-4" />
                                     </Button>
                                </TableCell>
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>
          </CardContent>
      </Card>
    </div>
  );
};

export default CodeBackupTab;