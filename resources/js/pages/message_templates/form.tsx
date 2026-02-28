import AppLayout from '@/layouts/app-layout';
import MessageTemplateLayout from '@/layouts/message_templates/layout';
import { BreadcrumbItem, PageProps } from '@/types';
import { Card, CardContent } from "@/components/ui/card";
import { Head, useForm as useInertiaForm, usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import * as z from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { useEffect, useMemo, useRef, useState, Fragment } from 'react';
import { Pilcrow, AlertCircle, Trash2/*, File, Image as ImageIcon, Video*/ } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

import {
    TemplateFormPageProps,
} from '@/types/message-template.d';
import HeaderTypeButton from '@/components/message_templates/HeaderTypeButton';
import { generateSlug } from '@/lib/utils';
import MessagePreview from '@/components/message_templates/MessagePreview';
import { ButtonsSection } from '@/components/message_templates/ButtonsSection';

// --- Zod Schemas ---
const variableSchemaItem = z.object({
    placeholder: z.string(),
    example: z.string().min(1, 'Example value is required'),
});

const buttonConfigItem = z.object({
    type: z.enum(['QUICK_REPLY', 'URL', 'PHONE_NUMBER', 'COPY_CODE']),
    text: z.string().min(1, 'Button text is required'),
    url: z.string().nullable().optional(),
    phone_number: z.string().optional(),
}).superRefine((data, ctx) => {
    if (data.type === 'URL') {
        if (!data.url || data.url.trim() === '') {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: 'URL is required for URL buttons.',
                path: ['url'],
            });
        } else {
            const urlSchema = z.string().url();
            const result = urlSchema.safeParse(data.url);
            if (!result.success) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: 'Must be a valid URL.',
                    path: ['url'],
                });
            }
        }
    }
});

const formSchema = z.object({
    chatbot_channel_id: z.number({ required_error: 'Channel is required' }).min(1, 'Channel is required'),
    display_name: z.string().min(1, 'Display name is required'),
    name: z.string().min(1, 'Template name is required'),
    category_id: z.number({ required_error: 'Category is required' }).min(1, 'Category is required'),
    language: z.string().min(1, 'Language is required'),
    header_type: z.enum(['none', 'text', 'image', 'video', 'document']),
    header_content: z.string().optional(),
    header_variable: variableSchemaItem.nullable().optional(),
    header_variable_type: z.enum(['positional', 'named']).optional(),
    header_variable_mapping: z.object({
        placeholder: z.string(),
        source: z.string(),
        label: z.string(),
        fallback_value: z.string().nullable().optional(),
    }).nullable().optional(),
    body_content: z.string().min(1, 'Message content is required'),
    footer_content: z.string().optional(),
    button_config: z.array(buttonConfigItem).nullable(),
    variables_schema: z.array(variableSchemaItem).nullable(),
    variable_type: z.enum(['positional', 'named']).optional(),
    variable_mappings: z.array(z.object({
        placeholder: z.string(),
        source: z.string(), // e.g., "contact.first_name"
        label: z.string(), // e.g., "Contact: Name"
        fallback_value: z.string().nullable().optional(),
    })).nullable(),
});

export type TemplateFormValues = z.infer<typeof formSchema>;

// --- Helper Functions ---
const extractPlaceholders = (text: string): string[] => {
    const regex = /\{\{(\d+|[a-zA-Z_][a-zA-Z0-9_]*)}}/g;
    const matches = text.matchAll(regex);
    const placeholders: string[] = [];
    for (const match of matches) {
        const placeholder = `{{${match[1]}}}`;
        if (!placeholders.includes(placeholder)) {
            placeholders.push(placeholder);
        }
    }
    return placeholders;
};

const detectVariableType = (placeholders: string[]): 'positional' | 'named' | 'mixed' | null => {
    if (placeholders.length === 0) return null;

    const hasPositional = placeholders.some(p => /^\{\{\d+}}$/.test(p));
    const hasNamed = placeholders.some(p => /^\{\{[a-zA-Z_][a-zA-Z0-9_]*}}$/.test(p));

    if (hasPositional && hasNamed) return 'mixed';
    if (hasPositional) return 'positional';
    if (hasNamed) return 'named';
    return null;
};

const getNextPositionalNumber = (placeholders: string[]): number => {
    const numbers = placeholders
        .filter(p => /^\{\{\d+}}$/.test(p))
        .map(p => parseInt(p.replace(/[{}]/g, '')))
        .sort((a, b) => a - b);

    if (numbers.length === 0) return 1;
    return Math.max(...numbers) + 1;
};

const WABA_CHANNEL_ID = 1;

