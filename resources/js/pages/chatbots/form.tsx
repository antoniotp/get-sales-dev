import AppLayout from '@/layouts/app-layout';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { BreadcrumbItem } from '@/types';
import { Head, Link, useForm as useInertiaForm } from '@inertiajs/react';
import { useEffect } from 'react';
import * as z from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Form,
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { useTranslation } from 'react-i18next';

interface Chatbot {
    id: number;
    name: string;
    description: string;
    system_prompt: string | null;
    response_delay_min: number;
    response_delay_max: number;
    status: number;
    ai_enabled: boolean;
    agent_visibility: 'all' | 'assigned_only';
}

interface Props {
    chatbot?: Chatbot;
}

// Zod schema definition
const formSchema = z.object({
    name: z.string().min(1, 'Agent name is required'),
    description: z.string().min(1, 'Description is required'),
    system_prompt: z.string().optional(),
    response_delay_min: z.number().min(0, 'Minimum delay must be 0 or greater'),
    response_delay_max: z.number().min(0, 'Maximum delay must be 0 or greater'),
    status: z.number().optional(),
    ai_enabled: z.boolean().optional(),
    agent_visibility: z.enum(['all', 'assigned_only']).optional(),
}).refine((data) => data.response_delay_max >= data.response_delay_min, {
    message: "Maximum delay must be greater than or equal to minimum delay",
    path: ["response_delay_max"],
});

type ChatbotFormValues = z.infer<typeof formSchema>;

