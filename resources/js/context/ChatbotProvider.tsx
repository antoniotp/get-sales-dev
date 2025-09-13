import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { Chatbot } from '@/types';

interface ChatbotContextType {
    switcherChatbots: Chatbot[];
    isLoading: boolean;
}

const ChatbotContext = createContext<ChatbotContextType | undefined>(undefined);

export function ChatbotProvider({ children }: { children: ReactNode }) {
    const [switcherChatbots, setSwitcherChatbots] = useState<Chatbot[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        fetch(route('chatbot_switcher.list'))
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
    }, []);

    const value = { switcherChatbots, isLoading };

    return (
        <ChatbotContext.Provider value={value}>
            {children}
        </ChatbotContext.Provider>
    );
}

export function useChatbots() {
    const context = useContext(ChatbotContext);
    if (context === undefined) {
        throw new Error('useChatbots must be used within a ChatbotProvider');
    }
    return context;
}
