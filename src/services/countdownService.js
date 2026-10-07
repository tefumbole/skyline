/**
 * Countdown Service
 * Calculates time remaining until the Official Launch Event
 * Date: April 5th, 2026 at 11:00 AM
 */

// Target date: April 5, 2026 11:00:00
// We use a specific date string format that creates a Date object in the user's local timezone
const TARGET_DATE = new Date('April 5, 2026 11:00:00').getTime();

export const getTimeRemaining = () => {
  const now = new Date().getTime();
  const total = TARGET_DATE - now;

  if (total <= 0) {
    return {
      total: 0,
      days: 0,
      hours: 0,
      minutes: 0,
      seconds: 0,
      isExpired: true
    };
  }

  const seconds = Math.floor((total / 1000) % 60);
  const minutes = Math.floor((total / 1000 / 60) % 60);
  const hours = Math.floor((total / (1000 * 60 * 60)) % 24);
  const days = Math.floor(total / (1000 * 60 * 60 * 24));

  return {
    total,
    days,
    hours,
    minutes,
    seconds,
    isExpired: false
  };
};