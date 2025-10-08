import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import axios from 'axios';
import { useRoute } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { usePage } from '@inertiajs/react';
import type { Chat, Chatbot, PageProps } from '@/types';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { ArrowLeft, UserPlus } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';

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
    onSuccess: (newChat: Chat) => void;
    chatbotChannels: ChatbotChannel[];
}

export function NewConversationView({ onBack, onSuccess, chatbotChannels }: Props) {
    const route = useRoute();
    const { chatbot } = usePage<PageProps>().props as { chatbot: Chatbot };
    const [isSubmitting, setIsSubmitting] = useState(false);

    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<ContactSearchResult[]>([]);
    const [isSearching, setIsSearching] = useState(false);
    const [selectedContact, setSelectedContact] = useState<ContactSearchResult | null>(null);
    const [isCreatingNew, setIsCreatingNew] = useState(false);

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

    // Auto-select a channel if there is only one
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
        setIsSubmitting(true);
        try {
            const response = await axios.post(route('chats.store', { chatbot: chatbot.id }), data);
            onSuccess(response.data);
        } catch (error) {
            console.error('Failed to create conversation:', error);
            if (axios.isAxiosError(error) && error.response?.data.errors) {
                const errors = error.response.data.errors;
                if (errors.phone_number) {
                    form.setError('phone_number', { type: 'manual', message: errors.phone_number[0] });
                }
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleSelectContact = (contact: ContactSearchResult) => {
        form.setValue('contact_id', contact.id);
        form.setValue('first_name', '');
        form.setValue('last_name', '');
        form.setValue('phone_number', '');
        setSelectedContact(contact);
        setIsCreatingNew(false);
        setSearchQuery('');
        setSearchResults([]);
    };

    const handleCreatingNew = () => {
        form.setValue('contact_id', null);
        form.setValue('phone_number', '');
        form.setValue('first_name', '');
        form.setValue('last_name', '');
        setSelectedContact(null);
        setIsCreatingNew(true);
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
                            {!selectedContact && !isCreatingNew && (
                                <div className="py-2">
                                    <Button variant="ghost" onClick={handleCreatingNew} className="justify-start w-full p-4 border-b">
                                        <UserPlus className="mr-2 h-4 w-4" />
                                        New Contact
                                    </Button>
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
                                        <Button type="submit" disabled={isSubmitting} className="w-full">
                                            {isSubmitting ? 'Starting...' : 'Start Conversation'}
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {isCreatingNew && (
                                <div className="my-4 flex-grow flex flex-col">
                                    <h3 className="mb-4 font-medium">Create New Contact</h3>
                                    <div className="space-y-4 rounded-md border p-4">
                                        <FormField
                                            control={form.control}
                                            name="phone_number"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Phone Number</FormLabel>
                                                    <FormControl>
                                                        <PhoneInput
                                                            country={'us'}
                                                            value={field.value}
                                                            onChange={field.onChange}
                                                            inputClass="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                            placeholder="Enter phone number"
                                                        />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="first_name"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>First Name (Optional)</FormLabel>
                                                    <FormControl><Input placeholder="John" {...field} /></FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="last_name"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Last Name (Optional)</FormLabel>
                                                    <FormControl><Input placeholder="Doe" {...field} /></FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>

                                    <div className="flex-grow" />

                                    <div className="mt-auto py-4">
                                        <Button type="submit" disabled={isSubmitting} className="w-full">
                                            {isSubmitting ? 'Starting...' : 'Start Conversation'}
                                        </Button>
                                        <Button variant="ghost" onClick={() => setIsCreatingNew(false)} className="w-full mt-2">
                                            Back to Search
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
