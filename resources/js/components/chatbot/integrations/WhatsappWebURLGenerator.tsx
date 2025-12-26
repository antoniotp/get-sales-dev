import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useState } from 'react';
import { Copy } from 'lucide-react';
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';

interface Props {
    chatbotId: number;
    chatbotChannelId: number;
    defaultCountry: string;
}

export function WhatsappWebURLGenerator({ chatbotId, chatbotChannelId, defaultCountry }: Props) {
    const [phoneNumber, setPhoneNumber] = useState('');
    const [message, setMessage] = useState('');
    const [generatedUrl, setGeneratedUrl] = useState('');
    const [copied, setCopied] = useState(false);

    const handleCreateUrl = () => {
        if (!phoneNumber) {
            setGeneratedUrl('');
            return;
        }

        const url = route('chats.start', {
            chatbot: chatbotId,
            phone_number: phoneNumber,
            cc_id: chatbotChannelId,
            text: message ? encodeURIComponent(message) : undefined,
        });

        setGeneratedUrl(url);
    };

    const handleCopy = () => {
        if (generatedUrl) {
            navigator.clipboard.writeText(generatedUrl);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000); // Reset after 2 seconds
        }
    };

    const baseUrl = route('chats.start', {
        chatbot: chatbotId,
        phone_number: 'YOUR_PHONE_NUMBER',
        cc_id: chatbotChannelId,
    });

    return (
        <Card>
            <CardHeader>
                <CardTitle>Start Conversation Link</CardTitle>
                <CardDescription>Generate a URL to start a conversation directly with a specific number.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div>
                    <h4 className="text-sm font-medium mb-2">URL Structure</h4>
                    <code className="relative rounded bg-muted px-[0.3rem] py-[0.2rem] font-mono text-sm">
                        {baseUrl}&amp;text=YOUR_MESSAGE
                    </code>
                </div>
                {/* NEW BORDERED GROUP */}
                <div className="rounded-md border p-4 space-y-4">
                    <CardDescription>Fill in the fields below to generate an example link:</CardDescription>
                    {/* Phone Number Input (inline) */}
                    <div className="flex items-center gap-2">
                        <Label htmlFor="phone-number" className="flex-none w-32">Phone Number</Label>
                        <PhoneInput
                            country={defaultCountry}
                            value={phoneNumber}
                            onChange={(phone) => setPhoneNumber(phone)}
                            inputClass="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Enter phone number"
                        />
                    </div>

                    {/* Initial Message Textarea */}
                    <div className="space-y-2">
                        <Label htmlFor="initial-message">Initial Message (Optional)</Label>
                        <Textarea
                            id="initial-message"
                            placeholder="Enter a pre-filled message for the user..."
                            value={message}
                            onChange={(e) => setMessage(e.target.value)}
                        />
                    </div>

                    {/* Generated URL Display (MOVED HERE) */}
                    {generatedUrl && (
                        <div className="w-full space-y-2">
                            <Label htmlFor="generated-url">Generated URL</Label>
                            <div className="flex w-full items-center space-x-2">
                                <Input id="generated-url" value={generatedUrl} readOnly />
                                <Button variant="secondary" type="button" onClick={handleCopy}>
                                    <Copy className="h-4 w-4 mr-2" />
                                    {copied ? 'Copied!' : 'Copy'}
                                </Button>
                            </div>
                        </div>
                    )}

                    {/* Create URL Button */}
                    <div className="flex justify-end">
                         <Button onClick={handleCreateUrl} type="button">Create URL</Button>
                    </div>
                </div>
            </CardContent>
            <CardFooter className="flex flex-col items-start gap-4 pt-6">
            </CardFooter>
        </Card>
    );
}
