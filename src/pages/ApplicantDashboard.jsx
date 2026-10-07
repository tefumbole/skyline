import React from 'react';
import { useAuth } from '@/context/AuthContext';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Briefcase, Calendar, CheckCircle, LogOut } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

const ApplicantDashboard = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6 md:p-10">
      <div className="max-w-7xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <div>
            <h1 className="text-2xl font-bold text-[#003D82]">Candidate Portal</h1>
            <p className="text-gray-500">Applicant: {user?.user_metadata?.full_name || user?.email}</p>
          </div>
          <Button onClick={handleLogout} variant="outline" className="text-red-600 border-red-200 hover:bg-red-50">
            <LogOut className="w-4 h-4 mr-2" /> Logout
          </Button>
        </div>

        {/* Application Status */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
                <CardHeader>
                    <CardTitle>Active Applications</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="p-4 border rounded-lg bg-white shadow-sm border-l-4 border-l-[#D4AF37]">
                        <div className="flex justify-between items-start mb-2">
                            <h3 className="font-bold text-gray-900">Senior Frontend Developer</h3>
                            <span className="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded font-bold">Under Review</span>
                        </div>
                        <p className="text-sm text-gray-500 mb-4">Applied on Feb 10, 2026 • Ref: DEV-2026-001</p>
                        <div className="flex gap-2">
                            <Button size="sm" variant="outline">View Details</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Upcoming Interviews</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-col items-center justify-center py-8 text-center">
                        <Calendar className="w-12 h-12 text-gray-300 mb-2" />
                        <p className="text-gray-500 font-medium">No interviews scheduled yet.</p>
                        <p className="text-xs text-gray-400">We will notify you via email and WhatsApp when an interview is set.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
      </div>
    </div>
  );
};

export default ApplicantDashboard;