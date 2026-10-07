/**
 * Formats a number or range string as currency
 * @param {number|string} amount - The amount to format (e.g. 50000 or "50000-70000")
 * @param {string} currency - The currency code (default: 'RWF')
 * @returns {string} Formatted string (e.g., "200,000 RWF")
 */
export const formatCurrency = (amount, currency = 'RWF') => {
  if (amount === null || amount === undefined || amount === '') return '';
  
  const stringAmount = String(amount);
  
  // Handle ranges like "50000-70000"
  if (stringAmount.includes('-')) {
    const parts = stringAmount.split('-').map(part => part.trim());
    const formattedParts = parts.map(part => {
      const num = Number(part);
      return isNaN(num) ? part : num.toLocaleString('en-US');
    });
    return `${formattedParts[0]} ${currency} - ${formattedParts[1]} ${currency}`;
  }

  const num = Number(amount);
  if (isNaN(num)) return amount;
  
  return `${num.toLocaleString('en-US')} ${currency}`;
};

/**
 * Formats a salary with amount, currency and period
 * @param {number|string} amount - The salary amount
 * @param {string} currency - The currency code (default: 'RWF')
 * @param {string} period - The payment period (default: 'Month')
 * @returns {string} Formatted string (e.g., "200,000 RWF / Month")
 */
export const formatSalary = (amount, currency = 'RWF', period = 'Month') => {
  if (!amount) return 'Not specified';
  const formattedAmount = formatCurrency(amount, currency);
  return `${formattedAmount} / ${period}`;
};