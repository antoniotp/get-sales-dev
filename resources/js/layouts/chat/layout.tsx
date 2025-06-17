import AppLayout from '@/layouts/app-layout'
import { type PropsWithChildren } from 'react'

export default function ChatLayout({ children }: PropsWithChildren) {
    return (
        <AppLayout>
            <div className="flex h-full flex-1 flex-col rounded-xl overflow-hidden dark:bg-gray-900">
                {children}
            </div>
        </AppLayout>
    )
}
