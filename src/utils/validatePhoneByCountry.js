export const validatePhoneByCountry = (phone, countryCode) => {
  if (!phone) return { isValid: false, error: 'Phone number is required' };
  
  // Basic validation: remove non-digits
  const cleanPhone = phone.replace(/\D/g, '');
  
  // Check length (simplified validation)
  if (cleanPhone.length < 8 || cleanPhone.length > 15) {
    return { isValid: false, error: 'Invalid phone number length' };
  }
  
  return { 
    isValid: true, 
    formatted: `${countryCode} ${phone}`,
    clean: cleanPhone 
  };
};