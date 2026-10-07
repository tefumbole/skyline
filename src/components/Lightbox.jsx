import React, { useEffect } from 'react';
import { X, ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';

const Lightbox = ({ images = [], currentIndex = 0, onClose, onNavigate }) => {
  const handlePrevious = () => {
    const newIndex = currentIndex === 0 ? images.length - 1 : currentIndex - 1;
    onNavigate(newIndex);
  };

  const handleNext = () => {
    const newIndex = currentIndex === images.length - 1 ? 0 : currentIndex + 1;
    onNavigate(newIndex);
  };

  // Keyboard navigation
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        onClose();
      } else if (e.key === 'ArrowLeft') {
        handlePrevious();
      } else if (e.key === 'ArrowRight') {
        handleNext();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [currentIndex, images.length]);

  // Prevent body scroll when lightbox is open
  useEffect(() => {
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = 'unset';
    };
  }, []);

  if (!images || images.length === 0) return null;

  const currentImage = images[currentIndex];

  return (
    <div
      className="fixed inset-0 bg-black/95 z-50 flex items-center justify-center p-4"
      onClick={onClose}
    >
      {/* Close Button */}
      <Button
        onClick={onClose}
        variant="ghost"
        className="absolute top-4 right-4 text-white hover:bg-white/10 z-10"
        size="icon"
      >
        <X className="w-6 h-6" />
      </Button>

      {/* Image Counter */}
      <div className="absolute top-4 left-1/2 -translate-x-1/2 bg-black/50 text-white px-4 py-2 rounded-full text-sm font-medium z-10">
        {currentIndex + 1} / {images.length}
      </div>

      {/* Navigation Buttons */}
      {images.length > 1 && (
        <>
          <Button
            onClick={(e) => {
              e.stopPropagation();
              handlePrevious();
            }}
            variant="ghost"
            className="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:bg-white/10 z-10"
            size="icon"
          >
            <ChevronLeft className="w-8 h-8" />
          </Button>

          <Button
            onClick={(e) => {
              e.stopPropagation();
              handleNext();
            }}
            variant="ghost"
            className="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:bg-white/10 z-10"
            size="icon"
          >
            <ChevronRight className="w-8 h-8" />
          </Button>
        </>
      )}

      {/* Image Container */}
      <div
        className="relative max-w-7xl max-h-full flex items-center justify-center"
        onClick={(e) => e.stopPropagation()}
      >
        <img
          src={currentImage.url}
          alt={currentImage.alt || `Image ${currentIndex + 1}`}
          className="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl"
        />
      </div>
    </div>
  );
};

export default Lightbox;