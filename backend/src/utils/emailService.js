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

exports.sendPasswordResetEmail = async ({ email, name, resetUrl, expiresInMinutes }) => {
  const safeName = name || 'there';
  const subject = 'Reset your LumbaRong password';
  const text = `Hello ${safeName}, use this link to reset your LumbaRong password: ${resetUrl}. This link expires in ${expiresInMinutes} minutes.`;
  const html = `
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937;">
      <h2 style="margin-bottom: 8px;">Reset your LumbaRong password</h2>
      <p>Hello ${safeName},</p>
      <p>Click the button below to set a new password. This link expires in ${expiresInMinutes} minutes.</p>
      <p style="margin: 24px 0;">
        <a href="${resetUrl}" style="display: inline-block; padding: 12px 18px; background: #c0422a; color: #ffffff; text-decoration: none; border-radius: 999px;">Reset Password</a>
      </p>
      <p>If the button does not work, copy and paste this link into your browser:</p>
      <p><a href="${resetUrl}">${resetUrl}</a></p>
      <p>If you did not request this reset, you can ignore this email.</p>
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

  console.warn(`[password-reset] Email provider not configured. Reset link for ${email}: ${resetUrl}`);
  return { provider: 'console' };
};
