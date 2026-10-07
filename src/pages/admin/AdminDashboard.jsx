import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { 
  Users, 
  BookOpen, 
  Calendar, 
  DollarSign, 
  Settings, 
  FileText, 
  Briefcase, 
  ShieldCheck,
  TrendingUp,
  Activity
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const AdminDashboard = () => {
  const { user, role } = useAuth();
  
  const stats = [
    { title: "Total Students", value: "124", icon: Users, color: "text-blue-600", bg: "bg-blue-100", link: "/admin/students" },
    { title: "Active Courses", value: "8", icon: BookOpen, color: "text-green-600", bg: "bg-green-100", link: "/admin/courses" },
    { title: "Revenue (M)", value: "$12.5k", icon: DollarSign, color: "text-[#D4AF37]", bg: "bg-yellow-100", link: "/admin/payments" },
    { title: "Upcoming Events", value: "3", icon: Calendar, color: "text-purple-600", bg: "bg-purple-100", link: "/admin/events" },
  ];

  const quickLinks = [
    { title: "Manage Users", icon: Users, link: "/admin/users", desc: "Staff & Admins" },
    { title: "Recruitment", icon: Briefcase, link: "/admin/recruitment-dashboard", desc: "Jobs & Applications" },
    { title: "Shareholders", icon: TrendingUp, link: "/admin/shareholders", desc: "Equity Management" },
    { title: "General Settings", icon: Settings, link: "/admin/general-settings", desc: "System Config" },
  ];

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Dashboard</h1>
          <p className="text-gray-500 mt-1">
            Welcome back, <span className="font-semibold text-gray-800">{user?.full_name || user?.email}</span>
          </p>
          <div className="flex items-center gap-2 mt-2">
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
              {role || 'Admin'}
            </span>
            <span className="text-xs text-gray-400">Last login: Today</span>
          </div>
        </div>
        <div className="flex gap-2">
            <Link to="/admin/general-settings">
                <Button variant="outline" className="border-[#003D82] text-[#003D82] hover:bg-blue-50">
                    <Settings className="w-4 h-4 mr-2" /> Settings
                </Button>
            </Link>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat, index) => (
          <Link key={index} to={stat.link}>
             <Card className="hover:shadow-lg transition-shadow cursor-pointer border-t-4 border-t-[#003D82]">
                <CardContent className="p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-gray-500">{stat.title}</p>
                      <h3 className="text-2xl font-bold text-gray-900 mt-1">{stat.value}</h3>
                    </div>
                    <div className={`p-3 rounded-full ${stat.bg}`}>
                      <stat.icon className={`w-6 h-6 ${stat.color}`} />
                    </div>
                  </div>
                </CardContent>
             </Card>
          </Link>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Quick Actions */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle className="text-[#003D82]">Quick Access</CardTitle>
            <CardDescription>Frequently used management tools</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {quickLinks.map((item, i) => (
                    <Link key={i} to={item.link}>
                        <div className="flex items-center p-4 border rounded-xl hover:bg-gray-50 hover:border-[#D4AF37] transition-all group">
                            <div className="bg-blue-50 p-3 rounded-lg group-hover:bg-[#003D82] group-hover:text-white transition-colors">
                                <item.icon className="w-6 h-6 text-[#003D82] group-hover:text-white" />
                            </div>
                            <div className="ml-4">
                                <h4 className="font-semibold text-gray-900">{item.title}</h4>
                                <p className="text-xs text-gray-500">{item.desc}</p>
                            </div>
                        </div>
                    </Link>
                ))}
            </div>
          </CardContent>
        </Card>

        {/* System Status / Profile Snippet */}
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Activity className="w-5 h-5 text-green-600" /> System Status
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm text-gray-600">Database Connection</span>
                    <span className="text-xs font-bold text-green-600 bg-green-100 px-2 py-1 rounded">Active</span>
                </div>
                <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm text-gray-600">OTP Service</span>
                    <span className="text-xs font-bold text-green-600 bg-green-100 px-2 py-1 rounded">Operational</span>
                </div>
                <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm text-gray-600">Security</span>
                    <span className="text-xs font-bold text-[#003D82] bg-blue-100 px-2 py-1 rounded flex items-center gap-1">
                        <ShieldCheck className="w-3 h-3" /> Enabled
                    </span>
                </div>
                
                <div className="mt-6 pt-4 bg-gray-50 p-4 rounded-lg">
                    <h4 className="text-sm font-bold text-gray-700 mb-2">My Profile</h4>
                    <p className="text-xs text-gray-500 mb-1">Update your contact info in Settings.</p>
                    <Link to="/admin/general-settings">
                        <Button size="sm" className="w-full bg-[#003D82] text-white hover:bg-[#002855]">
                             Go to Profile Settings
                        </Button>
                    </Link>
                </div>
            </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default AdminDashboard;