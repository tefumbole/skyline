import { Router } from 'express';
import multer from 'multer';
import { requireAuth } from '../middleware/auth.js';
import {
  sendTextMessage,
  sendDocumentMessage,
  sendImageMessage,
  uploadBufferToWasender,
  sendTextThenDocumentBuffer,
  isWasenderConfigured,
  formatPhoneNumber,
} from '../services/wasenderWhatsAppService.js';

const router = Router();
const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 50 * 1024 * 1024 },
});

router.get('/status', (_req, res) => {
  res.json({ configured: isWasenderConfigured() });
});

router.post('/send', requireAuth, async (req, res) => {
  try {
    const { to, text, documentUrl, imageUrl, fileName } = req.body || {};

    if (!to) {
      return res.status(400).json({ success: false, error: 'Phone number (to) is required' });
    }

    const formatted = formatPhoneNumber(to);
    if (!formatted) {
      return res.status(400).json({
        success: false,
        error: `Invalid phone number: ${to}. Use international format e.g. +237675321739`,
      });
    }

    let result;
    if (documentUrl) {
      result = await sendDocumentMessage(formatted, documentUrl, text || null, fileName || null);
    } else if (imageUrl) {
      result = await sendImageMessage(formatted, imageUrl, text || null);
    } else {
      result = await sendTextMessage(formatted, text || '', 'text');
    }

    res.status(result.success ? 200 : 502).json(result);
  } catch (err) {
    console.error('[whatsapp/send]', err);
    res.status(500).json({ success: false, error: err.message || 'WhatsApp send failed' });
  }
});

router.post('/upload-buffer', requireAuth, upload.single('file'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ success: false, error: 'File is required' });
    }

    const result = await uploadBufferToWasender(
      req.file.buffer,
      req.file.mimetype || 'application/octet-stream'
    );

    res.status(result.success ? 200 : 502).json(result);
  } catch (err) {
    console.error('[whatsapp/upload-buffer]', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.post('/send-document', requireAuth, (req, res, next) => {
  upload.single('file')(req, res, (err) => {
    if (err) {
      const message = err.code === 'LIMIT_FILE_SIZE'
        ? 'PDF file is too large. Try again after the page reloads with the optimized PDF generator.'
        : err.message;
      return res.status(400).json({ success: false, error: message });
    }
    next();
  });
}, async (req, res) => {
  try {
    const { to, text, fileName } = req.body || {};
    if (!to || !req.file) {
      return res.status(400).json({ success: false, error: 'Phone number (to) and file are required' });
    }

    const result = await sendTextThenDocumentBuffer(
      to,
      text || '',
      req.file.buffer,
      fileName || req.file.originalname || 'document.pdf',
      req.file.mimetype || 'application/pdf'
    );

    res.status(result.success ? 200 : 502).json(result);
  } catch (err) {
    console.error('[whatsapp/send-document]', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.post('/format-phone', (req, res) => {
  const { phone, country } = req.body || {};
  res.json({ formatted: formatPhoneNumber(phone, country) });
});

export default router;
