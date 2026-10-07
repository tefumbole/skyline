import React, { useState, useEffect } from 'react';
import { getShareholderStats } from '@/services/shareholderService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Users, CheckCircle, Clock, PieChart, DollarSign, CreditCard, RefreshCw, AlertCircle } from 'lucide-react';

const AdminShareHolderDashboardPage = () => {
  const { toast } = useToast();

  const [stats, setStats] = useState({
    totalShareholders: 0,
    approvedShareholders: 0,
    pendingShareholders: 0,
    totalShares: 0,
    totalInvestment: 0,
    completedPayments: 0,
    pendingPayments: 0,
    availableShares: 100,
    totalCompanyShares: 100,
    pricePerShare: 1000,
  });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchStats = async () => {
    console.log('[DASHBOARD] Fetching shareholder statistics');
    setLoading(true);
    setError(null);

    try {
      const statsData = await getShareholderStats();
      console.log('[DASHBOARD] Stats received:', statsData);

      // Ensure all values are defined and are numbers
      const safeStats = {
        totalShareholders: statsData?.totalShareholders || 0,
        approvedShareholders: statsData?.approvedShareholders || 0,
        pendingShareholders: statsData?.pendingShareholders || 0,
        totalShares: statsData?.totalShares || 0,
        totalInvestment: statsData?.totalInvestment || 0,
        completedPayments: statsData?.completedPayments || 0,
        pendingPayments: statsData?.pendingPayments || 0,
        availableShares: statsData?.availableShares || 100,
        totalCompanyShares: statsData?.totalCompanyShares || 100,
        pricePerShare: statsData?.pricePerShare || 1000,
      };

      console.log('[DASHBOARD] Processed stats:', safeStats);
      setStats(safeStats);

    } catch (err) {
      console.error('[DASHBOARD] Error fetching stats:', err);
      setError(err.message || 'Failed to load dashboard statistics');
      
      toast({
        title: "Error",
        description: err.message || 'Failed to load statistics',
        variant: "destructive"
      });

      // Keep default stats on error
      setStats({
        totalShareholders: 0,
        approvedShareholders: 0,
        pendingShareholders: 0,
        totalShares: 0,
        totalInvestment: 0,
        completedPayments: 0,
        pendingPayments: 0,
        availableShares: 100,
        totalCompanyShares: 100,
        pricePerShare: 1000,
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchStats();
  }, []);

  const handleRefresh = () => {
    console.log('[DASHBOARD] Manual refresh triggered');
    fetchStats();
  };

  const handleRetry = () => {
    console.log('[DASHBOARD] Retry triggered');
    window.location.reload();
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount || 0);
  };

  // Loading state
  if (loading) {
    return (
      <div className="p-6">
        <div className="flex flex-col items-center justify-center min-h-[400px] space-y-4">
          <Loader2 className="h-12 w-12 animate-spin text-blue-600" />
          <p className="text-gray-600 text-lg">Loading dashboard statistics...</p>
        </div>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="p-6">
        <Alert variant="destructive" className="max-w-2xl mx-auto">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription className="flex items-center justify-between">
            <span>{error}</span>
            <Button onClick={handleRetry} variant="outline" size="sm">
              Retry
            </Button>
          </AlertDescription>
        </Alert>
      </div>
    );
  }

  const statCards = [
    {
      title: 'Total Shareholders',
      value: stats.totalShareholders,
      icon: Users,
      color: 'from-blue-500 to-blue-600',
      bgColor: 'bg-blue-50',
      iconColor: 'text-blue-600'
    },
    {
      title: 'Approved Shareholders',
      value: stats.approvedShareholders,
      icon: CheckCircle,
      color: 'from-green-500 to-green-600',
      bgColor: 'bg-green-50',
      iconColor: 'text-green-600'
    },
    {
      title: 'Pending Approvals',
      value: stats.pendingShareholders,
      icon: Clock,
      color: 'from-yellow-500 to-yellow-600',
      bgColor: 'bg-yellow-50',
      iconColor: 'text-yellow-600'
    },
    {
      title: 'Total Shares Allocated',
      value: stats.totalShares,
      icon: PieChart,
      color: 'from-purple-500 to-purple-600',
      bgColor: 'bg-purple-50',
      iconColor: 'text-purple-600'
    },
    {
      title: 'Total Investment',
      value: formatCurrency(stats.totalInvestment),
      icon: DollarSign,
      color: 'from-emerald-500 to-emerald-600',
      bgColor: 'bg-emerald-50',
      iconColor: 'text-emerald-600'
    },
    {
      title: 'Completed Payments',
      value: stats.completedPayments,
      icon: CreditCard,
      color: 'from-indigo-500 to-indigo-600',
      bgColor: 'bg-indigo-50',
      iconColor: 'text-indigo-600'
    }
  ];

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Shareholder Dashboard</h1>
          <p className="text-gray-600 mt-1">Overview of all shareholder registrations and investments</p>
        </div>
        <Button onClick={handleRefresh} variant="outline" className="gap-2">
          <RefreshCw className="h-4 w-4" />
          Refresh
        </Button>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {statCards.map((stat, index) => {
          const Icon = stat.icon;
          
          return (
            <Card key={index} className="border-none shadow-lg hover:shadow-xl transition-shadow duration-300">
              <CardHeader className={`${stat.bgColor} border-b`}>
                <div className="flex items-center justify-between">
                  <CardTitle className="text-sm font-medium text-gray-600 uppercase tracking-wider">
                    {stat.title}
                  </CardTitle>
                  <div className={`p-3 rounded-full ${stat.bgColor}`}>
                    <Icon className={`h-6 w-6 ${stat.iconColor}`} />
                  </div>
                </div>
              </CardHeader>
              <CardContent className="pt-6">
                <div className={`text-3xl font-bold bg-gradient-to-r ${stat.color} bg-clip-text text-transparent`}>
                  {stat.value}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {/* Additional Info Card */}
      <Card className="border-l-4 border-l-blue-600">
        <CardHeader>
          <CardTitle className="text-lg">Share Allocation Summary</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-3 gap-4">
            <div className="bg-gray-50 p-4 rounded-lg">
              <p className="text-sm text-gray-600 mb-1">Total Company Shares</p>
              <p className="text-2xl font-bold text-gray-800">{stats.totalCompanyShares ?? 100}</p>
            </div>
            <div className="bg-blue-50 p-4 rounded-lg">
              <p className="text-sm text-gray-600 mb-1">Allocated Shares</p>
              <p className="text-2xl font-bold text-blue-700">{stats.totalShares}</p>
            </div>
            <div className="bg-green-50 p-4 rounded-lg">
              <p className="text-sm text-gray-600 mb-1">Available Shares</p>
              <p className="text-2xl font-bold text-green-700">{stats.availableShares}</p>
            </div>
          </div>
          
          <div className="mt-4">
            <div className="flex justify-between text-sm mb-2">
              <span className="text-gray-600">Allocation Progress</span>
              <span className="font-medium text-gray-800">
                {stats.totalCompanyShares > 0
                  ? ((stats.totalShares / stats.totalCompanyShares) * 100).toFixed(1)
                  : 0}%
              </span>
            </div>
            <div className="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
              <div
                className="bg-gradient-to-r from-blue-500 to-blue-600 h-full rounded-full transition-all duration-500"
                style={{ width: `${Math.min((stats.totalShares / (stats.totalCompanyShares || 100)) * 100, 100)}%` }}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Payment Status Card */}
      <Card className="border-l-4 border-l-green-600">
        <CardHeader>
          <CardTitle className="text-lg">Payment Status Overview</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-2 gap-4">
            <div className="bg-green-50 p-4 rounded-lg">
              <p className="text-sm text-gray-600 mb-1">Completed Payments</p>
              <p className="text-2xl font-bold text-green-700">{stats.completedPayments}</p>
              <p className="text-xs text-gray-500 mt-1">Shareholders with paid status</p>
            </div>
            <div className="bg-yellow-50 p-4 rounded-lg">
              <p className="text-sm text-gray-600 mb-1">Pending Payments</p>
              <p className="text-2xl font-bold text-yellow-700">{stats.pendingPayments}</p>
              <p className="text-xs text-gray-500 mt-1">Awaiting payment confirmation</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default AdminShareHolderDashboardPage;