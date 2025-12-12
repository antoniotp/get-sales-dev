import { Message } from '@/types';
import { Clock, Check, CheckCheck, XCircle } from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface MessageStatusProps {
    message: Message;
}

const MessageStatus = ({ message }: MessageStatusProps) => {
    if (message.type !== 'outgoing') {
        return null;
    }

    let statusIcon: React.ReactNode;
    let statusText: string;

    if (message.failed_at) {
        statusIcon = <XCircle className="h-5 w-5 text-red-500" />;
        statusText = `Failed: ${message.error_message || 'Unknown error'}`;
    } else if (message.read_at) {
        statusIcon = <CheckCheck className="h-5 w-5 text-green-300" />;
        statusText = `Read at ${new Date(message.read_at).toLocaleString()}`;
    } else if (message.delivered_at) {
        statusIcon = <CheckCheck className="h-5 w-5 text-white" />;
        statusText = `Delivered at ${new Date(message.delivered_at).toLocaleString()}`;
    } else if (message.sent_at) {
        statusIcon = <Check className="h-5 w-5 text-white" />;
        statusText = `Sent at ${new Date(message.sent_at).toLocaleString()}`;
    } else {
        statusIcon = <Clock className="h-5 w-5 text-white animate-spin" />;
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
