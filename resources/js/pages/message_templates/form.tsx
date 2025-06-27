import AppLayout from '@/layouts/app-layout'
import MessageTemplateLayout from '@/layouts/message_templates/layout'
import type { BreadcrumbItem } from '@/types'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"

import { Head, useForm as useInertiaForm } from '@inertiajs/react';
import * as z from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';

import { Button } from '@/components/ui/button';
import {
    Form,
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    RadioGroup,
    RadioGroupItem
} from '@/components/ui/radio-group'
import { Separator } from '@/components/ui/separator'
import { Label } from '@/components/ui/label'
import {useEffect, useState} from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Message templates management',
        href: route('message-templates.index'),
    },
    {
        title: 'Create template',
        href: route( 'message-templates.create' ),
    },
];

interface Category {
    id: number;
    name: string;
}

interface Template {
    id?: number;
    name: string;
    category_id: number;
    language: string;
    header_type: 'none' | 'text' | 'image' | 'video' | 'document';
    header_content?: string;
    body_content: string;
    footer_content?: string;
    button_config?: never[];
    variables_count: number;
    variables_schema: Array<{ placeholder: string; example?: string }> | null;
}

interface Props {
    categories: Category[];
    template: Template | null;
}

const variableSchemaItem = z.object({
    placeholder: z.string(),
    example: z.string().optional(),
});

const formSchema = z.object({
    name: z.string().min(1, 'Template name is required'),
    category_id: z.number({ required_error: 'Category is required' }).min(1, 'Category is required'),
    language: z.string().min(1, 'Language is required'),
    header_type: z.enum(['none', 'text', 'image', 'video', 'document']),
    header_content: z.string().optional(),
    body_content: z.string().min(1, 'Message content is required'),
    footer_content: z.string().optional(),
    // button_config: z.array(z.any()).optional(), // Consider refining this schema later
    button_config: z.array(z.any()).refine((data) => {
        try {
            return Array.isArray(data);
        } catch {
            return false;
        }
    }, "Invalid button configuration format"),
    variables_schema: z.array(variableSchemaItem).nullable(),
});

type TemplateFormValues = z.infer<typeof formSchema>;

