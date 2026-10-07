import QRCode from 'qrcode';

export const generateQRCode = async (text) => {
  try {
    const url = await QRCode.toDataURL(text, {
      width: 400,
      margin: 2,
      color: {
        dark: '#003D82',
        light: '#FFFFFF'
      }
    });
    return url;
  } catch (err) {
    console.error("QR Code Generation Error", err);
    throw new Error("Failed to generate QR Code");
  }
};