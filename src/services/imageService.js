export const validateImageFile = (file) => {
  const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
  if (!validTypes.includes(file.type)) {
    return { valid: false, error: 'File must be a JPG, PNG, or WebP image.' };
  }
  
  const maxSize = 5 * 1024 * 1024; // 5MB
  if (file.size > maxSize) {
    return { valid: false, error: 'File size must be less than 5MB.' };
  }
  
  return { valid: true };
};

export const getImageDimensions = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        resolve({ width: img.width, height: img.height });
      };
      img.onerror = () => reject(new Error('Failed to load image for dimension calculation'));
      img.src = e.target.result;
    };
    reader.onerror = () => reject(new Error('Failed to read file'));
    reader.readAsDataURL(file);
  });
};

export const generateThumbnail = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = 200;
        canvas.height = 150;
        const ctx = canvas.getContext('2d');
        
        // Cover crop
        const scale = Math.max(canvas.width / img.width, canvas.height / img.height);
        const x = (canvas.width / scale - img.width) / 2;
        const y = (canvas.height / scale - img.height) / 2;
        
        ctx.drawImage(img, x, y, img.width, img.height, 0, 0, img.width * scale, img.height * scale);
        resolve(canvas.toDataURL('image/jpeg', 0.7));
      };
      img.onerror = () => reject(new Error('Failed to load image for thumbnail'));
      img.src = e.target.result;
    };
    reader.onerror = () => reject(new Error('Failed to read file'));
    reader.readAsDataURL(file);
  });
};