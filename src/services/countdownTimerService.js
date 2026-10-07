import { differenceInSeconds } from 'date-fns';

/**
 * Calculates the remaining time until a deadline.
 * @param {string|Date} deadline - The deadline date.
 * @returns {Object} { days, hours, minutes, seconds, expired, totalSeconds }
 */
export const calculateCountdown = (deadline) => {
  if (!deadline) {
    return { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true, totalSeconds: 0 };
  }

  const now = new Date();
  const end = new Date(deadline);
  const totalSeconds = Math.max(0, differenceInSeconds(end, now));

  if (totalSeconds <= 0) {
    return { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true, totalSeconds: 0 };
  }

  const days = Math.floor(totalSeconds / (3600 * 24));
  const hours = Math.floor((totalSeconds % (3600 * 24)) / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = Math.floor(totalSeconds % 60);

  return {
    days,
    hours,
    minutes,
    seconds,
    expired: false,
    totalSeconds
  };
};

/**
 * Formats a single digit number to double digit string (e.g., 5 -> "05")
 * @param {number} num 
 * @returns {string}
 */
export const formatTimeValue = (num) => {
  return num.toString().padStart(2, '0');
};

/**
 * Formats the time left object into a readable string
 * @param {Object} timeLeft - { days, hours, minutes, seconds }
 * @returns {string}
 */
export const formatTimeRemaining = (timeLeft) => {
  if (!timeLeft) return "";
  if (timeLeft.expired) return "Expired";
  
  const { days, hours, minutes } = timeLeft;
  const parts = [];
  
  if (days > 0) parts.push(`${days}d`);
  if (hours > 0 || days > 0) parts.push(`${hours}h`);
  parts.push(`${minutes}m`);
  
  return parts.join(' ');
};