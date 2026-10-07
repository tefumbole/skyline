import React, { useState, useEffect } from 'react';
import { calculateCountdown, formatTimeValue } from '@/services/countdownTimerService';
import { cn } from '@/lib/utils';

const TimeBox = ({ value, label }) => (
  <div className="flex flex-col items-center">
    <div className="bg-gradient-to-b from-[#003D82] to-[#002855] text-white rounded-md shadow-sm w-9 h-9 md:w-10 md:h-10 flex items-center justify-center border border-blue-800/50">
      <span className="text-sm md:text-base font-bold font-mono tracking-wider">
        {formatTimeValue(value)}
      </span>
    </div>
    <span className="text-[9px] uppercase font-bold text-gray-500 mt-1 tracking-wider">
      {label}
    </span>
  </div>
);

const Separator = () => (
  <div className="text-[#003D82] font-bold text-lg -mt-4">:</div>
);

const CountdownTimerBox = ({ deadline, className }) => {
  const [timeLeft, setTimeLeft] = useState(calculateCountdown(deadline));

  useEffect(() => {
    // Initial calculation
    setTimeLeft(calculateCountdown(deadline));

    // Update every second
    const interval = setInterval(() => {
      setTimeLeft(calculateCountdown(deadline));
    }, 1000);

    return () => clearInterval(interval);
  }, [deadline]);

  if (timeLeft.expired) {
    return (
      <div className={cn("text-red-500 font-bold uppercase text-xs tracking-wide border border-red-200 bg-red-50 px-3 py-2 rounded-md", className)}>
        Application Closed
      </div>
    );
  }

  return (
    <div className={cn("flex items-center gap-1.5", className)}>
        <TimeBox value={timeLeft.days} label="DAYS" />
        <Separator />
        <TimeBox value={timeLeft.hours} label="HRS" />
        <Separator />
        <TimeBox value={timeLeft.minutes} label="MINS" />
        <Separator />
        <TimeBox value={timeLeft.seconds} label="SECS" />
    </div>
  );
};

export default CountdownTimerBox;