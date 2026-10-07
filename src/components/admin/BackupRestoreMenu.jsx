import React from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Database, FolderArchive, Code, UploadCloud, Clock } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const BackupRestoreMenu = ({ onBackupDB, onBackupStorage, onBackupConfig, onRestore }) => {
  return (
    <div className="w-full">
      <Tabs defaultValue="backup" className="w-full">
        <TabsList className="grid w-full grid-cols-3 mb-8">
          <TabsTrigger value="backup">Backup Operations</TabsTrigger>
          <TabsTrigger value="restore">System Restore</TabsTrigger>
          <TabsTrigger value="history">Activity Log</TabsTrigger>
        </TabsList>

        <TabsContent value="backup" className="space-y-6">
           <div className="grid md:grid-cols-3 gap-6">
              <Card className="hover:border-blue-500 transition-colors cursor-pointer" onClick={onBackupDB}>
                  <CardHeader>
                      <Database className="w-10 h-10 text-blue-600 mb-2" />
                      <CardTitle>Database Backup</CardTitle>
                      <CardDescription>Export all table data as JSON</CardDescription>
                  </CardHeader>
                  <CardContent>
                      <p className="text-sm text-gray-500 mb-4">Includes shareholders, members, payments, and settings.</p>
                      <Button className="w-full bg-blue-600 hover:bg-blue-700">Export JSON</Button>
                  </CardContent>
              </Card>

              <Card className="hover:border-amber-500 transition-colors cursor-pointer" onClick={onBackupStorage}>
                  <CardHeader>
                      <FolderArchive className="w-10 h-10 text-amber-600 mb-2" />
                      <CardTitle>Storage Manifest</CardTitle>
                      <CardDescription>List all stored files</CardDescription>
                  </CardHeader>
                  <CardContent>
                      <p className="text-sm text-gray-500 mb-4">Generates a detailed report of all files in storage buckets.</p>
                      <Button variant="outline" className="w-full border-amber-200 text-amber-700 hover:bg-amber-50">Generate Report</Button>
                  </CardContent>
              </Card>

              <Card className="hover:border-purple-500 transition-colors cursor-pointer" onClick={onBackupConfig}>
                  <CardHeader>
                      <Code className="w-10 h-10 text-purple-600 mb-2" />
                      <CardTitle>Config Export</CardTitle>
                      <CardDescription>System configuration check</CardDescription>
                  </CardHeader>
                  <CardContent>
                      <p className="text-sm text-gray-500 mb-4">Export local settings and runtime configuration.</p>
                      <Button variant="outline" className="w-full border-purple-200 text-purple-700 hover:bg-purple-50">Export Config</Button>
                  </CardContent>
              </Card>
           </div>
           
           <div className="bg-blue-50 p-4 rounded-lg border border-blue-100 flex items-start gap-3">
               <Clock className="w-5 h-5 text-blue-600 mt-0.5" />
               <div>
                   <h4 className="font-bold text-blue-800">Automated Backups</h4>
                   <p className="text-sm text-blue-600">Supabase performs automatic daily backups of your database. These manual exports are for your local records.</p>
               </div>
           </div>
        </TabsContent>

        <TabsContent value="restore">
            <Card className="border-2 border-dashed border-gray-200">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <UploadCloud className="w-6 h-6" /> Restore Database
                    </CardTitle>
                    <CardDescription>Upload a previously exported JSON backup file to restore data.</CardDescription>
                </CardHeader>
                <CardContent className="flex flex-col items-center justify-center py-12">
                     <div className="bg-gray-100 p-6 rounded-full mb-4">
                         <Database className="w-12 h-12 text-gray-400" />
                     </div>
                     <p className="text-center text-gray-500 max-w-md mb-6">
                        Drag and drop your <strong>backup.json</strong> file here, or click to browse.
                        <br/><span className="text-xs text-red-500">Warning: This will attempt to merge/update existing records.</span>
                     </p>
                     <Button variant="secondary" onClick={onRestore}>Select Backup File</Button>
                </CardContent>
            </Card>
        </TabsContent>

        <TabsContent value="history">
            <Card>
                <CardHeader>
                    <CardTitle>Operation History</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="flex justify-between items-center p-3 hover:bg-gray-50 rounded border-b last:border-0">
                                <div className="flex items-center gap-3">
                                    <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">Backup</Badge>
                                    <div>
                                        <p className="font-medium">Database Export</p>
                                        <p className="text-xs text-gray-500">Oct {10 + i}, 2024 at 14:3{i} PM</p>
                                    </div>
                                </div>
                                <span className="text-xs font-mono text-gray-400">SUCCESS</span>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default BackupRestoreMenu;