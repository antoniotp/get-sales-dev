import { type PropsWithChildren } from 'react'
import Heading from '@/components/heading';

export default function MessageTemplateLayout({ children }: PropsWithChildren) {
    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    return (
        <div className="flex h-full flex-1 flex-col px-4 py-6 rounded-xl overflow-hidden dark:bg-gray-900">
            <Heading title="Templates" description="Manage your templates" />
            {children}
        </div>
    )
}
