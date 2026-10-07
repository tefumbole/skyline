import React, { useState, useEffect } from 'react';
import { useTimeSheet } from '@/context/TimeSheetContext';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { MapPin, Clock, PlayCircle, StopCircle, History } from 'lucide-react';

const ClockInOutComponent = () => {
  const { clockStatus, clockIn, clockOut } = useTimeSheet();
  const [currentTime, setCurrentTime] = useState(new Date());
  const [locationError, setLocationError] = useState('');
  const [loadingLoc, setLoadingLoc] = useState(false);

  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  const handleClockIn = () => {
    setLoadingLoc(true);
    setLocationError('');

    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(
        async (position) => {
          const { latitude, longitude } = position.coords;
          // In a real app, we'd reverse geocode here. For now, use coordinates.
          const address = `Lat: ${latitude.toFixed(4)}, Long: ${longitude.toFixed(4)}`;
          clockIn(address);
          setLoadingLoc(false);
        },
        (error) => {
          console.error("Geolocation error", error);
          // Fallback if GPS fails or permission denied
          clockIn("Manual Entry (GPS Failed)");
          setLoadingLoc(false);
        }
      );
    } else {
      clockIn("GPS Not Supported");
      setLoadingLoc(false);
    }
  };

  const getElapsedTime = () => {
    if (!clockStatus.startTime) return "00:00:00";
    const start = new Date(clockStatus.startTime);
    const diff = currentTime - start;
    const hours = Math.floor(diff / 3600000);
    const minutes = Math.floor((diff % 3600000) / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
  };

  return (
    <Card className="w-full shadow-lg border-t-4 border-t-[#D4AF37]">
      <CardHeader className="pb-2">
        <CardTitle className="text-lg font-bold text-[#003D82] flex justify-between items-center">
            <span>Time Clock</span>
            <span className="text-sm font-normal text-gray-500">{currentTime.toLocaleDateString()}</span>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="flex flex-col items-center justify-center space-y-6">
            <div className="text-4xl font-mono font-bold text-gray-800 tracking-wider">
                {clockStatus.isClockedIn ? getElapsedTime() : currentTime.toLocaleTimeString()}
            </div>
            
            <div className="flex items-center space-x-2 text-sm text-gray-500">
                <MapPin className="w-4 h-4 text-[#D4AF37]" />
                <span>{clockStatus.isClockedIn ? clockStatus.location : "Ready to clock in"}</span>
            </div>

            {locationError && <p className="text-xs text-red-500">{locationError}</p>}

            <div className="w-full">
                {!clockStatus.isClockedIn ? (
                    <Button 
                        onClick={handleClockIn} 
                        disabled={loadingLoc}
                        className="w-full h-14 text-lg bg-green-600 hover:bg-green-700 text-white shadow-md transition-all hover:scale-[1.02]"
                    >
                        {loadingLoc ? "Locating..." : (
                            <><PlayCircle className="w-6 h-6 mr-2" /> Clock In</>
                        )}
                    </Button>
                ) : (
                    <Button 
                        onClick={clockOut} 
                        className="w-full h-14 text-lg bg-red-600 hover:bg-red-700 text-white shadow-md transition-all hover:scale-[1.02]"
                    >
                        <StopCircle className="w-6 h-6 mr-2" /> Clock Out
                    </Button>
                )}
            </div>

            {clockStatus.isClockedIn && (
                 <div className="w-full bg-blue-50 p-3 rounded-lg border border-blue-100 flex items-start space-x-2">
                    <History className="w-4 h-4 text-blue-600 mt-0.5" />
                    <div className="text-xs text-blue-800">
                        <span className="font-semibold">Session Started:</span> {new Date(clockStatus.startTime).toLocaleTimeString()}
                    </div>
                 </div>
            )}
        </div>
      </CardContent>
    </Card>
  );
};

export default ClockInOutComponent;