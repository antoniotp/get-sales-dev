import AppLayout from '@/layouts/app-layout'
import MessageTemplateLayout from '@/layouts/message_templates/layout'
import { Head, Link , useForm } from '@inertiajs/react'
import { useMemo } from 'react';
import type { BreadcrumbItem } from '@/types'
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Card } from "@/components/ui/card"
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { DropdownMenu, DropdownMenuLabel, DropdownMenuTrigger } from '@radix-ui/react-dropdown-menu';
import {Button} from "@/components/ui/button";
import { Badge } from '@/components/ui/badge'
import { BadgeCheckIcon, DeleteIcon, MoreHorizontal } from 'lucide-react';
import { DropdownMenuContent, DropdownMenuItem } from '@/components/ui/dropdown-menu';

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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Message templates management',
        href: route('message-templates.index'),
    },
];

const TemplateTable = ({ templates }: { templates: Template[] }) => {
    const { delete: inertiaDelete } = useForm(); // Destructure delete method from useForm

    const handleDelete = (templateId: number) => {
        if (confirm('Are you sure you want to delete this template?')) {
            inertiaDelete(route('message-templates.destroy', templateId), { // Use inertiaDelete for DELETE request
                onSuccess: () => {
                    // Optionally, you can add some feedback here,
                    // Inertia will automatically re-render the page with updated data.
                },
                onError: (errors) => {
                    console.error('Error deleting template:', errors);
                    alert('Failed to delete template.');
                }
            });
        }
    };

    const getLocalStatus = useMemo(
        () => (template: Template) => {
            if (template.isDeleted) return (
                <Badge
                    className=""
                    variant="destructive"
                >
                    <DeleteIcon />
                    Deleted
                </Badge>
            );
            return template.platformStatus === 1 ? (
                <Badge className="bg-blue-500 text-white dark:bg-blue-600">
                    <BadgeCheckIcon />
                    Active
                </Badge>
            ) : (
                <Badge className="bg-yellow-500 text-white dark:bg-yellow-600">
                    <BadgeCheckIcon />
                    Inactive
                </Badge>
            );
        },
        [],
    );


    return (
        <Table>
            <TableCaption>A list of your templates.</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead className="w-[100px]">Name</TableHead>
                    <TableHead>Platform Status</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead>Language</TableHead>
                    <TableHead>Active</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {templates.length > 0 ? (
                    templates.map((template) => (
                        <TableRow key={template.id}>
                            <TableCell className="font-medium">{template.name}</TableCell>
                            <TableCell>{template.status}</TableCell>
                            <TableCell>{template.category}</TableCell>
                            <TableCell>{template.language}</TableCell>
                            <TableCell>{getLocalStatus(template)}</TableCell>
                            {!template.isDeleted && (
                                <TableCell className="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                <span className="sr-only">Open menu</span>
                                                <MoreHorizontal />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                            <DropdownMenuItem asChild>
                                                <Link href={route('message-templates.edit', template.id)}>Edit</Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem onClick={() => handleDelete(template.id)}>Delete</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            )}
                        </TableRow>
                    ))
                ) : (
                    <TableRow>
                        <TableCell colSpan={5} className="text-center">No templates found</TableCell>
                    </TableRow>
                )}
            </TableBody>
        </Table>
    )
}

export default function Templates({ allTemplates, activeTemplates, deletedTemplates }: TemplatesProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Message templates | List" />
            <MessageTemplateLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <Card className="w-full p-3">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-2xl font-bold">Message Templates</h2>
                            <Link href={route('message-templates.create')}>
                                <Button>Create Template</Button>
                            </Link>
                        </div>
                        <Tabs defaultValue="template_library" className="w-full">
                            <TabsList>
                                <TabsTrigger value="template_library">Template Library</TabsTrigger>
                                <TabsTrigger value="active_templates">Active</TabsTrigger>
                                <TabsTrigger value="deleted_templates">Deleted</TabsTrigger>
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
