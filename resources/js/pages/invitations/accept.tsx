import { Head, Link, usePage, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
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
    const { t } = useTranslation('invitations');
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
                        {processing ? t('accept.accepting') : t('accept.acceptInvitation')}
                    </Button>
                );
            }
            return (
                <div>
                    <p className="mb-4 text-red-600">
                        {t('accept.wrongUserMessage', {
                            invitationEmail: invitationDetails.email,
                            loggedInEmail: loggedInUser?.email
                        })}
                    </p>
                    <p className="mb-4">
                        {t('accept.logoutInstruction')}
                    </p>
                    <Link href={route('logout')} method="post" as="button" className="underline">
                        {t('accept.logout')}
                    </Link>
                </div>
            );
        }

        // User is not logged in
        if (userExists) {
            return (
                <div>
                    <p className="mb-4">
                        {t('accept.accountExistsMessage', { email: invitationDetails.email })}
                    </p>
                    <Button asChild>
                        <Link href={route('login')}>
                            {t('accept.login')}
                        </Link>
                    </Button>
                </div>
            );
        }

        // User does not exist
        return (
            <div>
                <p className="mb-4">
                    {t('accept.createAccountMessage', { email: invitationDetails.email })}
                </p>
                <Button asChild>
                    <Link href={route('register', { email: invitationDetails.email, token: token })}>
                        {t('accept.createAccount')}
                    </Link>
                </Button>
            </div>
        );
    };

    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-100">
            <Head title={t('accept.headTitle')} />
            <div className="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md text-center">
                <h1 className="text-2xl font-bold text-gray-800">
                    {t('accept.title')}
                </h1>

                <p className="text-gray-600">
                    {t('accept.invitationMessage', {
                        inviter: invitationDetails.inviter_name,
                        organization: invitationDetails.organization_name
                    })}
                </p>

                <div className="pt-4">
                    {renderContent()}
                </div>
            </div>
        </div>
    );
}