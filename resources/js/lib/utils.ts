import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import parsePhoneNumber from 'libphonenumber-js';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export const formatPhoneNumber = (phone: string): string => {
    if (!phone) return '';
    if (!phone.startsWith('+')) {
        phone = '+' + phone;
    }
    try {
        const phoneNumber = parsePhoneNumber(phone);
        return phoneNumber ? phoneNumber.formatInternational() : phone;
    } catch {
        console.warn('Failed to parse phone number:', phone);
        return phone;
    }
};