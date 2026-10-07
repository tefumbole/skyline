import { supabase } from '@/lib/customSupabaseClient';

/**
 * Share Price Service
 * Manages fetching and calculating share prices from system_settings
 */

const DEFAULT_PRICE = 1000;
const DEFAULT_CURRENCY = 'USD';

const DEFAULT_SETTINGS = {
  price_per_share: DEFAULT_PRICE,
  total_shares_available: 100,
  total_sold_admin_override: 0,
  currency: DEFAULT_CURRENCY,
  total_available: 100,
};

function withTimeout(promise, ms = 5000, fallback = null) {
  return Promise.race([
    promise,
    new Promise((resolve) => setTimeout(() => resolve(fallback), ms)),
  ]);
}

/**
 * Fetches current share price from system_settings
 * @returns {Promise<number>} Share price (defaults to 1000 if not found)
 */
export const getSharePrice = async () => {
  try {
    const { data, error } = await supabase
      .from('system_settings')
      .select('price_per_share')
      .single();

    if (error) {
      console.error('sharePriceService: Error fetching share price:', error);
      return DEFAULT_PRICE;
    }

    const price = parseFloat(data?.price_per_share);
    return isNaN(price) || price <= 0 ? DEFAULT_PRICE : price;

  } catch (err) {
    console.error('sharePriceService: Unexpected error:', err);
    return DEFAULT_PRICE;
  }
};

/**
 * Fetches all system settings including share configuration
 * @returns {Promise<object>} System settings object
 */
export const getSystemSettings = async () => {
  try {
    const result = await withTimeout(
      supabase.from('system_settings').select('*').single(),
      5000,
      null
    );

    if (!result) {
      console.warn('sharePriceService: Settings request timed out, using defaults');
      return { ...DEFAULT_SETTINGS };
    }

    const { data, error } = result;

    if (error) {
      console.error('sharePriceService: Error fetching system settings:', error);
      return { ...DEFAULT_SETTINGS };
    }

    return {
      price_per_share: parseFloat(data?.price_per_share) || DEFAULT_PRICE,
      total_shares_available: parseInt(data?.total_shares_available, 10) || 100,
      total_sold_admin_override: parseInt(data?.total_sold_admin_override, 10) || 0,
      currency: data?.currency || DEFAULT_CURRENCY,
      total_available:
        (parseInt(data?.total_shares_available, 10) || 100) -
        (parseInt(data?.total_sold_admin_override, 10) || 0),
    };
  } catch (err) {
    console.error('sharePriceService: Unexpected error:', err);
    return { ...DEFAULT_SETTINGS };
  }
};

/**
 * Calculates total investment amount
 * @param {number} numberOfShares - Number of shares to purchase
 * @param {number} pricePerShare - Price per share (optional, fetches if not provided)
 * @returns {Promise<number>} Total investment amount
 */
export const calculateTotalInvestment = async (numberOfShares, pricePerShare = null) => {
  try {
    const shares = parseInt(numberOfShares);
    if (isNaN(shares) || shares <= 0) {
      return 0;
    }

    const price = pricePerShare !== null 
      ? parseFloat(pricePerShare) 
      : await getSharePrice();

    return shares * price;

  } catch (err) {
    console.error('sharePriceService: Error calculating investment:', err);
    return 0;
  }
};

/**
 * Formats price with currency symbol
 * @param {number} amount - Amount to format
 * @param {string} currency - Currency code (defaults to USD)
 * @returns {string} Formatted price string
 */
export const formatPrice = (amount, currency = 'USD') => {
  try {
    const num = parseFloat(amount);
    if (isNaN(num)) return `$0.00`;

    const currencySymbols = {
      'USD': '$',
      'EUR': '€',
      'GBP': '£',
      'RWF': 'RWF ',
      'XAF': 'FCFA '
    };

    const symbol = currencySymbols[currency] || '$';
    const formatted = num.toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

    return currency === 'RWF' || currency === 'XAF' 
      ? `${symbol}${formatted}` 
      : `${symbol}${formatted}`;

  } catch (err) {
    console.error('sharePriceService: Error formatting price:', err);
    return `$0.00`;
  }
};

/**
 * Gets available shares count
 * @returns {Promise<number>} Number of shares still available
 */
export const getAvailableShares = async () => {
  try {
    const settings = await getSystemSettings();
    return settings.total_available;
  } catch (err) {
    console.error('sharePriceService: Error fetching available shares:', err);
    return 0;
  }
};

export default {
  getSharePrice,
  getSystemSettings,
  calculateTotalInvestment,
  formatPrice,
  getAvailableShares
};