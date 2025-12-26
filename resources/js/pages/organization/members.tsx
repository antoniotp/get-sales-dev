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
                <Label htmlFor="email">Email Address</Label>
                <Input
                    id="email"
                    type="email"
                    placeholder="name@example.com"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    disabled={processing}
                />
                {errors.email && <p className="text-sm text-red-500 mt-1">{errors.email}</p>}
            </div>
            <div className="grid gap-2">
                <Label htmlFor="role">Role</Label>
                <Select onValueChange={(value) => setData('role_id', value)} value={data.role_id} disabled={processing}>
                    <SelectTrigger>
                        <SelectValue placeholder="Select a role" />
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
                    {processing ? 'Sending...' : 'Send Invitation'}
                </Button>
            </DialogFooter>
        </form>
    );
}


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Members', href: route('organizations.members.index') },
];
// --- Main Page Component ---
export default function OrganizationMembers({
    organizationDetails,
    members,
    roles,
    currentUserRoleSlug,
    invitations,
}: MembersPageProps) {
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Members of ${organizationDetails.name}`} />
            <AppContentDefaultLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <div className="w-full overflow-auto pb-12 space-y-8">
                        <Card className="w-full p-3 overflow-auto">
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>Organization Members</CardTitle>
                                <Dialog open={isInviteDialogOpen} onOpenChange={setInviteDialogOpen}>
                                    <DialogTrigger asChild>
                                        <Button disabled={!canInvite}>Invite Member</Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Invite a new member</DialogTitle>
                                            <DialogDescription>
                                                Enter the email address and assign a role for the new member.
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
                                            <TableHead>Name</TableHead>
                                            <TableHead>Email</TableHead>
                                            <TableHead>Role</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Joined At</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {members.map((member) => (
                                            <TableRow key={member.id}>
                                                <TableCell>{member.name}</TableCell>
                                                <TableCell>{member.email}</TableCell>
                                                <TableCell>{roles[member.pivot.role_id]?.name || 'N/A'}</TableCell>
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
                                    <CardTitle>Invitations</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Email</TableHead>
                                                <TableHead>Role</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Expires At</TableHead>
                                                <TableHead>Actions</TableHead>
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
                                                                Cancel
                                                            </Button>
                                                        ) : (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleResendInvitation(invitation.id)}>
                                                                Resend
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
