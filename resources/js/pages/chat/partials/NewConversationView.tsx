import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import axios from 'axios';
import { useRoute } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
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

interface ContactSearchResult {
    id: number;
    first_name: string;
    last_name: string;
    phone_number: string;
}

interface ChatbotChannel {
    id: number;
    channel: { name: string };
}

interface Props {
    onBack: () => void;
    chatbotChannels: ChatbotChannel[];
}

export function NewConversationView({ onBack, chatbotChannels }: Props) {
    const route = useRoute();

    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<ContactSearchResult[]>([]);
    const [isSearching, setIsSearching] = useState(false);
    const [selectedContact, setSelectedContact] = useState<ContactSearchResult | null>(null);

    const form = useForm<FormData>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            contact_id: null,
            first_name: '',
            last_name: '',
            phone_number: '',
        },
    });

    const selectedChannelId = form.watch('chatbot_channel_id');
    const { setValue } = form;

    // Auto-select channel if there is only one
    useEffect(() => {
        if (chatbotChannels.length === 1) {
            setValue('chatbot_channel_id', chatbotChannels[0].id);
        }
    }, [chatbotChannels, setValue]);

    // Debounced search effect
    useEffect(() => {
        if (searchQuery.length > 2 && selectedChannelId) {
            const handler = setTimeout(() => {
                setIsSearching(true);
                axios.get(route('contacts.search', { q: searchQuery }))
                    .then(response => setSearchResults(response.data))
                    .finally(() => setIsSearching(false));
            }, 500);
            return () => clearTimeout(handler);
        } else {
            setSearchResults([]);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [searchQuery, selectedChannelId]);

    const onSubmit = async (data: FormData) => {
        console.log('Sending form:', data);
    };

    const handleSelectContact = (contact: ContactSearchResult) => {
        form.setValue('contact_id', contact.id);
        form.setValue('first_name', '');
        form.setValue('last_name', '');
        form.setValue('phone_number', '');
        setSelectedContact(contact);
        setSearchQuery('');
        setSearchResults([]);
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

                        <div className={`mt-4 transition-opacity ${!selectedChannelId ? 'opacity-50 pointer-events-none' : 'opacity-100'}`}>
                            {!selectedContact && (
                                <div className="py-2">
                                    <Command shouldFilter={false}>
                                        <div className="py-4">
                                            <CommandInput
                                                value={searchQuery}
                                                placeholder="Search by name or phone..."
                                                onValueChange={setSearchQuery}
                                            />
                                        </div>
                                        <CommandList>
                                            <CommandEmpty>{isSearching ? 'Searching...' : 'No results found.'}</CommandEmpty>
                                            <CommandGroup>
                                                {searchResults.map((contact) => (
                                                    <CommandItem
                                                        key={contact.id}
                                                        onSelect={() => handleSelectContact(contact)}
                                                    >
                                                        {`${contact.first_name} ${contact.last_name || ''} (${contact.phone_number})`}
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </div>
                            )}

                            {selectedContact && (
                                <div className="my-4">
                                    <div className="p-4 border rounded-md relative">
                                        <p className="font-medium">{`${selectedContact.first_name} ${selectedContact.last_name || ''}`}</p>
                                        <p className="text-sm text-muted-foreground">{selectedContact.phone_number}</p>
                                        <Button variant="ghost" size="sm" className="absolute top-1 right-1" onClick={() => setSelectedContact(null)}>Change</Button>
                                    </div>
                                    <div className="mt-4">
                                        <Button type="submit" className="w-full">
                                            Start Conversation
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </form>
                </Form>
            </div>
        </div>
    );
}
