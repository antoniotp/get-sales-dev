import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import type { BreadcrumbItem, ChatbotChannel, PageProps as GlobalPageProps } from '@/types';
import { WhatsappWebConnection } from '@/components/chatbot/integrations/WhatsappWebConnection';
import { WhatsappWebSettings } from '@/components/chatbot/integrations/WhatsappWebSettings';
import { WhatsappWebURLGenerator } from '@/components/chatbot/integrations/WhatsappWebURLGenerator';
import { parsePhoneNumberFromString } from 'libphonenumber-js';

interface PageProps extends GlobalPageProps {
    whatsAppWebChatbotChannel: ChatbotChannel | null;
    callRejectionMessage: string | null;
}

export default function WhatsAppWebIntegration() {
    const { t } = useTranslation('chatbot');

    const { chatbot, whatsAppWebChatbotChannel, callRejectionMessage } =
        usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            {
                title: chatbot.name,
                href: route('chatbots.index'),
            },
            {
                title: t('web.breadcrumbs.integrations'),
                href: route('chatbots.integrations', { chatbot: chatbot.id }),
            },
            {
                title: t('web.breadcrumbs.whatsappWeb'),
                href: route('chatbots.integrations.whatsapp-web', { chatbot: chatbot.id }),
            },
        ],
        [chatbot, t]
    );

    const defaultCountry = useMemo(() => {
        const connectedNumber = whatsAppWebChatbotChannel?.data?.phone_number;
        if (connectedNumber) {
            let parsableNumber = connectedNumber;
            if (!parsableNumber.startsWith('+')) {
                parsableNumber = '+' + parsableNumber;
            }
            const phoneNumber = parsePhoneNumberFromString(parsableNumber);
            if (phoneNumber?.country) {
                return phoneNumber.country.toLowerCase();
            }
        }
        return 'us'; // Fallback country
    }, [whatsAppWebChatbotChannel]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('web.headTitle')} />
            <AppContentDefaultLayout>
                <div className="flex flex-col gap-6">
                    <WhatsappWebConnection
                        chatbot={chatbot}
                        chatbotChannel={whatsAppWebChatbotChannel}
                    />

                    {whatsAppWebChatbotChannel && (
                        <>
                            <WhatsappWebSettings
                                chatbotChannel={whatsAppWebChatbotChannel}
                                callRejectionMessage={callRejectionMessage}
                            />
                            <WhatsappWebURLGenerator
                                chatbotId={chatbot.id}
                                chatbotChannelId={whatsAppWebChatbotChannel.id}
                                defaultCountry={defaultCountry}
                            />
                        </>
                    )}
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}
