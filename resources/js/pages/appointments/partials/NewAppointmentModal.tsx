import { useState, useEffect, forwardRef } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { UserPlus } from 'lucide-react';
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';
import { parsePhoneNumberFromString } from 'libphonenumber-js';
import type { Chatbot, PageProps, ChatbotChannel, Appointment } from '@/types';

interface ContactSearchResult {
    id: number;
    first_name: string;
    last_name: string;
    phone_number: string;
}

interface ChatbotChannelType extends ChatbotChannel{
    id: number;
    name: string;
    phone_number: string | null;
    credentials: {
        phone_number: string;
    } | null;
}

interface Props {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: (newAppointment: Appointment) => void;
    initialDate: Date | null;
    chatbotChannels: ChatbotChannelType[];
}

// Helper function to format a Date object into a YYYY-MM-DDTHH:mm string in local time
function toLocalISOString(date: Date): string {
    const pad = (num: number) => num < 10 ? '0' + num : '' + num;

    return date.getFullYear() +
        '-' + pad(date.getMonth() + 1) +
        '-' + pad(date.getDate()) +
        'T' + pad(date.getHours()) +
        ':' + pad(date.getMinutes());
}

export const NewAppointmentModal = forwardRef<HTMLDivElement, Props>(
    ({ isOpen, onClose, onSuccess, initialDate, chatbotChannels }, ref) => {

        const { chatbot } = usePage<PageProps>().props as { chatbot: Chatbot };
        const { t } = useTranslation('appointments');

        // Zod Schema for the form
        const formSchema = z.object({
            contact_id: z.number().nullable(),
            first_name: z.string().optional(),
            last_name: z.string().optional(),
            phone_number: z.string().optional(),
            chatbot_channel_id: z
                .number({ required_error: t('newAppointmentModal.validation.channelRequired') })
                .min(1, t('newAppointmentModal.validation.channelRequired')),

            appointment_at: z
                .string({ required_error: t('newAppointmentModal.validation.appointmentRequired') })
                .min(1, t('newAppointmentModal.validation.appointmentRequired')),
            end_at: z.string().nullable().optional(), // New: Optional end time
            remind_at: z.string().nullable().optional(), // New: Optional reminder time
        }).superRefine((data, ctx) => {
            if (!data.contact_id && !data.phone_number) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    path: ['phone_number'],
                    message: t('newAppointmentModal.validation.phoneRequired'),
                });
            }

            // New: end_at must be after appointment_at if both are present
            if (data.appointment_at && data.end_at && data.appointment_at >= data.end_at) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    path: ['end_at'],
                    message: t('newAppointmentModal.validation.endAfterStart'),
                });
            }
        });

        type FormData = z.infer<typeof formSchema>;

        const [isSubmitting, setIsSubmitting] = useState(false);
        const [defaultCountry, setDefaultCountry] = useState('us');

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

        const { setValue, reset, watch } = form;
        const appointmentAt = watch('appointment_at');

        // Reset form state when the modal is closed or opened
        useEffect(() => {
            if (isOpen) {

                const defaultAppointmentAt = initialDate ? toLocalISOString(initialDate) : '';

                const defaultEndAt = initialDate // Default 1 hour later
                    ? toLocalISOString(new Date(initialDate.getTime() + 60 * 60 * 1000))
                    : '';

                reset({
                    contact_id: null,
                    first_name: '',
                    last_name: '',
                    phone_number: '',
                    chatbot_channel_id: chatbotChannels.length === 1 ? chatbotChannels[0].id : undefined,
                    appointment_at: defaultAppointmentAt,
                    end_at: defaultEndAt, // New
                    remind_at: '', // New - initialize as empty string
                });

                setSelectedContact(null);
                setIsCreatingNew(false);
                setSearchQuery('');
                setSearchResults([]);
            }
        }, [isOpen, reset, initialDate, chatbotChannels]);

        const selectedChannelId = form.watch('chatbot_channel_id');

        // Set default country for phone input based on selected channel
        useEffect(() => {
            if (selectedChannelId) {

                const selectedChannel = chatbotChannels.find(c => c.id === selectedChannelId);
                const phone = selectedChannel?.credentials?.phone_number;

                if (phone) {
                    const phoneNumber = parsePhoneNumberFromString(phone.startsWith('+') ? phone : `+${phone}`);

                    if (phoneNumber?.country) {
                        setDefaultCountry(phoneNumber.country.toLowerCase());
                    }
                }
            }
        }, [selectedChannelId, chatbotChannels]);

        // update end_at when appointment_at changes
        useEffect(() => {
            if (appointmentAt) {

                const startDate = new Date(appointmentAt);

                if (!isNaN(startDate.getTime())) {

                    const endDate = new Date(startDate.getTime() + 60 * 60 * 1000); // add 1 hour

                    setValue('end_at', toLocalISOString(endDate), { shouldValidate: true });
                }
            }
        }, [appointmentAt, setValue]);

        // Debounced search for contacts
        useEffect(() => {
            if (searchQuery.length > 2) {

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
        }, [searchQuery]);

        // Form submission handler
        const onSubmit = async (data: FormData) => {

            setIsSubmitting(true);

            try {

                const response = await axios.post(route('appointments.store', { chatbot: chatbot.id }), data);

                onSuccess(response.data);
                onClose();

            } catch (error) {

                console.error('Failed to create appointment:', error);

            } finally {

                setIsSubmitting(false);

            }
        };

        const handleSelectContact = (contact: ContactSearchResult) => {
            setValue('contact_id', contact.id);
            setSelectedContact(contact);
            setIsCreatingNew(false);
            setSearchQuery('');
            setSearchResults([]);
        };

        const handleCreatingNew = () => {
            setValue('contact_id', null);
            setSelectedContact(null);
            setIsCreatingNew(true);
            setSearchQuery('');
            setSearchResults([]);
        };

        const handleBackToSearch = () => {
            setValue('contact_id', null);
            setSelectedContact(null);
            setIsCreatingNew(false);
            setSearchQuery('');
        };

        return (
            <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
                <DialogContent ref={ref} className="sm:max-w-[425px] flex flex-col h-full max-h-[90vh]">

                    <DialogHeader>
                        <DialogTitle>{t('newAppointmentModal.title')}</DialogTitle>
                        <DialogDescription>
                            {t('newAppointmentModal.description')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex-grow overflow-y-auto -mx-6 px-6">

                        <Form {...form}>
                            <form id="appointment-form" onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">

                                {/* Step 1: Contact Selection */}
                                <div className="space-y-2">

                                    <FormLabel>{t('newAppointmentModal.contact')}</FormLabel>

                                    {selectedContact ? (
                                        <div className="p-3 border rounded-md relative bg-muted/50">
                                            <p className="font-medium">{`${selectedContact.first_name} ${selectedContact.last_name || ''}`}</p>
                                            <p className="text-sm text-muted-foreground">{selectedContact.phone_number}</p>

                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="absolute top-1 right-1 h-auto px-2 py-1"
                                                onClick={() => setSelectedContact(null)}
                                            >
                                                {t('newAppointmentModal.change')}
                                            </Button>
                                        </div>
                                    ) : isCreatingNew ? (
                                        <div className="space-y-4 rounded-md border p-4">

                                            <FormField
                                                control={form.control}
                                                name="phone_number"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>{t('newAppointmentModal.phoneNumber')}</FormLabel>
                                                        <FormControl>
                                                            <PhoneInput
                                                                country={defaultCountry}
                                                                value={field.value}
                                                                onChange={field.onChange}
                                                                inputClass="!w-full"
                                                                enableLongNumbers={true}
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
                                                        <FormLabel>{t('newAppointmentModal.firstName')}</FormLabel>
                                                        <FormControl>
                                                            <Input placeholder="John" {...field} />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />

                                            <FormField
                                                control={form.control}
                                                name="last_name"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>{t('newAppointmentModal.lastName')}</FormLabel>
                                                        <FormControl>
                                                            <Input placeholder="Doe" {...field} />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />

                                            <Button
                                                variant="link"
                                                size="sm"
                                                className="p-0 h-auto"
                                                onClick={handleBackToSearch}
                                            >
                                                {t('newAppointmentModal.backToSearch')}
                                            </Button>

                                        </div>
                                    ) : (
                                        <Command shouldFilter={false}>

                                            <CommandInput
                                                value={searchQuery}
                                                placeholder={t('newAppointmentModal.searchPlaceholder')}
                                                onValueChange={setSearchQuery}
                                            />

                                            <CommandList>

                                                <CommandEmpty>
                                                    {isSearching
                                                        ? t('newAppointmentModal.searching')
                                                        : t('newAppointmentModal.noResults')}
                                                </CommandEmpty>

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

                                            <Button
                                                variant="ghost"
                                                onClick={handleCreatingNew}
                                                className="justify-start w-full mt-2"
                                            >
                                                <UserPlus className="mr-2 h-4 w-4" />
                                                {t('newAppointmentModal.newContact')}
                                            </Button>

                                        </Command>
                                    )}
                                </div>

                                {/* Step 2: Channel & Time */}
                                <FormField
                                    control={form.control}
                                    name="chatbot_channel_id"
                                    render={({ field }) => (
                                        <FormItem>

                                            <FormLabel>{t('newAppointmentModal.channel')}</FormLabel>

                                            <Select
                                                onValueChange={(value) => field.onChange(parseInt(value, 10))}
                                                value={field.value ? String(field.value) : undefined}
                                            >
                                                <FormControl>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder={t('newAppointmentModal.selectChannel')} />
                                                    </SelectTrigger>
                                                </FormControl>

                                                <SelectContent>
                                                    {chatbotChannels.map(channel => (
                                                        <SelectItem key={channel.id} value={String(channel.id)}>
                                                            {channel.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>

                                            </Select>

                                            <FormMessage />

                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="appointment_at"
                                    render={({ field }) => (
                                        <FormItem>

                                            <FormLabel>{t('newAppointmentModal.startTime')}</FormLabel>

                                            <FormControl>
                                                <Input type="datetime-local" {...field} />
                                            </FormControl>

                                            <FormMessage />

                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="end_at"
                                    render={({ field }) => (
                                        <FormItem>

                                            <FormLabel>{t('newAppointmentModal.endTime')}</FormLabel>

                                            <FormControl>
                                                <Input
                                                    type="datetime-local"
                                                    {...field}
                                                    value={field.value || ''} // Fix: Provide empty string for null/undefined
                                                />
                                            </FormControl>

                                            <FormMessage />

                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="remind_at"
                                    render={({ field }) => (
                                        <FormItem>

                                            <FormLabel>{t('newAppointmentModal.reminderTime')}</FormLabel>

                                            <FormControl>
                                                <Input
                                                    type="datetime-local"
                                                    {...field}
                                                    value={field.value || ''} // Fix: Provide empty string for null/undefined
                                                />
                                            </FormControl>

                                            <FormMessage />

                                        </FormItem>
                                    )}
                                />

                            </form>
                        </Form>

                    </div>

                    <div className="flex justify-end gap-2 pt-4 border-t flex-shrink-0 -mx-6 px-6">

                        <Button variant="ghost" onClick={onClose}>
                            {t('newAppointmentModal.cancel')}
                        </Button>

                        <Button type="submit" form="appointment-form" disabled={isSubmitting}>
                            {isSubmitting
                                ? t('newAppointmentModal.scheduling')
                                : t('newAppointmentModal.scheduleAppointment')}
                        </Button>

                    </div>

                </DialogContent>
            </Dialog>
        );
    }
);

NewAppointmentModal.displayName = "NewAppointmentModal";