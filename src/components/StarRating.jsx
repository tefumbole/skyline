import React, { useState } from 'react';
import { Star } from 'lucide-react';
import { cn } from '@/lib/utils';

const StarRating = ({ rating = 0, onRatingChange, readOnly = false, className, size = 24 }) => {
    const [hoverRating, setHoverRating] = useState(0);

    const handleMouseEnter = (index) => {
        if (!readOnly) {
            setHoverRating(index);
        }
    };

    const handleMouseLeave = () => {
        if (!readOnly) {
            setHoverRating(0);
        }
    };

    const handleClick = (index) => {
        if (!readOnly && onRatingChange) {
            onRatingChange(index);
        }
    };

    return (
        <div className={cn("flex items-center gap-1", className)}>
            {[1, 2, 3, 4, 5].map((index) => {
                const isFilled = (hoverRating || rating) >= index;
                const isHovered = hoverRating >= index;

                return (
                    <button
                        key={index}
                        type="button"
                        onClick={() => handleClick(index)}
                        onMouseEnter={() => handleMouseEnter(index)}
                        onMouseLeave={handleMouseLeave}
                        disabled={readOnly}
                        className={cn(
                            "transition-all duration-200 transform",
                            readOnly ? "cursor-default" : "cursor-pointer hover:scale-110",
                            "focus:outline-none"
                        )}
                        aria-label={`Rate ${index} stars`}
                    >
                        <Star
                            size={size}
                            className={cn(
                                "transition-colors duration-200",
                                isFilled 
                                    ? "fill-yellow-400 text-yellow-400" 
                                    : "fill-transparent text-gray-300"
                            )}
                            strokeWidth={isFilled ? 0 : 1.5}
                        />
                    </button>
                );
            })}
        </div>
    );
};

export default StarRating;