import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';
import React from 'react';

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
 export interface NavItems {
     title: string;
     href: string;
     icon?: LucideIcon | null;
     isActive?: boolean;
     items?: NavItem[];
     component?: React.ComponentType | null;
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
    level: number | null;
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
        session_id: string | null;
        phone_number_verified_name: string;
        display_phone_number: string;
    }
    status: number;
    [key: string]: unknown;
}

interface FlashMessages {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
}

interface Organization {
    id: number;
    name: string;
    website?: string | null;
    address?: string | null;
    timezone?: string | null;
    locale?: string | null;
}

interface Organizations {
    list: Organization[];
    current: Organization;
}

interface PageProps {
    auth: Auth;
    flash: FlashMessages;
    organization: Organizations;
    chatbot: Chatbot;
    [key: string]: unknown;
}

export interface Agent {
    id: number;
    name: string;
}

export interface Chat {
    id: number
    name: string
    phone: string
    avatar: string
    lastMessage: string
    lastMessageTime: string
    unreadCount: number
    mode: 'ai' | 'human'
    assigned_user_id: number | null
    assigned_user_name: string | null
    recipient: string | null
}
