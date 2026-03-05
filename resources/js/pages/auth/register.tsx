import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';
import { useTranslation } from 'react-i18next';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    token: string; // Changed from string | null
};

interface RegisterPageProps {
    email?: string;
    token?: string;
}

export default function Register({ email = '', token }: RegisterPageProps) {
    const { t } = useTranslation('register');

    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        name: '',
        email: email,
        password: '',
        password_confirmation: '',
        token: token || '', // Ensure it's always a string
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title={t('head.title')}
            description={t('head.description')}
        >
            <Head title={t('head.page_title')} />
            <form className="flex flex-col gap-6" onSubmit={submit}>
                <input type="hidden" name="token" value={data.token || ''} />
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t('fields.name')}</Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            disabled={processing}
                            placeholder={t('fields.name_placeholder')}
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">{t('fields.email')}</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            tabIndex={2}
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            disabled={processing || !!email}
                            placeholder={t('fields.email_placeholder')}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">{t('fields.password')}</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            tabIndex={3}
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            disabled={processing}
                            placeholder={t('fields.password_placeholder')}
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">{t('fields.password_confirmation')}</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            tabIndex={4}
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            disabled={processing}
                            placeholder={t('fields.password_confirmation_placeholder')}
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>

                    <Button type="submit" className="mt-2 w-full" tabIndex={5} disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        {t('actions.create')}
                    </Button>
                </div>

                <div className="text-center text-sm text-muted-foreground">
                    {t('footer.have_account')}{' '}
                    <TextLink href={route('login')} tabIndex={6}>
                        {t('footer.login')}
                    </TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}