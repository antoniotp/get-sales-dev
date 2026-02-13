import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { BreadcrumbItem, type PageProps } from '@/types';
import { Button } from '@/components/ui/button';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { EllipsisVertical } from 'lucide-react';
import { DeleteContactDialog } from '@/pages/contacts/partials/delete-contact-dialog';
import { UpsertContactSheet } from '@/pages/contacts/partials/upsert-contact-sheet';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Contact } from '@/types/contact';
import { Card } from '@/components/ui/card';
import { CustomPagination } from '@/components/general/CustomPagination';

interface FilterOptions {
    countries: string[];
    languages: string[];
    chatbots: { id: number; name: string }[];
    channels: { id: number; name: string }[];
}

interface ContactsPageProps extends PageProps {
    contacts: {
        data: Contact[];
        links: { url: string | null; label: string; active: boolean }[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search?: string;
        country?: string;
        language?: string;
        chatbot?: string;
        channel?: string;
    };
    filterOptions: FilterOptions;
}

interface FormData {
    search: string;
    country: string;
    language: string;
    chatbot: string;
    channel: string;
    [key: string]: string | undefined;
}

export default function ContactsPage({ contacts, filters, filterOptions }: ContactsPageProps) {
    const { t } = useTranslation('contact');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('contactsPage.breadcrumb'), href: route('contacts.index') },
    ];

    const [isSheetOpen, setIsSheetOpen] = useState(false);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [selectedContact, setSelectedContact] = useState<Contact | undefined>(undefined);
    const [contactToDelete, setContactToDelete] = useState<number | undefined>(undefined);

    const { data, setData, get, transform } = useForm<FormData>({
        search: filters.search || '',
        country: filters.country || 'all',
        language: filters.language || 'all',
        chatbot: filters.chatbot || 'all',
        channel: filters.channel || 'all',
    });

    transform((data) => ({
        ...data,
        country: data.country === 'all' ? '' : data.country,
        language: data.language === 'all' ? '' : data.language,
        chatbot: data.chatbot === 'all' ? '' : data.chatbot,
        channel: data.channel === 'all' ? '' : data.channel,
    }));

    function handleFilterChange() {
        get(route('contacts.index'), {
            preserveState: true,
            preserveScroll: true,
        });
    }

    function handleSelectChange(name: keyof FormData, value: string) {
        setData(name, value);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('contactsPage.headTitle')} />
            <AppContentDefaultLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <Card className="w-full p-3">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-2xl font-bold">{t('contactsPage.title')}</h2>
                            <Button className="ml-2" onClick={() => {
                                setTimeout(() => {
                                    setSelectedContact(undefined);
                                    setIsSheetOpen(true);
                                }, 100);
                            }}>{t('contactsPage.createContact')}</Button>
                        </div>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-2">
                                <Input
                                    placeholder={t('contactsPage.searchPlaceholder')}
                                    className="max-w-sm"
                                    value={data.search}
                                    onChange={(e) => setData('search', e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleFilterChange()}
                                />
                                <Select value={data.country} onValueChange={(v) => handleSelectChange('country', v)}>
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder={t('contactsPage.filters.country')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('contactsPage.filters.allCountries')}</SelectItem>
                                        {filterOptions.countries.map((country) => (
                                            <SelectItem key={country} value={country}>
                                                {country}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={data.language} onValueChange={(v) => handleSelectChange('language', v)}>
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder={t('contactsPage.filters.language')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('contactsPage.filters.allLanguages')}</SelectItem>
                                        {filterOptions.languages.map((lang) => (
                                            <SelectItem key={lang} value={lang}>
                                                {lang}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={data.chatbot} onValueChange={(v) => handleSelectChange('chatbot', v)}>
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder={t('contactsPage.filters.chatbot')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('contactsPage.filters.allChatbots')}</SelectItem>
                                        {filterOptions.chatbots.map((bot) => (
                                            <SelectItem key={bot.id} value={String(bot.id)}>
                                                {bot.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={data.channel} onValueChange={(v) => handleSelectChange('channel', v)}>
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder={t('contactsPage.filters.channel')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('contactsPage.filters.allChannels')}</SelectItem>
                                        {filterOptions.channels.map((chan) => (
                                            <SelectItem key={chan.id} value={String(chan.id)}>
                                                {chan.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Button onClick={handleFilterChange}>{t('contactsPage.applyFilters')}</Button>
                            </div>

                        </div>

                        <div className="rounded-md border h-[calc(100vh-20rem)] overflow-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('contactsPage.table.name')}</TableHead>
                                        <TableHead>{t('contactsPage.table.contact')}</TableHead>
                                        <TableHead>{t('contactsPage.table.location')}</TableHead>
                                        <TableHead>{t('contactsPage.table.chatbots')}</TableHead>
                                        <TableHead>{t('contactsPage.table.channels')}</TableHead>
                                        <TableHead></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {contacts.data.length > 0 ? (
                                        contacts.data.map((contact) => (
                                            <TableRow key={contact.id}>
                                                <TableCell className="font-medium">
                                                    {contact.first_name} {contact.last_name}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-col">
                                                        <span>{contact.email}</span>
                                                        <span>{contact.phone_number}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-col">
                                                        <span>{contact.country_code}</span>
                                                        <span>{contact.language_code}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-wrap gap-1">
                                                        {contact.chatbots.map((bot) => (
                                                            <Badge key={bot} variant="outline">{bot}</Badge>
                                                        ))}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-wrap gap-1">
                                                        {contact.channels.map((chan) => (
                                                            <Badge key={chan} variant="secondary">{chan}</Badge>
                                                        ))}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <span className="sr-only">{t('contactsPage.openMenu')}</span>
                                                                <EllipsisVertical className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem
                                                                onClick={() => {
                                                                    setTimeout(() => {
                                                                        setSelectedContact(contact);
                                                                        setIsSheetOpen(true);
                                                                    }, 100);
                                                                }}
                                                            >
                                                                {t('contactsPage.edit')}
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem
                                                                onClick={() => {
                                                                    setContactToDelete(contact.id);
                                                                    setIsDeleteDialogOpen(true);
                                                                }}
                                                                className="text-red-600"
                                                            >
                                                                {t('contactsPage.delete')}
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={6} className="h-24 text-center">
                                                {t('contactsPage.noResults')}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <CustomPagination links={contacts.links} />
                    </Card>
                </div>
                <UpsertContactSheet
                    open={isSheetOpen}
                    onOpenChange={setIsSheetOpen}
                    contact={selectedContact}
                />

                {contactToDelete && (
                    <DeleteContactDialog
                        open={isDeleteDialogOpen}
                        onOpenChange={setIsDeleteDialogOpen}
                        contactId={contactToDelete}
                    />
                )}
            </AppContentDefaultLayout>
        </AppLayout>
    );
}