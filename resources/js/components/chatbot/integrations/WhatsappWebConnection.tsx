import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useEffect, useRef, useState } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import axios from 'axios';
import type { Chatbot } from '@/types';
import Echo from 'laravel-echo';
import { LucideLoaderPinwheel } from 'lucide-react';

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

    // Listen for QR code event
    useEffect(() => {
        if (sessionId && echoRef.current) {
            const channel = echoRef.current.private(`whatsapp-web.${sessionId}`);
            channel.listen('.qr-code.received', (e: { qrCode: string }) => {
                setQrCode(e.qrCode);
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

                {status === 'connecting' && !qrCode && <p><LucideLoaderPinwheel/> Waiting for QR code...</p>}

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
