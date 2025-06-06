import AppLayout from '@/layouts/app-layout'
import { type PropsWithChildren } from 'react'

export default function ChatLayout({ children }: PropsWithChildren) {
    return (
        <AppLayout>
            <div className="flex h-[calc(100vh-4rem)] bg-white dark:bg-gray-900">
                {children}
            </div>
        </AppLayout>
    )
}
