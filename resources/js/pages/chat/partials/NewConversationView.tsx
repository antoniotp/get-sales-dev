import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';

interface Props {
    onBack: () => void;
}
export function NewConversationView({onBack}:Props) {
    return (
        <div>
            <Button variant="ghost" size="icon" onClick={onBack}>
                <ArrowLeft className="h-6 w-6" />
            </Button>
            <h2 className="text-xl font-semibold">New Conversation</h2>
        </div>
    )
}
