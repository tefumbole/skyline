import React, { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

const SharePurchasePortal = () => {
  const navigate = useNavigate();
  
  useEffect(() => {
    navigate('/shares');
  }, [navigate]);

  return <div className="p-10 text-center">Redirecting to new shares portal...</div>;
};

export default SharePurchasePortal;