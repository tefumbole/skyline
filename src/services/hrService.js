const API_BASE = import.meta.env.VITE_API_URL || '/api';

function getToken() {
  try {
    const raw = localStorage.getItem('alpha_supabase_auth');
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed?.access_token || parsed?.currentSession?.access_token || null;
  } catch {
    return null;
  }
}

async function hrApi(path, options = {}) {
  const token = getToken();
  const res = await fetch(`${API_BASE}/hr${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || res.statusText || 'Request failed');
  return data;
}

export const hrService = {
  listStaff: (params = {}) => {
    const q = new URLSearchParams(params).toString();
    return hrApi(`/staff${q ? `?${q}` : ''}`);
  },
  getStaff: (id) => hrApi(`/staff/${id}`),
  createStaff: (body) => hrApi('/staff', { method: 'POST', body: JSON.stringify(body) }),
  updateStaff: (id, body) => hrApi(`/staff/${id}`, { method: 'PUT', body: JSON.stringify(body) }),
  searchUsers: (q) => hrApi(`/users/search?q=${encodeURIComponent(q)}`),
  getNextStaffCode: () => hrApi('/staff/next-code'),
  verifyPayslip: (code) => fetch(`${API_BASE}/hr/payslips/verify/${encodeURIComponent(code)}`).then((r) => r.json()),

  listCategories: () => hrApi('/categories'),
  createCategory: (body) => hrApi('/categories', { method: 'POST', body: JSON.stringify(body) }),
  updateCategory: (id, body) => hrApi(`/categories/${id}`, { method: 'PUT', body: JSON.stringify(body) }),
  deleteCategory: (id) => hrApi(`/categories/${id}`, { method: 'DELETE' }),

  listPositionRates: () => hrApi('/position-rates'),
  createPositionRate: (body) => hrApi('/position-rates', { method: 'POST', body: JSON.stringify(body) }),
  updatePositionRate: (id, body) => hrApi(`/position-rates/${id}`, { method: 'PUT', body: JSON.stringify(body) }),
  deletePositionRate: (id) => hrApi(`/position-rates/${id}`, { method: 'DELETE' }),

  listAllowanceTypes: () => hrApi('/allowance-types'),
  createAllowanceType: (body) => hrApi('/allowance-types', { method: 'POST', body: JSON.stringify(body) }),
  updateAllowanceType: (id, body) => hrApi(`/allowance-types/${id}`, { method: 'PUT', body: JSON.stringify(body) }),
  deleteAllowanceType: (id) => hrApi(`/allowance-types/${id}`, { method: 'DELETE' }),
  bulkDeleteAllowanceTypes: (ids) => hrApi('/allowance-types/bulk-delete', { method: 'POST', body: JSON.stringify({ ids }) }),

  listDeductionTypes: () => hrApi('/deduction-types'),
  createDeductionType: (body) => hrApi('/deduction-types', { method: 'POST', body: JSON.stringify(body) }),
  updateDeductionType: (id, body) => hrApi(`/deduction-types/${id}`, { method: 'PUT', body: JSON.stringify(body) }),
  deleteDeductionType: (id) => hrApi(`/deduction-types/${id}`, { method: 'DELETE' }),
  bulkDeleteDeductionTypes: (ids) => hrApi('/deduction-types/bulk-delete', { method: 'POST', body: JSON.stringify({ ids }) }),

  listJobs: (params = {}) => {
    const q = new URLSearchParams(params).toString();
    return hrApi(`/jobs${q ? `?${q}` : ''}`);
  },
  getJob: (id) => hrApi(`/jobs/${id}`),
  createJob: (body) => hrApi('/jobs', { method: 'POST', body: JSON.stringify(body) }),
  updateJob: (id, body) => hrApi(`/jobs/${id}`, { method: 'PUT', body: JSON.stringify(body) }),
  assignJobStaff: (jobId, body) => hrApi(`/jobs/${jobId}/staff`, { method: 'POST', body: JSON.stringify(body) }),
  updateJobStaff: (jobId, rowId, body) => hrApi(`/jobs/${jobId}/staff/${rowId}`, { method: 'PUT', body: JSON.stringify(body) }),
  removeJobStaff: (jobId, rowId) => hrApi(`/jobs/${jobId}/staff/${rowId}`, { method: 'DELETE' }),
  syncJobTimesheet: (jobId) => hrApi(`/jobs/${jobId}/sync-timesheet`, { method: 'POST' }),

  listPayrollRuns: (params = {}) => {
    const q = new URLSearchParams(params).toString();
    return hrApi(`/payroll-runs${q ? `?${q}` : ''}`);
  },
  getPayrollRun: (id) => hrApi(`/payroll-runs/${id}`),
  createJobPayroll: (jobId) => hrApi(`/payroll-runs/from-job/${jobId}`, { method: 'POST' }),
  createMonthlyPayroll: (body) => hrApi('/payroll-runs/monthly', { method: 'POST', body: JSON.stringify(body) }),
  updatePayrollItem: (runId, itemId, body) =>
    hrApi(`/payroll-runs/${runId}/items/${itemId}`, { method: 'PUT', body: JSON.stringify(body) }),
  submitPayrollReview: (id, notes) =>
    hrApi(`/payroll-runs/${id}/submit-review`, { method: 'POST', body: JSON.stringify({ notes }) }),
  approvePayroll: (id, notes) =>
    hrApi(`/payroll-runs/${id}/approve`, { method: 'POST', body: JSON.stringify({ notes }) }),
  forwardPayrollFinance: (id, notes) =>
    hrApi(`/payroll-runs/${id}/forward-finance`, { method: 'POST', body: JSON.stringify({ notes }) }),
  rejectPayroll: (id, notes) =>
    hrApi(`/payroll-runs/${id}/reject`, { method: 'POST', body: JSON.stringify({ notes }) }),

  listAdvances: (params = {}) => {
    const q = new URLSearchParams(params).toString();
    return hrApi(`/advances${q ? `?${q}` : ''}`);
  },
  createAdvance: (body) => hrApi('/advances', { method: 'POST', body: JSON.stringify(body) }),

  listFinance: (params = {}) => {
    const q = new URLSearchParams(params).toString();
    return hrApi(`/finance${q ? `?${q}` : ''}`);
  },
  updateFinancePayment: (id, body) => hrApi(`/finance/${id}`, { method: 'PUT', body: JSON.stringify(body) }),

  listPayslips: () => hrApi('/payslips'),
  generatePayslip: (itemId) => hrApi(`/payslips/generate/${itemId}`, { method: 'POST' }),
  getPayslipDetail: (itemId) => hrApi(`/payslips/detail/${itemId}`),
  sendPayslipWhatsApp: (itemId) => hrApi(`/payslips/${itemId}/send-whatsapp`, { method: 'POST' }),

  getReportsSummary: (month) => hrApi(`/reports/summary${month ? `?month=${month}` : ''}`),
  getStaffPaymentHistory: (staffId) => hrApi(`/reports/staff-history/${staffId}`),

  listTimesheet: (params = {}) => {
    const q = new URLSearchParams(params).toString();
    return hrApi(`/timesheet${q ? `?${q}` : ''}`);
  },
  saveTimesheet: (body) => hrApi('/timesheet', { method: 'POST', body: JSON.stringify(body) }),
};

export function formatFcfa(amount) {
  return `${Number(amount || 0).toLocaleString()} FCFA`;
}

export default hrService;
