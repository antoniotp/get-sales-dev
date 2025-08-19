import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Chatbot {
    id: number;
    organization_id: number;
    name: string;
    description: string | null;
    status: number;
    created_at: string;
}

export interface Channel {
    id: number;
    name: string;
    slug: string;
    icon: string;
    status: number;
    [key: string]: unknown;
}

export interface ChatbotChannel {
    id: number;
    chatbot_id: number;
    channel_id: number;
    data: {
        phone_number_verified_name: string;
        display_phone_number: string;
    }
    status: number;
    [key: string]: unknown;
}
