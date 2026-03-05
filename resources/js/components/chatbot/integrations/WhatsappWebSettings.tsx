import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { ChatbotChannel } from '@/types';
import { useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface Props {
    chatbotChannel: ChatbotChannel;
    callRejectionMessage: string | null;
}

const SETTING_KEY = 'call_rejection_message';

export function WhatsappWebSettings({ chatbotChannel, callRejectionMessage }: Props) {

    const { t } = useTranslation('chatbot');

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
                newSettings[0].value = t('integrations.settings.default_message');
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
                    <CardTitle>{t('integrations.settings.title')}</CardTitle>
                    <CardDescription>
                        {t('integrations.settings.description')}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="flex flex-col space-y-4 rounded-md border p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex-1 space-y-1">
                                <p className="text-sm font-medium leading-none">
                                    {t('integrations.settings.incoming_call_title')}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {t('integrations.settings.incoming_call_description')}
                                </p>
                            </div>
                            <Switch checked={isEnabled} onCheckedChange={handleSwitchChange} />
                        </div>

                        {isEnabled && (
                            <div className="space-y-2">
                                <Label htmlFor="rejection-message">
                                    {t('integrations.settings.auto_reply_label')}
                                </Label>
                                <Textarea
                                    id="rejection-message"
                                    placeholder={t('integrations.settings.auto_reply_placeholder')}
                                    value={data.settings[0].value}
                                    onChange={handleTextareaChange}
                                    minLength={1}
                                    required
                                />
                                {(errors as Record<string, string>)['settings.0.value'] && (
                                    <p className="text-sm text-red-600">
                                        {(errors as Record<string, string>)['settings.0.value']}
                                    </p>
                                )}
                            </div>
                        )}
                    </div>
                </CardContent>
                <CardFooter className="flex justify-end pt-6">
                    <Button type="submit" disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        {t('integrations.settings.save')}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    );
}