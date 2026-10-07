import React, { useState, useEffect } from 'react';
import { getCourseFeedbackStats } from '@/services/feedbackService';
import StarRating from './StarRating';
import { Loader2 } from 'lucide-react';
import { Progress } from '@/components/ui/progress';

const FeedbackStatsCard = ({ courseId }) => {
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchStats = async () => {
            if (courseId) {
                const data = await getCourseFeedbackStats(courseId);
                setStats(data);
                setLoading(false);
            }
        };
        fetchStats();
    }, [courseId]);

    if (loading) {
        return <div className="h-24 flex items-center justify-center text-gray-400"><Loader2 className="w-6 h-6 animate-spin" /></div>;
    }

    if (!stats || stats.totalReviews === 0) {
        return (
            <div className="py-2">
                <div className="flex items-center gap-2 text-sm text-gray-500">
                    <StarRating rating={0} readOnly size={16} />
                    <span>No reviews yet</span>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <div className="flex items-center gap-2">
                <span className="text-2xl font-bold text-[#003D82]">{stats.averageRating}</span>
                <div className="flex flex-col">
                     <StarRating rating={Math.round(stats.averageRating)} readOnly size={16} />
                     <span className="text-xs text-gray-500">{stats.totalReviews} review{stats.totalReviews !== 1 ? 's' : ''}</span>
                </div>
            </div>
            
            <div className="space-y-1">
                {[5, 4, 3, 2, 1].map(star => {
                    const count = stats.ratingDistribution[star] || 0;
                    const percent = stats.totalReviews > 0 ? (count / stats.totalReviews) * 100 : 0;
                    
                    return (
                        <div key={star} className="flex items-center gap-2 text-xs">
                            <span className="w-3 text-gray-600">{star}</span>
                            <StarRating rating={1} readOnly size={10} className="w-3" />
                            <Progress value={percent} className="h-1.5 flex-1" />
                            <span className="w-6 text-right text-gray-400">{count}</span>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

export default FeedbackStatsCard;