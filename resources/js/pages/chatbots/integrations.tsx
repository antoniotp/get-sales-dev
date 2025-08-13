import { FacebookEmbeddedSignUpBtn } from '@/components/facebook-embedded-signup-btn';
import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Card, CardContent, CardDescription } from '@/components/ui/card';
import { useMemo } from 'react';
import type { BreadcrumbItem, Chatbot } from '@/types';

interface PageProps {
    chatbot: Chatbot;
    [key: string]: never | Chatbot;
}
export default function Integrations() {
    const { chatbot } = usePage<PageProps>().props as { chatbot: Chatbot };

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            {
                title: chatbot.name,
                href: route('chatbots.index'),
            },
            {
                title: 'Integrations',
                href: route('chatbots.integrations', { chatbot: chatbot.id }),
            },
        ],
        [chatbot]
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Agents | Integrations" />
            <AppContentDefaultLayout>
                <div className="space-y-6">
                    {/* Header Section */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Agents</h1>
                            <p className="text-muted-foreground">Manage your AI Agents integrations</p>
                        </div>
                    </div>
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <Card>
                            <CardContent>
                                <CardDescription>
                                    <FacebookEmbeddedSignUpBtn onSuccess={() => {
                                        console.log('Facebook sign-up successful');
                                    }}/>
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}
