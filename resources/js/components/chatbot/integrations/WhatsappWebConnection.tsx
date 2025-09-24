import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useEffect, useRef, useState } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import axios from 'axios';
import type { Chatbot } from '@/types';
import Echo from 'laravel-echo';


interface Props {
    chatbot: Chatbot;
}

export function WhatsappWebConnection({ chatbot }: Props) {
    const [qrCode, setQrCode] = useState<string | null>(null);
    const [sessionId, setSessionId] = useState<string | null>(null);
    const [status, setStatus] = useState<'disconnected' | 'connecting' | 'connected'>('disconnected');
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

    const handleConnect = async () => {
        setStatus('connecting');
        setQrCode(null); // Reset QR code on a new attempt
        try {
            const response = await axios.post(route('chatbots.integrations.whatsapp-web.start', { chatbot: chatbot.id }));
            if (response.data.session_id) {
                setSessionId(response.data.session_id);
            }
        } catch (error) {
            console.error('Failed to start session', error);
            setStatus('disconnected');
        }
    };

    // Listen for QR code and connection status updates
    useEffect(() => {
        if (sessionId && echoRef.current) {
            const channel = echoRef.current.private(`whatsapp-web.${sessionId}`);

            // Listen for QR code event
            channel.listen('.qr-code.received', (e: { qrCode: string }) => {
                setQrCode(e.qrCode);
            });

            // Listen for connection status updated event
            channel.listen('.connection.status.updated', (e: { status: number }) => {
                switch (e.status) {
                    case 0: // STATUS_DISCONNECTED
                        setStatus('disconnected');
                        setQrCode(null); // Clear QR code on disconnect
                        setSessionId(null); // Clear session ID
                        break;
                    case 1: // STATUS_CONNECTED
                        setStatus('connected');
                        setQrCode(null); // Clear QR code on connect
                        break;
                    case 2: // STATUS_CONNECTING
                        setStatus('connecting');
                        break;
                    default:
                        setStatus('disconnected'); // Fallback
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

    return (
        <Card>
            <CardHeader>
                <CardTitle>WhatsApp Web (Unofficial) Connection</CardTitle>
                <CardDescription>Connect a WhatsApp number using your phone.</CardDescription>
            </CardHeader>
            <CardContent>
                {status === 'disconnected' && <Button onClick={handleConnect}>Generate QR Code</Button>}

                {status === 'connecting' && !qrCode && (
                    <p className="flex items-center">
                        <svg className="animate-spin h-5 w-5 text-gray-500 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Waiting for QR code...
                    </p>
                )}

                {status === 'connecting' && qrCode && (
                    <div className="flex flex-col items-center gap-4">
                        <p>Scan this QR code with your phone:</p>
                        <QRCodeSVG value={qrCode} size={256} />
                    </div>
                )}

                {status === 'connected' && <p>Your phone is connected.</p>}
            </CardContent>
        </Card>
    );
}
