import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getRecentEvents } from '@/services/eventsService';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Calendar, MapPin, ArrowRight } from 'lucide-react';
import { format } from 'date-fns';
import { motion } from 'framer-motion';

const RecentEventsSection = () => {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchEvents = async () => {
      setLoading(true);

      try {
        const data = await getRecentEvents(6);
        setEvents(data || []);
      } catch (err) {
        console.error('RecentEventsSection: Error fetching events:', err);
        setEvents([]);
      } finally {
        setLoading(false);
      }
    };

    fetchEvents();
  }, []);

  // Don't render section if no events
  if (!loading && events.length === 0) {
    return null;
  }

  if (loading) {
    return (
      <section className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <div className="animate-pulse space-y-4">
              <div className="h-8 bg-gray-200 rounded w-64 mx-auto"></div>
              <div className="h-4 bg-gray-200 rounded w-96 mx-auto"></div>
            </div>
          </div>
        </div>
      </section>
    );
  }

  return (
    <section className="py-16 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="text-center mb-12">
          <h2 className="text-4xl font-bold text-[#003D82] mb-4">Recent Events & Highlights</h2>
          <p className="text-xl text-gray-600">
            Join us at our upcoming events and technology showcases
          </p>
        </div>

        {/* Events Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
          {events.map((event, index) => (
            <motion.div
              key={event.id}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: index * 0.1 }}
            >
              <Link to={`/events/${event.id}`} className="group">
                <Card className="h-full overflow-hidden border-2 border-gray-100 hover:border-[#003D82] transition-all duration-300 hover:shadow-xl">
                  {/* Event Image */}
                  <div className="relative h-48 overflow-hidden bg-gray-200">
                    {event.featured_image ? (
                      <img
                        src={event.featured_image}
                        alt={event.title}
                        className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#003D82] to-[#0066CC]">
                        <Calendar className="w-16 h-16 text-white opacity-50" />
                      </div>
                    )}
                  </div>

                  <CardContent className="p-6">
                    {/* Event Title */}
                    <h3 className="text-xl font-bold text-gray-900 mb-3 line-clamp-2 group-hover:text-[#003D82] transition-colors">
                      {event.title}
                    </h3>

                    {/* Event Date */}
                    <div className="flex items-center gap-2 text-sm text-gray-600 mb-2">
                      <Calendar className="w-4 h-4 text-[#003D82]" />
                      <span>{format(new Date(event.event_date), 'MMMM dd, yyyy')}</span>
                    </div>

                    {/* Event Location */}
                    {event.location && (
                      <div className="flex items-center gap-2 text-sm text-gray-600">
                        <MapPin className="w-4 h-4 text-[#003D82]" />
                        <span className="line-clamp-1">{event.location}</span>
                      </div>
                    )}
                  </CardContent>
                </Card>
              </Link>
            </motion.div>
          ))}
        </div>

        {/* View All Button */}
        <div className="text-center">
          <Link to="/events">
            <Button
              size="lg"
              className="bg-[#003D82] hover:bg-[#002855] text-white px-8"
            >
              View All Events
              <ArrowRight className="w-5 h-5 ml-2" />
            </Button>
          </Link>
        </div>
      </div>
    </section>
  );
};

export default RecentEventsSection;