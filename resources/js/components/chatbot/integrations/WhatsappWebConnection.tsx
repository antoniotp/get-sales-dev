import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useEffect, useMemo, useRef, useState } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import axios from 'axios';
import type { Chatbot, ChatbotChannel } from '@/types';
import Echo from 'laravel-echo';
import { Loader2, MoreHorizontal } from 'lucide-react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from 'react-i18next';

interface Props {
    chatbot: Chatbot;
    chatbotChannel: ChatbotChannel | null;
}

type ConnectionStatus =
    | 'VERIFYING'
    | 'CONNECTED'
    | 'DISCONNECTED'
    | 'ERROR'
    | 'CONNECTING_NEW_SESSION'
    | 'RECONNECTING_WAITING_FOR_EVENTS';

export function WhatsappWebConnection({ chatbot, chatbotChannel }: Props) {
    const { t } = useTranslation('chatbot');

    const [status, setStatus] = useState<ConnectionStatus>('VERIFYING');
    const [qrCode, setQrCode] = useState<string | null>(null);
    const [sessionId, setSessionId] = useState<string | null>(null);
    const [reconnecting, setReconnecting] = useState<boolean>(false);
    const echoRef = useRef<Echo<'pusher'> | null>(null);

    // Initialize Echo connection
    useEffect(() => {
        echoRef.current = new Echo<'pusher'>({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS: true,
        });

        return () => {
            echoRef.current?.disconnect();
        };
    }, []);

    // Initial status check or set up for a new connection
    useEffect(() => {
        if (chatbotChannel && chatbotChannel?.data?.phone_number) {
            // Existing channel, fetch its status
            setStatus('VERIFYING');
            axios
                .get(route('chatbots.integrations.whatsapp-web.status', { chatbot: chatbot.id }))
                .then((response) => {
                    setStatus(response.data.status as ConnectionStatus);
                    if (chatbotChannel.data?.phone_number) {
                        setSessionId(chatbotChannel.data.session_id);
                    }
                })
                .catch(() => {
                    setStatus('ERROR');
                });
        } else {
            // No existing channel, ready to start a new session
            setStatus('DISCONNECTED'); // Or 'IDLE'
        }
    }, [chatbot.id, chatbotChannel]);

    const handleStartNewSession = async () => {
        setStatus('CONNECTING_NEW_SESSION');
        setQrCode(null); // Reset QR code on a new attempt
        try {
            const response = await axios.post(
                route('chatbots.integrations.whatsapp-web.start', { chatbot: chatbot.id })
            );
            if (response.data.session_id) {
                setSessionId(response.data.session_id);
            }
        } catch (error) {
            console.error('Failed to start new session', error);
            setStatus('DISCONNECTED');
        }
    };

    const handleReconnect = async () => {
        setReconnecting(true);
        setQrCode(null); // Clear any old QR code
        try {
            const response = await axios.post(
                route('chatbots.integrations.whatsapp-web.reconnect', { chatbot: chatbot.id })
            );
            if (response.status === 200) {
                if (!response.data.success) {
                    if (response.data.message === 'session_not_found') {
                        handleStartNewSession();
                    }
                } else {
                    setStatus('RECONNECTING_WAITING_FOR_EVENTS');
                }
            }
        } catch (error) {
            console.error('Failed to send reconnect command', error);
            setStatus('ERROR'); // Set the status to error if the reconnect command fails
        } finally {
            setReconnecting(false);
        }
    };

    // Listen for QR code and connection status updates
    useEffect(() => {
        if (sessionId && echoRef.current) {
            const channelName = echoRef.current.private(`whatsapp-web.${sessionId}`);

            // Listen for QR code event
            channelName.listen('.qr-code.received', (e: { qrCode: string }) => {
                setQrCode(e.qrCode);
                setStatus('DISCONNECTED');
            });

            // Listen for connection status updated event
            channelName.listen('.connection.status.updated', (e: { status: number }) => {
                switch (e.status) {
                    case 0: // STATUS_DISCONNECTED
                        setStatus('DISCONNECTED');
                        setQrCode(null); // Clear QR code on disconnect
                        setSessionId(null); // Clear session ID if fully disconnected
                        break;
                    case 1: // STATUS_CONNECTED
                        setStatus('CONNECTED');
                        setQrCode(null); // Clear QR code on connected
                        break;
                    case 2: // STATUS_CONNECTING
                        setStatus('VERIFYING'); // Use VERIFYING for the CONNECTING state
                        break;
                    default:
                        setStatus('DISCONNECTED'); // Fallback
                        break;
                }
            });
        }

        return () => {
            if (sessionId && echoRef.current) {
                echoRef.current.leave(`whatsapp-web.${sessionId}`);
            }
        };
    }, [sessionId]);

    const whatsAppLink = useMemo(() => {
        if (!chatbotChannel || !chatbotChannel.data.display_phone_number) {
            return '';
        }
        return `https://wa.me/${chatbotChannel.data.display_phone_number}`;
    }, [chatbotChannel]);

    const renderContent = () => {
        switch (status) {
            case 'VERIFYING':
            case 'CONNECTING_NEW_SESSION':
            case 'RECONNECTING_WAITING_FOR_EVENTS':
                return (
                    <div className="flex items-center justify-center gap-2">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        <span>
                            {status === 'VERIFYING' &&
                                t('webConnection.status.verifying')}
                            {status === 'CONNECTING_NEW_SESSION' &&
                                !qrCode &&
                                t('webConnection.status.waitingQr')}
                            {status === 'RECONNECTING_WAITING_FOR_EVENTS' &&
                                t('webConnection.status.reconnectingWaiting')}
                        </span>
                    </div>
                );

            case 'CONNECTED':
                return (
                    <div className="grid gap-6 md:grid-cols-2"> {/* Added grid layout */}
                        <Card> {/* Card for the table */}
                            <CardHeader>
                                <CardTitle>
                                    {t('webConnection.connected.title')}
                                </CardTitle>
                                <CardDescription>
                                    {t('webConnection.connected.description')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>
                                                {t('webConnection.table.phone')}
                                            </TableHead>
                                            <TableHead>
                                                {t('webConnection.table.displayName')}
                                            </TableHead>
                                            <TableHead>
                                                {t('webConnection.table.status')}
                                            </TableHead>
                                            <TableHead>
                                                <span className="sr-only">
                                                    {t('webConnection.table.actions')}
                                                </span>
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {chatbotChannel?.data?.phone_number ? (
                                            <TableRow>
                                                <TableCell>
                                                    {chatbotChannel.data?.display_phone_number ||
                                                        t('webConnection.common.na')}
                                                </TableCell>
                                                <TableCell>
                                                    {chatbotChannel.data?.phone_number_verified_name ||
                                                        t('webConnection.common.na')}
                                                </TableCell>
                                                <TableCell>
                                                    {chatbotChannel.status === 1
                                                        ? t('webConnection.common.active')
                                                        : t('webConnection.common.inactive')}
                                                </TableCell>
                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <span className="sr-only">
                                                                    {t('webConnection.table.openMenu')}
                                                                </span>
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem>
                                                                {t('webConnection.actions.delete')}
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={4} className="text-center">
                                                    <p className="py-4">
                                                        {t('webConnection.connected.noNumber')}
                                                    </p>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        <Card> {/* Card for the test QR code */}
                            <CardHeader>
                                <CardTitle>
                                    {t('webConnection.test.title')}
                                </CardTitle>
                                <CardDescription>
                                    {t('webConnection.test.description')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex items-center justify-center">
                                {/* Placeholder for test QR code */}
                                <div className="w-32 h-32 bg-gray-200 flex items-center justify-center text-gray-500">
                                    {whatsAppLink ? (
                                        <QRCodeSVG value={whatsAppLink} size={128} />
                                    ) : (
                                        <p>
                                            {t('webConnection.test.noQr')}
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                );

            case 'DISCONNECTED':
                return (
                    <div>
                        <p>
                            {t('webConnection.status.label')}{' '}
                            <span className="font-semibold text-red-600">
                                {t('webConnection.status.disconnected')}
                            </span>
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {qrCode
                                ? t('webConnection.disconnected.scanQr')
                                : t('webConnection.disconnected.lost')}
                        </p>

                        {qrCode && (
                            <div className="flex flex-col items-center gap-4 mt-4">
                                <QRCodeSVG value={qrCode} size={256} />
                            </div>
                        )}

                        {!qrCode && !chatbotChannel?.data?.phone_number && ( // Show "Generate QR Code" only if no chatbotChannel exists
                            <Button className="mt-4" onClick={handleStartNewSession} disabled={reconnecting}>
                                {t('webConnection.actions.generateQr')}
                            </Button>
                        )}

                        {!qrCode && chatbotChannel?.data?.phone_number && ( // Show "Reconnect" only if chatbotChannel exists and no QR
                            <Button className="mt-4" onClick={handleReconnect} disabled={reconnecting}>
                                {reconnecting && (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                )}
                                {t('webConnection.actions.reconnect')}
                            </Button>
                        )}
                    </div>
                );

            case 'ERROR':
                return (
                    <div>
                        <p>
                            {t('webConnection.status.label')}{' '}
                            <span className="font-semibold text-red-600">
                                {t('webConnection.status.error')}
                            </span>
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {t('webConnection.error.description')}
                        </p>
                        <Button className="mt-4" onClick={handleReconnect} disabled={reconnecting}>
                            {reconnecting && (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            {t('webConnection.actions.reconnect')}
                        </Button>
                    </div>
                );

            default:
                return null;
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    {t('webConnection.header.title')}
                </CardTitle>
                <CardDescription>
                    {t('webConnection.header.description')}
                </CardDescription>
            </CardHeader>
            <CardContent>{renderContent()}</CardContent>
        </Card>
    );
}
