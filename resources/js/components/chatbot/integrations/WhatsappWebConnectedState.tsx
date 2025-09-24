// resources/js/components/chatbot/integrations/WhatsappWebConnectedState.tsx

import { useEffect, useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Chatbot, ChatbotChannel } from '@/types';
import axios from 'axios';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Props {
    chatbot: Chatbot;
    channel: ChatbotChannel;
}

type ConnectionStatus = 'VERIFYING' | 'CONNECTED' | 'DISCONNECTED' | 'ERROR';

export function WhatsappWebConnectedState({ chatbot }: Props) {
    const [status, setStatus] = useState<ConnectionStatus>('VERIFYING');
    const [reconnecting, setReconnecting] = useState<boolean>(false); // New state for reconnect button loading

    const fetchStatus = () => {
        axios
            .get(route('chatbots.integrations.whatsapp-web.status', { chatbot: chatbot.id }))
            .then((response) => {
                setStatus(response.data.status as ConnectionStatus);
            })
            .catch(() => {
                setStatus('ERROR');
            });
    };

    useEffect(() => {
        fetchStatus(); // Initial fetch
    }, [chatbot.id]);

    const handleReconnect = async () => {
        setReconnecting(true);
        try {
            await axios.post(route('chatbots.integrations.whatsapp-web.reconnect', { chatbot: chatbot.id }));
            // If successful, re-fetch status to see if it's connecting or connected
            fetchStatus();
        } catch (error) {
            console.error('Failed to send reconnect command', error);
            setStatus('ERROR'); // Set status to error if reconnect command fails
        } finally {
            setReconnecting(false);
        }
    };

    const renderContent = () => {
        switch (status) {
            case 'VERIFYING':
                return (
                    <div className="flex items-center justify-center gap-2">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        <span>Verifying connection status...</span>
                    </div>
                );
            case 'CONNECTED':
                return (
                    <div>
                        <p>
                            Status: <span className="font-semibold text-green-600">Connected</span>
                        </p>
                        <p className="text-sm text-muted-foreground">
                            The chatbot is ready to send and receive messages.
                        </p>
                        {/* TODO: Add QR code for testing */}
                    </div>
                );
            case 'DISCONNECTED':
                return (
                    <div>
                        <p>
                            Status: <span className="font-semibold text-red-600">Disconnected</span>
                        </p>
                        <p className="text-sm text-muted-foreground">
                            The connection was lost. Please reconnect.
                        </p>
                        <Button className="mt-4" onClick={handleReconnect} disabled={reconnecting}>
                            {reconnecting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Reconnect
                        </Button>
                    </div>
                );
            case 'ERROR':
                return (
                    <div>
                        <p>
                            Status: <span className="font-semibold text-red-600">Error</span>
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Could not verify the connection status. Please try again later.
                        </p>
                        <Button className="mt-4" onClick={handleReconnect} disabled={reconnecting}>
                            {reconnecting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Reconnect
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
                <CardTitle>Connected Number (Unofficial API)</CardTitle>
                <CardDescription>
                    The WhatsApp number connected via the unofficial API.
                </CardDescription>
            </CardHeader>
            <CardContent>{renderContent()}</CardContent>
        </Card>
    );
}
