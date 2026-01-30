import { ControllerRenderProps } from 'react-hook-form';
import { ReactNode } from 'react';
import { TemplateFormValues } from '@/pages/message_templates/form';

// Main entity types
export interface Category {
    id: number;
    name: string;
}

export type HeaderType = 'none' | 'text' | 'image' | 'video' | 'document';

export interface ButtonConfig {
    type: 'reply' | 'url' | 'call';
    text: string;
    url?: string;
    phone_number?: string;
}

export interface VariableSchema {
    placeholder: string;
    example?: string;
}

export interface Template {
    id?: number;
    name: string;
    category_id: number;
    language: string;
    header_type: HeaderType;
    header_content?: string;
    body_content: string;
    footer_content?: string;
    button_config?: ButtonConfig[] | null;
    variables_schema: VariableSchema[] | null;
}

// Prop types for components
export interface TemplateFormPageProps {
    categories: Category[];
    template: Template | null;
}

export interface HeaderTypeButtonProps {
    field: ControllerRenderProps<TemplateFormValues, 'header_type'>;
    value: HeaderType;
    icon: ReactNode;
    label: string;
    currentType: HeaderType;
}
