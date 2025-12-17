import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { useMemo } from 'react';
import type { BreadcrumbItem, ChatbotChannel, PageProps as GlobalPageProps } from '@/types';
import { WhatsappWebConnection } from '@/components/chatbot/integrations/WhatsappWebConnection';

interface PageProps extends GlobalPageProps {
    whatsAppWebChatbotChannel: ChatbotChannel | null;
}

export default function WhatsAppWebIntegration() {
    const { chatbot, whatsAppWebChatbotChannel } = usePage<PageProps>().props;

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
            {
                title: 'WhatsApp Web',
                href: route('chatbots.integrations.whatsapp-web', { chatbot: chatbot.id }),
            },
        ],
        [chatbot]
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="WhatsApp Web Integration" />
            <AppContentDefaultLayout>
                <div className="flex flex-col gap-6">
                    {/* Render the unified WhatsappWebConnection component */}
                    <WhatsappWebConnection chatbot={chatbot} chatbotChannel={whatsAppWebChatbotChannel} />
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}
