import AppLayout from '@/layouts/app-layout';
import AppContentDefaultLayout from '@/layouts/app/app-content-default-layout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { User, Organization } from '@/types';

interface Member extends User {
    pivot: {
        organization_id: number;
        user_id: number;
        role_id: number;
        status: string;
        joined_at: string;
    };
}

interface Role {
    id: number;
    name: string;
    slug: string;
    description: string;
    can_manage_chats: boolean;
    level: number;
}

interface Roles {
    [key: number]: Role;
}

interface MembersPageProps {
    organizationDetails: Organization;
    members: Member[];
    roles: Roles;
}

export default function OrganizationMembers({ organizationDetails, members, roles }: MembersPageProps) {
    const formatDate = (dateString: string) => {
        const options: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    };

    return (
        <AppLayout>
            <Head title={`Members of ${organizationDetails.name}`} />
            <AppContentDefaultLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <div className="w-full overflow-auto pb-12">
                        <Card className="w-full p-3 overflow-auto">
                            <CardHeader>
                                <CardTitle>Organization Members</CardTitle>
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
                    </div>
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
}
