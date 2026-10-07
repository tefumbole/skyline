import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { getApplicationStats } from '@/services/applicationStatusService';
import { 
    Users, 
    FileText, 
    CheckCircle, 
    XCircle, 
    QrCode, 
    Briefcase,
    TrendingUp,
    Clock
} from 'lucide-react';

const AdminJobApplicationDashboard = () => {
  const [stats, setStats] = useState({ total: 0, pending: 0, rejected: 0, shortlisted: 0 });

  useEffect(() => {
      getApplicationStats().then(setStats).catch(console.error);
  }, []);

  const statCards = [
      { title: 'Total Applications', value: stats.total, icon: FileText, color: 'text-blue-600', bg: 'bg-blue-100' },
      { title: 'Pending Review', value: stats.pending, icon: Clock, color: 'text-yellow-600', bg: 'bg-yellow-100' },
      { title: 'Shortlisted', value: stats.shortlisted, icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-100' },
      { title: 'Rejected', value: stats.rejected, icon: XCircle, color: 'text-red-600', bg: 'bg-red-100' },
  ];

  return (
    <div className="p-6 space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82]">Recruitment Dashboard</h1>
        <p className="text-gray-500">Overview of job applications and hiring pipeline.</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {statCards.map((stat, idx) => (
              <Card key={idx} className="border-none shadow-sm hover:shadow-md transition-shadow">
                  <CardContent className="p-6 flex items-center justify-between">
                      <div>
                          <p className="text-sm font-medium text-gray-500">{stat.title}</p>
                          <h3 className={`text-3xl font-bold ${stat.color} mt-1`}>{stat.value}</h3>
                      </div>
                      <div className={`p-3 rounded-full ${stat.bg}`}>
                          <stat.icon className={`w-6 h-6 ${stat.color}`} />
                      </div>
                  </CardContent>
              </Card>
          ))}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <Card className="hover:shadow-lg transition-shadow border-t-4 border-t-blue-600">
              <CardHeader>
                  <CardTitle className="text-lg flex items-center gap-2">
                      <Briefcase className="w-5 h-5 text-blue-600" /> Quick Actions
                  </CardTitle>
              </CardHeader>
              <CardContent className="grid grid-cols-2 gap-4">
                  <Link to="/admin/applications" className="p-4 bg-gray-50 hover:bg-blue-50 rounded-lg border text-center transition-colors">
                      <FileText className="w-8 h-8 mx-auto text-blue-600 mb-2" />
                      <span className="font-semibold text-gray-700">All Applications</span>
                  </Link>
                  <Link to="/admin/jobs" className="p-4 bg-gray-50 hover:bg-blue-50 rounded-lg border text-center transition-colors">
                      <Briefcase className="w-8 h-8 mx-auto text-blue-600 mb-2" />
                      <span className="font-semibold text-gray-700">Manage Jobs</span>
                  </Link>
                  <Link to="/admin/applications/rejected" className="p-4 bg-gray-50 hover:bg-red-50 rounded-lg border text-center transition-colors">
                      <XCircle className="w-8 h-8 mx-auto text-red-600 mb-2" />
                      <span className="font-semibold text-gray-700">Rejected List</span>
                  </Link>
                  <Link to="/admin/applications/shortlisted" className="p-4 bg-gray-50 hover:bg-green-50 rounded-lg border text-center transition-colors">
                      <CheckCircle className="w-8 h-8 mx-auto text-green-600 mb-2" />
                      <span className="font-semibold text-gray-700">Shortlisted</span>
                  </Link>
              </CardContent>
          </Card>

          <Card className="hover:shadow-lg transition-shadow border-t-4 border-t-[#D4AF37]">
              <CardHeader>
                  <CardTitle className="text-lg flex items-center gap-2">
                      <QrCode className="w-5 h-5 text-[#D4AF37]" /> Tools
                  </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                  <Link to="/qr-scanner" className="flex items-center p-4 bg-gray-50 rounded-lg border hover:bg-gray-100 transition-colors">
                      <div className="bg-[#D4AF37]/20 p-3 rounded-full mr-4">
                          <QrCode className="w-6 h-6 text-[#003D82]" />
                      </div>
                      <div>
                          <h4 className="font-semibold text-gray-900">QR Code Scanner</h4>
                          <p className="text-sm text-gray-500">Scan candidate application codes to view status.</p>
                      </div>
                  </Link>
                  <div className="p-4 bg-blue-50 rounded-lg border border-blue-100">
                      <h4 className="font-semibold text-blue-900 flex items-center gap-2">
                          <TrendingUp className="w-4 h-4" /> Analytics Insight
                      </h4>
                      <p className="text-sm text-blue-700 mt-1">
                          Most applications are coming for Engineering roles this month.
                      </p>
                  </div>
              </CardContent>
          </Card>
      </div>
    </div>
  );
};

export default AdminJobApplicationDashboard;