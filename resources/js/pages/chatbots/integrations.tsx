import AppLayout from '@/layouts/app-layout';
import { Head, usePage, Link } from '@inertiajs/react';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useMemo } from 'react';
import type { BreadcrumbItem, ChatbotChannel, PageProps as GlobalPageProps } from '@/types';
import { WhatsAppIcon } from '@/components/icons/whatsapp';

interface PageProps extends GlobalPageProps{
    linkedChannels: ChatbotChannel[];
}

export default function Integrations() {
    const props = usePage<PageProps>().props;
    const chatbot = props.chatbot;
    const linkedChannels = props.linkedChannels;

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            {
                title: chatbot.name,
                href: route('chatbots.edit', { chatbot: chatbot.id }),
            },
            {
                title: 'Integrations',
                href: route('chatbots.integrations', { chatbot: chatbot.id }),
            },
        ],
        [chatbot]
    );

    // Check if there is a connected WhatsApp channel (channel_id = 1)
    const whatsappChannel = useMemo(() => {
        return linkedChannels.find(channel => channel.channel_id === 1);
    }, [linkedChannels]);

    const whatsappButton = useMemo(() => {
        const buttonText = whatsappChannel ? 'Manage' : 'Connect';
        return (
            <Link href={route('chatbots.integrations.whatsapp', { chatbot: chatbot.id })}>
                <Button>{buttonText}</Button>
            </Link>
        );
    }, [whatsappChannel, chatbot.id]);

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
                                <CardHeader className="flex flex-row items-center gap-2">
                                    <WhatsAppIcon className="bg-green-500 p-2 text-white rounded-lg" size={48} />
                                    <CardTitle>WhatsApp</CardTitle>
                                </CardHeader>
                                <CardContent className="py-4">
                                    <CardDescription>Connect your WhatsApp account in just a few steps.</CardDescription>
                                </CardContent>
                                <CardFooter className="flex justify-end gap-2">
                                    {/*<Button variant="outline">Guía de conexión</Button>*/}
                                    {whatsappButton}
                                </CardFooter>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}
