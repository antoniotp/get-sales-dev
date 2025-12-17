import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { ChatbotChannel } from '@/types';
import { useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';

interface Props {
    chatbotChannel: ChatbotChannel;
    callRejectionMessage: string | null;
}

const SETTING_KEY = 'call_rejection_message';

export function WhatsappWebSettings({ chatbotChannel, callRejectionMessage }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        settings: [{
            key: SETTING_KEY,
            value: callRejectionMessage || '',
        }],
    });

    const isEnabled = !!data.settings[0].value;

    const handleSwitchChange = (checked: boolean) => {
        const newSettings = [...data.settings];
        if (checked) {
            // If enabling, but there's no previous message, set a default one
            if (!newSettings[0].value) {
                newSettings[0].value = 'Sorry, I cannot take calls right now. Please leave a message.';
            }
        } else {
            newSettings[0].value = '';
        }
        setData('settings', newSettings);
    };

    const handleTextareaChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        const newSettings = [...data.settings];
        newSettings[0].value = e.target.value;
        setData('settings', newSettings);
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('chatbot_channels.settings.store', { chatbot_channel: chatbotChannel.id }));
    };

    return (
        <Card>
            <form onSubmit={handleSubmit}>
                <CardHeader>
                    <CardTitle>Settings</CardTitle>
                    <CardDescription>Configure additional settings for your WhatsApp Web connection.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="flex flex-col space-y-4 rounded-md border p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex-1 space-y-1">
                                <p className="text-sm font-medium leading-none">Incoming Call Auto-Reply</p>
                                <p className="text-sm text-muted-foreground">
                                    Define a message to automatically send when you receive calls on this number.
                                </p>
                            </div>
                            <Switch checked={isEnabled} onCheckedChange={handleSwitchChange} />
                        </div>

                        {isEnabled && (
                            <div className="space-y-2">
                                <Label htmlFor="rejection-message">Auto-Reply Message</Label>
                                <Textarea
                                    id="rejection-message"
                                    placeholder="Enter the message to send when a call is received..."
                                    value={data.settings[0].value}
                                    onChange={handleTextareaChange}
                                    minLength={1}
                                    required
                                />
                                {(errors as Record<string, string>)['settings.0.value'] && <p className="text-sm text-red-600">{(errors as Record<string, string>)['settings.0.value']}</p>}
                            </div>
                        )}
                    </div>
                </CardContent>
                <CardFooter className="flex justify-end pt-6">
                    <Button type="submit" disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Save Settings
                    </Button>
                </CardFooter>
            </form>
        </Card>
    );
}
