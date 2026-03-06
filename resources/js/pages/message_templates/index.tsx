import AppLayout from '@/layouts/app-layout'
import MessageTemplateLayout from '@/layouts/message_templates/layout'
import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { router } from '@inertiajs/react';
import { useMemo } from 'react';
import { BreadcrumbItem, PageProps } from '@/types'
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Card } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { DropdownMenu, DropdownMenuTrigger } from '@radix-ui/react-dropdown-menu';
import { Button } from "@/components/ui/button";
import { Badge } from '@/components/ui/badge'
import {
    DeleteIcon,
    MoreHorizontal,
    Clock,
    CheckCircle,
    XCircle,
    PauseCircle,
    Ban,
    Info,
    AlertTriangle,
} from 'lucide-react';
import { DropdownMenuContent, DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {useTranslation, Trans} from "react-i18next";

interface Template {
    id: number;
    name: string;
    status: string;
    category: string;
    platformStatus: number;
    isDeleted: number;
    language: string;
    channel_id: number;
}

interface TemplatesProps {
    allTemplates: Template[];
    activeTemplates: Template[];
    deletedTemplates: Template[];
}


const TemplateTable = ({ templates }: { templates: Template[] }) => {
    const { delete: inertiaDelete } = useForm(); // Destructure delete method from useForm
    const {t} = useTranslation('messageTemplates');

    const handleDelete = (templateId: number) => {
        if (confirm(t('index.actions.delete.confirm'))) {
            inertiaDelete(route('message-templates.destroy', templateId), { // Use inertiaDelete for DELETE request
                onSuccess: () => {
                    // Inertia will automatically re-render the page with updated data.
                },
                onError: (errors) => {
                    console.error('Error deleting template:', errors);
                    alert(t('index.actions.delete.error'));
                }
            });
        }
    };

    const handleSendToReview = (templateId: number) => {
        if (!templateId) return; // Should only be available for existing templates

        // Perform the Inertia POST request to the new endpoint
        router.post(
            route('message-templates.send-for-review', { template: templateId }),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    // The backend redirect already handles the flash message
                },
                onError: (errors) => {
                    console.error('Error sending for review:', errors);
                },
            },
        );
    };

    // Function to render the platform status badge with its appropriate color and icon
    const getPlatformStatusBadge = useMemo(
        () => (status: string) => {
            const statusLower = status.toLowerCase();
            const label = t(`index.platform_status.${statusLower}`, { defaultValue: status });

            switch (statusLower) {
                case 'draft':
                    return (
                        <Badge className="bg-slate-500 text-white">
                            <Clock className="w-3 h-3 mr-1" />
                            {label}
                        </Badge>
                    )
                case 'pending':
                    return (
                        <Badge className="bg-yellow-500 text-white dark:bg-yellow-600">
                            <Clock className="mr-1 h-3 w-3" />
                            {label}
                        </Badge>
                    );
                case 'approved':
                    return (
                        <Badge className="bg-green-500 text-white dark:bg-green-600">
                            <CheckCircle className="mr-1 h-3 w-3" />
                            {label}
                        </Badge>
                    );
                case 'rejected':
                    return (
                        <Badge className="bg-red-500 text-white dark:bg-red-600">
                            <XCircle className="mr-1 h-3 w-3" />
                            {label}
                        </Badge>
                    );
                case 'paused':
                    return (
                        <Badge className="bg-orange-500 text-white dark:bg-orange-600">
                            <PauseCircle className="mr-1 h-3 w-3" />
                            {label}
                        </Badge>
                    );
                case 'disabled':
                    return (
                        <Badge className="bg-gray-500 text-white dark:bg-gray-600">
                            <Ban className="mr-1 h-3 w-3" />
                            {label}
                        </Badge>
                    );
                default:
                    return (
                        <Badge variant="secondary">
                            {status}
                        </Badge>
                    );
            }
        },
        [t],
    );

    const getLocalStatus = useMemo(
        () => (template: Template) => {
            if (template.isDeleted) return (
                <Badge variant="destructive">
                    <DeleteIcon className="mr-1 h-3 w-3" />
                    {t('index.status.deleted')}
                </Badge>
            );
            return template.platformStatus === 1 ? (
                <Badge className="bg-green-500 text-white dark:bg-green-600">
                    <CheckCircle className="mr-1 h-3 w-3" />
                    {t('index.status.active')}
                </Badge>
            ) : (
                <Badge className="bg-gray-500 text-white dark:bg-gray-600">
                    <XCircle className="mr-1 h-3 w-3" />
                    {t('index.status.inactive')}
                </Badge>
            );
        },
        [t],
    );

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead className="w-[100px]">{t('index.table.name')}</TableHead>
                    <TableHead>{t('index.table.platform_status')}</TableHead>
                    <TableHead>{t('index.table.category')}</TableHead>
                    <TableHead>{t('index.table.language')}</TableHead>
                    <TableHead>{t('index.table.active')}</TableHead>
                    <TableHead className="text-right">{t('index.table.actions')}</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {templates.length > 0 ? (
                    templates.map((template) => (
                        <TableRow key={template.id}>
                            <TableCell className="font-medium">{template.name}</TableCell>
                            <TableCell>{getPlatformStatusBadge(template.status)}</TableCell>
                            <TableCell>{template.category}</TableCell>
                            <TableCell>{template.language}</TableCell>
                            <TableCell>{getLocalStatus(template)}</TableCell>
                            {!template.isDeleted && (
                                <TableCell className="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                <span className="sr-only">{t('index.table.open_menu')}</span>
                                                <MoreHorizontal />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={route('message-templates.edit', template.id)}>{t('index.actions.edit')}</Link>
                                            </DropdownMenuItem>
                                            {template.channel_id === 1 && (template.status === 'draft' || template.status === 'rejected') && (
                                                <DropdownMenuItem onClick={() => handleSendToReview(template.id)}>
                                                    {t('index.actions.send_to_review')}
                                                </DropdownMenuItem>
                                            )}
                                            <DropdownMenuItem onClick={() => handleDelete(template.id)}>
                                                {t('index.actions.delete.delete')}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            )}
                        </TableRow>
                    ))
                ) : (
                    <TableRow>
                        <TableCell colSpan={5} className="text-center">
                            {t('index.table.no_templates')}
                        </TableCell>
                    </TableRow>
                )}
            </TableBody>
        </Table>
    );
}

