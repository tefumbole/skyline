import React, { useEffect, useState } from 'react';
import { ShieldAlert, ArrowLeft, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useNavigate } from 'react-router-dom';
import { getCurrentUserRole, formatRoleLabel } from '@/services/roleService';

const AccessDenied = ({ title = "Access Denied", message }) => {
  const navigate = useNavigate();
  const [roleLabel, setRoleLabel] = useState('Loading...');

  useEffect(() => {
      const getRole = async () => {
          const roleStr = await getCurrentUserRole();
          setRoleLabel(formatRoleLabel(roleStr));
      }
      getRole();
  }, []);

  return (
    <div className="min-h-[80vh] flex flex-col items-center justify-center p-4">
      <div className="bg-white p-8 rounded-lg shadow-xl max-w-md w-full text-center border-t-4 border-red-600">
        <div className="mb-6 relative inline-block">
          <div className="absolute inset-0 bg-red-100 rounded-full scale-150 opacity-50 animate-pulse"></div>
          <ShieldAlert className="w-16 h-16 text-red-600 relative z-10" />
        </div>
        
        <h1 className="text-3xl font-bold text-gray-900 mb-2">{title}</h1>
        <div className="flex items-center justify-center gap-2 text-red-500 font-medium mb-4">
            <Lock className="w-4 h-4" />
            <span>Insufficient Permissions</span>
        </div>
        
        <div className="bg-gray-50 p-3 rounded-md mb-6 border border-gray-200">
            <p className="text-sm text-gray-500 mb-1">Your current role:</p>
            <span className="font-bold text-[#003D82]">{roleLabel}</span>
        </div>

        <p className="text-gray-600 mb-8 leading-relaxed">
          {message || "You do not have the required permissions to access this page. Please contact your system administrator if you believe this is an error."}
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

export default AccessDenied;