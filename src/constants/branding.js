/** Canonical brand assets and contact details — single source of truth */

export const COMPANY_NAME =
  import.meta.env.VITE_COMPANY_NAME || 'Beyond Enterprise';

export const COMPANY_NAME_SHORT =
  import.meta.env.VITE_COMPANY_NAME_SHORT || 'Beyond Enterprise';

export const WHATSAPP_PHONE =
  import.meta.env.VITE_ADMIN_PHONE_NUMBER || '+237675321739';

/** Digits only — for wa.me links */
export const WHATSAPP_WA_ME = WHATSAPP_PHONE.replace(/\D/g, '');

export const CONTACT_PHONE_DISPLAY =
  import.meta.env.VITE_CONTACT_PHONE_DISPLAY || '+237 675 321 739';

export const WEBSITE_URL =
  import.meta.env.VITE_SITE_URL || 'https://beyondtechworld.com';

export const WEBSITE_HOST =
  import.meta.env.VITE_WEBSITE_HOST || 'www.beyondtechworld.com';

export const CONTACT_EMAIL =
  import.meta.env.VITE_CONTACT_EMAIL || 'info@beyondtechworld.com';

export const DEFAULT_LOGO_URL =
  import.meta.env.VITE_LOGO_URL || '/branding/beyond-logo.png';

export const HERO_IMAGE_URL =
  import.meta.env.VITE_HERO_IMAGE_URL || '/branding/beyond-hero.png';

/** Software developer credit — shown in footer/login. Phone links to WhatsApp. */
export const DEVELOPER = {
  name: 'Sr. Engr. Tefu R. Mbole',
  phone: '+237675321739',
  waMe: '237675321739',
  whatsAppUrl: 'https://wa.me/237675321739',
};

export function whatsAppUrl(message) {
  const text = typeof message === 'string' ? message : '';
  return `https://wa.me/${WHATSAPP_WA_ME}?text=${encodeURIComponent(text)}`;
}

export function isValidLogoUrl(url) {
  if (!url || typeof url !== 'string') return false;
  return /^https?:\/\/.+/i.test(url.trim()) || url.startsWith('/');
}
