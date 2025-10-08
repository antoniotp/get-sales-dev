import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { Chatbot, PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

interface ChatbotContextType {
    switcherChatbots: Chatbot[];
    isLoading: boolean;
}

const ChatbotContext = createContext<ChatbotContextType | undefined>(undefined);

export function ChatbotProvider({ children }: { children: ReactNode }) {
    const { auth } = usePage<PageProps>().props;
    const [switcherChatbots, setSwitcherChatbots] = useState<Chatbot[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        console.log('auth.user?.current_organization', auth.user?.last_organization_id)
        if (auth.user?.last_organization_id) {
            fetch(route('chatbot_switcher.list'), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(response => response.json())
                .then(data => {
                    setSwitcherChatbots(data.switcherChatbots);
                })
                .catch(error => {
                    console.error('Error fetching chatbots:', error);
                })
                .finally(() => {
                    setIsLoading(false);
                });
        } else {
            console.log('No organization found');
            console.log('auth', auth)
            setIsLoading(false);
        }
    }, [auth.user?.last_organization_id]);

    const value = { switcherChatbots, isLoading };

    return <ChatbotContext.Provider value={value}>{children}</ChatbotContext.Provider>;
}

export function useChatbots() {
    const context = useContext(ChatbotContext);
    if (context === undefined) {
        throw new Error('useChatbots must be used within a ChatbotProvider');
    }
    return context;
}
