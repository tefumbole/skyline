import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function AudioAdminDashboard() {
  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6">Audio Admin Dashboard</h1>
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardHeader>
            <CardTitle>Total Templates</CardTitle>
          </CardHeader>
          <CardContent>156</CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Instruments</CardTitle>
          </CardHeader>
          <CardContent>42</CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Genres</CardTitle>
          </CardHeader>
          <CardContent>18</CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Active Sessions</CardTitle>
          </CardHeader>
          <CardContent>89</CardContent>
        </Card>
      </div>
    </div>
  );
}