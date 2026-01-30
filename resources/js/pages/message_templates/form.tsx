import AppLayout from '@/layouts/app-layout';
import MessageTemplateLayout from '@/layouts/message_templates/layout';
import { BreadcrumbItem, PageProps } from '@/types';
import { Card, CardContent } from "@/components/ui/card";
import { Head, useForm as useInertiaForm, usePage } from '@inertiajs/react';
import * as z from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm, ControllerRenderProps } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { useEffect, useMemo, ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { File, Image as ImageIcon, Pilcrow, Video } from "lucide-react";

// --- Type Definitions ---
interface Category {
    id: number;
    name: string;
}

type HeaderType = 'none' | 'text' | 'image' | 'video' | 'document';

interface ButtonConfig {
    type: 'reply' | 'url' | 'call';
    text: string;
    url?: string;
    phone_number?: string;
}

interface VariableSchema {
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

interface Props {
    categories: Category[];
    template: Template | null;
}

// --- Zod Schemas ---
const variableSchemaItem = z.object({
    placeholder: z.string(),
    example: z.string().optional(),
});

const buttonConfigItem = z.object({
    type: z.enum(['reply', 'url', 'call']),
    text: z.string(),
    url: z.string().optional(),
    phone_number: z.string().optional(),
});

const formSchema = z.object({
    name: z.string().min(1, 'Template name is required'),
    category_id: z.number({ required_error: 'Category is required' }).min(1, 'Category is required'),
    language: z.string().min(1, 'Language is required'),
    header_type: z.enum(['none', 'text', 'image', 'video', 'document']),
    header_content: z.string().optional(),
    body_content: z.string().min(1, 'Message content is required'),
    footer_content: z.string().optional(),
    button_config: z.array(buttonConfigItem).nullable(),
    variables_schema: z.array(variableSchemaItem).nullable(),
});

type TemplateFormValues = z.infer<typeof formSchema>;

// --- Helper Components ---
interface HeaderTypeButtonProps {
    field: ControllerRenderProps<TemplateFormValues, 'header_type'>;
    value: HeaderType;
    icon: ReactNode;
    label: string;
    currentType: HeaderType;
}

const HeaderTypeButton = ({ field, value, icon, label, currentType }: HeaderTypeButtonProps) => (
    <Button
        type="button"
        variant="outline"
        className={cn("flex items-center gap-2", currentType === value && "ring-2 ring-primary")}
        onClick={() => field.onChange(value)}
    >
        {icon}
        <span>{label}</span>
    </Button>
);

// --- Main Component ---
export default function TemplateForm({ categories, template }: Props) {
    const { props } = usePage<PageProps>();

    const breadcrumbs: BreadcrumbItem[] = useMemo(() => [
        { title: 'Message Templates', href: route('message-templates.index', props.chatbot.id) },
        { title: template ? 'Edit Template' : 'Create Template', href: template ? route('message-templates.edit', { id: template.id }) : route('message-templates.create') },
    ], [props.chatbot, template]);

    const { data: inertiaData, setData: setInertiaData, post, put, processing } = useInertiaForm<TemplateFormValues>(
        template
            ? {
                ...template,
                header_content: template.header_content || '',
                footer_content: template.footer_content || '',
                button_config: template.button_config || null,
            }
            : {
                name: '',
                category_id: 0,
                language: 'es',
                header_type: 'none',
                header_content: '',
                body_content: '',
                footer_content: '',
                button_config: null,
                variables_schema: null,
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
                                                name="name"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>Template Name</FormLabel>
                                                        <FormControl><Input placeholder="template_name" {...field} /></FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                            <FormField
                                                control={form.control}
                                                name="category_id"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>Template Category</FormLabel>
                                                        <Select onValueChange={(value) => field.onChange(Number(value))} defaultValue={field.value?.toString()}>
                                                            <FormControl><SelectTrigger><SelectValue placeholder="Select a category" /></SelectTrigger></FormControl>
                                                            <SelectContent>
                                                                {categories.map((category) => (
                                                                    <SelectItem key={category.id} value={category.id.toString()}>{category.name}</SelectItem>
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
                                                        <Select onValueChange={field.onChange} defaultValue={field.value}>
                                                            <FormControl><SelectTrigger><SelectValue placeholder="Select language" /></SelectTrigger></FormControl>
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
                                        </div>

                                        {/* Sub-columna Derecha del Formulario */}
                                        <div className="md:col-span-2 space-y-6">
                                            <div className="flex justify-between items-center">
                                                <h3 className="text-lg font-medium">
                                                    {form.watch('name') || 'template_name'} • {form.watch('language')}
                                                </h3>
                                                <Button type="submit" disabled={processing}>
                                                    {processing ? 'Saving...' : 'Send To Review'}
                                                </Button>
                                            </div>

                                            <div className="space-y-2">
                                                <Label>Header <span className="text-gray-500">(Optional)</span></Label>
                                                <FormField
                                                    control={form.control}
                                                    name="header_type"
                                                    render={({ field }) => (
                                                        <div className="flex flex-wrap gap-2">
                                                            <HeaderTypeButton field={field} value="text" icon={<Pilcrow size={16} />} label="Text" currentType={watchedHeaderType} />
                                                            <HeaderTypeButton field={field} value="image" icon={<ImageIcon size={16} />} label="Image" currentType={watchedHeaderType} />
                                                            <HeaderTypeButton field={field} value="video" icon={<Video size={16} />} label="Video" currentType={watchedHeaderType} />
                                                            <HeaderTypeButton field={field} value="document" icon={<File size={16} />} label="File" currentType={watchedHeaderType} />
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
                                                                    <Input placeholder={watchedHeaderType === 'text' ? 'Enter header text...' : 'Enter media URL...'} {...field} />
                                                                </FormControl>
                                                                <FormMessage />
                                                            </FormItem>
                                                        )}
                                                    />
                                                )}
                                            </div>

                                            <FormField
                                                control={form.control}
                                                name="body_content"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <Label>Message</Label>
                                                        <FormControl>
                                                            <Textarea
                                                                placeholder="Enter message content..."
                                                                className="min-h-[150px]"
                                                                {...field}
                                                            />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />

                                            <FormField
                                                control={form.control}
                                                name="footer_content"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <Label>Footer <span className="text-gray-500">(Optional)</span></Label>
                                                        <FormControl><Input placeholder="Enter footer text..." {...field} /></FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Columna de Vista Previa */}
                            <div className="w-[290px] hidden lg:block">
                                <div className="sticky top-0">
                                    <div
                                        className="relative w-full max-w-sm mx-auto h-[550px] bg-no-repeat bg-contain bg-center"
                                        style={{ backgroundImage: "url('/images/chat-template.webp')" }}
                                    ></div>
                                </div>
                            </div>
                        </form>
                    </Form>
                </div>
            </MessageTemplateLayout>
        </AppLayout>
    );
}
