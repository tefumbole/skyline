import React from 'react';
import { useAuth } from '@/context/AuthContext';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { TrendingUp, FileText, PieChart, LogOut, Download } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

const ShareholderDashboard = () => {
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
            <h1 className="text-2xl font-bold text-[#003D82]">Shareholder Dashboard</h1>
            <p className="text-gray-500">Investor: {user?.user_metadata?.full_name || user?.email}</p>
          </div>
          <Button onClick={handleLogout} variant="outline" className="text-red-600 border-red-200 hover:bg-red-50">
            <LogOut className="w-4 h-4 mr-2" /> Logout
          </Button>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-gray-500">Total Shares</CardTitle>
              <PieChart className="w-5 h-5 text-[#003D82]" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">150</div>
              <p className="text-xs text-gray-500 mt-1">Class A Common Stock</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-gray-500">Current Value</CardTitle>
              <TrendingUp className="w-5 h-5 text-green-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">$15,000</div>
              <p className="text-xs text-green-600 mt-1 flex items-center gap-1">
                +12% <span className="text-gray-500 font-normal">since last year</span>
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-gray-500">Dividends</CardTitle>
              <FileText className="w-5 h-5 text-[#D4AF37]" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">$1,250</div>
              <p className="text-xs text-gray-500 mt-1">Paid YTD</p>
            </CardContent>
          </Card>
        </div>

        {/* Documents Section */}
        <Card>
            <CardHeader>
                <CardTitle>Documents & Agreements</CardTitle>
                <CardDescription>Access your signed legal documents</CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-4">
                    <div className="flex items-center justify-between p-4 border rounded-lg bg-gray-50">
                        <div className="flex items-center gap-4">
                            <div className="bg-blue-100 p-2 rounded">
                                <FileText className="w-6 h-6 text-[#003D82]" />
                            </div>
                            <div>
                                <h4 className="font-semibold text-gray-900">Shareholder Agreement (Signed)</h4>
                                <p className="text-xs text-gray-500">Executed on Jan 15, 2026</p>
                            </div>
                        </div>
                        <Button variant="outline" size="sm">
                            <Download className="w-4 h-4 mr-2" /> Download PDF
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default ShareholderDashboard;