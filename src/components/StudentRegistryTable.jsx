import React, { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowUpDown, MoreHorizontal, Eye } from 'lucide-react';
import StudentDetailsModal from './StudentDetailsModal';

const StudentRegistryTable = ({ data, loading, onDataChange }) => {
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  // Pagination logic
  const totalPages = Math.ceil(data.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const currentData = data.slice(startIndex, startIndex + itemsPerPage);

  const handleRowClick = (student) => {
    setSelectedStudent(student);
  };

  if (loading) {
    return (
      <div className="w-full h-64 flex items-center justify-center text-gray-500">
        Loading registry data...
      </div>
    );
  }

  if (data.length === 0) {
    return (
        <div className="w-full h-64 flex flex-col items-center justify-center text-gray-500 border rounded-lg bg-gray-50">
            <p className="mb-2">No registrations found.</p>
        </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="rounded-md border bg-white overflow-hidden shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-gray-700 uppercase bg-gray-50 border-b">
              <tr>
                <th className="px-6 py-3 font-medium">Name / Email</th>
                <th className="px-6 py-3 font-medium hidden md:table-cell">Module</th>
                <th className="px-6 py-3 font-medium hidden lg:table-cell">Date</th>
                <th className="px-6 py-3 font-medium">Payment</th>
                <th className="px-6 py-3 font-medium">Status</th>
                <th className="px-6 py-3 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {currentData.map((student) => (
                <tr 
                  key={student.id} 
                  className="bg-white border-b hover:bg-gray-50 cursor-pointer transition-colors"
                  onClick={() => handleRowClick(student)}
                >
                  <td className="px-6 py-4">
                    <div className="font-medium text-gray-900">{student.full_name}</div>
                    <div className="text-gray-500 text-xs">{student.email}</div>
                  </td>
                  <td className="px-6 py-4 hidden md:table-cell max-w-[200px] truncate" title={student.module}>
                    {student.module}
                  </td>
                  <td className="px-6 py-4 hidden lg:table-cell whitespace-nowrap text-gray-500">
                    {new Date(student.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-2">
                       {student.payment_method === 'mobile_money' ? '📱 Mobile' : '💳 Card'}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <Badge variant={
                      student.payment_status === 'paid' ? 'success' : 
                      student.payment_status === 'failed' ? 'destructive' : 'warning'
                    }>
                      {student.payment_status}
                    </Badge>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                      <Eye className="h-4 w-4" />
                      <span className="sr-only">View</span>
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Pagination Controls */}
      <div className="flex items-center justify-between px-2">
         <div className="text-sm text-gray-500">
            Showing {startIndex + 1} to {Math.min(startIndex + itemsPerPage, data.length)} of {data.length} entries
         </div>
         <div className="flex gap-2">
            <Button 
                variant="outline" 
                size="sm" 
                onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                disabled={currentPage === 1}
            >
                Previous
            </Button>
            <Button 
                variant="outline" 
                size="sm" 
                onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
                disabled={currentPage === totalPages}
            >
                Next
            </Button>
         </div>
      </div>

      <StudentDetailsModal 
        student={selectedStudent} 
        isOpen={!!selectedStudent} 
        onClose={() => setSelectedStudent(null)}
        onUpdate={() => {
            setSelectedStudent(null);
            onDataChange();
        }}
      />
    </div>
  );
};

export default StudentRegistryTable;