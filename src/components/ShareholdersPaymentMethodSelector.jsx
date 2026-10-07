import React, { useState, useEffect } from 'react';
import { CreditCard, Smartphone, Building2, Coins, Loader2, AlertCircle, RefreshCw } from 'lucide-react';
import { getSharePrice, calculateTotalInvestment, formatPrice } from '@/services/sharePriceService';
import { Alert, AlertDescription } from '@/components/ui/alert';

function ShareholdersPaymentMethodSelector({ 
  numberOfShares = 1, 
  amount = null, 
  onPaymentMethodSelect = () => {},
  onAmountChange = () => {}
}) {
  const [sharePrice, setSharePrice] = useState(1000);
  const [totalAmount, setTotalAmount] = useState(amount);
  const [selectedMethod, setSelectedMethod] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Fetch share price on mount
  useEffect(() => {
    const fetchPrice = async () => {
      setLoading(true);
      setError(null);

      try {
        const price = await getSharePrice();
        setSharePrice(price);

        // If amount not provided, calculate it
        if (amount === null) {
          const calculatedAmount = await calculateTotalInvestment(numberOfShares, price);
          setTotalAmount(calculatedAmount);
          onAmountChange(calculatedAmount);
        } else {
          setTotalAmount(amount);
        }
      } catch (err) {
        console.error('ShareholdersPaymentMethodSelector: Error fetching price:', err);
        setError(err.message || 'Failed to load share price');
        
        // Fallback calculation
        const fallbackAmount = numberOfShares * 1000;
        setTotalAmount(fallbackAmount);
        onAmountChange(fallbackAmount);
      } finally {
        setLoading(false);
      }
    };

    fetchPrice();
  }, []); // Run only on mount

  // Recalculate when numberOfShares changes
  useEffect(() => {
    if (loading) return; // Skip during initial load

    const recalculate = async () => {
      try {
        const calculatedAmount = await calculateTotalInvestment(numberOfShares, sharePrice);
        setTotalAmount(calculatedAmount);
        onAmountChange(calculatedAmount);
      } catch (err) {
        console.error('ShareholdersPaymentMethodSelector: Recalculation error:', err);
        const fallbackAmount = numberOfShares * sharePrice;
        setTotalAmount(fallbackAmount);
        onAmountChange(fallbackAmount);
      }
    };

    recalculate();
  }, [numberOfShares, sharePrice]);

  // Update totalAmount when amount prop changes externally
  useEffect(() => {
    if (amount !== null && amount !== totalAmount) {
      setTotalAmount(amount);
    }
  }, [amount]);

  const handleMethodSelect = (method) => {
    setSelectedMethod(method);
    
    const paymentData = {
      method,
      amount: totalAmount,
      numberOfShares,
      pricePerShare: sharePrice
    };

    onPaymentMethodSelect(paymentData);
  };

  const retryFetch = async () => {
    setLoading(true);
    setError(null);

    try {
      const price = await getSharePrice();
      setSharePrice(price);
      const calculatedAmount = await calculateTotalInvestment(numberOfShares, price);
      setTotalAmount(calculatedAmount);
      onAmountChange(calculatedAmount);
    } catch (err) {
      setError(err.message || 'Failed to load share price');
      const fallbackAmount = numberOfShares * 1000;
      setTotalAmount(fallbackAmount);
      onAmountChange(fallbackAmount);
    } finally {
      setLoading(false);
    }
  };

  // Safe amount validation before rendering
  const displayAmount = totalAmount && typeof totalAmount === 'number' ? totalAmount : 0;

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-[#003D82] mb-3" />
        <p className="text-gray-600">Loading payment options...</p>
      </div>
    );
  }

  if (error) {
    return (
      <Alert variant="destructive" className="mb-4">
        <AlertCircle className="h-4 w-4" />
        <AlertDescription className="ml-2 flex items-center justify-between">
          <span>{error}</span>
          <button 
            onClick={retryFetch}
            className="ml-4 flex items-center gap-1 text-sm underline hover:no-underline"
          >
            <RefreshCw className="w-3 h-3" />
            Retry
          </button>
        </AlertDescription>
      </Alert>
    );
  }

  return (
    <div className="space-y-6">
      <div className="text-center mb-6">
        <h3 className="text-xl font-bold text-[#003D82]">Select Payment Method</h3>
        <div className="mt-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
          <p className="text-sm text-gray-600">Investment Amount</p>
          <p className="text-2xl font-bold text-[#003D82]">{formatPrice(displayAmount)}</p>
          <p className="text-xs text-gray-500 mt-1">
            {numberOfShares} share{numberOfShares !== 1 ? 's' : ''} × {formatPrice(sharePrice)}
          </p>
        </div>
      </div>

      {selectedMethod && (
        <Alert className="bg-green-50 border-green-200">
          <AlertDescription className="text-green-800 font-medium">
            ✓ Payment method selected: {selectedMethod === 'bank_transfer' ? 'Bank Transfer' : 
              selectedMethod === 'mobile_money' ? 'Mobile Money' : 
              selectedMethod === 'card' ? 'Card Payment' : 'Cryptocurrency'}
          </AlertDescription>
        </Alert>
      )}

      <div className="grid grid-cols-1 gap-4">
        {/* Bank Transfer */}
        <button
          onClick={() => handleMethodSelect('bank_transfer')}
          className={`flex items-center p-4 border-2 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all group text-left ${
            selectedMethod === 'bank_transfer' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
          }`}
        >
          <div className="p-3 bg-blue-100 rounded-full mr-4 group-hover:bg-blue-200">
            <Building2 className="w-6 h-6 text-blue-600" />
          </div>
          <div className="flex-1">
            <h4 className="font-bold text-[#003D82]">Bank Transfer</h4>
            <p className="text-xs text-gray-500">Direct bank deposit or wire transfer</p>
          </div>
          {selectedMethod === 'bank_transfer' && (
            <div className="ml-2 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
              <span className="text-white text-sm">✓</span>
            </div>
          )}
        </button>

        {/* Mobile Money */}
        <button
          onClick={() => handleMethodSelect('mobile_money')}
          className={`flex items-center p-4 border-2 rounded-lg hover:border-yellow-500 hover:bg-yellow-50 transition-all group text-left ${
            selectedMethod === 'mobile_money' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200'
          }`}
        >
          <div className="p-3 bg-yellow-100 rounded-full mr-4 group-hover:bg-yellow-200">
            <Smartphone className="w-6 h-6 text-yellow-600" />
          </div>
          <div className="flex-1">
            <h4 className="font-bold text-[#003D82]">Mobile Money</h4>
            <p className="text-xs text-gray-500">MTN Mobile Money / Airtel Money / Orange Money</p>
          </div>
          {selectedMethod === 'mobile_money' && (
            <div className="ml-2 w-6 h-6 bg-yellow-500 rounded-full flex items-center justify-center">
              <span className="text-white text-sm">✓</span>
            </div>
          )}
        </button>

        {/* Card Payment */}
        <button
          onClick={() => handleMethodSelect('card')}
          className={`flex items-center p-4 border-2 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all group text-left ${
            selectedMethod === 'card' ? 'border-green-500 bg-green-50' : 'border-gray-200'
          }`}
        >
          <div className="p-3 bg-green-100 rounded-full mr-4 group-hover:bg-green-200">
            <CreditCard className="w-6 h-6 text-green-600" />
          </div>
          <div className="flex-1">
            <h4 className="font-bold text-[#003D82]">VISA / MasterCard</h4>
            <p className="text-xs text-gray-500">Secure international card payment via Stripe</p>
          </div>
          {selectedMethod === 'card' && (
            <div className="ml-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
              <span className="text-white text-sm">✓</span>
            </div>
          )}
        </button>

        {/* Cryptocurrency */}
        <button
          onClick={() => handleMethodSelect('crypto')}
          className={`flex items-center p-4 border-2 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all group text-left ${
            selectedMethod === 'crypto' ? 'border-purple-500 bg-purple-50' : 'border-gray-200'
          }`}
        >
          <div className="p-3 bg-purple-100 rounded-full mr-4 group-hover:bg-purple-200">
            <Coins className="w-6 h-6 text-purple-600" />
          </div>
          <div className="flex-1">
            <h4 className="font-bold text-[#003D82]">Cryptocurrency</h4>
            <p className="text-xs text-gray-500">Bitcoin (BTC) / Ethereum (ETH) / USDT</p>
          </div>
          {selectedMethod === 'crypto' && (
            <div className="ml-2 w-6 h-6 bg-purple-500 rounded-full flex items-center justify-center">
              <span className="text-white text-sm">✓</span>
            </div>
          )}
        </button>
      </div>
      
      <p className="text-xs text-center text-gray-400 pt-4 border-t">
        🔒 Secure SHA-256 encrypted transaction. Your financial data is never stored on our servers.
      </p>
    </div>
  );
}

export default ShareholdersPaymentMethodSelector;