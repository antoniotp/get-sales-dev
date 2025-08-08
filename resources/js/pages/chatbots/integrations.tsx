import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Card, CardContent, CardDescription } from '@/components/ui/card';

export default function Integrations() {
    return (
        <AppLayout>
            <Head title="Agents | Integrations" />
            <AppContentDefaultLayout>
                <div className="space-y-6">
                    {/* Header Section */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Agents</h1>
                            <p className="text-muted-foreground">
                                Manage your AI Agents integrations
                            </p>
                        </div>
                    </div>
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <Card>
                            <CardContent>
                                <CardDescription>
                                    Whatsapp
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}
