import AppLayout from '@/layouts/app-layout';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { BreadcrumbItem } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { Bot, Plus, MoreHorizontal, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Chatbot {
    id: number;
    name: string;
    description: string | null;
    status: number;
    created_at: string;
}

interface ChatbotsIndexProps {
    chatbots: Chatbot[];
    hasNoChatbots: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Agents', href: route('chatbots.index') },
];

interface FlashMessages {
    success?: string;
    error?: string;
}

interface PageProps {
    flash: FlashMessages;
    [key: string]: never|FlashMessages;
}

export default function ChatbotsIndex({ chatbots, hasNoChatbots }: ChatbotsIndexProps) {
    const { props } = usePage<PageProps>();

    useEffect(() => {
        if (props.flash?.success) {
            toast.success(props.flash.success);
        } else if (props.flash?.error) {
            toast.error(props.flash.error);
        }
    }, [props.flash]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Agents | List" />
            <AppContentDefaultLayout>
                <div className="space-y-6">
                    {/* Header Section */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Agents</h1>
                            <p className="text-muted-foreground">
                                Manage your AI Agents and their configurations
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={route('chatbots.create')}>
                                <Plus className="mr-2 h-4 w-4" />
                                Create Agent
                            </Link>
                        </Button>
                    </div>

                    {/* Content Section */}
                    {hasNoChatbots ? (
                        <EmptyState />
                    ) : (
                        <ChatbotGrid chatbots={chatbots} />
                    )}
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}

function EmptyState() {
    return (
        <div className="flex min-h-[400px] flex-col items-center justify-center rounded-lg border border-dashed p-8 text-center">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                <Bot className="h-8 w-8 text-muted-foreground" />
            </div>
            <h3 className="mt-4 text-lg font-semibold">No chatbots found</h3>
            <p className="mb-6 mt-2 text-sm text-muted-foreground max-w-sm">
                You haven't created any agents yet. Create your first agents to get started with automated conversations.
            </p>
            <Button asChild>
                <Link href={route('chatbots.create')}>
                    <Plus className="mr-2 h-4 w-4" />
                    Create Your First Agent
                </Link>
            </Button>
        </div>
    );
}

function ChatbotGrid({ chatbots }: { chatbots: Chatbot[] }) {
    return (
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {chatbots.map((chatbot) => (
                <ChatbotCard key={chatbot.id} chatbot={chatbot} />
            ))}
        </div>
    );
}

function ChatbotCard({ chatbot }: { chatbot: Chatbot }) {
    const isActive = chatbot.status === 1;
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDelete = () => {
        setIsDeleting(true);
        router.delete(route('chatbots.destroy', chatbot.id), {
            onSuccess: () => {
                setShowDeleteDialog(false);
                setIsDeleting(false);
            },
            onError: () => {
                setIsDeleting(false);
                toast.error('Failed to delete agent. Please try again.');
            }
        });
    };

    return (
        <>
            <Card className="group hover:shadow-md transition-shadow duration-200">
                <CardHeader className="pb-0">
                    <div className="flex items-start justify-between">
                        <div className="flex items-center space-x-2">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-purple-600">
                                <Bot className="h-5 w-5 text-white" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <CardTitle className="truncate text-base">{chatbot.name}</CardTitle>
                            </div>
                        </div>
                        <div className="flex items-center space-x-1">
                            <div
                                className={`h-2 w-2 rounded-full ${
                                    isActive ? 'bg-green-500' : 'bg-gray-400'
                                }`}
                            />
                            <span className="text-xs text-muted-foreground">
                                {isActive ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </div>
                </CardHeader>

                <CardContent className="pt-0">
                    <CardDescription className="line-clamp-3 min-h-[30px] text-sm">
                        {chatbot.description || 'No description provided'}
                    </CardDescription>

                    <div className="mt-4 flex items-center justify-between">
                        <span className="text-xs text-muted-foreground">
                            Created {chatbot.created_at}
                        </span>
                        <div className="flex items-center space-x-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('chatbots.edit', chatbot.id)}>
                                    View Details
                                </Link>
                            </Button>

                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="sm" className="h-8 w-8 p-0">
                                        <MoreHorizontal className="h-4 w-4" />
                                        <span className="sr-only">Open menu</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        onClick={() => setShowDeleteDialog(true)}
                                        className="text-red-600 focus:text-red-600"
                                    >
                                        <Trash2 className="mr-2 h-4 w-4" />
                                        Delete Agent
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete Agent</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete "{chatbot.name}"? This action cannot be undone.
                            All conversations and data associated with this agent will be permanently removed.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={isDeleting}>
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDelete}
                            disabled={isDeleting}
                            className="bg-red-600 hover:bg-red-700 focus:ring-red-600"
                        >
                            {isDeleting ? 'Deleting...' : 'Delete Agent'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
