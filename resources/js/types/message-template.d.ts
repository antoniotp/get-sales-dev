import { ControllerRenderProps } from 'react-hook-form';
import { ReactNode } from 'react';
import { TemplateFormValues } from '@/pages/message_templates/form';
import { ChatbotChannel as GlobalChatbotChannel, Channel } from '@/types';

// Main entity types
export interface Category {
    id: number;
    name: string;
}

export interface ChatbotChannel extends GlobalChatbotChannel {
    channel: Channel;
}

export type HeaderType = 'none' | 'text' | 'image' | 'video' | 'document';
export type VariableType = 'positional' | 'named';

export interface ButtonConfig {
    type: 'QUICK_REPLY' | 'URL' | 'PHONE_NUMBER' | 'COPY_CODE';
    text: string;
    url?: string | null;
    phone_number?: string;
}

export interface VariableSchema {
    placeholder: string;
    example: string;
}

export interface Template {
    id?: number;
    display_name?: string;
    name: string;
    category_id: number;
    chatbot_channel_id: number;
    chatbot_channel?: ChatbotChannel;
    language: string;
    header_type: HeaderType;
    header_content?: string;
    header_variable?: VariableSchema | null;
    header_variable_type?: VariableType;
    body_content: string;
    footer_content?: string;
    button_config?: ButtonConfig[] | null;
    variables_schema: VariableSchema[] | null;
    variable_type?: VariableType;
}

// Prop types for components
export interface TemplateFormPageProps {
    categories: Category[];
    chatbotChannels: ChatbotChannel[];
    template: Template | null;
    availableLanguages: { code: string; name: string }[];
}

export interface HeaderTypeButtonProps {
    field: ControllerRenderProps<TemplateFormValues, 'header_type'>;
    value: HeaderType;
    icon: ReactNode;
    label: string;
    currentType: HeaderType;
}
