import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useMemo } from 'react';
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
import { WhatsappWebConnection } from '@/components/chatbot/integrations/WhatsappWebConnection';

interface PageProps extends GlobalPageProps {
    whatsAppChannel: ChatbotChannel | null;
    whatsAppWebChatbotChannel: ChatbotChannel | null;
    isWhatsappOnboardingEnabled: boolean;
}

export default function WhatsAppIntegration() {
    const { chatbot, whatsAppChannel, whatsAppWebChatbotChannel, isWhatsappOnboardingEnabled } = usePage<PageProps>().props;

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
                title: 'WhatsApp',
                href: route('chatbots.integrations.whatsapp', { chatbot: chatbot.id }),
            },
        ],
        [chatbot]
    );

    const whatsAppLink = useMemo(() => {
        if (!whatsAppChannel || !whatsAppChannel.data.display_phone_number) {
            return '';
        }
        return `https://wa.me/${whatsAppChannel.data.display_phone_number}`;
    }, [whatsAppChannel]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="WhatsApp Integration" />
            <AppContentDefaultLayout>
                <div className="flex flex-col gap-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Connected Number (Official API)</CardTitle>
                                <CardDescription>
                                    The WhatsApp number connected to this agent via the Official Business API.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Phone Number</TableHead>
                                            <TableHead>Display Name</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>
                                                <span className="sr-only">Actions</span>
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {whatsAppChannel ? (
                                            <TableRow>
                                                <TableCell>{whatsAppChannel.data.display_phone_number}</TableCell>
                                                <TableCell>{whatsAppChannel.data.phone_number_verified_name}</TableCell>
                                                <TableCell>{whatsAppChannel.status === 1 ? 'Active' : 'Inactive'}</TableCell>
                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <span className="sr-only">Open menu</span>
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem>Delete</DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            <>
                                                <TableRow>
                                                    <TableCell colSpan={4} className="text-center">
                                                        <p className="py-4">No WhatsApp number connected.</p>
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
                                <CardTitle>Test Connection</CardTitle>
                                <CardDescription>Scan the QR code to start a conversation.</CardDescription>
                            </CardHeader>
                            <CardContent className="flex items-center justify-center">
                                {whatsAppLink ? (
                                    <QRCodeSVG value={whatsAppLink} size={128} />
                                ) : (
                                    <p>No QR code available. Please connect a number first.</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                    {/* Render the unified WhatsappWebConnection component */}
                    <WhatsappWebConnection chatbot={chatbot} chatbotChannel={whatsAppWebChatbotChannel} />
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}
