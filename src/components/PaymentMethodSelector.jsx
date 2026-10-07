import React from 'react';
import { CreditCard, Smartphone } from 'lucide-react';

function PaymentMethodSelector({ onSelectMethod, amount }) {
  return (
    <div className="space-y-6 text-center animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div className="bg-gray-50 p-4 rounded-lg border border-gray-100">
        <p className="text-sm text-gray-500">Total Amount Due</p>
        <p className="text-3xl font-bold text-navy">${amount} USD</p>
      </div>

      <h3 className="text-lg font-semibold text-gray-800">Select Payment Method</h3>
      
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <button
          onClick={() => onSelectMethod('card')}
          className="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-xl hover:border-tech-blue hover:bg-blue-50 transition-all group"
        >
          <div className="bg-blue-100 p-3 rounded-full mb-3 group-hover:bg-blue-200 transition-colors">
            <CreditCard className="w-8 h-8 text-tech-blue" />
          </div>
          <span className="font-bold text-gray-700">Credit / Debit Card</span>
          <span className="text-xs text-gray-400 mt-1">Stripe Secure Payment</span>
        </button>

        <button
          onClick={() => onSelectMethod('mobile_money')}
          className="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-xl hover:border-gold hover:bg-yellow-50 transition-all group"
        >
           <div className="bg-yellow-100 p-3 rounded-full mb-3 group-hover:bg-yellow-200 transition-colors">
            <Smartphone className="w-8 h-8 text-yellow-700" />
          </div>
          <span className="font-bold text-gray-700">Mobile Money</span>
          <span className="text-xs text-gray-400 mt-1">MTN / Airtel Rwanda</span>
        </button>
      </div>
      
      <p className="text-xs text-gray-400">
        Secure encrypted transaction. Your data is protected.
      </p>
    </div>
  );
}

export default PaymentMethodSelector;