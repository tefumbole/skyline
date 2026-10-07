import React from 'react';
import { Link } from 'react-router-dom';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { FileBarChart, Clock, Users, Calendar, DollarSign, Activity } from 'lucide-react';

const ReportCard = ({ title, description, icon: Icon, link, color }) => (
    <Link to={link}>
        <Card className="h-full hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 cursor-pointer group border-l-4" style={{ borderLeftColor: color }}>
            <CardHeader>
                <div className="flex justify-between items-start">
                    <div className={`p-3 rounded-lg bg-gray-50 group-hover:bg-white transition-colors`}>
                        <Icon className="w-8 h-8" style={{ color }} />
                    </div>
                </div>
                <CardTitle className="mt-4 text-xl group-hover:text-[#003D82] transition-colors">{title}</CardTitle>
                <CardDescription className="mt-2">{description}</CardDescription>
            </CardHeader>
        </Card>
    </Link>
);

const AdminReportsPage = () => {
    return (
        <div className="space-y-8 animate-in fade-in duration-500 py-8">
            <div className="text-center md:text-left">
                <h1 className="text-4xl font-bold text-[#003D82] mb-2">Reports & Analytics</h1>
                <p className="text-gray-500 max-w-2xl">Access comprehensive data regarding financial performance, workforce productivity, and system usage.</p>
            </div>

            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <ReportCard 
                    title="Financial Reports" 
                    description="View revenue summaries, outstanding payments, and transaction history."
                    icon={DollarSign}
                    color="#10B981" // Green
                    link="/admin/payments"
                />
                
                <ReportCard 
                    title="Time Sheet Reports" 
                    description="Analyze employee attendance, hours worked, overtime, and productivity trends."
                    icon={Clock}
                    color="#D4AF37" // Gold
                    link="/admin/timesheets/reports"
                />

                <ReportCard 
                    title="Activity Logs" 
                    description="Breakdown of tasks performed, categorized by project and type."
                    icon={Activity}
                    color="#3B82F6" // Blue
                    link="/admin/timesheets"
                />

                <ReportCard 
                    title="User Statistics" 
                    description="User registration growth, role distribution, and active session data."
                    icon={Users}
                    color="#8B5CF6" // Purple
                    link="/admin/users"
                />

                <ReportCard 
                    title="Event Analytics" 
                    description="Registration numbers, attendance rates, and event popularity metrics."
                    icon={Calendar}
                    color="#F59E0B" // Orange
                    link="/admin/events"
                />

                <ReportCard 
                    title="System History" 
                    description="Audit logs of all administrative actions and system modifications."
                    icon={FileBarChart}
                    color="#64748B" // Gray
                    link="/admin/history"
                />
            </div>
        </div>
    );
};

export default AdminReportsPage;