// --- Main Component ---
export default function TemplateForm({ categories, chatbotChannels, template, availableLanguages, availableVariables }: TemplateFormPageProps) {
    const { props } = usePage<PageProps>();
    const headerInputRef = useRef<HTMLInputElement>(null);
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const [namedVariableName, setNamedVariableName] = useState('');
    const [namedHeaderVariableName, setNamedHeaderVariableName] = useState('');
    const [variableTypeError, setVariableTypeError] = useState<string | null>(null);
    const [dbSelectValue, setDbSelectValue] = useState<string>('');
    const [headerDbSelectValue, setHeaderDbSelectValue] = useState<string>('');

    const breadcrumbs: BreadcrumbItem[] = useMemo(() => [
        { title: 'Message Templates', href: route('message-templates.index', props.chatbot.id) },
        { title: template ? 'Edit Template' : 'Create Template', href: template ? route('message-templates.edit', { id: template.id }) : route('message-templates.create') },
    ], [props.chatbot, template]);

    const { data: inertiaData, setData: setInertiaData, post, put, processing } = useInertiaForm<TemplateFormValues>(
        template
            ? {
                ...template,
                chatbot_channel_id: template.chatbot_channel_id || 0,
                display_name: template.display_name || template.name,
                header_content: template.header_content || '',
                header_variable: template.header_variable || null,
                header_variable_type: template.header_variable_type || template.variable_type || 'named',
                header_variable_mapping: template.header_variable_mapping || null,
                footer_content: template.footer_content || '',
                button_config: template.button_config || null,
                variable_type: template.variable_type || 'named',
                variable_mappings: template.variable_mappings || null,
            }
            : {
                chatbot_channel_id: chatbotChannels[0]?.id || 0, // Set default if available, otherwise 0 for validation
                display_name: '',
                name: '',
                category_id: 0,
                language: 'es',
                header_type: 'none',
                header_content: '',
                header_variable: null,
                header_variable_type: 'named',
                header_variable_mapping: null,
                body_content: '',
                footer_content: '',
                button_config: null,
                variables_schema: null,
                variable_type: 'named',
                variable_mappings: null,
            }
    );

    const form = useForm<TemplateFormValues>({
        resolver: zodResolver(formSchema),
        defaultValues: inertiaData,
        mode: 'onBlur',
    });

    useEffect(() => {
        const subscription = form.watch((value) => {
            setInertiaData(value as TemplateFormValues);
        });
        return () => subscription.unsubscribe();
    }, [form, setInertiaData]);

    useEffect(() => {
        const errors = form.formState.errors;
        if (Object.keys(errors).length > 0) {
            console.log('--- Form Validation Errors ---');
            console.log(errors);
        }
    }, [form.formState.errors]);


    const onSubmit = () => {
        if (template?.id) {
            put(route('message-templates.update', { template: template.id }), {
                preserveScroll: true,
            });
        } else {
            post(route('message-templates.store', props.chatbot.id), {
                preserveScroll: true,
                onSuccess: () => form.reset(),
            });
        }
    };

    // which chatbot_channel is related to WABA channel.
    const watchedHeaderType = form.watch('header_type');
    const watchedHeaderContent = form.watch('header_content');
    const watchedHeaderVariable = form.watch('header_variable');
    const watchedHeaderVariableType = form.watch('header_variable_type');
    const watchedBodyContent = form.watch('body_content');
    const watchedVariableType = form.watch('variable_type');
    const watchedVariablesSchema = form.watch('variables_schema');
    const selectedChabotChannelId = form.watch('chatbot_channel_id');
    const watchedVariableMappings = form.watch('variable_mappings');

    // --- Header Variables Logic ---
    const detectedHeaderPlaceholders = useMemo(() => {
        return extractPlaceholders(watchedHeaderContent || '');
    }, [watchedHeaderContent]);

    const hasHeaderVariable = !!watchedHeaderVariable;

    const insertHeaderPlaceholder = (placeholder: string) => {
        const input = headerInputRef.current;
        if (!input) return;

        const start = input.selectionStart || 0;
        const end = input.selectionEnd || 0;
        const currentValue = watchedHeaderContent || '';
        const newValue = currentValue.substring(0, start) + placeholder + currentValue.substring(end);
        form.setValue('header_content', newValue, { shouldValidate: true });

        setTimeout(() => {
            input.focus();
            const newCursorPos = start + placeholder.length;
            input.setSelectionRange(newCursorPos, newCursorPos);
        }, 0);
    };

    const handleAddHeaderPlaceholder = () => {
        if (detectedHeaderPlaceholders.length > 0) {
            form.setError('header_content', { type: 'manual', message: 'Only one placeholder is allowed in the header.' });
            return;
        }

        let placeholder: string;
        let placeholderName: string = '';

        if (watchedHeaderVariableType === 'positional') {
            placeholder = '{{1}}';
        } else {
            placeholderName = namedHeaderVariableName.trim();

            if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(placeholderName)) {
                form.setError('header_content', {
                    type: 'manual',
                    message: 'Variable name must start with a letter or underscore and contain only letters, numbers, and underscores.'
                });
                return;
            }

            const isDbVariableConflict = availableVariables.some(dbVar => dbVar.placeholder_name === placeholderName);
            if (isDbVariableConflict) {
                form.setError('header_content', {
                    type: 'manual',
                    message: `The name "${placeholderName}" is reserved for a database variable. Please choose a different name.`
                });
                return;
            }

            placeholder = `{{${placeholderName}}}`;
        }

        insertHeaderPlaceholder(placeholder);
        form.setValue('header_variable', { placeholder, example: '' }, { shouldValidate: true });

        if (watchedHeaderVariableType === 'named') {
            form.setValue('header_variable_mapping', {
                placeholder: placeholder,
                source: 'manual',
                label: `Manual: ${placeholderName}`,
                fallback_value: null,
            }, { shouldValidate: true });

            setNamedHeaderVariableName('');
        } else {
            form.setValue('header_variable_mapping', null, { shouldValidate: true });
        }
    };

    const handleAddDbHeaderPlaceholder = (variable: (typeof availableVariables)[number]) => {
        form.setValue('header_variable_type', 'named', { shouldValidate: true });

        const placeholderText = `{{${variable.placeholder_name}}}`;
        insertHeaderPlaceholder(placeholderText);

        form.setValue('header_variable', { placeholder: placeholderText, example: '' }, { shouldValidate: true });

        form.setValue(
            'header_variable_mapping',
            {
                placeholder: placeholderText,
                source: variable.source_path,
                label: variable.label,
                fallback_value: '',
            },
            { shouldValidate: true },
        );
    };

    const handleRemoveHeaderVariable = () => {
        if (!watchedHeaderVariable) return;

        const { placeholder } = watchedHeaderVariable;
        form.setValue('header_variable', null, { shouldValidate: true });

        const currentHeader = watchedHeaderContent || '';
        const escapedPlaceholder = placeholder.replace(/[{}]/g, '\\$&');
        const regex = new RegExp(escapedPlaceholder, 'g');
        const newHeader = currentHeader.replace(regex, '');
        form.setValue('header_content', newHeader, { shouldValidate: true });
        form.clearErrors('header_content');
        form.setValue('header_variable_mapping', null, { shouldValidate: true });
    };

    const syncHeaderVariable = () => {
        const placeholders = extractPlaceholders(watchedHeaderContent || '');

        if (placeholders.length > 1) {
            form.setError('header_content', { type: 'manual', message: 'Only one placeholder is allowed in the header.' });
            return;
        }

        form.clearErrors('header_content');
        if (variableTypeError?.includes('header')) {
            setVariableTypeError(null);
        }

        const placeholder = placeholders[0] || null;

        // Get the current values of header_variable and header_variable_mapping
        const currentHeaderVariable = form.getValues('header_variable');
        const currentHeaderVariableMapping = form.getValues('header_variable_mapping');

        if (placeholder) {
            let newHeaderVariableMapping = null;
            const placeholderName = placeholder.replace(/[{}]/g, '');

            // 1. Reuse existing mapping if any
            if (currentHeaderVariableMapping && currentHeaderVariableMapping.placeholder === placeholder) {
                newHeaderVariableMapping = currentHeaderVariableMapping;
            } else {
                // 2. If there is no mapping, detect if it is a DB variable
                const dbVar = availableVariables.find(v => v.placeholder_name === placeholderName);
                if (dbVar) {
                    newHeaderVariableMapping = {
                        placeholder: placeholder,
                        source: dbVar.source_path,
                        label: dbVar.label,
                        fallback_value: '',
                    };
                    // If we detect a DB var, the type must be 'named'
                    form.setValue('header_variable_type', 'named', { shouldValidate: true });
                } else {
                    // 3. If none of the above, it is a manual placeholder
                    newHeaderVariableMapping = {
                        placeholder: placeholder,
                        source: 'manual',
                        label: `${placeholderName}`,
                        fallback_value: null,
                    };
                    // the type must be 'named' if it is not positional
                    if (!/^\d+$/.test(placeholderName)) { // If it is not a number (i.e., it is not positional)
                        form.setValue('header_variable_type', 'named', { shouldValidate: true });
                    } else {
                        form.setValue('header_variable_type', 'positional', { shouldValidate: true });
                    }
                }
            }

            // Update header_variable if necessary
            if (!currentHeaderVariable || currentHeaderVariable.placeholder !== placeholder) {
                form.setValue('header_variable', { placeholder, example: currentHeaderVariable?.example || '' }, { shouldValidate: true });
            }

            // Update header_variable_mapping if it has changed
            if (JSON.stringify(currentHeaderVariableMapping) !== JSON.stringify(newHeaderVariableMapping)) {
                form.setValue('header_variable_mapping', newHeaderVariableMapping, { shouldValidate: true });
            }

        } else { // There is no placeholder in the header
            if (currentHeaderVariable) {
                form.setValue('header_variable', null, { shouldValidate: true });
            }
            if (currentHeaderVariableMapping) {
                form.setValue('header_variable_mapping', null, { shouldValidate: true });
            }
            // Reset the type to positional if there are no variables (default behavior)
            form.setValue('header_variable_type', 'positional', { shouldValidate: true });
        }
    };

    const handleHeaderExampleChange = (example: string) => {
        if (!watchedHeaderVariable) return;
        form.setValue('header_variable', { ...watchedHeaderVariable, example }, { shouldValidate: true });
    };


    // --- Body Variables Logic ---
    // Detect placeholders
    const detectedPlaceholders = useMemo(() => {
        return extractPlaceholders(watchedBodyContent || '');
    }, [watchedBodyContent]);

    // Check for added variables
    const hasVariables = (watchedVariablesSchema?.length ?? 0) > 0;

    const nextPositionalNumber = useMemo(() => {
        return getNextPositionalNumber(detectedPlaceholders);
    }, [detectedPlaceholders]);

    // Function to insert placeholder in the textarea
    const insertPlaceholder = (placeholder: string) => {
        const textarea = textareaRef.current;
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const currentValue = watchedBodyContent || '';

        const newValue = currentValue.substring(0, start) + placeholder + currentValue.substring(end);

        form.setValue('body_content', newValue, { shouldValidate: true });

        // Reset focus and position cursor after placeholder
        setTimeout(() => {
            textarea.focus();
            const newCursorPos = start + placeholder.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
        }, 0);
    };

    // Add a new placeholder
    const handleAddPlaceholder = () => {
        let placeholder: string;
        let placeholderName: string;

        if (watchedVariableType === 'positional') {
            placeholderName = '';
            placeholder = `{{${nextPositionalNumber}}}`;
        } else {
            // Named
            placeholderName = namedVariableName.trim();

            // check format
            if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(placeholderName)) {
                form.setError('body_content', {
                    type: 'manual',
                    message: 'Variable name must start with a letter or underscore and contain only letters, numbers, and underscores',
                });
                return;
            }

            placeholder = `{{${placeholderName}}}`;

            // check duplicates
            const currentSchema = watchedVariablesSchema || [];
            if (currentSchema.some(v => v.placeholder === placeholder)) {
                form.setError('body_content', {
                    type: 'manual',
                    message: `Variable ${placeholder} already exists`
                });
                return;
            }

            const isDbVariableConflict = availableVariables.some(dbVar => dbVar.placeholder_name === placeholderName);
            if (isDbVariableConflict) {
                form.setError('body_content', {
                    type: 'manual',
                    message: `The name "${placeholderName}" is reserved for a database variable. Please choose a different name.`
                });
                return;
            }
        }

        insertPlaceholder(placeholder);

        // Add to variables_schema
        const currentSchema = watchedVariablesSchema || [];
        form.setValue('variables_schema', [
            ...currentSchema,
            { placeholder, example: '' }
        ], { shouldValidate: true });

        if (watchedVariableType === 'named') {
            const currentMappings = watchedVariableMappings || [];
            form.setValue(
                'variable_mappings',
                [
                    ...currentMappings,
                    {
                        placeholder: placeholder,
                        source: 'manual',
                        label: `Manual: ${placeholderName}`, // Etiqueta descriptiva
                        fallback_value: null,
                    },
                ],
                { shouldValidate: true },
            );

            setNamedVariableName('');
        }
    };

    const handleAddDbPlaceholder = (variable: typeof availableVariables[number]) => {
        if (watchedVariableType === 'positional' && !hasVariables) {
            form.setValue('variable_type', 'named', { shouldValidate: true });
        }
        const placeholderText = `{{${variable.placeholder_name}}}`;
        insertPlaceholder(placeholderText);

        const currentSchema = watchedVariablesSchema || [];
        form.setValue('variables_schema', [
            ...currentSchema,
            { placeholder: placeholderText, example: '' }
        ], { shouldValidate: true });

        const currentMappings = watchedVariableMappings || [];
        form.setValue('variable_mappings', [
            ...currentMappings,
            {
                placeholder: placeholderText,
                source: variable.source_path,
                label: variable.label,
                fallback_value: '',
            }
        ], { shouldValidate: true });
    };

    const handleRemoveVariable = (placeholder: string) => {
        // Remove var from variables_schema
        const currentSchema = watchedVariablesSchema || [];
        const newSchema = currentSchema.filter(v => v.placeholder !== placeholder);
        form.setValue('variables_schema', newSchema.length > 0 ? newSchema : null, { shouldValidate: true });

        const currentMappings = watchedVariableMappings || [];
        const newMappings = currentMappings.filter(m => m.placeholder !== placeholder);
        form.setValue('variable_mappings', newMappings.length > 0 ? newMappings : null, { shouldValidate: true });

        // Remove var from the textarea
        const currentBody = watchedBodyContent || '';
        const escapedPlaceholder = placeholder.replace(/[{}]/g, '\\$&');
        const regex = new RegExp(escapedPlaceholder, 'g');
        const newBody = currentBody.replace(regex, '');
        form.setValue('body_content', newBody, { shouldValidate: true });
    };

    // Sync variables on textarea blur
    const handleBodyContentBlur = () => {
        const placeholders = extractPlaceholders(watchedBodyContent || '');

        // Detect mixed variable types
        const detectedType = detectVariableType(placeholders);
        if (detectedType === 'mixed') {
            setVariableTypeError('You cannot mix Positional ({{1}}) and Named ({{name}}) variables in the same template. Please use only one type.');
            form.setError('body_content', {
                type: 'manual',
                message: 'Mixed variable types detected'
            });
            return;
        } else {
            setVariableTypeError(null);
            form.clearErrors('body_content');
        }

         if (detectedType) {
             if (form.getValues('variable_type') !== detectedType) {
                 form.setValue('variable_type', detectedType, { shouldValidate: true });
             }
         }

        const currentSchema = watchedVariablesSchema || [];
        const currentMappings = watchedVariableMappings || [];

        const newSchema: typeof variableSchemaItem._type[] = [];
        const newMappings: TemplateFormValues['variable_mappings'] = [];

        // Sync all placeholders found in the text.
        placeholders.forEach(placeholder => {
            // Sync the example schema
            const existingSchemaEntry = currentSchema.find(v => v.placeholder === placeholder);
            newSchema.push(existingSchemaEntry || { placeholder, example: '' });

            const placeholderName = placeholder.replace(/[{}]/g, '');

            let mappingFound = false;

            // Re-use existing mapping if exists
            const existingMapping = currentMappings.find(m => m.placeholder === placeholder);
            if (existingMapping) {
                newMappings.push(existingMapping);
                mappingFound = true;
            }

            // if there is no mapping, detect if this is a DB variable only if it is not a number.
            if (!mappingFound && !/^\d+$/.test(placeholderName)) {
                const dbVar = availableVariables.find(v => v.placeholder_name === placeholderName);
                if (dbVar) {
                    newMappings.push({
                        placeholder: placeholder,
                        source: dbVar.source_path,
                        label: dbVar.label,
                        fallback_value: '',
                    });
                    mappingFound = true;
                }
            }

            // if it is not one of the above, take it as a manual placeholder, including positional placeholders.
            if (!mappingFound) {
                newMappings.push({
                    placeholder: placeholder,
                    source: 'manual',
                    label: `${placeholderName}`,
                    fallback_value: null,
                });
            }
        });

        // compare and update state only if there are changes to prevent re-renders
        const schemaHasChanges = JSON.stringify(currentSchema) !== JSON.stringify(newSchema);
        if (schemaHasChanges) {
            form.setValue('variables_schema', newSchema.length > 0 ? newSchema : null, { shouldValidate: true });
        }

        const mappingsHasChanges = JSON.stringify(currentMappings) !== JSON.stringify(newMappings);
        if (mappingsHasChanges) {
            form.setValue('variable_mappings', newMappings.length > 0 ? newMappings : null, { shouldValidate: true });
        }
    };

    const handleExampleChange = (placeholder: string, example: string) => {
        const currentSchema = watchedVariablesSchema || [];
        const newSchema = currentSchema.map(v =>
            v.placeholder === placeholder ? { ...v, example } : v
        );
        form.setValue('variables_schema', newSchema, { shouldValidate: true });
    };

    const handleSendToReview = () => {
        if (!template?.id) return; // Should only be available for existing templates

        // Perform the Inertia POST request to the new endpoint
        router.post(route('message-templates.send-for-review', { template: template.id }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // The backend redirect already handles the flash message
            },
            onError: (errors) => {
                console.error('Error sending for review:', errors);
            },
        });
    };

    const selectedChatbotChannel = useMemo(() => {
        return chatbotChannels.find(channel => channel.id === selectedChabotChannelId);
    }, [chatbotChannels, selectedChabotChannelId]);

    const isWabaChannelSelected = selectedChatbotChannel?.channel_id === WABA_CHANNEL_ID;

    return (
        <AppLayout breadcrumbs={breadcrumbs} customMainClassName="overflow-y-hidden">
            <Head title={template ? 'Edit Message Template' : 'Create Message Template'} />
            <MessageTemplateLayout>
                <h2 className="text-2xl font-bold mb-2">{template ? 'Update Template' : 'Create Template'}</h2>
                <div className="h-[calc(100vh-9rem)] w-full overflow-auto">
                    <Form {...form}>
                        <form onSubmit={form.handleSubmit(onSubmit)} className="flex flex-row gap-2 w-full">

                            {/* Columna del Formulario */}
                            <div className="flex-1">
                                <Card className="py-2">
                                    <CardContent className="p-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                                        {/* Sub-columna Izquierda del Formulario */}
                                        <div className="md:col-span-1 space-y-6">
                                            <FormField
                                                control={form.control}
                                                name="chatbot_channel_id"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>Channel</FormLabel>
                                                        <Select
                                                            onValueChange={(value) => field.onChange(Number(value))}
                                                            value={field.value?.toString()}
                                                            disabled={chatbotChannels.length <= 1 && !!template} // Disable if only one channel and editing
                                                        >
                                                            <FormControl>
                                                                <SelectTrigger>
                                                                    <SelectValue placeholder="Select a channel" />
                                                                </SelectTrigger>
                                                            </FormControl>
                                                            <SelectContent>
                                                                {chatbotChannels.map((channel) => (
                                                                    <SelectItem key={channel.id} value={channel.id.toString()}>
                                                                        {channel.channel.name}
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                            <FormField
                                                control={form.control}
                                                name="display_name"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>Display Name</FormLabel>
                                                        <FormControl>
                                                            <Input
                                                                placeholder="My Template Name"
                                                                {...field}
                                                                onChange={(e) => {
                                                                    field.onChange(e);
                                                                    const slug = generateSlug(e.target.value);
                                                                    form.setValue('name', slug, { shouldValidate: true });
                                                                }}
                                                            />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                            <FormField
                                                control={form.control}
                                                name="category_id"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>Category</FormLabel>
                                                        <Select
                                                            onValueChange={(value) => field.onChange(Number(value))}
                                                            value={field.value?.toString()}
                                                        >
                                                            <FormControl>
                                                                <SelectTrigger>
                                                                    <SelectValue placeholder="Select a category" />
                                                                </SelectTrigger>
                                                            </FormControl>
                                                            <SelectContent>
                                                                {categories.map((category) => (
                                                                    <SelectItem key={category.id} value={category.id.toString()}>
                                                                        {category.name}
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                            <FormField
                                                control={form.control}
                                                name="language"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>Language</FormLabel>
                                                        <Select onValueChange={field.onChange} value={field.value}>
                                                            <FormControl>
                                                                <SelectTrigger>
                                                                    <SelectValue placeholder="Select language" />
                                                                </SelectTrigger>
                                                            </FormControl>
                                                            <SelectContent>
                                                                {availableLanguages.map((lang) => (
                                                                    <SelectItem key={lang.code} value={lang.code}>
                                                                        {lang.name}
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />

                                            <div className="flex flex-col gap-2">
                                                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                                    Cancel
                                                </Button>
                                                <Button type="submit" disabled={processing}>
                                                    {processing ? 'Saving...' : 'Save'}
                                                </Button>

                                                {template && isWabaChannelSelected && (
                                                    <Button
                                                        type="button"
                                                        className="btn-whatsapp"
                                                        onClick={handleSendToReview}
                                                        disabled={processing}
                                                    >
                                                        {processing ? 'Sending...' : 'Send To Review'}
                                                    </Button>
                                                )}
                                            </div>
                                        </div>

                                        {/* Sub-columna Derecha del Formulario */}
                                        <div className="md:col-span-2 space-y-6 md:h-[calc(100vh-12rem)] md:overflow-auto">
                                            <div className="flex justify-between items-center">
                                                <h3 className="text-lg font-medium">
                                                    {form.watch('display_name') || 'Template Name'} • {form.watch('language')}
                                                </h3>
                                            </div>

                                            <div className="space-y-3 border-b pb-6 mb-6">
                                                <div>
                                                    <Label className="text-base font-semibold">Variable Format</Label>
                                                </div>
                                                <TooltipProvider>
                                                    <Tooltip delayDuration={250}>
                                                        <TooltipTrigger asChild>
                                                            <div>
                                                                <FormField
                                                                    control={form.control}
                                                                    name="variable_type"
                                                                    render={({ field }) => (
                                                                        <RadioGroup
                                                                            onValueChange={(value) => {
                                                                                field.onChange(value);
                                                                                form.setValue('header_variable_type', value as 'named' | 'positional');
                                                                            }}
                                                                            value={field.value}
                                                                            className="flex flex-col space-y-2"
                                                                            disabled={hasVariables || hasHeaderVariable}
                                                                        >
                                                                            <div className="flex items-center space-x-3">
                                                                                <RadioGroupItem value="named" id="master-named" />
                                                                                <Label htmlFor="master-named" className="font-normal cursor-pointer">
                                                                                    <strong>Named:</strong> Use descriptive names like {"{{customer_name}}"}. (Recommended)
                                                                                </Label>
                                                                            </div>
                                                                            <div className="flex items-center space-x-3">
                                                                                <RadioGroupItem value="positional" id="master-positional" />
                                                                                <Label htmlFor="master-positional" className="font-normal cursor-pointer">
                                                                                    <strong>Positional:</strong> Use numbers like {"{{1}}"}.
                                                                                </Label>
                                                                            </div>
                                                                        </RadioGroup>
                                                                    )}
                                                                />
                                                            </div>
                                                        </TooltipTrigger>
                                                        {(hasVariables || hasHeaderVariable) && (
                                                            <TooltipContent
                                                                className="border-amber-600 bg-amber-500 text-white [&_svg]:!bg-amber-500 [&_svg]:!fill-amber-500"
                                                                side="bottom"
                                                            >
                                                                <p>Remove all variables to change the format</p>
                                                            </TooltipContent>
                                                        )}
                                                    </Tooltip>
                                                </TooltipProvider>
                                            </div>

                                            <div className="space-y-2">
                                                <Label>
                                                    Header <span className="text-gray-500">(Optional)</span>
                                                </Label>
                                                <FormField
                                                    control={form.control}
                                                    name="header_type"
                                                    render={({ field }) => (
                                                        <div className="flex flex-wrap gap-2">
                                                            <HeaderTypeButton
                                                                field={field}
                                                                value="text"
                                                                icon={<Pilcrow size={16} />}
                                                                label="Text"
                                                                currentType={watchedHeaderType}
                                                            />
                                                            {/*
                                                            TODO: Implement a file uploader and a file sender to send the uploaded file to WABA API.
                                                            <HeaderTypeButton
                                                                field={field}
                                                                value="image"
                                                                icon={<ImageIcon size={16} />}
                                                                label="Image"
                                                                currentType={watchedHeaderType}
                                                            />
                                                            <HeaderTypeButton
                                                                field={field}
                                                                value="video"
                                                                icon={<Video size={16} />}
                                                                label="Video"
                                                                currentType={watchedHeaderType}
                                                            />
                                                            <HeaderTypeButton
                                                                field={field}
                                                                value="document"
                                                                icon={<File size={16} />}
                                                                label="File"
                                                                currentType={watchedHeaderType}
                                                            />*/}
                                                        </div>
                                                    )}
                                                />
                                                {watchedHeaderType && watchedHeaderType !== 'none' && (
                                                    <FormField
                                                        control={form.control}
                                                        name="header_content"
                                                        render={({ field }) => (
                                                            <FormItem>
                                                                <FormControl>
                                                                    <div className="flex items-center gap-2">
                                                                        <Input
                                                                            placeholder={
                                                                                watchedHeaderType === 'text'
                                                                                    ? 'Enter header text...'
                                                                                    : 'Enter media URL...'
                                                                            }
                                                                            {...field}
                                                                            ref={headerInputRef}
                                                                            onBlur={syncHeaderVariable}
                                                                        />
                                                                        <Button
                                                                            type="button"
                                                                            variant="ghost"
                                                                            size="icon"
                                                                            onClick={() => {
                                                                                form.setValue('header_type', 'none');
                                                                                form.setValue('header_content', '');
                                                                                if (watchedHeaderVariable) {
                                                                                    handleRemoveHeaderVariable();
                                                                                }
                                                                            }}
                                                                        >
                                                                            <Trash2 className="h-4 w-4" />
                                                                        </Button>
                                                                    </div>
                                                                </FormControl>
                                                                <FormMessage />
                                                            </FormItem>
                                                        )}
                                                    />
                                                )}
                                                {watchedHeaderType === 'text' && (
                                                    <div className="space-y-3">
                                                        <Label className="text-sm">Header Variable</Label>
                                                        <div className="flex flex-wrap items-center gap-3 mt-2">
                                                            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 items-center">
                                                                {watchedHeaderVariableType === 'named' && (
                                                                    <Input
                                                                        placeholder="variable_name"
                                                                        value={namedHeaderVariableName}
                                                                        onChange={(e) => setNamedHeaderVariableName(e.target.value)}
                                                                        className="w-full"
                                                                        disabled={hasHeaderVariable}
                                                                        onKeyDown={(e) => {
                                                                            if (e.key === 'Enter') {
                                                                                e.preventDefault();
                                                                                handleAddHeaderPlaceholder();
                                                                            }
                                                                        }}
                                                                    />
                                                                )}
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="w-full"
                                                                    onClick={handleAddHeaderPlaceholder}
                                                                    disabled={
                                                                        hasHeaderVariable ||
                                                                        (watchedHeaderVariableType === 'named' && !namedVariableName.trim())
                                                                    }
                                                                >
                                                                    + Add Placeholder
                                                                    {watchedHeaderVariableType === 'positional' && ' {{1}}'}
                                                                </Button>
                                                                {availableVariables && availableVariables.length > 0 && (
                                                                    <div
                                                                        className={`
                                                                            w-full
                                                                            md:col-span-2
                                                                            xl:col-span-1
                                                                            ${watchedHeaderVariableType !== 'named' ? 'md:col-span-1 xl:col-span-1' : ''}
                                                                        `}
                                                                    >
                                                                        <TooltipProvider>
                                                                            <Tooltip delayDuration={250}>
                                                                                <TooltipTrigger asChild>
                                                                                    <div className="inline-block w-full">
                                                                                        <Select
                                                                                            value={headerDbSelectValue}
                                                                                            onValueChange={(value) => {
                                                                                                const selectedVar = availableVariables.find(
                                                                                                    (v) => v.source_path === value,
                                                                                                );
                                                                                                if (selectedVar) {
                                                                                                    handleAddDbHeaderPlaceholder(selectedVar);
                                                                                                    setHeaderDbSelectValue("");
                                                                                                }
                                                                                            }}
                                                                                            disabled={hasHeaderVariable}
                                                                                        >
                                                                                            <SelectTrigger className="w-full">
                                                                                                <SelectValue placeholder="+ Insert DB Variable" />
                                                                                            </SelectTrigger>
                                                                                            <SelectContent>
                                                                                                {availableVariables.map((variable) => (
                                                                                                    <SelectItem
                                                                                                        key={variable.source_path}
                                                                                                        value={variable.source_path}
                                                                                                    >
                                                                                                        {variable.label}
                                                                                                    </SelectItem>
                                                                                                ))}
                                                                                            </SelectContent>
                                                                                        </Select>
                                                                                    </div>
                                                                                </TooltipTrigger>
                                                                                {hasHeaderVariable && (
                                                                                    <TooltipContent
                                                                                        className="border-amber-600 bg-amber-500 text-white [&_svg]:!bg-amber-500 [&_svg]:!fill-amber-500"
                                                                                        side="bottom"
                                                                                    >
                                                                                        <p>Remove the existing variable to add a new one.</p>
                                                                                    </TooltipContent>
                                                                                )}
                                                                            </Tooltip>
                                                                        </TooltipProvider>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                        {hasHeaderVariable && watchedHeaderVariable && (
                                                            <div className="space-y-3 rounded-lg border p-3">
                                                                <div className="flex flex-col xl:flex-row xl:items-center gap-2">
                                                                    <span className="font-mono text-sm font-medium">
                                                                        {watchedHeaderVariable.placeholder}
                                                                    </span>

                                                                    <div className="flex items-center gap-2 flex-grow">
                                                                        <FormField
                                                                            control={form.control}
                                                                            name="header_variable.example"
                                                                            render={({ field }) => (
                                                                                <FormItem className="flex-grow space-y-0">
                                                                                    <FormControl>
                                                                                        <Input
                                                                                            placeholder="Enter example value..."
                                                                                            value={field.value}
                                                                                            onChange={(e) => {
                                                                                                handleHeaderExampleChange(e.target.value);
                                                                                                field.onChange(e);
                                                                                            }}
                                                                                            onBlur={field.onBlur}
                                                                                        />
                                                                                    </FormControl>
                                                                                    <FormMessage className="text-xs" />
                                                                                </FormItem>
                                                                            )}
                                                                        />
                                                                        <Button
                                                                            type="button"
                                                                            variant="ghost"
                                                                            size="icon"
                                                                            onClick={handleRemoveHeaderVariable}
                                                                        >
                                                                            <Trash2 className="h-4 w-4" />
                                                                        </Button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>

                                            <hr/>

                                            <FormField
                                                control={form.control}
                                                name="body_content"
                                                render={({ field }) => (
                                                    <FormItem className="mb-0">
                                                        <Label>Message</Label>
                                                        <FormControl>
                                                            <Textarea
                                                                placeholder="Enter message content..."
                                                                className="min-h-[150px]"
                                                                {...field}
                                                                ref={(e) => {
                                                                    field.ref(e);
                                                                    textareaRef.current = e;
                                                                }}
                                                                onBlur={() => {
                                                                    field.onBlur();
                                                                    handleBodyContentBlur();
                                                                }}
                                                            />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                            {/* Variables Section */}
                                            <div className="space-y-2">
                                                {variableTypeError && (
                                                    <Alert variant="destructive">
                                                        <AlertCircle className="h-4 w-4" />
                                                        <AlertDescription>{variableTypeError}</AlertDescription>
                                                    </Alert>
                                                )}

                                                {/* Variable Type Selector + Add Placeholder */}
                                                <div className="flex flex-wrap items-center gap-3 mt-2">
                                                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 items-center">
                                                        {watchedVariableType === 'named' && (
                                                            <Input
                                                                placeholder="variable_name"
                                                                value={namedVariableName}
                                                                onChange={(e) => setNamedVariableName(e.target.value)}
                                                                className="w-full"
                                                                onKeyDown={(e) => {
                                                                    if (e.key === 'Enter') {
                                                                        e.preventDefault();
                                                                        handleAddPlaceholder();
                                                                    }
                                                                }}
                                                            />
                                                        )}

                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            className="w-full"
                                                            onClick={handleAddPlaceholder}
                                                            disabled={watchedVariableType === 'named' && !namedVariableName.trim()}
                                                        >
                                                            + Add Placeholder
                                                            {watchedVariableType === 'positional' && ` {{${nextPositionalNumber}}}`}
                                                        </Button>

                                                        {availableVariables && availableVariables.length > 0 && (
                                                            <div
                                                                className={`
                                                                    w-full
                                                                    md:col-span-2
                                                                    xl:col-span-1
                                                                    ${watchedVariableType !== 'named' ? 'md:col-span-1 xl:col-span-1' : ''}
                                                                `}
                                                            >
                                                                <TooltipProvider>
                                                                    <Tooltip delayDuration={250}>
                                                                        <TooltipTrigger asChild>
                                                                            <div className="inline-block w-full">
                                                                                <Select
                                                                                    value={dbSelectValue}
                                                                                    onValueChange={(value) => {
                                                                                        const selectedVar = availableVariables.find(v => v.source_path === value);
                                                                                        if (selectedVar) {
                                                                                            handleAddDbPlaceholder(selectedVar);
                                                                                            setDbSelectValue("");
                                                                                        }
                                                                                    }}
                                                                                    disabled={watchedVariableType === 'positional' && hasVariables}
                                                                                >
                                                                                    <SelectTrigger className="w-full">
                                                                                        <SelectValue placeholder="+ Insert DB Variable" />
                                                                                    </SelectTrigger>
                                                                                    <SelectContent>
                                                                                        {availableVariables.map((variable) => (
                                                                                            <SelectItem key={variable.source_path} value={variable.source_path}>
                                                                                                {variable.label}
                                                                                            </SelectItem>
                                                                                        ))}
                                                                                    </SelectContent>
                                                                                </Select>
                                                                            </div>
                                                                        </TooltipTrigger>
                                                                        {watchedVariableType === 'positional' && hasVariables && (
                                                                            <TooltipContent
                                                                                className="border-amber-600 bg-amber-500 text-white [&_svg]:!bg-amber-500 [&_svg]:!fill-amber-500"
                                                                                side="bottom"
                                                                            >
                                                                                <p>Select "Named" variable type to use DB variables.</p>
                                                                            </TooltipContent>
                                                                        )}
                                                                    </Tooltip>
                                                                </TooltipProvider>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Variables List */}
                                                {watchedVariablesSchema && watchedVariablesSchema.length > 0 && (
                                                    <div className="space-y-3">
                                                        <Label className="text-sm">Detected Variables ({watchedVariablesSchema.length})</Label> *<small>All are required</small>
                                                        <div className="grid grid-cols-1 xl:grid-cols-[auto_1fr_auto] gap-2 items-center rounded-lg border p-3">
                                                            {watchedVariablesSchema.map((variable, index) => (
                                                                <Fragment key={index}>
                                                                    <span className="font-mono text-sm font-medium col-span-full xl:col-span-1">
                                                                        {variable.placeholder}
                                                                    </span>
                                                                    <div className="col-span-full xl:col-span-2 flex items-center gap-2">
                                                                        <div className="flex-grow">
                                                                            <FormField
                                                                                control={form.control}
                                                                                name={`variables_schema.${index}.example`}
                                                                                render={({ field }) => (
                                                                                    <FormItem className="space-y-0">
                                                                                        <FormControl>
                                                                                            <Input
                                                                                                placeholder="Enter example value..."
                                                                                                value={field.value}
                                                                                                onChange={(e) => {
                                                                                                    handleExampleChange(variable.placeholder, e.target.value)
                                                                                                    field.onChange(e)
                                                                                                }}
                                                                                                onBlur={field.onBlur}
                                                                                            />
                                                                                        </FormControl>
                                                                                        <FormMessage className="text-xs" />
                                                                                    </FormItem>
                                                                                )}
                                                                            />
                                                                        </div>
                                                                        {/* El Button */}
                                                                        <Button
                                                                            type="button"
                                                                            variant="ghost"
                                                                            size="icon"
                                                                            onClick={() => handleRemoveVariable(variable.placeholder)}
                                                                        >
                                                                            <Trash2 className="h-4 w-4" />
                                                                        </Button>
                                                                    </div>
                                                                </Fragment>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>

                                            <hr />

                                            <FormField
                                                control={form.control}
                                                name="footer_content"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <Label>
                                                            Footer <span className="text-gray-500">(Optional)</span>
                                                        </Label>
                                                        <FormControl>
                                                            <Input placeholder="Enter footer text..." {...field} />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />

                                            <hr />

                                            <ButtonsSection control={form.control} />
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Columna de Vista Previa */}
                            <div className="w-[290px] hidden lg:block">
                                <div className="sticky top-0">
                                    <MessagePreview templateData={form.watch()} />
                                </div>
                            </div>
                        </form>
                    </Form>
                </div>
            </MessageTemplateLayout>
        </AppLayout>
    );
}
