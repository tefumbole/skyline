import React from 'react';
import { useAuth } from '@/context/AuthContext';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { BookOpen, Award, Clock, LogOut, User } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

const StudentDashboard = () => {
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
            <h1 className="text-2xl font-bold text-[#003D82]">Student Portal</h1>
            <p className="text-gray-500">Welcome back, {user?.user_metadata?.full_name || user?.email}</p>
          </div>
          <Button onClick={handleLogout} variant="outline" className="text-red-600 border-red-200 hover:bg-red-50">
            <LogOut className="w-4 h-4 mr-2" /> Logout
          </Button>
        </div>

        {/* Quick Stats */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-gray-500">Enrolled Courses</CardTitle>
              <BookOpen className="w-5 h-5 text-blue-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">2</div>
              <p className="text-xs text-gray-500 mt-1">Active learning paths</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-gray-500">Certificates</CardTitle>
              <Award className="w-5 h-5 text-[#D4AF37]" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">0</div>
              <p className="text-xs text-gray-500 mt-1">Completed certifications</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-gray-500">Study Hours</CardTitle>
              <Clock className="w-5 h-5 text-green-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">12.5</div>
              <p className="text-xs text-gray-500 mt-1">Total hours this month</p>
            </CardContent>
          </Card>
        </div>

        {/* Content Area */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>My Courses</CardTitle>
              <CardDescription>Continue where you left off</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="p-4 border rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                  <div className="flex justify-between items-start">
                    <div>
                      <h3 className="font-semibold text-[#003D82]">Full Stack Web Development</h3>
                      <p className="text-sm text-gray-500">Module 3: React & State Management</p>
                    </div>
                    <span className="bg-blue-100 text-[#003D82] text-xs px-2 py-1 rounded-full font-bold">In Progress</span>
                  </div>
                  <div className="w-full bg-gray-200 h-2 rounded-full mt-4">
                    <div className="bg-[#D4AF37] h-2 rounded-full" style={{ width: '45%' }}></div>
                  </div>
                </div>
                
                <div className="p-4 border rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                  <div className="flex justify-between items-start">
                    <div>
                      <h3 className="font-semibold text-[#003D82]">Database Design Fundamentals</h3>
                      <p className="text-sm text-gray-500">Module 1: SQL Basics</p>
                    </div>
                    <span className="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-bold">New</span>
                  </div>
                  <div className="w-full bg-gray-200 h-2 rounded-full mt-4">
                    <div className="bg-[#D4AF37] h-2 rounded-full" style={{ width: '10%' }}></div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Profile</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col items-center text-center">
               <div className="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mb-4">
                  <User className="w-10 h-10 text-gray-400" />
               </div>
               <h3 className="font-bold text-lg">{user?.email}</h3>
               <p className="text-sm text-gray-500 mb-4">Student ID: {user?.id?.slice(0,8)}</p>
               <Button className="w-full bg-[#003D82]">Edit Profile</Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
};

export default StudentDashboard;