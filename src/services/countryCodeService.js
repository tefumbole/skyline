/**
 * Country Code Service
 * Manages country codes for international phone number handling
 */

export const COUNTRY_CODES = [
  { code: '+250', country: 'Rwanda', flag: '🇷🇼' },
  { code: '+237', country: 'Cameroon', flag: '🇨🇲' },
  { code: '+254', country: 'Kenya', flag: '🇰🇪' },
  { code: '+256', country: 'Uganda', flag: '🇺🇬' },
  { code: '+255', country: 'Tanzania', flag: '🇹🇿' },
  { code: '+1', country: 'United States', flag: '🇺🇸' },
  { code: '+44', country: 'United Kingdom', flag: '🇬🇧' },
  { code: '+27', country: 'South Africa', flag: '🇿🇦' },
  { code: '+234', country: 'Nigeria', flag: '🇳🇬' }
];

/**
 * Get country details by country code
 * @param {string} code - Country code (e.g., '+237')
 * @returns {object|null} Country object or null if not found
 */
export const getCountryByCode = (code) => {
  return COUNTRY_CODES.find(c => c.code === code) || null;
};

/**
 * Get country code by country name
 * @param {string} countryName - Country name (e.g., 'Cameroon')
 * @returns {string|null} Country code or null if not found
 */
export const getCodeByCountry = (countryName) => {
  const country = COUNTRY_CODES.find(
    c => c.country.toLowerCase() === countryName.toLowerCase()
  );
  return country ? country.code : null;
};

/**
 * Format country code display as "Code Country"
 * @param {string} code - Country code
 * @returns {string} Formatted display string
 */
export const formatCountryCodeDisplay = (code) => {
  const country = getCountryByCode(code);
  if (!country) return code;
  return `${country.code} ${country.country}`;
};

/**
 * Combine country code and phone number
 * @param {string} countryCode - Country code (e.g., '+237')
 * @param {string} phoneNumber - Phone number without country code
 * @returns {string} Combined full phone number
 */
export const combinePhoneNumber = (countryCode, phoneNumber) => {
  if (!countryCode || !phoneNumber) return '';
  
  // Remove any leading zeros from phone number
  const cleanedPhone = phoneNumber.replace(/^0+/, '');
  
  // Ensure country code starts with +
  const cleanedCode = countryCode.startsWith('+') ? countryCode : `+${countryCode}`;
  
  return `${cleanedCode}${cleanedPhone}`;
};

/**
 * Validate phone number format (6-15 digits)
 * @param {string} phoneNumber - Phone number to validate
 * @returns {object} Validation result with valid boolean and error message
 */
export const validatePhoneNumber = (phoneNumber) => {
  if (!phoneNumber || phoneNumber.trim() === '') {
    return { valid: false, error: 'Phone number is required' };
  }

  // Remove all non-digit characters for validation
  const digitsOnly = phoneNumber.replace(/\D/g, '');

  if (digitsOnly.length < 6) {
    return { valid: false, error: 'Phone number must be at least 6 digits' };
  }

  if (digitsOnly.length > 15) {
    return { valid: false, error: 'Phone number cannot exceed 15 digits' };
  }

  return { valid: true, error: null };
};

/**
 * Get country code options for dropdown
 * @returns {Array} Array of options with value, label, and country properties
 */
export const getCountryCodeOptions = () => {
  return COUNTRY_CODES.map(c => ({
    value: c.code,
    label: `${c.flag} ${c.code} ${c.country}`,
    country: c.country,
    code: c.code
  }));
};

export default {
  COUNTRY_CODES,
  getCountryByCode,
  getCodeByCountry,
  formatCountryCodeDisplay,
  combinePhoneNumber,
  validatePhoneNumber,
  getCountryCodeOptions
};