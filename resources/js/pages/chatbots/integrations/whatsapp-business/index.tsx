import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import type { BreadcrumbItem, ChatbotChannel, PageProps as GlobalPageProps } from '@/types';
import { QRCodeSVG } from 'qrcode.react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { MoreHorizontal } from 'lucide-react';
import { FacebookEmbeddedSignUpBtn } from '@/components/facebook-embedded-signup-btn';

interface PageProps extends GlobalPageProps {
    whatsAppChannel: ChatbotChannel | null;
    isWhatsappOnboardingEnabled: boolean;
}

export default function WhatsAppBusinessIntegration() {
    const { t } = useTranslation('chatbot');
    const { chatbot, whatsAppChannel, isWhatsappOnboardingEnabled } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            {
                title: chatbot.name,
                href: route('chatbots.index'),
            },
            {
                title: t('business.breadcrumb_integrations'),
                href: route('chatbots.integrations', { chatbot: chatbot.id }),
            },
            {
                title: t('business.breadcrumb_whatsapp'),
                href: route('chatbots.integrations.whatsapp-business', { chatbot: chatbot.id }),
            },
        ],
        [chatbot, t]
    );

    const whatsAppLink = useMemo(() => {
        if (!whatsAppChannel || !whatsAppChannel.data.display_phone_number) {
            return '';
        }
        return `https://wa.me/${whatsAppChannel.data.display_phone_number}`;
    }, [whatsAppChannel]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('business.page_title')} />
            <AppContentDefaultLayout>
                <div className="flex flex-col gap-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('business.connected_number_title')}</CardTitle>
                                <CardDescription>
                                    {t('business.connected_number_description')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('business.table_phone')}</TableHead>
                                            <TableHead>{t('business.table_display_name')}</TableHead>
                                            <TableHead>{t('business.table_status')}</TableHead>
                                            <TableHead>
                                                <span className="sr-only">{t('business.table_actions')}</span>
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {whatsAppChannel ? (
                                            <TableRow>
                                                <TableCell>{whatsAppChannel.data.display_phone_number}</TableCell>
                                                <TableCell>{whatsAppChannel.data.phone_number_verified_name}</TableCell>
                                                <TableCell>
                                                    {whatsAppChannel.status === 1
                                                        ? t('business.status_active')
                                                        : t('business.status_inactive')}
                                                </TableCell>
                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <span className="sr-only">
                                                                    {t('business.open_menu')}
                                                                </span>
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem>
                                                                {t('business.delete')}
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            <>
                                                <TableRow>
                                                    <TableCell colSpan={4} className="text-center">
                                                        <p className="py-4">
                                                            {t('business.no_number_connected')}
                                                        </p>
                                                        <FacebookEmbeddedSignUpBtn
                                                            isWhatsappOnboardingEnabled={isWhatsappOnboardingEnabled}
                                                            onSuccess={() => {
                                                                console.log('Facebook sign-up successful');
                                                                router.reload();
                                                            }}
                                                        />
                                                    </TableCell>
                                                </TableRow>
                                            </>
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('business.test_connection_title')}</CardTitle>
                                <CardDescription>
                                    {t('business.test_connection_description')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex items-center justify-center">
                                {whatsAppLink ? (
                                    <QRCodeSVG value={whatsAppLink} size={128} />
                                ) : (
                                    <p>{t('business.no_qr_available')}</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}