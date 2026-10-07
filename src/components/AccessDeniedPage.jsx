import React from 'react';
import { ShieldAlert, ArrowLeft, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useNavigate } from 'react-router-dom';

const AccessDeniedPage = () => {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
      <div className="bg-white p-8 rounded-lg shadow-xl max-w-md w-full text-center border-t-4 border-red-600">
        <div className="mb-6 relative inline-block">
          <div className="absolute inset-0 bg-red-100 rounded-full scale-150 opacity-50 animate-pulse"></div>
          <ShieldAlert className="w-16 h-16 text-red-600 relative z-10" />
        </div>
        
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Access Denied</h1>
        <div className="flex items-center justify-center gap-2 text-red-500 font-medium mb-6">
            <Lock className="w-4 h-4" />
            <span>Insufficient Permissions</span>
        </div>
        
        <p className="text-gray-600 mb-8 leading-relaxed">
          You do not have the required permissions to access this page. 
          Please contact your system administrator if you believe this is an error.
        </p>
        
        <div className="flex gap-4 justify-center">
          <Button 
            variant="outline" 
            onClick={() => navigate(-1)}
            className="flex items-center gap-2"
          >
            <ArrowLeft className="w-4 h-4" /> Go Back
          </Button>
          <Button 
            onClick={() => navigate('/admin/dashboard')}
            className="bg-[#003D82] hover:bg-[#002855] text-white"
          >
            Dashboard
          </Button>
        </div>
      </div>
    </div>
  );
};

export default AccessDeniedPage;