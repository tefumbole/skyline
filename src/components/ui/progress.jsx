import React from 'react';
import { cn } from '@/lib/utils';

const Progress = React.forwardRef(({ className, value = 0, ...props }, ref) => (
  <div
    ref={ref}
    className={cn('relative w-full h-2 bg-gray-200 rounded-full overflow-hidden', className)}
    {...props}
  >
    <div
      className="h-full bg-blue-500 rounded-full transition-all duration-300"
      style={{ width: `${Math.min(value, 100)}%` }}
    />
  </div>
));

Progress.displayName = 'Progress';

export { Progress };