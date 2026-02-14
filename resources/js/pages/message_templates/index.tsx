import AppLayout from '@/layouts/app-layout'
import MessageTemplateLayout from '@/layouts/message_templates/layout'
import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { useMemo } from 'react';
import { BreadcrumbItem, PageProps } from '@/types'
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Card } from "@/components/ui/card"
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { DropdownMenu, DropdownMenuLabel, DropdownMenuTrigger } from '@radix-ui/react-dropdown-menu';
import { Button } from "@/components/ui/button";
import { Badge } from '@/components/ui/badge'
import {
    DeleteIcon,
    MoreHorizontal,
    Clock,
    CheckCircle,
    XCircle,
    PauseCircle,
    Ban
} from 'lucide-react';
import { DropdownMenuContent, DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useTranslation } from 'react-i18next';

interface Template {
    id: number;
    name: string;
    status: string;
    category: string;
    platformStatus: number;
    isDeleted: number;
    language: string;
}

interface TemplatesProps {
    allTemplates: Template[];
    activeTemplates: Template[];
    deletedTemplates: Template[];
}

const TemplateTable = ({ templates }: { templates: Template[] }) => {
    const { delete: inertiaDelete } = useForm(); // Destructure delete method from useForm
    const { t } = useTranslation('message_templates');

    const handleDelete = (templateId: number) => {
        if (confirm(t('templates.confirm_delete'))) {
            inertiaDelete(route('message-templates.destroy', templateId), { // Use inertiaDelete for DELETE request
                onSuccess: () => {
                    // Optionally, you can add some feedback here
                    // Inertia will automatically re-render the page with updated data.
                },
                onError: (errors) => {
                    console.error('Error deleting template:', errors);
                    alert(t('templates.delete_failed'));
                }
            });
        }
    };

    // Function to render platform status badge with appropriate color and icon
    const getPlatformStatusBadge = useMemo(
        () => (status: string) => {
            const statusLower = status.toLowerCase();

            switch (statusLower) {
                case 'pending':
                    return (
                        <Badge className="bg-yellow-500 text-white dark:bg-yellow-600">
                            <Clock className="w-3 h-3 mr-1" />
                            {t('templates.status.pending')}
                        </Badge>
                    );
                case 'approved':
                    return (
                        <Badge className="bg-green-500 text-white dark:bg-green-600">
                            <CheckCircle className="w-3 h-3 mr-1" />
                            {t('templates.status.approved')}
                        </Badge>
                    );
                case 'rejected':
                    return (
                        <Badge className="bg-red-500 text-white dark:bg-red-600">
                            <XCircle className="w-3 h-3 mr-1" />
                            {t('templates.status.rejected')}
                        </Badge>
                    );
                case 'paused':
                    return (
                        <Badge className="bg-orange-500 text-white dark:bg-orange-600">
                            <PauseCircle className="w-3 h-3 mr-1" />
                            {t('templates.status.paused')}
                        </Badge>
                    );
                case 'disabled':
                    return (
                        <Badge className="bg-gray-500 text-white dark:bg-gray-600">
                            <Ban className="w-3 h-3 mr-1" />
                            {t('templates.status.disabled')}
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
                    <DeleteIcon className="w-3 h-3 mr-1" />
                    {t('templates.deleted')}
                </Badge>
            );
            return template.platformStatus === 1 ? (
                <Badge className="bg-green-500 text-white dark:bg-green-600">
                    <CheckCircle className="w-3 h-3 mr-1" />
                    {t('templates.active')}
                </Badge>
            ) : (
                <Badge className="bg-gray-500 text-white dark:bg-gray-600">
                    <XCircle className="w-3 h-3 mr-1" />
                    {t('templates.inactive')}
                </Badge>
            );
        },
        [t],
    );

    return (
        <Table>
            <TableCaption>{t('templates.caption')}</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead className="w-[100px]">{t('templates.table.name')}</TableHead>
                    <TableHead>{t('templates.table.platform_status')}</TableHead>
                    <TableHead>{t('templates.table.category')}</TableHead>
                    <TableHead>{t('templates.table.language')}</TableHead>
                    <TableHead>{t('templates.table.active')}</TableHead>
                    <TableHead className="text-right">{t('templates.table.actions')}</TableHead>
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
                                                <span className="sr-only">{t('templates.open_menu')}</span>
                                                <MoreHorizontal />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuLabel>{t('templates.actions')}</DropdownMenuLabel>
                                            <DropdownMenuItem asChild>
                                                <Link href={route('message-templates.edit', template.id)}>
                                                    {t('templates.edit')}
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem onClick={() => handleDelete(template.id)}>
                                                {t('templates.delete')}
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
                            {t('templates.no_templates')}
                        </TableCell>
                    </TableRow>
                )}
            </TableBody>
        </Table>
    )
}

export default function Templates({ allTemplates, activeTemplates, deletedTemplates }: TemplatesProps) {
    const { props } = usePage<PageProps>();
    const { t } = useTranslation('message_templates');

    const breadcrumbs: BreadcrumbItem[] = useMemo(() => [
        {
            title: props.chatbot.name,
            href: route('chatbots.edit', props.chatbot.id),
        },
        {
            title: t('templates.management'),
            href: route('message-templates.index', props.chatbot.id),
        },
    ], [props.chatbot, t]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('templates.page_title')} />
            <MessageTemplateLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <Card className="w-full p-3">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-2xl font-bold">{t('templates.title')}</h2>
                            <Link href={route('message-templates.create')}>
                                <Button>{t('templates.create')}</Button>
                            </Link>
                        </div>
                        <Tabs defaultValue="template_library" className="w-full overflow-auto">
                            <TabsList>
                                <TabsTrigger value="template_library">{t('templates.tabs.library')}</TabsTrigger>
                                <TabsTrigger value="active_templates">{t('templates.tabs.active')}</TabsTrigger>
                                <TabsTrigger value="deleted_templates">{t('templates.tabs.deleted')}</TabsTrigger>
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
                    </Card>
                </div>
            </MessageTemplateLayout>
        </AppLayout>
    )
}