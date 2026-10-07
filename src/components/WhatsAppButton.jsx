import React from 'react';
import { MessageCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useWhatsApp } from '@/context/WhatsAppContext';
import { cn } from '@/lib/utils';

function WhatsAppButton({ className, variant = "default", text = "Chat on WhatsApp", showIcon = true, ...props }) {
  const { openModal } = useWhatsApp();

  if (variant === "floating") {
    return (
      <button
        onClick={openModal}
        className={cn(
          "bg-[#25D366] hover:bg-[#1EBE57] text-white rounded-full p-4 shadow-2xl transition-all duration-300 hover:scale-110 group flex items-center justify-center",
          className
        )}
        {...props}
      >
        <MessageCircle className="w-6 h-6" />
        <span className="absolute right-full mr-3 top-1/2 -translate-y-1/2 bg-navy text-white px-3 py-2 rounded-lg text-sm whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
          {text}
        </span>
      </button>
    );
  }

  return (
    <Button 
      onClick={openModal}
      className={cn("btn-gold", className)}
      {...props}
    >
      {showIcon && <MessageCircle className="w-5 h-5 mr-2" />}
      {text}
    </Button>
  );
}

export default WhatsAppButton;