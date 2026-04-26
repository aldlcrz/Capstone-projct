const axios = require('axios');
const nodemailer = require('nodemailer');

const RESEND_API_URL = process.env.RESEND_API_URL || 'https://api.resend.com/emails';
const PLACEHOLDER_GMAIL_USER = 'REPLACE_WITH_YOUR_GMAIL_ADDRESS';

const normalizeEnvValue = (value) => String(value || '').trim();

const sendWithResend = async ({ email, subject, html, text }) => {
  const apiKey = normalizeEnvValue(process.env.RESEND_API_KEY);
  const from = normalizeEnvValue(process.env.MAIL_FROM);

  if (!apiKey || !from) return false;

  try {
    await axios.post(
      RESEND_API_URL,
      { from, to: email, subject, html, text },
      { headers: { Authorization: `Bearer ${apiKey}`, 'Content-Type': 'application/json' } }
    );
    return true;
  } catch (error) {
    console.error('Resend delivery failed:', error.response?.data || error.message);
    return false;
  }
};

const sendWithGmail = async ({ email, subject, html, text }) => {
  const user = normalizeEnvValue(process.env.GMAIL_USER);
  const pass = normalizeEnvValue(process.env.GMAIL_APP_PASS).replace(/\s+/g, '');

  if (!user || !pass) return false;
  if (user === PLACEHOLDER_GMAIL_USER || !user.includes('@')) return false;

  try {
    const transporter = nodemailer.createTransport({
      service: 'gmail',
      auth: { user, pass },
    });

    await transporter.sendMail({
      from: `"LumbaRong Support" <${user}>`,
      to: email,
      subject,
      text,
      html,
    });
    return true;
  } catch (error) {
    console.error('Gmail delivery failed:', error.message);
    return false;
  }
};

exports.sendPasswordResetEmail = async ({ email, name, otp, expiresInMinutes }) => {
  const safeName = name || 'there';
  const subject = 'Your LumbaRong Password Recovery Code';
  const text = `Hello ${safeName}, your 6-digit password recovery code is: ${otp}. This code expires in ${expiresInMinutes} minutes.`;
  const html = `
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px;">
      <h2 style="color: #c0422a; margin-bottom: 16px;">Password Recovery</h2>
      <p>Hello <strong>${safeName}</strong>,</p>
      <p>You requested to reset your LumbaRong password. Use the code below to continue:</p>
      <div style="margin: 32px 0; padding: 24px; background: #f9fafb; border-radius: 12px; text-align: center;">
        <span style="font-family: monospace; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #1c1917;">${otp}</span>
      </div>
      <p style="font-size: 14px; color: #6b7280;">This code will expire in <strong>${expiresInMinutes} minutes</strong>. If you did not request this, please ignore this email.</p>
      <hr style="margin: 32px 0; border: 0; border-top: 1px solid #e5e7eb;" />
      <p style="font-size: 12px; color: #9ca3af; text-align: center;">LumbaRong &copy; 2026 | Artisan Registry of Lumban</p>
    </div>
  `;

  // Try Resend first
  if (await sendWithResend({ email, subject, html, text })) {
    return { provider: 'resend' };
  }

  // Try Gmail second
  if (await sendWithGmail({ email, subject, html, text })) {
    return { provider: 'gmail' };
  }

  if (process.env.NODE_ENV === 'production') {
    throw new Error('No email provider is configured or all providers failed');
  }

  console.warn(`[password-reset] Email provider not configured. OTP for ${email}: ${otp}`);
  return { provider: 'console' };
};