export default function ChatbotForm({ chatbot }: Props) {

    const { t } = useTranslation('chatbot');

    const isEditing = !!chatbot;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: chatbot?.name || t('form.head.create'),
            href: chatbot?.id ? route('chatbots.edit', { chatbot: chatbot?.id }): route('chatbots.create'),
        },
        {
            title: isEditing ? t('form.head.edit') : t('form.titles.create'),
            href: isEditing ? route('chatbots.edit', chatbot.id) : route('chatbots.create')
        },
    ];

    // Inertia form for API calls
    const {
        data: inertiaData,
        setData: setInertiaData,
        post,
        put,
        processing,
    } = useInertiaForm<ChatbotFormValues>(
        isEditing ? {
            name: chatbot.name,
            description: chatbot.description,
            system_prompt: chatbot.system_prompt || '',
            response_delay_min: chatbot.response_delay_min,
            response_delay_max: chatbot.response_delay_max,
            status: chatbot.status,
            ai_enabled: chatbot.ai_enabled,
            agent_visibility: chatbot.agent_visibility,
        } : {
            name: '',
            description: '',
            system_prompt: '',
            response_delay_min: 0,
            response_delay_max: 3,
            ai_enabled: false,
            agent_visibility: 'all',
        }
    );

    // React Hook Form with Zod validation
    const form = useForm<ChatbotFormValues>({
        resolver: zodResolver(formSchema),
        defaultValues: inertiaData,
        mode: 'onBlur',
    });

    const aiEnabled = form.watch('ai_enabled');

    // Synchronize react-hook-form with Inertia data
    useEffect(() => {
        const subscription = form.watch((value) => {
            setInertiaData(value as ChatbotFormValues);
        });
        return () => subscription.unsubscribe();
    }, [form, setInertiaData]);

    const onSubmit = (values: ChatbotFormValues) => {
        console.log('Sending form:', values);

        if (isEditing) {
            put(route('chatbots.update', chatbot.id), {
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Agent updated!');
                },
                onError: (errors) => {
                    console.error('Error updating agent:', errors);
                },
            });
        } else {
            post(route('chatbots.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Agent created!');
                    form.reset();
                },
                onError: (errors) => {
                    console.error('Error creating agent:', errors);
                },
            });
        }
    };

    const handleStatusChange = (checked: boolean) => {
        form.setValue('status', checked ? 1 : 0);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${isEditing ? t('form.head.edit') : t('form.head.create')}`} />
            <AppContentDefaultLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <div className="w-full overflow-auto pb-12">
                        <h2 className="text-2xl font-bold">{isEditing ? t('form.titles.edit') : t('form.titles.create')}</h2>

                        {/* Form */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('form.card.title')}</CardTitle>
                                <CardDescription>
                                    {t('form.card.description')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form {...form}>
                                    <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                                        {/* Name */}
                                        <FormField
                                            control={form.control}
                                            name="name"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>{t('form.fields.name')}</FormLabel>
                                                    <FormControl>
                                                        <Input
                                                            placeholder={t('form.fields.name_placeholder')}
                                                            {...field}
                                                        />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        {/* Description */}
                                        <FormField
                                            control={form.control}
                                            name="description"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>{t('form.fields.description')}</FormLabel>
                                                    <FormControl>
                                                        <Textarea
                                                            placeholder={t('form.fields.description_placeholder')}
                                                            rows={3}
                                                            {...field}
                                                        />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        {/* Agent Visibility */}
                                        <FormField
                                            control={form.control}
                                            name="agent_visibility"
                                            render={({ field }) => (
                                                <FormItem className="space-y-3">
                                                    <FormLabel>{t('form.fields.visibility')}</FormLabel>
                                                    <FormControl>
                                                        <RadioGroup
                                                            onValueChange={field.onChange}
                                                            defaultValue={field.value}
                                                            className="flex flex-col space-y-1"
                                                        >
                                                            <FormItem className="flex items-center space-x-3 space-y-0">
                                                                <FormControl>
                                                                    <RadioGroupItem value="all" />
                                                                </FormControl>
                                                                <FormLabel className="font-normal">
                                                                    {t('form.fields.visibility_all')}
                                                                </FormLabel>
                                                            </FormItem>
                                                            <FormItem className="flex items-center space-x-3 space-y-0">
                                                                <FormControl>
                                                                    <RadioGroupItem value="assigned_only" />
                                                                </FormControl>
                                                                <FormLabel className="font-normal">
                                                                    {t('form.fields.visibility_assigned')}
                                                                </FormLabel>
                                                            </FormItem>
                                                        </RadioGroup>
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        {/* AI Enabled */}
                                        <FormField
                                            control={form.control}
                                            name="ai_enabled"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>{t('form.fields.enable_ai')}</FormLabel>
                                                    <div className="flex items-center space-x-2 relative">
                                                        <FormControl>
                                                            <Switch
                                                                checked={field.value}
                                                                onCheckedChange={field.onChange}
                                                            />
                                                        </FormControl>
                                                        <Label className="text-sm font-normal">
                                                            {field.value ? t('form.fields.ai_active') : t('form.fields.ai_inactive')}
                                                        </Label>
                                                    </div>
                                                    <FormDescription>
                                                        {t('form.fields.ai_description')}
                                                    </FormDescription>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        {/* System Prompt (Conditional) */}
                                        {aiEnabled && (
                                            <FormField
                                                control={form.control}
                                                name="system_prompt"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>{t('form.fields.system_prompt')}</FormLabel>
                                                        <FormControl>
                                                            <Textarea
                                                                placeholder={t('form.fields.system_prompt_placeholder')}
                                                                rows={4}
                                                                {...field}
                                                                className="overflow-auto max-h-80"
                                                            />
                                                        </FormControl>
                                                        <FormDescription>
                                                            {t('form.fields.system_prompt_description')}
                                                        </FormDescription>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                        )}

                                        {/* Response Delays */}
                                        <div className="grid grid-cols-2 gap-4">
                                            <FormField
                                                control={form.control}
                                                name="response_delay_min"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>{t('form.fields.min_delay')}</FormLabel>
                                                        <FormControl>
                                                            <Input
                                                                type="number"
                                                                min="0"
                                                                {...field}
                                                                onChange={(e) => field.onChange(parseInt(e.target.value) || 0)}
                                                            />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                            <FormField
                                                control={form.control}
                                                name="response_delay_max"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>{t('form.fields.max_delay')}</FormLabel>
                                                        <FormControl>
                                                            <Input
                                                                type="number"
                                                                min="0"
                                                                {...field}
                                                                onChange={(e) => field.onChange(parseInt(e.target.value) || 0)}
                                                            />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                        </div>

                                        {/* Status - Only show when editing */}
                                        {isEditing && (
                                            <FormField
                                                control={form.control}
                                                name="status"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>{t('form.fields.status')}</FormLabel>
                                                        <div className="flex items-center space-x-2 relative">
                                                            <FormControl>
                                                                <Switch
                                                                    checked={field.value === 1}
                                                                    onCheckedChange={handleStatusChange}
                                                                />
                                                            </FormControl>
                                                            <Label className="text-sm font-normal">
                                                                {field.value === 1 ? t('form.fields.active') : t('form.fields.inactive')}
                                                            </Label>
                                                        </div>
                                                        <FormDescription>
                                                            {t('form.fields.status_description')}
                                                        </FormDescription>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                        )}

                                        {/* Actions */}
                                        <div className="flex items-center justify-end space-x-3 pt-4">
                                            <Button variant="outline" asChild>
                                                <Link href={route('chatbots.index')}>{t('form.actions.cancel')}</Link>
                                            </Button>
                                            <Button type="submit" disabled={processing}>
                                                {processing
                                                    ? (isEditing ? t('form.actions.updating') : t('form.actions.creating'))
                                                    : (isEditing ? t('form.actions.update') : t('form.actions.create'))
                                                }
                                            </Button>
                                        </div>
                                    </form>
                                </Form>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}