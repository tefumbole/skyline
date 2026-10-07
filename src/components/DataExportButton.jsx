import React from 'react';
import { Button } from '@/components/ui/button';
import { Download } from 'lucide-react';

const DataExportButton = ({ data }) => {
  const handleExport = () => {
    if (!data || data.length === 0) return;

    // 1. Define CSV Headers
    const headers = [
      'ID', 'Created At', 'Full Name', 'Email', 'Phone', 
      'Company', 'Module', 'Experience Level', 
      'Payment Method', 'Payment Status', 'Transaction ID', 'Amount'
    ];

    // 2. Format Data Rows
    const rows = data.map(item => [
      item.id,
      item.created_at,
      `"${item.full_name}"`, // Quote strings to handle commas
      item.email,
      item.phone,
      `"${item.company_name || ''}"`,
      `"${item.module}"`,
      item.experience_level,
      item.payment_method,
      item.payment_status,
      item.transaction_id || '',
      item.amount
    ]);

    // 3. Combine to CSV String
    const csvContent = [
      headers.join(','),
      ...rows.map(e => e.join(','))
    ].join('\n');

    // 4. Create Blob and Download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `students_export_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <Button 
      variant="outline" 
      onClick={handleExport}
      className="border-[#003366] text-[#003366] hover:bg-[#003366] hover:text-white"
    >
      <Download className="w-4 h-4 mr-2" />
      Export CSV
    </Button>
  );
};

export default DataExportButton;