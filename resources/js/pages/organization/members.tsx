import AppLayout from '@/layouts/app-layout';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Head, useForm, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { User, Organization, BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';

interface Member extends User {
    pivot: {
        role_id: number;
        status: string;
        joined_at: string;
    };
}

interface Role {
    id: number;
    name: string;
    slug: string;
}

interface Roles {
    [key: number]: Role;
}

interface Invitation {
    id: number;
    email: string;
    role: Role;
    status: 'pending' | 'expired';
    expires_at: string;
}

interface MembersPageProps {
    organizationDetails: Organization;
    members: Member[];
    roles: Roles;
    currentUserRoleSlug: string | null;
    invitations: Invitation[];
}

// --- Invite Member Form ---
function InviteMemberForm({ roles, setOpen }: { roles: Roles; setOpen: (open: boolean) => void }) {
    const { t } = useTranslation('organization');

    const { data, setData, post, processing, errors, wasSuccessful, reset } = useForm({
        email: '',
        role_id: '',
    });

    useEffect(() => {
        if (wasSuccessful) {
            setOpen(false);
            reset();
        }
    }, [wasSuccessful, reset, setOpen]);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('invitations.store'), {
            preserveScroll: true,
        });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid gap-2">
                <Label htmlFor="email">{t('members.invite.fields.email')}</Label>
                <Input
                    id="email"
                    type="email"
                    placeholder={t('members.invite.placeholders.email')}
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    disabled={processing}
                />
                {errors.email && <p className="text-sm text-red-500 mt-1">{errors.email}</p>}
            </div>
            <div className="grid gap-2">
                <Label htmlFor="role">{t('members.invite.fields.role')}</Label>
                <Select onValueChange={(value) => setData('role_id', value)} value={data.role_id} disabled={processing}>
                    <SelectTrigger>
                        <SelectValue placeholder={t('members.invite.placeholders.role')} />
                    </SelectTrigger>
                    <SelectContent>
                        {Object.values(roles).map((role) => (
                            <SelectItem key={role.id} value={String(role.id)}>
                                {role.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {errors.role_id && <p className="text-sm text-red-500 mt-1">{errors.role_id}</p>}
            </div>
            <DialogFooter>
                <Button type="submit" disabled={processing}>
                    {processing ? t('members.invite.actions.sending') : t('members.invite.actions.send')}
                </Button>
            </DialogFooter>
        </form>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'organization:members.breadcrumb', href: route('organizations.members.index') },
];

// --- Main Page Component ---
export default function OrganizationMembers({
    organizationDetails,
    members,
    roles,
    currentUserRoleSlug,
    invitations,
}: MembersPageProps) {
    const { t } = useTranslation('organization');
    const [isInviteDialogOpen, setInviteDialogOpen] = useState(false);

    const formatDate = (dateString: string) => {
        const options: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    };

    const canInvite = currentUserRoleSlug === 'admin' || currentUserRoleSlug === 'owner';

    const handleCancelInvitation = (invitationId: number) => {
        router.delete(route('invitations.destroy', invitationId), {
            preserveScroll: true,
        });
    };

    const handleResendInvitation = (invitationId: number) => {
        router.post(route('invitations.resend', invitationId), undefined, {
            preserveScroll: true,
        });
    };

    const translatedBreadcrumbs = [
        { ...breadcrumbs[0], title: t('members.breadcrumb') },
    ];

    return (
        <AppLayout breadcrumbs={translatedBreadcrumbs}>
            <Head title={t('members.head.title', { name: organizationDetails.name })} />
            <AppContentDefaultLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <div className="w-full overflow-auto pb-12 space-y-8">
                        <Card className="w-full p-3 overflow-auto">
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>{t('members.title')}</CardTitle>
                                <Dialog open={isInviteDialogOpen} onOpenChange={setInviteDialogOpen}>
                                    <DialogTrigger asChild>
                                        <Button disabled={!canInvite}>
                                            {t('members.invite.button')}
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>{t('members.invite.title')}</DialogTitle>
                                            <DialogDescription>
                                                {t('members.invite.description')}
                                            </DialogDescription>
                                        </DialogHeader>
                                        <InviteMemberForm roles={roles} setOpen={setInviteDialogOpen} />
                                    </DialogContent>
                                </Dialog>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('members.table.name')}</TableHead>
                                            <TableHead>{t('members.table.email')}</TableHead>
                                            <TableHead>{t('members.table.role')}</TableHead>
                                            <TableHead>{t('members.table.status')}</TableHead>
                                            <TableHead>{t('members.table.joined')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {members.map((member) => (
                                            <TableRow key={member.id}>
                                                <TableCell>{member.name}</TableCell>
                                                <TableCell>{member.email}</TableCell>
                                                <TableCell>{roles[member.pivot.role_id]?.name || t('members.table.na')}</TableCell>
                                                <TableCell>{member.pivot.status}</TableCell>
                                                <TableCell>{formatDate(member.pivot.joined_at)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        {invitations.length > 0 && (
                            <Card className="w-full p-3 overflow-auto">
                                <CardHeader>
                                    <CardTitle>{t('members.invitations.title')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>{t('members.table.email')}</TableHead>
                                                <TableHead>{t('members.table.role')}</TableHead>
                                                <TableHead>{t('members.table.status')}</TableHead>
                                                <TableHead>{t('members.table.expires')}</TableHead>
                                                <TableHead>{t('members.table.actions')}</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {invitations.map((invitation) => (
                                                <TableRow key={invitation.id}>
                                                    <TableCell>{invitation.email}</TableCell>
                                                    <TableCell>{invitation.role.name}</TableCell>
                                                    <TableCell>{invitation.status}</TableCell>
                                                    <TableCell>{formatDate(invitation.expires_at)}</TableCell>
                                                    <TableCell>
                                                        {invitation.status === 'pending' ? (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleCancelInvitation(invitation.id)}>
                                                                {t('members.actions.cancel')}
                                                            </Button>
                                                        ) : (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleResendInvitation(invitation.id)}>
                                                                {t('members.actions.resend')}
                                                            </Button>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}
