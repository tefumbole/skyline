import React, { useState, useEffect } from 'react';
import { Clock, AlertCircle } from 'lucide-react';
import { differenceInDays, differenceInHours, differenceInMinutes, differenceInSeconds, parseISO, isPast, isValid } from 'date-fns';

const JobCountdownTimer = ({ deadline, className = "" }) => {
  const [timeLeft, setTimeLeft] = useState(calculateTimeLeft());
  const [isExpired, setIsExpired] = useState(false);

  function calculateTimeLeft() {
    if (!deadline) return { days: 0, hours: 0, minutes: 0, seconds: 0 };
    
    const deadlineDate = typeof deadline === 'string' ? parseISO(deadline) : deadline;
    if (!isValid(deadlineDate)) return { days: 0, hours: 0, minutes: 0, seconds: 0 };

    if (isPast(deadlineDate)) {
      return { days: 0, hours: 0, minutes: 0, seconds: 0 };
    }

    const now = new Date();
    return {
      days: differenceInDays(deadlineDate, now),
      hours: differenceInHours(deadlineDate, now) % 24,
      minutes: differenceInMinutes(deadlineDate, now) % 60,
      seconds: differenceInSeconds(deadlineDate, now) % 60
    };
  }

  useEffect(() => {
    // Initial check
    const deadlineDate = typeof deadline === 'string' ? parseISO(deadline) : deadline;
    if (!deadline || (isValid(deadlineDate) && isPast(deadlineDate))) {
      setIsExpired(true);
      return;
    }

    const timer = setInterval(() => {
      const remaining = calculateTimeLeft();
      setTimeLeft(remaining);
      
      const target = typeof deadline === 'string' ? parseISO(deadline) : deadline;
      if (isPast(target)) {
        setIsExpired(true);
        clearInterval(timer);
      }
    }, 1000);

    return () => clearInterval(timer);
  }, [deadline]);

  if (!deadline) return null;

  if (isExpired) {
    return (
      <div className={`w-full bg-red-50 border-2 border-red-200 rounded-xl p-4 flex items-center justify-center gap-3 text-red-600 font-bold ${className}`}>
        <AlertCircle className="w-6 h-6" />
        <span className="text-lg uppercase tracking-wider">Application Closed</span>
      </div>
    );
  }

  const TimeBlock = ({ value, label }) => (
    <div className="flex flex-col items-center min-w-[60px]">
      <span className="text-2xl font-bold text-[#003D82] leading-none">
        {String(value).padStart(2, '0')}
      </span>
      <span className="text-[10px] uppercase tracking-wider text-gray-500 font-medium mt-1">
        {label}
      </span>
    </div>
  );

  const Separator = () => (
    <div className="text-2xl font-bold text-blue-300 pb-4">:</div>
  );

  return (
    <div className={`w-full bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-3 shadow-sm ${className}`}>
      <div className="flex items-center justify-between px-2 sm:px-4">
        <div className="flex items-center gap-2 text-blue-700 font-medium bg-white/50 px-2 py-1 rounded-md">
            <Clock className="w-4 h-4 animate-pulse" />
            <span className="text-xs uppercase tracking-wide">Time Remaining</span>
        </div>
        
        <div className="flex items-center gap-2 sm:gap-4">
          <TimeBlock value={timeLeft.days} label="Days" />
          <Separator />
          <TimeBlock value={timeLeft.hours} label="Hours" />
          <Separator />
          <TimeBlock value={timeLeft.minutes} label="Mins" />
        </div>
      </div>
    </div>
  );
};

export default JobCountdownTimer;