import AppLayout from '@/layouts/app-layout'
import { type PropsWithChildren } from 'react'

export default function ChatLayout({ children }: PropsWithChildren) {
    return (
        <AppLayout>
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto dark:bg-gray-900">
                {children}
            </div>
        </AppLayout>
    )
}
