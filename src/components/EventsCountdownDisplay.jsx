import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';

const EventsCountdownDisplay = ({ targetDate }) => {
  const calculateTimeLeft = () => {
    const difference = +new Date(targetDate) - +new Date();
    let newTimeLeft = {};

    if (difference > 0) {
      newTimeLeft = {
        days: Math.floor(difference / (1000 * 60 * 60 * 24)),
        hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((difference / 1000 / 60) % 60),
        seconds: Math.floor((difference / 1000) % 60),
      };
    } else {
      newTimeLeft = { days: 0, hours: 0, minutes: 0, seconds: 0 };
    }
    return newTimeLeft;
  };

  const [timeLeft, setTimeLeft] = useState(calculateTimeLeft());
  const [isExpired, setIsExpired] = useState(false);

  useEffect(() => {
    const timer = setInterval(() => {
      const t = calculateTimeLeft();
      setTimeLeft(t);
      if (t.days === 0 && t.hours === 0 && t.minutes === 0 && t.seconds === 0) {
        setIsExpired(true);
      }
    }, 1000);

    return () => clearInterval(timer);
  }, [targetDate]);

  if (isExpired) return <div className="text-red-500 font-bold text-sm">Event Started</div>;

  return (
    <div className="grid grid-cols-4 gap-2 text-center bg-gradient-to-r from-[#003D82] to-[#00509d] p-2 rounded-lg text-white">
      <div>
        <span className="block text-lg font-bold leading-none">{timeLeft.days}</span>
        <span className="text-[10px] uppercase opacity-75">Days</span>
      </div>
      <div>
        <span className="block text-lg font-bold leading-none">{timeLeft.hours}</span>
        <span className="text-[10px] uppercase opacity-75">Hrs</span>
      </div>
      <div>
        <span className="block text-lg font-bold leading-none">{timeLeft.minutes}</span>
        <span className="text-[10px] uppercase opacity-75">Min</span>
      </div>
      <div>
        <span className="block text-lg font-bold leading-none">{timeLeft.seconds}</span>
        <span className="text-[10px] uppercase opacity-75">Sec</span>
      </div>
    </div>
  );
};

export default EventsCountdownDisplay;