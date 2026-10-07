const STORAGE_KEY = 'alpha_bridge_shares';

const initializeShares = () => {
  if (!localStorage.getItem(STORAGE_KEY)) {
    const initialShares = [
      { id: '1', name: 'Series A', totalShares: 1000, remainingShares: 450, price: 500, createdAt: new Date().toISOString() }
    ];
    localStorage.setItem(STORAGE_KEY, JSON.stringify(initialShares));
  }
};

initializeShares();

export const getShares = async () => {
  const data = localStorage.getItem(STORAGE_KEY);
  return data ? JSON.parse(data) : [];
};

export const getShareById = async (id) => {
  const shares = await getShares();
  return shares.find(s => s.id === id);
};

export const createShare = async (shareData) => {
  const shares = await getShares();
  const newShare = {
    id: Date.now().toString(),
    name: shareData.name,
    totalShares: Number(shareData.totalShares),
    remainingShares: Number(shareData.totalShares), // Initially same as total
    price: Number(shareData.price),
    createdAt: new Date().toISOString()
  };
  shares.push(newShare);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(shares));
  return newShare;
};

export const updateShare = async (id, updates) => {
  const shares = await getShares();
  const index = shares.findIndex(s => s.id === id);
  if (index === -1) throw new Error('Share not found');
  
  const updatedShare = { ...shares[index], ...updates };
  // Ensure numeric values
  if (updates.totalShares) updatedShare.totalShares = Number(updates.totalShares);
  if (updates.remainingShares) updatedShare.remainingShares = Number(updates.remainingShares);
  if (updates.price) updatedShare.price = Number(updates.price);
  
  shares[index] = updatedShare;
  localStorage.setItem(STORAGE_KEY, JSON.stringify(shares));
  return updatedShare;
};

export const deleteShare = async (id) => {
  let shares = await getShares();
  shares = shares.filter(s => s.id !== id);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(shares));
  return true;
};