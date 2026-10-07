import JSZip from 'jszip';
import { saveAs } from 'file-saver';
import { supabase } from '@/lib/customSupabaseClient';
import { formatBytes } from '@/utils/imageCompression';

// NOTE: In a frontend-only environment, we cannot automatically scan the file system.
// We must maintain a manifest of critical files to backup.
const CRITICAL_FILES_MANIFEST = [
  'package.json',
  'vite.config.js',
  'tailwind.config.js',
  'index.html',
  'src/main.jsx',
  'src/App.jsx',
  'src/index.css',
  'src/lib/customSupabaseClient.js',
  'src/lib/utils.js',
  'src/layouts/MainLayout.jsx',
  'src/layouts/AdminLayout.jsx',
  'src/components/Header.jsx',
  'src/components/Footer.jsx',
  'src/components/ProtectedRoute.jsx',
  'src/context/AuthContext.jsx',
  'src/context/PermissionContext.jsx',
  'src/services/membersService.js',
  'src/services/backupService.js',
  'src/utils/imageCompression.js'
];

/**
 * Simulates scanning source files by fetching them via HTTP (works in Dev environment)
 * In production builds, this might fail if source maps or raw files aren't served.
 */
export const scanSourceFiles = async () => {
    const files = [];
    const errors = [];

    // Attempt to fetch files from the manifest
    for (const filePath of CRITICAL_FILES_MANIFEST) {
        try {
            const response = await fetch(`/${filePath}`);
            if (response.ok) {
                const content = await response.text();
                // Filter out HTML responses (which usually means 404 fallback in SPA)
                if (!content.trim().startsWith('<!DOCTYPE html>')) {
                     files.push({ path: filePath, content });
                } else {
                    // Check if it's actually an HTML file we wanted
                    if(filePath.endsWith('.html')) {
                        files.push({ path: filePath, content });
                    }
                }
            }
        } catch (error) {
            errors.push(`Failed to fetch ${filePath}: ${error.message}`);
        }
    }
    
    return { files, errors };
};

/**
 * Creates a ZIP file of the provided code files
 */
export const createCodeBackup = async () => {
    try {
        const { files, errors } = await scanSourceFiles();
        
        if (files.length === 0) {
            throw new Error("No source files could be found to backup. This feature requires dev server access or a file manifest.");
        }

        const zip = new JSZip();
        
        // Add metadata file
        const metadata = {
            timestamp: new Date().toISOString(),
            fileCount: files.length,
            environment: import.meta.env.MODE,
            manifest: files.map(f => f.path)
        };
        zip.file('backup_metadata.json', JSON.stringify(metadata, null, 2));

        // Add source files
        files.forEach(file => {
            zip.file(file.path, file.content);
        });

        const blob = await zip.generateAsync({ type: 'blob' });
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const fileName = `code_backup_${timestamp}.zip`;
        const sizeStr = formatBytes(blob.size);

        return { blob, fileName, sizeStr, fileCount: files.length };
    } catch (error) {
        console.error("Code backup generation failed:", error);
        throw error;
    }
};

/**
 * Triggers browser download
 */
export const downloadBackup = (blob, fileName) => {
    saveAs(blob, fileName);
};

/**
 * Logs backup to Supabase
 */
export const logCodeBackup = async (name, size, fileUrl = null) => {
    try {
        const { error } = await supabase.from('code_backups').insert({
            name,
            size,
            file_url: fileUrl,
            // created_by is handled by Supabase auth context if enabled, 
            // otherwise RLS might need adjustment or trigger manually
        });
        if (error) throw error;
    } catch (error) {
        console.error("Failed to log code backup:", error);
    }
};

export const getCodeBackupHistory = async () => {
    const { data, error } = await supabase
        .from('code_backups')
        .select('*')
        .order('created_at', { ascending: false });
    
    if (error) throw error;
    return data;
};

export const deleteCodeBackupRecord = async (id) => {
    const { error } = await supabase.from('code_backups').delete().eq('id', id);
    if (error) throw error;
};