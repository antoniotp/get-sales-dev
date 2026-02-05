import AppLayout from '@/layouts/app-layout';
import MessageTemplateLayout from '@/layouts/message_templates/layout';
import { BreadcrumbItem, PageProps } from '@/types';
import { Card, CardContent } from "@/components/ui/card";
import { Head, useForm as useInertiaForm, usePage } from '@inertiajs/react';
import * as z from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { useEffect, useMemo, useRef, useState } from 'react';
import { File, Image as ImageIcon, Pilcrow, Trash2, Video, AlertCircle } from 'lucide-react';
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
    type: z.enum(['reply', 'url', 'call']),
    text: z.string(),
    url: z.string().optional(),
    phone_number: z.string().optional(),
});

const formSchema = z.object({
    display_name: z.string().min(1, 'Display name is required'),
    name: z.string().min(1, 'Template name is required'),
    category_id: z.number({ required_error: 'Category is required' }).min(1, 'Category is required'),
    language: z.string().min(1, 'Language is required'),
    header_type: z.enum(['none', 'text', 'image', 'video', 'document']),
    header_content: z.string().optional(),
    header_variable: variableSchemaItem.nullable().optional(),
    header_variable_type: z.enum(['positional', 'named']).optional(),
    body_content: z.string().min(1, 'Message content is required'),
    footer_content: z.string().optional(),
    button_config: z.array(buttonConfigItem).nullable(),
    variables_schema: z.array(variableSchemaItem).nullable(),
    variable_type: z.enum(['positional', 'named']).optional(),
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

// --- Main Component ---
export default function TemplateForm({ categories, template }: TemplateFormPageProps) {
    const { props } = usePage<PageProps>();
    const headerInputRef = useRef<HTMLInputElement>(null);
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const [namedVariableName, setNamedVariableName] = useState('');
    const [namedHeaderVariableName, setNamedHeaderVariableName] = useState('');
    const [variableTypeError, setVariableTypeError] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = useMemo(() => [
        { title: 'Message Templates', href: route('message-templates.index', props.chatbot.id) },
        { title: template ? 'Edit Template' : 'Create Template', href: template ? route('message-templates.edit', { id: template.id }) : route('message-templates.create') },
    ], [props.chatbot, template]);

    const { data: inertiaData, setData: setInertiaData, post, put, processing } = useInertiaForm<TemplateFormValues>(
        template
            ? {
                ...template,
                display_name: template.display_name || template.name,
                header_content: template.header_content || '',
                header_variable: template.header_variable || null,
                header_variable_type: template.header_variable_type || 'positional',
                footer_content: template.footer_content || '',
                button_config: template.button_config || null,
                variable_type: template.variable_type || 'positional',
            }
            : {
                display_name: '',
                name: '',
                category_id: 0,
                language: 'es',
                header_type: 'none',
                header_content: '',
                header_variable: null,
                header_variable_type: 'positional',
                body_content: '',
                footer_content: '',
                button_config: null,
                variables_schema: null,
                variable_type: 'positional',
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

    const onSubmit = () => {
        if (template?.id) {
            put(route('message-templates.update', template.id), {
                preserveScroll: true,
            });
        } else {
            post(route('message-templates.store'), {
                preserveScroll: true,
                onSuccess: () => form.reset(),
            });
        }
    };

    const watchedHeaderType = form.watch('header_type');
    const watchedHeaderContent = form.watch('header_content');
    const watchedHeaderVariable = form.watch('header_variable');
    const watchedHeaderVariableType = form.watch('header_variable_type');
    const watchedBodyContent = form.watch('body_content');
    const watchedVariableType = form.watch('variable_type');
    const watchedVariablesSchema = form.watch('variables_schema');

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
        if (watchedHeaderVariableType === 'positional') {
            placeholder = '{{1}}';
        } else {
            const trimmedName = namedHeaderVariableName.trim();
            if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(trimmedName)) {
                form.setError('header_content', {
                    type: 'manual',
                    message: 'Variable name must start with a letter or underscore and contain only letters, numbers, and underscores.'
                });
                return;
            }
            placeholder = `{{${trimmedName}}}`;
        }

        insertHeaderPlaceholder(placeholder);
        form.setValue('header_variable', { placeholder, example: '' }, { shouldValidate: true });

        if (watchedHeaderVariableType === 'named') {
            setNamedHeaderVariableName('');
        }
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
        if (placeholder) {
            if (!watchedHeaderVariable || watchedHeaderVariable.placeholder !== placeholder) {
                form.setValue('header_variable', { placeholder, example: watchedHeaderVariable?.example || '' }, { shouldValidate: true });
            }
        } else if (watchedHeaderVariable) {
            form.setValue('header_variable', null, { shouldValidate: true });
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

    // Add new placeholder
    const handleAddPlaceholder = () => {
        let placeholder: string;

        if (watchedVariableType === 'positional') {
            placeholder = `{{${nextPositionalNumber}}}`;
        } else {
            // Named
            const trimmedName = namedVariableName.trim();

            // check format
            if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(trimmedName)) {
                form.setError('body_content', {
                    type: 'manual',
                    message: 'Variable name must start with a letter or underscore and contain only letters, numbers, and underscores'
                });
                return;
            }

            placeholder = `{{${trimmedName}}}`;

            // check duplicates
            const currentSchema = watchedVariablesSchema || [];
            if (currentSchema.some(v => v.placeholder === placeholder)) {
                form.setError('body_content', {
                    type: 'manual',
                    message: `Variable ${placeholder} already exists`
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
            setNamedVariableName('');
        }
    };

    const handleRemoveVariable = (placeholder: string) => {
        // Remove var from variables_schema
        const currentSchema = watchedVariablesSchema || [];
        const newSchema = currentSchema.filter(v => v.placeholder !== placeholder);
        form.setValue('variables_schema', newSchema.length > 0 ? newSchema : null, { shouldValidate: true });

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

        const currentSchema = watchedVariablesSchema || [];

        const newSchema = placeholders.map(placeholder => {
            const existing = currentSchema.find(v => v.placeholder === placeholder);
            return existing || { placeholder, example: '' };
        });

        const hasChanges = JSON.stringify(currentSchema.map(v => v.placeholder).sort()) !==
                          JSON.stringify(newSchema.map(v => v.placeholder).sort());

        if (hasChanges) {
            form.setValue('variables_schema', newSchema.length > 0 ? newSchema : null, { shouldValidate: true });
        }
    };

    const handleExampleChange = (placeholder: string, example: string) => {
        const currentSchema = watchedVariablesSchema || [];
        const newSchema = currentSchema.map(v =>
            v.placeholder === placeholder ? { ...v, example } : v
        );
        form.setValue('variables_schema', newSchema, { shouldValidate: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={template ? 'Edit Message Template' : 'Create Message Template'} />
            <MessageTemplateLayout>
                <h2 className="text-2xl font-bold mb-2">{template ? 'Update Template' : 'Create Template'}</h2>
                <div className="h-[calc(100vh-11rem)] w-full overflow-auto">
                    <Form {...form}>
                        <form onSubmit={form.handleSubmit(onSubmit)} className="flex flex-row gap-4 w-full">

                            {/* Columna del Formulario */}
                            <div className="flex-1">
                                <Card>
                                    <CardContent className="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                                        {/* Sub-columna Izquierda del Formulario */}
                                        <div className="md:col-span-1 space-y-6">
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
                                                                <SelectItem value="es">Spanish</SelectItem>
                                                                <SelectItem value="en_US">English</SelectItem>
                                                                <SelectItem value="pt_BR">Portuguese</SelectItem>
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

                                                {/*TODO: display only if is a WABA template*/}
                                                {/*<Button type="button" className="btn-whatsapp">
                                                    {processing ? 'Sending to Review...' : 'Send To Review'}
                                                </Button>*/}
                                            </div>
                                        </div>

                                        {/* Sub-columna Derecha del Formulario */}
                                        <div className="md:col-span-2 space-y-6">
                                            <div className="flex justify-between items-center">
                                                <h3 className="text-lg font-medium">
                                                    {form.watch('display_name') || 'Template Name'} • {form.watch('language')}
                                                </h3>
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
                                                            />
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
                                                    <div className="space-y-3 rounded-lg border p-3">
                                                        <Label className="text-sm">Header Variable</Label>
                                                        <div className="flex items-center gap-3">
                                                            <TooltipProvider>
                                                                <Tooltip delayDuration={250}>
                                                                    <TooltipTrigger asChild>
                                                                        <div>
                                                                            <FormField
                                                                                control={form.control}
                                                                                name="header_variable_type"
                                                                                render={({ field }) => (
                                                                                    <RadioGroup
                                                                                        onValueChange={field.onChange}
                                                                                        value={field.value}
                                                                                        className="flex items-center gap-4"
                                                                                        disabled={hasHeaderVariable}
                                                                                    >
                                                                                        <div className="flex items-center space-x-2">
                                                                                            <RadioGroupItem value="positional" id="h-positional" />
                                                                                            <Label htmlFor="h-positional" className="cursor-pointer font-normal">Positional</Label>
                                                                                        </div>
                                                                                        <div className="flex items-center space-x-2">
                                                                                            <RadioGroupItem value="named" id="h-named" />
                                                                                            <Label htmlFor="h-named" className="cursor-pointer font-normal">Named</Label>
                                                                                        </div>
                                                                                    </RadioGroup>
                                                                                )}
                                                                            />
                                                                        </div>
                                                                    </TooltipTrigger>
                                                                    {hasHeaderVariable && (
                                                                        <TooltipContent
                                                                        className="border-amber-600 bg-amber-500 text-white [&_svg]:!bg-amber-500 [&_svg]:!fill-amber-500"
                                                                        side="bottom"
                                                                        >
                                                                        <p>Remove the variable to change type</p>
                                                                        </TooltipContent>
                                                                    )}
                                                                </Tooltip>
                                                            </TooltipProvider>

                                                            {watchedHeaderVariableType === 'named' && (
                                                                <Input
                                                                    placeholder="variable_name"
                                                                    value={namedHeaderVariableName}
                                                                    onChange={(e) => setNamedHeaderVariableName(e.target.value)}
                                                                    className="max-w-[150px]"
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
                                                                onClick={handleAddHeaderPlaceholder}
                                                                disabled={
                                                                    hasHeaderVariable ||
                                                                    (watchedHeaderVariableType === 'named' && !namedHeaderVariableName.trim())
                                                                }
                                                            >
                                                                + Add Placeholder
                                                                {watchedHeaderVariableType === 'positional' && ' {{1}}'}
                                                            </Button>
                                                        </div>
                                                        {hasHeaderVariable && watchedHeaderVariable && (
                                                            <div className="grid grid-cols-[auto_1fr_auto] gap-2 items-center">
                                                                <span className="font-mono text-sm font-medium">
                                                                    {watchedHeaderVariable.placeholder}
                                                                </span>
                                                                <Input
                                                                    placeholder="Enter example value..."
                                                                    value={watchedHeaderVariable.example}
                                                                    onChange={(e) => handleHeaderExampleChange(e.target.value)}
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
                                                        )}
                                                    </div>
                                                )}
                                            </div>

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
                                                <div className="flex items-center gap-3">
                                                    <TooltipProvider>
                                                        <Tooltip delayDuration={250}>
                                                            <TooltipTrigger asChild>
                                                                <div>
                                                                    <FormField
                                                                        control={form.control}
                                                                        name="variable_type"
                                                                        render={({ field }) => (
                                                                            <RadioGroup
                                                                                onValueChange={field.onChange}
                                                                                value={field.value}
                                                                                className="flex items-center gap-4"
                                                                                disabled={hasVariables}
                                                                            >
                                                                                <div className="flex items-center space-x-2">
                                                                                    <RadioGroupItem value="positional" id="positional" />
                                                                                    <Label
                                                                                        htmlFor="positional"
                                                                                        className="cursor-pointer font-normal"
                                                                                    >
                                                                                        Positional
                                                                                    </Label>
                                                                                </div>
                                                                                <div className="flex items-center space-x-2">
                                                                                    <RadioGroupItem value="named" id="named" />
                                                                                    <Label htmlFor="named" className="cursor-pointer font-normal">
                                                                                        Named
                                                                                    </Label>
                                                                                </div>
                                                                            </RadioGroup>
                                                                        )}
                                                                    />
                                                                </div>
                                                            </TooltipTrigger>
                                                            {hasVariables && (
                                                                <TooltipContent
                                                                    className="border-amber-600 bg-amber-500 text-white [&_svg]:!bg-amber-500 [&_svg]:!fill-amber-500"
                                                                    side="bottom"
                                                                >
                                                                    <p>Remove all variables to change type</p>
                                                                </TooltipContent>
                                                            )}
                                                        </Tooltip>
                                                    </TooltipProvider>

                                                    {watchedVariableType === 'named' && (
                                                        <Input
                                                            placeholder="variable_name"
                                                            value={namedVariableName}
                                                            onChange={(e) => setNamedVariableName(e.target.value)}
                                                            className="max-w-[200px]"
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
                                                        onClick={handleAddPlaceholder}
                                                        disabled={watchedVariableType === 'named' && !namedVariableName.trim()}
                                                    >
                                                        + Add Placeholder
                                                        {watchedVariableType === 'positional' && ` {{${nextPositionalNumber}}}`}
                                                    </Button>
                                                </div>

                                                {/* Variables List */}
                                                {watchedVariablesSchema && watchedVariablesSchema.length > 0 && (
                                                    <div className="space-y-3">
                                                        <Label className="text-sm">Detected Variables ({watchedVariablesSchema.length})</Label> *<small>All are required</small>
                                                        <div className="grid grid-cols-[auto_1fr_auto] gap-2 items-center rounded-lg border p-3">
                                                            {watchedVariablesSchema.map((variable, index) => (
                                                                <>
                                                                    <span key={`label-${index}`} className="font-mono text-sm font-medium">
                                                                        {variable.placeholder}
                                                                    </span>
                                                                    <Input
                                                                        key={`input-${index}`}
                                                                        placeholder="Enter example value..."
                                                                        value={variable.example}
                                                                        onChange={(e) => handleExampleChange(variable.placeholder, e.target.value)}
                                                                    />
                                                                    <Button
                                                                        key={`btn-${index}`}
                                                                        type="button"
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        onClick={() => handleRemoveVariable(variable.placeholder)}
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                </>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>

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
