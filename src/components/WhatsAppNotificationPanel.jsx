import React, { useState, useEffect } from 'react';
import { getWhatsAppLogs } from '@/services/whatsappLogService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Search, MessageSquare, CheckCircle, XCircle } from 'lucide-react';
import { motion } from 'framer-motion';
import WhatsAppRetryHandler from './WhatsAppRetryHandler';

const WhatsAppNotificationPanel = () => {
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filterType, setFilterType] = useState('All');
    const [searchTerm, setSearchTerm] = useState('');

    const fetchLogs = async () => {
        setLoading(true);
        const { data } = await getWhatsAppLogs();
        setLogs(data || []);
        setLoading(false);
    };

    useEffect(() => {
        fetchLogs();
        // Poll every 30s
        const interval = setInterval(fetchLogs, 30000);
        return () => clearInterval(interval);
    }, []);

    const filteredLogs = logs.filter(log => {
        const matchesType = filterType === 'All' || log.message_type === filterType.toLowerCase();
        const matchesSearch = log.recipient_phone.includes(searchTerm);
        return matchesType && matchesSearch;
    });

    const stats = {
        total: logs.length,
        success: logs.filter(l => l.status === 'success').length,
        failed: logs.filter(l => l.status === 'failed').length
    };
    
    const successRate = stats.total > 0 ? Math.round((stats.success / stats.total) * 100) : 0;

    return (
        <div className="space-y-6">
            {/* Stats Overview */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-sm text-gray-500">Total Sent</CardTitle></CardHeader>
                    <CardContent><div className="text-2xl font-bold">{stats.total}</div></CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-sm text-gray-500">Failed</CardTitle></CardHeader>
                    <CardContent><div className="text-2xl font-bold text-red-600">{stats.failed}</div></CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-sm text-gray-500">Success Rate</CardTitle></CardHeader>
                    <CardContent><div className="text-2xl font-bold text-green-600">{successRate}%</div></CardContent>
                </Card>
            </div>

            {/* Filters */}
            <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
                <div className="relative w-full md:w-64">
                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-gray-500" />
                    <Input 
                        placeholder="Search phone number..." 
                        className="pl-8"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                </div>
                <select 
                    className="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm w-full md:w-auto"
                    value={filterType}
                    onChange={(e) => setFilterType(e.target.value)}
                >
                    <option value="All">All Types</option>
                    <option value="master_class">Master Class</option>
                    <option value="shareholder">Shareholder</option>
                    <option value="event">Event</option>
                </select>
            </div>

            {/* Logs List */}
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left">
                            <thead className="bg-gray-50 border-b text-xs uppercase text-gray-700">
                                <tr>
                                    <th className="px-6 py-3">Recipient</th>
                                    <th className="px-6 py-3">Type</th>
                                    <th className="px-6 py-3">Status</th>
                                    <th className="px-6 py-3">Time</th>
                                    <th className="px-6 py-3">Error</th>
                                    <th className="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {loading ? (
                                    <tr><td colSpan="6" className="p-8 text-center">Loading logs...</td></tr>
                                ) : filteredLogs.length === 0 ? (
                                    <tr><td colSpan="6" className="p-8 text-center text-gray-500">No logs found.</td></tr>
                                ) : (
                                    filteredLogs.map((log) => (
                                        <motion.tr 
                                            key={log.id} 
                                            initial={{ opacity: 0 }}
                                            animate={{ opacity: 1 }}
                                            className="border-b hover:bg-gray-50"
                                        >
                                            <td className="px-6 py-4 font-mono">{log.recipient_phone}</td>
                                            <td className="px-6 py-4">
                                                <span className="capitalize">{log.message_type.replace('_', ' ')}</span>
                                            </td>
                                            <td className="px-6 py-4">
                                                {log.status === 'success' ? (
                                                    <Badge className="bg-green-100 text-green-800 hover:bg-green-200 border-none">
                                                        <CheckCircle className="w-3 h-3 mr-1" /> Success
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="destructive" className="bg-red-100 text-red-800 hover:bg-red-200 border-none">
                                                        <XCircle className="w-3 h-3 mr-1" /> Failed
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-gray-500">
                                                {new Date(log.sent_at).toLocaleString()}
                                            </td>
                                            <td className="px-6 py-4 text-red-500 text-xs max-w-xs truncate" title={log.error_message}>
                                                {log.error_message || '-'}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                {log.status === 'failed' && (
                                                    <WhatsAppRetryHandler logEntry={log} onRetrySuccess={fetchLogs} />
                                                )}
                                            </td>
                                        </motion.tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default WhatsAppNotificationPanel;