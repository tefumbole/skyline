import React from 'react';
import { Button } from '@/components/ui/button';
import { AlertCircle, Home, RefreshCw, TerminalSquare } from 'lucide-react';

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error("ErrorBoundary caught an error:", error, errorInfo);
    this.setState({ errorInfo });
    // In a production environment, you could send this error to an error tracking service like Sentry or Supabase
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 p-4 text-center">
          <div className="bg-white p-8 rounded-xl shadow-2xl max-w-2xl w-full border border-gray-100">
            <div className="flex justify-center mb-6">
              <div className="p-4 bg-red-100 rounded-full">
                <AlertCircle className="w-12 h-12 text-red-600" />
              </div>
            </div>
            <h1 className="text-3xl font-bold text-gray-900 mb-3">System Error Encountered</h1>
            <p className="text-gray-600 mb-8 leading-relaxed text-lg">
              We encountered an unexpected error while loading this component. The development team has been notified.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center mb-8">
               <Button 
                onClick={() => window.location.reload()} 
                className="bg-[#003D82] hover:bg-[#002855] text-white h-12 px-6"
               >
                  <RefreshCw className="w-5 h-5 mr-2" />
                  Refresh Application
               </Button>
               <Button 
                onClick={() => window.location.href = '/'} 
                variant="outline"
                className="border-[#003D82] text-[#003D82] hover:bg-blue-50 h-12 px-6"
               >
                  <Home className="w-5 h-5 mr-2" />
                  Return to Dashboard
               </Button>
            </div>

            {import.meta.env.DEV && this.state.error && (
              <div className="text-left bg-gray-900 text-green-400 p-4 rounded-lg overflow-auto text-xs font-mono max-h-64 shadow-inner border border-gray-800">
                <div className="flex items-center gap-2 mb-2 text-gray-300 border-b border-gray-700 pb-2">
                    <TerminalSquare className="w-4 h-4" />
                    <span className="font-bold">Developer Error Trace</span>
                </div>
                <p className="font-bold text-red-400 mb-2">{this.state.error.toString()}</p>
                <pre className="whitespace-pre-wrap leading-relaxed">
                    {this.state.errorInfo?.componentStack}
                </pre>
              </div>
            )}
          </div>
        </div>
      );
    }

    return this.props.children; 
  }
}

export default ErrorBoundary;