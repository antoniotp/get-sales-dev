import { Message } from '@/types';
import { Clock, Check, CheckCheck, XCircle, AlertCircle } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import axios from 'axios';
import { useRoute } from 'ziggy-js';
import { useState } from 'react';

interface MessageStatusProps {
    message: Message;
    onlyError?: boolean;
}

const MessageStatus = ({ message, onlyError }: MessageStatusProps) => {
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
        } finally {
            // isRetrying stays true until the websocket event updates the message status
        }
    };

    if (message.failed_at && onlyError) {
        return (
            <div className="mt-2 flex flex-col items-end gap-1.5 rounded-lg bg-red-900/20 border border-red-400/30 p-2 text-right animate-in fade-in slide-in-from-top-1">
                <div className="flex items-center gap-1.5 text-red-200">
                    <AlertCircle className="h-3.5 w-3.5" />
                    <span className="text-[10px] font-bold uppercase tracking-wider">Failed to send</span>
                </div>
                <p className="text-xs font-medium leading-tight text-white/90 max-w-[100%]">
                    {message.error_message || 'Unknown error occurred'}
                </p>
                <button
                    onClick={() => handleRetry(message.id)}
                    disabled={isRetrying}
                    className="mt-1 flex items-center gap-1 rounded bg-white px-2.5 py-1 text-[10px] font-bold text-red-600 hover:bg-gray-100 transition-colors disabled:opacity-50"
                >
                    <Clock className="h-3 w-3" />
                    {isRetrying ? 'RETRYING...' : 'RETRY NOW'}
                </button>
            </div>
        );
    }

    if (onlyError) return null;

    let statusIcon: React.ReactNode;
    let statusText: string;

    if (message.failed_at) {
        statusIcon = <XCircle className="h-5 w-5 text-red-400" />;
        statusText = 'Error in delivery';
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
