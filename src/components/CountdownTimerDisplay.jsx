import React, { useState, useEffect } from 'react';
import { calculateCountdown, formatTimeValue } from '@/services/countdownTimerService';
import { cn } from '@/lib/utils';
import { motion } from 'framer-motion';

const TimeBox = ({ value, label }) => (
  <div className="flex flex-col items-center">
    <div className="bg-gradient-to-b from-[#003D82] to-[#002855] text-white rounded-lg shadow-md w-10 h-10 md:w-12 md:h-12 flex items-center justify-center border border-blue-800/50">
      <span className="text-lg md:text-xl font-bold font-mono tracking-wider">
        {formatTimeValue(value)}
      </span>
    </div>
    <span className="text-[10px] uppercase font-bold text-gray-500 mt-1 tracking-wider">
      {label}
    </span>
  </div>
);

const Separator = () => (
  <div className="text-[#003D82] font-bold text-xl md:text-2xl -mt-4">:</div>
);

const CountdownTimerDisplay = ({ deadline, className }) => {
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
      <div className={cn("text-red-500 font-bold uppercase text-sm tracking-wide", className)}>
        Application Closed
      </div>
    );
  }

  return (
    <div className={cn("flex items-center gap-1.5 md:gap-2", className)}>
        <TimeBox value={timeLeft.days} label="Days" />
        <Separator />
        <TimeBox value={timeLeft.hours} label="Hrs" />
        <Separator />
        <TimeBox value={timeLeft.minutes} label="Mins" />
        <Separator />
        <TimeBox value={timeLeft.seconds} label="Secs" />
    </div>
  );
};

export default CountdownTimerDisplay;