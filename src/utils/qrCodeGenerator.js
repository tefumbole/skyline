import QRCode from 'qrcode';

/**
 * Generates a Base64 QR Code string for various data types.
 * @param {Object} data - Data to encode. Must include a 'type' field.
 * @returns {Promise<string>} - Base64 data URL of the QR code
 */
export const generateShareholderQRCode = async (data) => {
  try {
    // Determine structure based on type, or default to generic dump
    let qrContent = {};

    if (data.type === 'shareholder') {
        qrContent = {
            type: 'shareholder',
            name: data.fullName,
            email: data.email,
            phone: data.phone,
            company: data.companyName,
            shares: data.shares,
            amount: data.totalAmount,
            payment_method: data.paymentMethod,
            txId: data.transactionId || 'PENDING',
            date: data.createdAt || new Date().toISOString()
        };
    } else if (data.type === 'event_attendee') {
        qrContent = {
            type: 'event_attendee',
            name: data.fullName,
            email: data.email,
            phone: data.phone,
            company: data.companyName,
            event: data.eventName,
            date: data.registrationDate,
            status: data.approvalStatus || 'approved',
            txId: data.transactionId || 'N/A'
        };
    } else if (data.type === 'master_class_student') {
        qrContent = {
            type: 'master_class_student',
            name: data.fullName,
            email: data.email,
            phone: data.phone,
            company: data.companyName,
            module: data.module,
            date: data.registrationDate,
            status: data.paymentStatus,
            payment_method: data.paymentMethod,
            amount: data.amount,
            txId: data.transactionId || 'PENDING'
        };
    } else {
        // Fallback for generic usage
        qrContent = { ...data };
    }

    const jsonString = JSON.stringify(qrContent);

    // Generate Data URL
    const dataUrl = await QRCode.toDataURL(jsonString, {
      width: 400,
      margin: 2,
      color: {
        dark: '#003366', // Navy Blue
        light: '#ffffff'
      }
    });

    return dataUrl;
  } catch (error) {
    console.error("QR Code Generation Error:", error);
    throw error;
  }
};