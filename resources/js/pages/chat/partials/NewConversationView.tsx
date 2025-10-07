import {useEffect} from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Button } from '@/components/ui/button';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { ArrowLeft } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const formSchema = z.object({
    contact_id: z.number().nullable(),
    first_name: z.string().optional(),
    last_name: z.string().optional(),
    phone_number: z.string().optional(),
    chatbot_channel_id: z.number({ required_error: "A channel must be selected." }).min(1, "A channel must be selected."),
}).superRefine((data, ctx) => {
    if (!data.contact_id && !data.phone_number) {
        ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['phone_number'],
            message: 'Phone number is required for a new contact.',
        });
    }
});

type FormData = z.infer<typeof formSchema>;

interface ChatbotChannel {
    id: number;
    channel: { name: string };
}

interface Props {
    onBack: () => void;
    chatbotChannels: ChatbotChannel[];
}

export function NewConversationView({ onBack, chatbotChannels }: Props) {

    const form = useForm<FormData>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            contact_id: null,
            first_name: '',
            last_name: '',
            phone_number: '',
        },
    });

    const { setValue } = form;

    // Auto-select channel if there is only one
    useEffect(() => {
        if (chatbotChannels.length === 1) {
            setValue('chatbot_channel_id', chatbotChannels[0].id);
        }
    }, [chatbotChannels, setValue]);

    const onSubmit = async (data: FormData) => {
        console.log('Sending form:', data);
    };

    return (
        <div className="h-full flex flex-col bg-white dark:bg-gray-900">
            <div className="flex items-center gap-2 p-3 border-b bg-gray-50 dark:bg-gray-800 flex-shrink-0">
                <Button variant="ghost" size="icon" onClick={onBack}>
                    <ArrowLeft className="h-6 w-6" />
                </Button>
                <h2 className="text-xl font-semibold">New Conversation</h2>
            </div>

            <div className="flex-grow overflow-y-auto">
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)} className="flex flex-col h-full p-4">

                        <FormField
                            control={form.control}
                            name="chatbot_channel_id"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Send From Channel</FormLabel>
                                    {chatbotChannels.length > 1 ? (
                                        <Select onValueChange={(value) => field.onChange(parseInt(value, 10))} value={field.value ? String(field.value) : undefined}>
                                            <FormControl>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select a channel to send from..." />
                                                </SelectTrigger>
                                            </FormControl>
                                            <SelectContent>
                                                {chatbotChannels.map(channel => (
                                                    <SelectItem key={channel.id} value={String(channel.id)}>
                                                        {channel.channel.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    ) : chatbotChannels.length === 1 ? (
                                        <div className="text-sm p-3 font-medium text-gray-700 dark:text-gray-300 rounded-md bg-gray-100 dark:bg-gray-800">
                                            Via: <strong>{chatbotChannels[0]?.channel.name}</strong>
                                        </div>
                                    ) : (
                                        <div className="text-sm p-3 font-medium text-red-700 dark:text-red-300 rounded-md bg-red-100 dark:bg-red-900">
                                            No channels configured for this chatbot.
                                        </div>
                                    )}
                                    <FormMessage />
                                </FormItem>
                            )}
                        />
                    </form>
                </Form>
            </div>
        </div>
    );
}
