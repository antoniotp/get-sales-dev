import { Message } from '@/types';
import { Clock, Check, CheckCheck, XCircle } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import axios from 'axios';
import { useRoute } from 'ziggy-js';
import { useState } from 'react';

interface MessageStatusProps {
    message: Message;
}

const MessageStatus = ({ message }: MessageStatusProps) => {
    const route = useRoute();
    const [isRetrying, setIsRetrying] = useState(false);

    if (message.type !== 'outgoing') {
        return null;
    }

    const handleRetry = async (messageId: number) => {
        if (isRetrying) return;
        setIsRetrying(true);
        try {
            await axios.post(route('messages.retry', { message: messageId }));
            // The UI will update automatically via the websocket event triggered by the backend.
        } catch (error) {
            console.error('Failed to retry message:', error);
            // Optionally show an error toast to the user
        } finally {
            // Do not set isRetrying back to false immediately.
            // The icon will change to a clock once the event is received,
            // which removes the retry button. If the retry fails again, a new event will
            // bring back the 'X' button, and at that point isRetrying will be reset.
        }
    };

    let statusIcon: React.ReactNode;
    let statusText: string;

    if (message.failed_at) {
        statusIcon = (
            <button
                onClick={() => handleRetry(message.id)}
                disabled={isRetrying}
                className="flex items-center gap-1 cursor-pointer disabled:cursor-not-allowed"
                aria-label="Retry sending message"
            >
                <XCircle className="h-5 w-5 text-red-500" />
                <span className="text-red-500 text-sm">Retry</span>
            </button>
        );
        statusText = `Failed: ${message.error_message || 'Unknown error'}`;
    } else if (message.read_at) {
        statusIcon = <CheckCheck className="h-5 w-5 text-blue-800" />;
        statusText = `Read at ${new Date(message.read_at).toLocaleString()}`;
    } else if (message.delivered_at) {
        statusIcon = <CheckCheck className="h-5 w-5 text-gray-100" />;
        statusText = `Delivered at ${new Date(message.delivered_at).toLocaleString()}`;
    } else if (message.sent_at) {
        statusIcon = <Check className="h-5 w-5 text-gray-100" />;
        statusText = `Sent at ${new Date(message.sent_at).toLocaleString()}`;
    } else {
        statusIcon = <Clock className="h-5 w-5 text-gray-100" />;
        statusText = 'Sending...';
    }

    return (
        <TooltipProvider delayDuration={100}>
            <Tooltip>
                <TooltipTrigger asChild>
                    <span className="ml-2 flex-shrink-0">{statusIcon}</span>
                </TooltipTrigger>
                <TooltipContent>
                    <p>{statusText}</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
};

export default MessageStatus;
