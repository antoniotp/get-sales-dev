import { Head, Link, usePage, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { PageProps } from '@/types';

interface InvitationDetails {
    organization_name: string;
    inviter_name: string;
    email: string;
}

interface AcceptPageProps {
    invitationDetails: InvitationDetails;
    token: string;
    userExists: boolean;
}

export default function AcceptInvitation({ invitationDetails, token, userExists }: AcceptPageProps) {
    const { auth } = usePage<PageProps>().props;
    const { post, processing } = useForm();

    const handleAccept = () => {
        post(route('invitations.accept', { token }));
    };

    const isUserLoggedIn = !!auth.user;
    const loggedInUser = auth.user;

    const renderContent = () => {
        if (isUserLoggedIn) {
            if (loggedInUser?.email === invitationDetails.email) {
                return (
                    <Button onClick={handleAccept} disabled={processing}>
                        {processing ? 'Accepting...' : 'Accept Invitation'}
                    </Button>
                );
            }
            return (
                <div>
                    <p className="mb-4 text-red-600">
                        This invitation was sent to <strong>{invitationDetails.email}</strong>, but you are logged in as <strong>{loggedInUser?.email}</strong>.
                    </p>
                    <p className="mb-4">Please log out and log back in with the correct account to accept this invitation.</p>
                    <Link href={route('logout')} method="post" as="button" className="underline">
                        Log Out
                    </Link>
                </div>
            );
        }

        // User is not logged in
        if (userExists) {
            return (
                <div>
                    <p className="mb-4">An account already exists for <strong>{invitationDetails.email}</strong>. Please log in to accept the invitation.</p>
                    <Button asChild>
                        <Link href={route('login')}>Log In</Link>
                    </Button>
                </div>
            );
        }

        // User does not exist
        return (
            <div>
                <p className="mb-4">To accept this invitation, please create an account for <strong>{invitationDetails.email}</strong>.</p>
                <Button asChild>
                    <Link href={route('register', { email: invitationDetails.email, token: token })}>Create Account</Link>
                </Button>
            </div>
        );
    };

    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-100">
            <Head title="Accept Invitation" />
            <div className="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md text-center">
                <h1 className="text-2xl font-bold text-gray-800">Invitation to Join</h1>
                <p className="text-gray-600">
                    <strong>{invitationDetails.inviter_name}</strong> has invited you to join the <strong>{invitationDetails.organization_name}</strong> organization.
                </p>
                <div className="pt-4">
                    {renderContent()}
                </div>
            </div>
        </div>
    );
}