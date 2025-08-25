export interface Contact {
    id: number;
    first_name: string | null;
    last_name: string | null;
    email: string | null;
    phone_number: string | null;
    country_code: string | null;
    language_code: string | null;
    chatbots: string[];
    channels: string[];
}
