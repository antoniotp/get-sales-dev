import AppLayout from '@/layouts/app-layout';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { FormEventHandler } from 'react';

const timezones = {
    'America': [
        'America/New_York',
        'America/Chicago',
        'America/Denver',
        'America/Los_Angeles',
        'America/Sao_Paulo',
        'America/Buenos_Aires',
    ],
    'Mexico': [
        'America/Mexico_City',
        'America/Cancun',
        'America/Tijuana',
    ],
    'Europe': [
        'Europe/London',
        'Europe/Paris',
        'Europe/Berlin',
        'Europe/Madrid',
        'Europe/Rome',
        'Europe/Moscow',
    ],
};

const locales = ['EN', 'ES', 'FR', 'IT', 'PT'];

export default function CreateOrganization() {
    const { data, setData, post, errors, processing } = useForm({
        name: '',
        website: '',
        address: '',
        timezone: '',
        locale: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('organizations.store'));
    };

    return (
        <AppLayout>
            <Head title="Create Organization" />
            <AppContentDefaultLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <div className="w-full overflow-auto pb-12">
                        <Card className="w-full p-3 overflow-auto">
                            <CardHeader>
                                <CardTitle>Create New Organization</CardTitle>
                                <CardDescription>Fill in the details below to create a new organization.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submit} className="space-y-6">
                                    <div>
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            required
                                        />
                                        {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="website">Website</Label>
                                        <Input
                                            id="website"
                                            type="url"
                                            value={data.website}
                                            onChange={(e) => setData('website', e.target.value)}
                                        />
                                        {errors.website && <p className="text-red-500 text-xs mt-1">{errors.website}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="address">Address</Label>
                                        <Input
                                            id="address"
                                            value={data.address}
                                            onChange={(e) => setData('address', e.target.value)}
                                        />
                                        {errors.address && <p className="text-red-500 text-xs mt-1">{errors.address}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="timezone">Timezone</Label>
                                        <Select onValueChange={(value) => setData('timezone', value)} value={data.timezone}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a timezone" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Object.entries(timezones).map(([group, zones]) => (
                                                    <div key={group}>
                                                        <p className="text-xs text-muted-foreground px-2 py-1.5 font-semibold">{group}</p>
                                                        {zones.map((zone) => (
                                                            <SelectItem key={zone} value={zone}>{zone}</SelectItem>
                                                        ))}
                                                    </div>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.timezone && <p className="text-red-500 text-xs mt-1">{errors.timezone}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="locale">Locale</Label>
                                        <Select onValueChange={(value) => setData('locale', value)} value={data.locale}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a locale" />
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
                                            Create Organization
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
