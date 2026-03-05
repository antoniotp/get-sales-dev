import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useForm } from '@inertiajs/react';
import { Contact } from '@/types/contact';
import { useEffect } from 'react';
import InputError from '@/components/input-error';
import { useTranslation } from 'react-i18next';

interface UpsertContactSheetProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    contact?: Contact;
}

export function UpsertContactSheet({ open, onOpenChange, contact }: UpsertContactSheetProps) {

    const { t } = useTranslation('contact');

    const { data, setData, post, put, processing, errors, reset } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone_number: '',
        country_code: '',
        language_code: '',
    });

    useEffect(() => {
        if (open) {
            reset();
            setData({
                first_name: contact?.first_name || '',
                last_name: contact?.last_name || '',
                email: contact?.email || '',
                phone_number: contact?.phone_number || '',
                country_code: contact?.country_code || '',
                language_code: contact?.language_code || '',
            });
        }
    }, [open, contact, reset, setData]);

    const handleOpenChange = (open: boolean) => {
        onOpenChange(open);
        if (!open) {
            reset();
        }
    };

    const onSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (contact) {
            put(route('contacts.update', contact.id), {
                onSuccess: () => onOpenChange(false),
            });
        } else {
            post(route('contacts.store'), {
                onSuccess: () => onOpenChange(false),
            });
        }
    };

    return (
        <Sheet open={open} onOpenChange={handleOpenChange}>
            <SheetContent>
                <SheetHeader>
                    <SheetTitle>
                        {contact ? t('form.editTitle') : t('form.createTitle')}
                    </SheetTitle>
                    <SheetDescription>
                        {contact
                            ? t('form.editDescription')
                            : t('form.createDescription')}
                    </SheetDescription>
                </SheetHeader>
                <form onSubmit={onSubmit} className="space-y-8 py-4">
                    <div className="grid gap-4">
                        <div className="grid grid-cols-4 items-center gap-4">
                            <Label htmlFor="first_name" className="text-right">
                                {t('form.fields.first_name')}
                            </Label>
                            <Input
                                id="first_name"
                                value={data.first_name}
                                onChange={(e) => setData('first_name', e.target.value)}
                                className="col-span-3"
                            />
                            <InputError message={errors.first_name} className="col-span-4" />
                        </div>
                        <div className="grid grid-cols-4 items-center gap-4">
                            <Label htmlFor="last_name" className="text-right">
                                {t('form.fields.last_name')}
                            </Label>
                            <Input
                                id="last_name"
                                value={data.last_name}
                                onChange={(e) => setData('last_name', e.target.value)}
                                className="col-span-3"
                            />
                            <InputError message={errors.last_name} className="col-span-4" />
                        </div>
                        <div className="grid grid-cols-4 items-center gap-4">
                            <Label htmlFor="email" className="text-right">
                                {t('form.fields.email')}
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="col-span-3"
                            />
                            <InputError message={errors.email} className="col-span-4" />
                        </div>
                        <div className="grid grid-cols-4 items-center gap-4">
                            <Label htmlFor="phone_number" className="text-right">
                                {t('form.fields.phone')}
                            </Label>
                            <Input
                                id="phone_number"
                                value={data.phone_number}
                                onChange={(e) => setData('phone_number', e.target.value)}
                                className="col-span-3"
                            />
                            <InputError message={errors.phone_number} className="col-span-4" />
                        </div>
                        <div className="grid grid-cols-4 items-center gap-4">
                            <Label htmlFor="country_code" className="text-right">
                                {t('form.fields.country')}
                            </Label>
                            <Input
                                id="country_code"
                                value={data.country_code}
                                onChange={(e) => setData('country_code', e.target.value)}
                                className="col-span-3"
                            />
                            <InputError message={errors.country_code} className="col-span-4" />
                        </div>
                        <div className="grid grid-cols-4 items-center gap-4">
                            <Label htmlFor="language_code" className="text-right">
                                {t('form.fields.language')}
                            </Label>
                            <Input
                                id="language_code"
                                value={data.language_code}
                                onChange={(e) => setData('language_code', e.target.value)}
                                className="col-span-3"
                            />
                            <InputError message={errors.language_code} className="col-span-4" />
                        </div>
                    </div>
                    <SheetFooter>
                        <SheetClose asChild>
                            <Button type="button" variant="secondary">
                                {t('form.cancel')}
                            </Button>
                        </SheetClose>
                        <Button type="submit" disabled={processing}>
                            {contact ? t('form.saveChanges') : t('form.create')}
                        </Button>
                    </SheetFooter>
                </form>
            </SheetContent>
        </Sheet>
    );
}