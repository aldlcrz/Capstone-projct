/**
 * lib/validation.ts — Shared input validators
 * Mirrors web lib/inputValidation.js for mobile use
 */

export function validatePersonName(value: string, fieldName = 'Name'): string {
  const trimmed = value.trim();
  if (!trimmed) throw new Error(`${fieldName} is required.`);
  if (trimmed.length < 2) throw new Error(`${fieldName} must be at least 2 characters.`);
  if (trimmed.length > 100) throw new Error(`${fieldName} must be at most 100 characters.`);
  if (!/^[a-zA-ZÀ-ÿ\s\-'.]+$/.test(trimmed)) throw new Error(`${fieldName} contains invalid characters.`);
  return trimmed;
}

export function validateEmail(value: string): string {
  const trimmed = value.trim().toLowerCase();
  if (!trimmed) throw new Error('Email address is required.');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) throw new Error('Please enter a valid email address.');
  if (trimmed.length > 254) throw new Error('Email address is too long.');
  return trimmed;
}

export function validatePassword(value: string): string {
  if (!value) throw new Error('Password is required.');
  if (value.length < 6) throw new Error('Password must be at least 6 characters.');
  if (value.length > 128) throw new Error('Password is too long.');
  return value;
}

export function validatePhilippineMobileNumber(value: string, fieldName = 'Mobile number'): string {
  const cleaned = value.replace(/\D/g, '');
  if (!cleaned) throw new Error(`${fieldName} is required.`);
  if (!/^(09\d{9}|\+639\d{9})$/.test(cleaned)) throw new Error(`${fieldName} must be a valid Philippine mobile number (e.g. 09XXXXXXXXX).`);
  return cleaned;
}

export function validatePaymentReference(value: string, fieldName = 'Reference number'): string {
  const cleaned = value.replace(/\s/g, '');
  if (!cleaned) throw new Error(`${fieldName} is required.`);
  if (cleaned.length < 5) throw new Error(`${fieldName} must be at least 5 digits.`);
  if (cleaned.length > 30) throw new Error(`${fieldName} is too long.`);
  return cleaned;
}
