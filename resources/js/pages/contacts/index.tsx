import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Pagination, PaginationContent, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { type PageProps } from '@/types';
import { Button } from '@/components/ui/button';

interface Contact {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone_number: string;
    country_code: string;
    language_code: string;
    chatbots: string[];
    channels: string[];
}

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
        <AppLayout>
            <Head title="Contacts" />
            <div className="space-y-4">
                <h1 className="text-2xl font-bold">Contacts</h1>

                <div className="flex items-center space-x-2">
                    <Input
                        placeholder="Search by name, email, phone..."
                        className="max-w-sm"
                        value={data.search}
                        onChange={(e) => setData('search', e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleFilterChange()}
                    />
                    <Select value={data.country} onValueChange={(v) => handleSelectChange('country', v)}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Country" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Countries</SelectItem>
                            {filterOptions.countries.map((country) => (
                                <SelectItem key={country} value={country}>
                                    {country}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={data.language} onValueChange={(v) => handleSelectChange('language', v)}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Language" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Languages</SelectItem>
                            {filterOptions.languages.map((lang) => (
                                <SelectItem key={lang} value={lang}>
                                    {lang}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={data.chatbot} onValueChange={(v) => handleSelectChange('chatbot', v)}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Chatbot" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Chatbots</SelectItem>
                            {filterOptions.chatbots.map((bot) => (
                                <SelectItem key={bot.id} value={String(bot.id)}>
                                    {bot.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={data.channel} onValueChange={(v) => handleSelectChange('channel', v)}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Channel" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Channels</SelectItem>
                            {filterOptions.channels.map((chan) => (
                                <SelectItem key={chan.id} value={String(chan.id)}>
                                    {chan.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button onClick={handleFilterChange}>Apply Filters</Button>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Contact</TableHead>
                                <TableHead>Location</TableHead>
                                <TableHead>Chatbots</TableHead>
                                <TableHead>Channels</TableHead>
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
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-24 text-center">
                                        No contacts found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <Pagination>
                    <PaginationContent>
                        {contacts.prev_page_url && (
                            <PaginationItem>
                                <PaginationPrevious href={contacts.prev_page_url} />
                            </PaginationItem>
                        )}
                        {contacts.links.map((link, index) => (
                            <PaginationItem key={index}>
                                <PaginationLink href={link.url || ''} isActive={link.active} dangerouslySetInnerHTML={{ __html: link.label }} />
                            </PaginationItem>
                        ))}
                        {contacts.next_page_url && (
                            <PaginationItem>
                                <PaginationNext href={contacts.next_page_url} />
                            </PaginationItem>
                        )}
                    </PaginationContent>
                </Pagination>
            </div>
        </AppLayout>
    );
}
