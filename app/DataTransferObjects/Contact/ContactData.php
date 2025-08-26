<?php

namespace App\DataTransferObjects\Contact;

use App\Models\Contact;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class ContactData implements Arrayable
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $email,
        public readonly ?string $phoneNumber,
        public readonly ?string $countryCode,
        public readonly ?string $languageCode,
        /** @var Collection<string> */
        public readonly Collection $chatbots,
        /** @var Collection<string> */
        public readonly Collection $channels,
    ) {
    }

    public static function fromModel(Contact $contact): self
    {
        $channels = $contact->contactChannels->map(fn ($cc) => $cc->channel->name)->unique()->values();

        $chatbots = $contact->conversations->map(fn ($conv) => $conv->chatbotchannel->chatbot?->name)->filter()->unique()->values();

        return new self(
            id: $contact->id,
            firstName: $contact->first_name,
            lastName: $contact->last_name,
            email: $contact->email,
            phoneNumber: $contact->phone_number,
            countryCode: $contact->country_code,
            languageCode: $contact->language_code,
            chatbots: $chatbots,
            channels: $channels,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone_number' => $this->phoneNumber,
            'country_code' => $this->countryCode,
            'language_code' => $this->languageCode,
            'chatbots' => $this->chatbots,
            'channels' => $this->channels,
        ];
    }
}
