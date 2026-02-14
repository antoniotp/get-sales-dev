import AppLayout from '@/layouts/app-layout';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { FormEventHandler } from 'react';
import { PageProps as GlobalPageProps } from '@/types';
import { useTranslation } from 'react-i18next';

interface Timezone {
    label: string;
    value: string;
    continent: string;
    offset: number;
}

interface Timezones {
    [continent: string]: Timezone[];
}

interface PageProps extends GlobalPageProps{
    timezones: Timezones;
}

const locales = ['EN', 'ES', 'FR', 'IT', 'PT'];

export default function OrganizationForm() {
    const { t } = useTranslation('organization');
    const {props} = usePage<PageProps>();
    const organization = props.organization.current;
    const timezones: Timezones = props.timezones;
    const isEditMode = organization !== null;

    const { data, setData, post, put, errors, processing } = useForm({
        name: organization?.name || '',
        website: organization?.website || '',
        address: organization?.address || '',
        timezone: organization?.timezone || '',
        locale: organization?.locale || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (isEditMode) {
            put(route('organizations.update', organization.id));
        } else {
            post(route('organizations.store'));
        }
    };

    return (
        <AppLayout>
            <Head title={isEditMode ? t('form.head.edit') : t('form.head.create')} />
            <AppContentDefaultLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <div className="w-full overflow-auto pb-12">
                        <Card className="w-full p-3 overflow-auto">
                            <CardHeader>
                                <CardTitle>
                                    {isEditMode ? t('form.titles.edit') : t('form.titles.create')}
                                </CardTitle>
                                <CardDescription>
                                    {isEditMode
                                        ? t('form.descriptions.edit')
                                        : t('form.descriptions.create')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submit} className="space-y-6">
                                    <div>
                                        <Label htmlFor="name">{t('form.fields.name')}</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            required
                                        />
                                        {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="website">{t('form.fields.website')}</Label>
                                        <Input
                                            id="website"
                                            type="url"
                                            value={data.website}
                                            onChange={(e) => setData('website', e.target.value)}
                                        />
                                        {errors.website && <p className="text-red-500 text-xs mt-1">{errors.website}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="address">{t('form.fields.address')}</Label>
                                        <Input
                                            id="address"
                                            value={data.address}
                                            onChange={(e) => setData('address', e.target.value)}
                                        />
                                        {errors.address && <p className="text-red-500 text-xs mt-1">{errors.address}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="timezone">{t('form.fields.timezone')}</Label>
                                        <Select onValueChange={(value) => setData('timezone', value)} value={data.timezone}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('form.placeholders.timezone')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Object.entries(timezones).map(([group, zones]) => (
                                                    <div key={group}>
                                                        <p className="text-xs text-muted-foreground px-2 py-1.5 font-semibold">{group}</p>
                                                        {zones.map((zone) => (
                                                            <SelectItem key={zone.value} value={zone.value}>{zone.label}</SelectItem>
                                                        ))}
                                                    </div>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.timezone && <p className="text-red-500 text-xs mt-1">{errors.timezone}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="locale">{t('form.fields.locale')}</Label>
                                        <Select onValueChange={(value) => setData('locale', value)} value={data.locale}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('form.placeholders.locale')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {locales.map((locale) => (
                                                    <SelectItem key={locale} value={locale}>{locale}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.locale && <p className="text-red-500 text-xs mt-1">{errors.locale}</p>}
                                    </div>

                                    <div className="flex items-center justify-end">
                                        <Button type="submit" disabled={processing}>
                                            {isEditMode
                                                ? t('form.actions.save')
                                                : t('form.actions.create')}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}
