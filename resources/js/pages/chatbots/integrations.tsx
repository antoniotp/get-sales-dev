import AppLayout from '@/layouts/app-layout';
import { Head, usePage, Link } from '@inertiajs/react';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useMemo } from 'react';
import type { BreadcrumbItem, ChatbotChannel as GlobalChatbotChannel, PageProps as GlobalPageProps } from '@/types';
import { WhatsAppIcon } from '@/components/icons/whatsapp';

interface ChatbotChannel extends GlobalChatbotChannel {
    slug: string;
}

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

    // Check if there is a connected WhatsApp channel (slug = 'whatsapp' for WABA)
    const whatsAppBusinessChannel = useMemo(() => {
        return linkedChannels.find((channel) => channel.slug === 'whatsapp');
    }, [linkedChannels]);

    // Check if there is a connected WhatsApp Web channel (slug = 'whatsapp-web' for WA-Web)
    const whatsAppWebChannel = useMemo(() => {
        return linkedChannels.find((channel) => channel.slug === 'whatsapp-web');
    }, [linkedChannels]);

    const whatsAppBusinessButton = useMemo(() => {
        const buttonText = whatsAppBusinessChannel ? 'Manage' : 'Connect';
        return (
            <Link href={route('chatbots.integrations.whatsapp-business', { chatbot: chatbot.id })}>
                <Button>{buttonText}</Button>
            </Link>
        );
    }, [whatsAppBusinessChannel, chatbot.id]);

    const whatsAppWebButton = useMemo(() => {
        const buttonText = whatsAppWebChannel ? 'Manage' : 'Connect';
        return (
            <Link href={route('chatbots.integrations.whatsapp-web', { chatbot: chatbot.id })}>
                <Button>{buttonText}</Button>
            </Link>
        );
    }, [whatsAppWebChannel, chatbot.id]);

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
                        <Card className="flex flex-col justify-between">
                            <div>
                                <CardHeader className="flex flex-row items-center gap-4">
                                    <img
                                        src="/images/whatsapp-business-app-icon-320.png"
                                        alt="WhatsApp Business Icon"
                                        className="text-white rounded-lg w-12 h-12"
                                    />
                                    <CardTitle>WhatsApp Business API</CardTitle>
                                </CardHeader>
                                <CardContent className="py-4">
                                    <CardDescription>
                                        Connect via the official Meta API for scalable, reliable communication.
                                    </CardDescription>
                                </CardContent>
                            </div>
                            <CardFooter className="flex justify-end gap-2">{whatsAppBusinessButton}</CardFooter>
                        </Card>
                        <Card className="flex flex-col justify-between">
                            <div>
                                <CardHeader className="flex flex-row items-center gap-2">
                                    <WhatsAppIcon className="bg-green-500 p-2 text-white rounded-lg" size={48} />
                                    <CardTitle>WhatsApp Web (QR)</CardTitle>
                                </CardHeader>
                                <CardContent className="py-4">
                                    <CardDescription>
                                        Connect a number by scanning a QR code, ideal for testing and small-scale use.
                                    </CardDescription>
                                </CardContent>
                            </div>
                            <CardFooter className="flex justify-end gap-2">{whatsAppWebButton}</CardFooter>
                        </Card>
                    </div>
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}