export default function Templates({ allTemplates, activeTemplates, deletedTemplates }: TemplatesProps) {
    const { props } = usePage<PageProps>();

    const { t } = useTranslation('messageTemplates');

    const breadcrumbs: BreadcrumbItem[] = useMemo(() => [
        {
            title: props.chatbot.name,
            href: route('chatbots.edit', props.chatbot.id),
        },
        {
            title: t('index.breadcrumbs.title'),
            href: route('message-templates.index', props.chatbot.id),
        },
    ], [props.chatbot,t]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('index.page.head.title')} />
            <MessageTemplateLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <Card className="w-full p-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-2xl font-bold">{t('index.page.title')}</h2>
                            <Link href={route('message-templates.create')}>
                                <Button>{t('index.actions.create')}</Button>
                            </Link>
                        </div>
                        <div className="overflow-auto">
                            <div className="mb-2 flex items-start gap-2 rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm text-muted-foreground dark:border-slate-800 dark:bg-slate-900/50">
                                <Info className="mt-0.5 h-4 w-4 shrink-0" />
                                <div className="space-y-3 leading-relaxed">
                                    <p>{t('index.description_part1')}</p>
                                    <p>{t('index.description_part2')}</p>
                                    <p>{t('index.description_part3')}</p>
                                </div>
                            </div>
                            <Alert className="mb-2 border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-500">
                                <AlertTriangle className="h-4 w-4 !text-amber-600" />
                                <AlertDescription>
                                    <span className="inline-block leading-normal">
                                        <Trans
                                            t={t}
                                            i18nKey="index.payment_notice"
                                            parent="span"
                                            components={[
                                                <strong key="bold" />,
                                                <a
                                                    key="link1"
                                                    href="https://business.facebook.com/wa/manage/home/"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-blue-800 underline"
                                                />,
                                                <a
                                                    key="link2"
                                                    href="https://www.facebook.com/business/help/488291839463771"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-blue-800 underline"
                                                />,
                                            ]}
                                        />
                                    </span>
                                </AlertDescription>
                            </Alert>
                            <Tabs defaultValue="template_library" className="w-full">
                                <TabsList>
                                    <TabsTrigger value="template_library">{t('index.page.tab_name.template_library')}</TabsTrigger>
                                    <TabsTrigger value="active_templates">{t('index.page.tab_name.active_templates')}</TabsTrigger>
                                    <TabsTrigger value="deleted_templates">{t('index.page.tab_name.deleted_templates')}</TabsTrigger>
                                </TabsList>
                                <TabsContent value="template_library">
                                    <TemplateTable templates={allTemplates} />
                                </TabsContent>
                                <TabsContent value="active_templates">
                                    <TemplateTable templates={activeTemplates} />
                                </TabsContent>
                                <TabsContent value="deleted_templates">
                                    <TemplateTable templates={deletedTemplates} />
                                </TabsContent>
                            </Tabs>
                        </div>
                    </Card>
                </div>
            </MessageTemplateLayout>
        </AppLayout>
    );
}