export default function TemplateForm({ categories, template }: Props) {
    const {
        data: inertiaData,
        setData: setInertiaData,
        post,
        put,
        processing,
    } = useInertiaForm<TemplateFormValues>(
        template
            ? { // Editing an existing template
                name: template.name,
                category_id: template.category_id,
                language: template.language,
                header_type: template.header_type,
                header_content: template.header_content || '', // Ensure it's a string
                body_content: template.body_content,
                footer_content: template.footer_content || '', // Ensure it's a string
                button_config: template.button_config || [],
                variables_schema: template.variables_schema || [],
            }
            : { // Creating a new template
                name: '',
                category_id: 0,
                language: '',
                header_type: 'none',
                header_content: '',
                body_content: '',
                footer_content: '',
                button_config: [],
                variables_schema: [],
            }
    );

    const [localButtonConfig, setLocalButtonConfig] = useState(() => JSON.stringify(inertiaData.button_config || [], null, 2));
    const [localVariablesSchema, setLocalVariablesSchema] = useState(() => JSON.stringify(inertiaData.variables_schema || [], null, 2));
    const [variablePlaceholders, setVariablePlaceholders] = useState<Array<{ placeholder: string; example: string }>>(
        template?.variables_schema?.map(item => ({
            placeholder: item.placeholder,
            example: item.example || '' // Ensure that the example is always a string
        })) || []
    );

    const form = useForm<TemplateFormValues>({
        resolver: zodResolver(formSchema),
        defaultValues: inertiaData,
        mode: 'onBlur',
    });

    // Synchronize changes from the react-hook-form form with Inertia data.
    // This is important so that useInertiaForm.data always reflects the current values of the form
    useEffect(() => {
        const subscription = form.watch((value) => {
            setInertiaData(value as TemplateFormValues);
        });
        return () => subscription.unsubscribe();
    }, [form, setInertiaData]);

    const onSubmit = (values: TemplateFormValues) => {
        console.log('Sending form:', values);
        if (template?.id) {
            put(`/message_templates/${template.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Template updated!');
                },
                onError: (errors) => {
                    console.error('Error at updating:', errors);
                    // Los errores de validación del servidor se manejarán por Inertia
                },
            });
        } else {
            post('/message_templates', {
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Template created!');
                    form.reset();
                },
                onError: (errors) => {
                    console.error('Error at creating:', errors);
                },
            });
        }
    };

    function generateInputsForPlaceholders(template: string) {
        console.log("body content: ", template);
        //count and extract unique placeholders
        const varRegex = /\{\{(\d+)}}/g;
        const uniqueVars = new Set<string>();
        let match;
        while ((match = varRegex.exec(template)) !== null) {
            uniqueVars.add(match[1]); // match[1] contains the number without braces
        }
        console.log("uniqueVars: ", uniqueVars);
        console.log( "uniqueVars.size: ", uniqueVars.size);

        // Convert uniqueVars to sorted array for consistent ordering
        const sortedVars = Array.from(uniqueVars).sort((a, b) => parseInt(a) - parseInt(b));

        // Create new variablePlaceholders array based on uniqueVars
        const newVariablePlaceholders = sortedVars.map(varNumber => {
            // Check if this variable already exists in current variablePlaceholders
            const existing = variablePlaceholders.find(vp => vp.placeholder === `{{${varNumber}}}`);

            return {
                placeholder: `{{${varNumber}}}`,
                example: existing?.example || '' // Keep existing example or empty string
            };
        });

        // Update the state
        setVariablePlaceholders(newVariablePlaceholders);

        // Update the form's variables_schema field
        const newVariablesSchema = newVariablePlaceholders.map(vp => ({
            placeholder: vp.placeholder,
            example: vp.example
        }));

        // Update form field
        form.setValue('variables_schema', newVariablesSchema);

        // Update Inertia data
        setInertiaData(prev => ({
            ...prev,
            variables_schema: newVariablesSchema
        }));

        // Update local JSON representation
        setLocalVariablesSchema(JSON.stringify(newVariablesSchema, null, 2));
    }

    // Function to handle example input changes
    const handleExampleChange = (placeholder: string, example: string) => {
        const updatedPlaceholders = variablePlaceholders.map(vp =>
            vp.placeholder === placeholder ? { ...vp, example } : vp
        );

        setVariablePlaceholders(updatedPlaceholders);

        // Update form and Inertia data
        const newVariablesSchema = updatedPlaceholders.map(vp => ({
            placeholder: vp.placeholder,
            example: vp.example
        }));

        form.setValue('variables_schema', newVariablesSchema);
        setInertiaData(prev => ({
            ...prev,
            variables_schema: newVariablesSchema
        }));
        setLocalVariablesSchema(JSON.stringify(newVariablesSchema, null, 2));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${template ? 'Edit' : 'Create'} Message Template`} />
            <MessageTemplateLayout>
                <div className="flex h-[calc(100vh-14rem)] w-full overflow-hidden">
                    <div className="w-full pb-12 overflow-auto">
                        <Form {...form}>
                            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                                {/* Basic Information */}
                                <Card className="p-4">
                                    <CardHeader>
                                        <CardTitle>Basic Information</CardTitle>
                                        <CardDescription>
                                            Define the core properties of your message template.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <FormField
                                            control={form.control}
                                            name="name"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Template Name</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="my_awesome_template" {...field} />
                                                    </FormControl>
                                                    <FormDescription>
                                                        A unique name for your template (e.g., `account_update`, `order_confirmation`). Use lowercase letters, numbers, and underscores.
                                                    </FormDescription>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4"> {/* Use md:grid-cols-2 for responsiveness */}
                                            <FormField
                                                control={form.control}
                                                name="category_id"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>Category</FormLabel>
                                                        <Select onValueChange={(value) => field.onChange(Number(value))} defaultValue={field.value ? field.value.toString() : ''}> {/* Ensure defaultValue handles 0 or null */}
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
                                                        <FormDescription>
                                                            Categorize your template (e.g., Utility, Marketing, Authentication).
                                                        </FormDescription>
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
                                                        <Select onValueChange={field.onChange} defaultValue={field.value}>
                                                            <FormControl>
                                                                <SelectTrigger>
                                                                    <SelectValue placeholder="Select language" />
                                                                </SelectTrigger>
                                                            </FormControl>
                                                            <SelectContent>
                                                                <SelectItem value="es">Spanish (es)</SelectItem>
                                                                <SelectItem value="en">English (en)</SelectItem>
                                                                <SelectItem value="pt">Portuguese (pt)</SelectItem>
                                                                {/* Add more languages as needed based on Meta's supported locales */}
                                                            </SelectContent>
                                                        </Select>
                                                        <FormDescription>
                                                            The language of your template content.
                                                        </FormDescription>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                        </div>
                                    </CardContent>
                                </Card>

                                <Separator />

                                {/* Content Section */}
                                <Card className="p-4">
                                    <CardHeader>
                                        <CardTitle>Template Content</CardTitle>
                                        <CardDescription>
                                            Craft the message that will be sent to your users.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-6">

                                        {/* Header Type */}
                                        <FormField
                                            control={form.control}
                                            name="header_type"
                                            render={({ field }) => (
                                                <FormItem className="space-y-3">
                                                    <FormLabel>Header Type</FormLabel>
                                                    <FormControl>
                                                        <RadioGroup
                                                            onValueChange={field.onChange}
                                                            defaultValue={field.value}
                                                            className="flex flex-row space-x-4"
                                                        >
                                                            {['none', 'text', 'image', 'video', 'document'].map((type) => (
                                                                <FormItem key={type} className="flex items-center space-x-3 space-y-0">
                                                                    <FormControl>
                                                                        <RadioGroupItem value={type} />
                                                                    </FormControl>
                                                                    <Label className="font-normal">
                                                                        {type.charAt(0).toUpperCase() + type.slice(1)}
                                                                    </Label>
                                                                </FormItem>
                                                            ))}
                                                        </RadioGroup>
                                                    </FormControl>
                                                    <FormDescription>
                                                        Choose the type of header for your message. 'None' means no header.
                                                    </FormDescription>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        {/* Conditional: Header Content */}
                                        {form.watch('header_type') !== 'none' && (
                                            <FormField
                                                control={form.control}
                                                name="header_content"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>Header Content</FormLabel>
                                                        <FormControl>
                                                            <Input
                                                                placeholder={
                                                                    form.watch('header_type') === 'text'
                                                                        ? 'Your header text (max 60 characters, no variables)'
                                                                        : 'URL to your media/document (e.g., https://example.com/image.jpg)'
                                                                }
                                                                {...field}
                                                            />
                                                        </FormControl>
                                                        <FormDescription>
                                                            {form.watch('header_type') === 'text'
                                                                ? "Enter the text for your header. Variables are not allowed in text headers."
                                                                : "Provide the public URL for your image (JPG/PNG), video (MP4), or document (PDF)."}
                                                        </FormDescription>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                        )}

                                        {/* Body Content */}
                                        <FormField
                                            control={form.control}
                                            name="body_content"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Message Body</FormLabel>
                                                    <FormControl>
                                                        <Textarea
                                                            placeholder="Enter message content with variables like {{1}}, {{2}}. E.g., Hello {{1}}, your order {{2}} has shipped."
                                                            className="min-h-[120px]"
                                                            {...field}
                                                            onBlur={(e) => generateInputsForPlaceholders(e.target.value)}
                                                        />
                                                    </FormControl>
                                                    <FormDescription>
                                                        {"This is the main content of your message. Use `{{1}}`, `{{2}}`, etc., for dynamic content variables."}
                                                    </FormDescription>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        {/* Dynamic Variable Inputs */}
                                        {variablePlaceholders.length > 0 && (
                                            <div className="space-y-4">
                                                <Label className="text-sm font-medium">Variable Examples</Label>
                                                <FormDescription>
                                                    Provide example values for each variable to help users understand what data should be passed.
                                                </FormDescription>
                                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    {variablePlaceholders.map((variable) => (
                                                        <div key={variable.placeholder} className="space-y-2">
                                                            <Label className="text-sm text-muted-foreground">
                                                                {variable.placeholder}
                                                            </Label>
                                                            <Input
                                                                placeholder={`Example for ${variable.placeholder}`}
                                                                value={variable.example}
                                                                onChange={(e) => handleExampleChange(variable.placeholder, e.target.value)}
                                                            />
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {/* Footer Content */}
                                        <FormField
                                            control={form.control}
                                            name="footer_content"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Footer Content (Optional)</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="e.g., Regards, Your Team (max 60 characters)" {...field} />
                                                    </FormControl>
                                                    <FormDescription>
                                                        Optional footer text for your message. Cannot contain variables.
                                                    </FormDescription>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </CardContent>
                                </Card>

                                <Separator />

                                {/* Advanced Configuration (Buttons and Variables) */}
                                <Card className="p-4">
                                    <CardHeader>
                                        <CardTitle>Advanced Configuration</CardTitle>
                                        <CardDescription>
                                            Configure interactive buttons and define your template variables.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {/* Button Configuration */}
                                        <FormField
                                            control={form.control}
                                            name="button_config"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <Label htmlFor="button_config_raw">Button Configuration (JSON)</Label>
                                                    <Textarea
                                                        id="button_config_raw"
                                                        value={localButtonConfig}
                                                        onChange={(e) => setLocalButtonConfig(e.target.value)}
                                                        onBlur={(e) => {
                                                            try {
                                                                const parsed = e.target.value.trim() ? JSON.parse(e.target.value) : [];
                                                                setInertiaData(prev => ({
                                                                    ...prev,
                                                                    button_config: parsed
                                                                }));
                                                                // Actualizar el valor del formulario
                                                                field.onChange(parsed);
                                                                form.clearErrors('button_config')
                                                            } catch (error) {
                                                                console.log("Error: ", error );
                                                                // Restaurar el valor anterior en caso de JSON inválido
                                                                setLocalButtonConfig(JSON.stringify(inertiaData.button_config || [], null, 2));
                                                                // Establecer error en el formulario
                                                                form.setError('button_config', {
                                                                    type: 'manual',
                                                                    message: 'Invalid JSON format'
                                                                });
                                                            }
                                                        }}
                                                        placeholder={`[\n  {\n    "type": "reply",\n    "text": "Yes!"\n  },\n  {\n    "type": "url",\n    "text": "Visit Site",\n    "url": "https://example.com/{{1}}"\n  }\n]`}
                                                        className="min-h-[150px] font-mono"
                                                    />
                                                    <FormDescription>
                                                        Enter a JSON array for button configuration. Supports `reply`, `url`, and `call` types.
                                                        URL buttons can use variables. Refer to Meta's documentation for exact structure.
                                                    </FormDescription>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </CardContent>
                                </Card>

                                <div className="flex justify-end space-x-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => window.history.back()}
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Saving...' : (template ? 'Update Template' : 'Create Template')}
                                    </Button>
                                </div>
                            </form>
                        </Form>
                    </div>
                </div>
            </MessageTemplateLayout>
        </AppLayout>
    )
}
