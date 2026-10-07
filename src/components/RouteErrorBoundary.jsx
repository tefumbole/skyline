import React from 'react';
import { useRouteError, isRouteErrorResponse, Link } from 'react-router-dom';
import { AlertCircle, Home, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function RouteErrorBoundary() {
  const error = useRouteError();
  console.error("Route Error:", error);

  let title = "Navigation Error";
  let message = "An unexpected error occurred while loading this page.";

  if (isRouteErrorResponse(error)) {
    if (error.status === 404) {
      title = "Page Not Found";
      message = "The page you are looking for does not exist or has been moved.";
    } else if (error.status === 401) {
      title = "Unauthorized";
      message = "You do not have permission to view this page.";
    } else if (error.status === 503) {
      title = "Service Unavailable";
      message = "The service is temporarily unavailable. Please try again later.";
    }
  }

  return (
    <div className="min-h-[70vh] flex flex-col items-center justify-center p-6 text-center animate-in fade-in duration-500">
      <div className="p-4 bg-red-50 rounded-full mb-6">
        <AlertCircle className="w-16 h-16 text-red-500" />
      </div>
      <h1 className="text-3xl font-bold text-gray-900 mb-4">{title}</h1>
      <p className="text-lg text-gray-600 max-w-md mb-8">{message}</p>
      
      <div className="flex flex-col sm:flex-row gap-4">
        <Button 
            onClick={() => window.history.back()} 
            variant="outline" 
            className="border-gray-300 text-gray-700 hover:bg-gray-50 h-12 px-6"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Go Back
        </Button>
        <Link to="/">
          <Button className="bg-[#003D82] hover:bg-[#002855] text-white h-12 px-6 w-full sm:w-auto">
            <Home className="w-5 h-5 mr-2" />
            Return Home
          </Button>
        </Link>
      </div>

      {import.meta.env.DEV && error?.message && (
         <div className="mt-12 max-w-2xl w-full text-left bg-gray-100 p-4 rounded-lg overflow-auto text-xs font-mono text-gray-800">
            <strong>Error Details:</strong> {error.message}
         </div>
      )}
    </div>
  );
}