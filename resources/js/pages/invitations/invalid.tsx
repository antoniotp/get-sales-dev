import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function InvalidInvitation() {
    const { t } = useTranslation('invitations');

    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-100">
            <Head title={t('invalid.headTitle')} />
            <div className="text-center">
                <h1 className="text-2xl font-bold text-gray-800">
                    {t('invalid.title')}
                </h1>

                <p className="mt-2 text-gray-600">
                    {t('invalid.description')}
                </p>

                <a
                    href="/"
                    className="mt-4 inline-block px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700"
                >
                    {t('invalid.homeLink')}
                </a>
            </div>
        </div>
    );
}