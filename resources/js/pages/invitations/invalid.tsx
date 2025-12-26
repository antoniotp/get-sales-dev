import { Head } from '@inertiajs/react';

export default function InvalidInvitation() {
    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-100">
            <Head title="Invalid Invitation" />
            <div className="text-center">
                <h1 className="text-2xl font-bold text-gray-800">Invitation Invalid</h1>
                <p className="mt-2 text-gray-600">This invitation link is either invalid or has expired.</p>
                <a href="/" className="mt-4 inline-block px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">
                    Go to Homepage
                </a>
            </div>
        </div>
    );
}
