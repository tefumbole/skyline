/**
 * Utility to format phone numbers for WhatsApp API.
 * Removes non-numeric characters and ensures country code is present.
 */
export const formatPhoneNumber = (phone) => {
  if (phone === null || phone === undefined || phone === '') {
    return null;
  }

  // Remove all non-numeric characters
  let cleaned = String(phone).replace(/\D/g, '');

  if (!cleaned) {
    return null;
  }

  // If the phone number is 9 digits (standard Cameroon local format), prepend 237
  if (cleaned.length === 9) {
    cleaned = '237' + cleaned;
  }

  // Return the formatted number (e.g., 2376XXXXXXXX)
  return cleaned;
};