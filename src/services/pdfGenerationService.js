import { supabase } from '@/lib/customSupabaseClient';
import QRCode from 'qrcode';
import JsBarcode from 'jsbarcode';
import html2pdf from 'html2pdf.js';
import { format } from 'date-fns';
import { getSystemSettings } from '@/services/settingsService';
import {
  buildPdfVerificationBlockHtml,
  wrapPdfContent,
} from '@/utils/pdfLetterhead';

const BUCKET_NAME = 'system-assets';
const PDF_FOLDER = 'message-pdfs';

export const generateQRCode = async (text) => {
  try {
    return await QRCode.toDataURL(text, { errorCorrectionLevel: 'H' });
  } catch (error) {
    console.error('Error generating QR code:', error);
    return null;
  }
};

export const generateBarcode = (text) => {
  try {
    const canvas = document.createElement('canvas');
    JsBarcode(canvas, text, {
      format: 'CODE128',
      displayValue: true,
      fontSize: 14,
      height: 40,
      margin: 10
    });
    return canvas.toDataURL('image/png');
  } catch (error) {
    console.error('Error generating Barcode:', error);
    return null;
  }
};

export const personalizeContent = (template, recipientData) => {
  if (!template) return '';
  let content = template;
  const today = format(new Date(), 'dd MMMM yyyy');

  const replacements = {
    '{name}': recipientData.name || 'Recipient',
    '{email}': recipientData.email || '',
    '{phone}': recipientData.phone || '',
    '{date}': today
  };

  for (const [key, value] of Object.entries(replacements)) {
    // Replace all instances of the variable (using global regex)
    const regex = new RegExp(key.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1"), 'g');
    content = content.replace(regex, value);
  }

  return content;
};

export const generateMessagePDFHTML = async (messageData, recipientData, settings = {}, brandingSettings = null) => {
  const personalizedBody = personalizeContent(messageData.body, recipientData);
  const qrCodeDataUrl = recipientData.verification_url ? await generateQRCode(recipientData.verification_url) : null;
  const barcodeDataUrl = recipientData.reference_code ? generateBarcode(recipientData.reference_code) : null;

  const branding = brandingSettings || settings.branding || settings;
  const verificationBlock = buildPdfVerificationBlockHtml({
    barcodeDataUrl,
    qrCodeDataUrl,
    label: 'Scan to Verify',
  });

  const bodyHtml = `
      <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
          <p style="margin: 0; font-weight: bold;">Date: ${format(new Date(), 'dd MMM yyyy')}</p>
          <p style="margin: 5px 0 0 0; font-weight: bold;">Ref: ${recipientData.reference_code || 'N/A'}</p>
        </div>
      </div>

      <div style="margin-bottom: 30px;">
        <h2 style="margin: 0 0 10px 0; color: #111;">${messageData.subject}</h2>
      </div>

      <div style="margin-bottom: 40px; white-space: pre-wrap;">
        ${personalizedBody}
      </div>

      ${verificationBlock}
  `;

  return wrapPdfContent(bodyHtml, branding);
};

export const generateAndUploadPDF = async (htmlContent, fileName) => {
  try {
    // Create a temporary element to hold the HTML
    const element = document.createElement('div');
    element.innerHTML = htmlContent;
    
    // Configure html2pdf
    const opt = {
      margin:       0,
      filename:     fileName,
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, useCORS: true },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    // Generate blob
    const pdfBlob = await html2pdf().set(opt).from(element).output('blob');
    
    // Upload to Supabase Storage
    const filePath = `${PDF_FOLDER}/${fileName}`;
    const { error: uploadError } = await supabase.storage
      .from(BUCKET_NAME)
      .upload(filePath, pdfBlob, {
        contentType: 'application/pdf',
        upsert: true
      });

    if (uploadError) throw uploadError;

    // Get public URL
    const { data: { publicUrl } } = supabase.storage
      .from(BUCKET_NAME)
      .getPublicUrl(filePath);

    return publicUrl;
  } catch (error) {
    console.error('Error generating or uploading PDF:', error);
    throw error;
  }
};

export const generatePDFsForMessage = async (messageId, messageData, recipients) => {
  try {
    const [{ data: settingsData }, brandingSettings] = await Promise.all([
      supabase.from('message_settings').select('*').limit(1).single(),
      getSystemSettings(),
    ]);

    const settings = settingsData || {};
    let successCount = 0;
    let failCount = 0;

    // Process sequentially to not overload browser memory
    for (const recipient of recipients) {
      try {
        const htmlContent = await generateMessagePDFHTML(messageData, recipient, settings, brandingSettings);
        const fileName = `${recipient.reference_code}.pdf`;
        const pdfUrl = await generateAndUploadPDF(htmlContent, fileName);

        // Update recipient record with pdf_url
        if (pdfUrl && recipient.id) {
          await supabase
            .from('message_recipients')
            .update({ pdf_url: pdfUrl })
            .eq('id', recipient.id);
          successCount++;
        } else {
            failCount++;
        }
      } catch (err) {
        console.error(`Failed to generate PDF for recipient ${recipient.name}:`, err);
        failCount++;
      }
    }

    return { successCount, failCount, total: recipients.length };
  } catch (error) {
    console.error('Error in batch PDF generation:', error);
    throw error;
  }
};