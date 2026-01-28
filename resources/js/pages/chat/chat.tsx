import ChatLayout from '@/layouts/chat/layout';
import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState, useRef, useCallback, useMemo } from 'react';
import axios from 'axios';
import { format } from 'date-fns';
import { useRoute } from 'ziggy-js';
import Echo from 'laravel-echo';
import parsePhoneNumber from 'libphonenumber-js';
import type { BreadcrumbItem, Chatbot, PageProps, Organizations, Agent, Chat, Message } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { ArrowLeft } from 'lucide-react';
import AgentAssignmentDropdown from '@/components/chat/AgentAssignmentDropdown';
import { Badge } from '@/components/ui/badge';
import { NewConversationView } from '@/pages/chat/partials/NewConversationView';
import { Button } from '@/components/ui/button';
import { PlusCircle } from 'lucide-react';
import LinkifyText from '@/components/chat/LinkifyText';
import MessageStatus from '@/components/chat/MessageStatus';

interface NewMessageEvent {
    message: Message;
}

interface NewConversationEvent {
    conversation: Chat;
}

interface ChatbotChannel {
    id: number;
    name: string;
    phone_number: string | null;
}

const formatPhoneNumber = (phone: string): string => {
    if (!phone) return '';
    if (!phone.startsWith('+')) {
        phone = '+' + phone;
    }
    try {
        const phoneNumber = parsePhoneNumber(phone);
        return phoneNumber ? phoneNumber.formatInternational() : phone;
    } catch {
        console.warn('Failed to parse phone number:', phone);
        return phone;
    }
};

const sortChatsByLastMessageTime = (chats: Chat[]): Chat[] => {
    return [...chats].sort((a, b) => {
        const timeA = new Date(a.lastMessageTime).getTime();
        const timeB = new Date(b.lastMessageTime).getTime();
        return timeB - timeA; // Descendente (más reciente primero)
    });
};

export default function Chat(
    { chats: initialChats, organization, agents, canAssign, chatbotChannels, selectedConversation }:
    {
        chats: Chat[];
        chatbotChannels: ChatbotChannel[];
        organization: Organizations;
        agents: Agent[];
        canAssign: boolean;
        selectedConversation: Chat | null;
    }
) {
    const [chats, setChats] = useState<Chat[]>(initialChats);
    const [selectedChat, setSelectedChat] = useState<Chat | null>(selectedConversation);
    const [messages, setMessages] = useState<Message[]>([]);
    const [newMessage, setNewMessage] = useState('');
    const [isSending, setIsSending] = useState(false);
    const [isLoadingMessages, setIsLoadingMessages] = useState(false);
    const [view, setView] = useState<'list' | 'new'>('list');
    const [conversationMode, setConversationMode] = useState<'ai' | 'human'>('ai');
    const { chatbot } = usePage<PageProps>().props as { chatbot: Chatbot };

    const route = useRoute();
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const messagesContainerRef = useRef<HTMLDivElement>(null);
    const echoRef = useRef<Echo<'pusher'> | null>(null);
    const activeChannelsRef = useRef<Set<string>>(new Set());
    const selectedChatRef = useRef<Chat | null>(null);

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            {
                title: chatbot.name,
                href: route('chatbots.edit', { chatbot: chatbot.id }),
            },
            {
                title: 'Chats',
                href: '',
            },
        ],
        [chatbot, route]
    );

    // Keep the selectedChatRef in sync with the selectedChat
    useEffect(() => {
        selectedChatRef.current = selectedChat;
    }, [selectedChat]);

    // Optimized scroll to bottom
    const scrollToBottom = useCallback(() => {
        if (messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, []);

    // Initialize Echo connection
    useEffect(() => {
        const initializeEcho = () => {
            if (echoRef.current) return echoRef.current;

            const echoInstance = new Echo<'pusher'>({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
                forceTLS: true,
            });

            echoRef.current = echoInstance;
            return echoInstance;
        };

        const echo = initializeEcho();

        return () => {
            // Cleanup all channels
            activeChannelsRef.current.forEach(channelName => {
                echo.leave(channelName);
            });
            activeChannelsRef.current.clear();

            if (echoRef.current) {
                echoRef.current.disconnect();
                echoRef.current = null;
            }
        };
    }, []);

    // Setup organization channel for new conversations
    useEffect(() => {
        if (!echoRef.current) return;

        const channelName = 'chat.organization.' + organization.current.id;

        if (activeChannelsRef.current.has(channelName)) return;

        const orgChannel = echoRef.current.channel(channelName);

        orgChannel.listen('.conversation.created', (e: NewConversationEvent) => {
            setChats(prevChats => {
                const existingIndex = prevChats.findIndex(chat => chat.id === e.conversation.id);
                // update if exists, otherwise add to the beginning
                if (existingIndex !== -1) {
                    const updatedChats = [...prevChats];
                    updatedChats[existingIndex] = e.conversation;
                    return sortChatsByLastMessageTime(updatedChats);
                }
                const updatedChats = [e.conversation, ...prevChats];
                return sortChatsByLastMessageTime(updatedChats);
            });
        });

        activeChannelsRef.current.add(channelName);

        return () => {
            if (echoRef.current && activeChannelsRef.current.has(channelName)) {
                echoRef.current.leave(channelName);
                activeChannelsRef.current.delete(channelName);
            }
        };
    }, [organization, setChats]);

    // Setup chat channels for messages - optimized to avoid unnecessary re-subscriptions
    useEffect(() => {
        if (!echoRef.current || chats.length === 0) return;

        const echo = echoRef.current;
        const newChannels: string[] = [];

        // Subscribe to new chat channels
        chats.forEach(chat => {
            const channelName = `chat.conversation.${chat.id}`;

            if (!activeChannelsRef.current.has(channelName)) {
                const channel = echo.channel(channelName);

                channel.listen('.message.received', (e: NewMessageEvent) => {
                    // Update messages if this is the selected chat - using ref to get the current value
                    const currentSelectedChat = selectedChatRef.current;
                    if (currentSelectedChat?.id === e.message.conversationId) {
                        setMessages(prevMessages => {
                            const messageIndex = prevMessages.findIndex(msg => msg.id === e.message.id);

                            // If message exists, update it. Otherwise, add it.
                            if (messageIndex !== -1) {
                                const updatedMessages = [...prevMessages];
                                updatedMessages[messageIndex] = e.message;
                                return updatedMessages;
                            } else {
                                return [...prevMessages, e.message];
                            }
                        });
                    }

                    // Update chat list - this should always happen regardless of selected chat
                    setChats(prevChats => {
                        const updatedChats = prevChats.map(prevChat => {
                            if (prevChat.id === e.message.conversationId) {
                                const isCurrentChat = currentSelectedChat?.id === e.message.conversationId;
                                return {
                                    ...prevChat,
                                    lastMessage: e.message.content,
                                    lastMessageTime: e.message.timestamp,
                                    unreadCount: isCurrentChat
                                        ? prevChat.unreadCount
                                        : prevChat.unreadCount + 1,
                                };
                            }
                            return prevChat;
                        });
                        return sortChatsByLastMessageTime(updatedChats);
                    });
                });

                activeChannelsRef.current.add(channelName);
                newChannels.push(channelName);
            }
        });

        // Cleanup function for this effect
        return () => {
            newChannels.forEach(channelName => {
                if (echo && activeChannelsRef.current.has(channelName)) {
                    echo.leave(channelName);
                    activeChannelsRef.current.delete(channelName);
                }
            });
        };
    }, [chats, setChats, setMessages]);

    // Load messages when chat is selected
    const loadChatMessages = useCallback(async (chatId: number) => {
        setIsLoadingMessages(true);
        try {
            const response = await axios.get(route('chats.messages', { conversation: chatId }));
            setMessages(response.data.messages || []);

            // Mark chat as read
            setChats(prevChats =>
                prevChats.map(chat =>
                    chat.id === chatId
                        ? { ...chat, unreadCount: 0 }
                        : chat
                )
            );

            // Scroll to the bottom after messages load
            setTimeout(scrollToBottom, 100);
        } catch (error) {
            console.error('Failed to load messages:', error);
            setMessages([]);
        } finally {
            setIsLoadingMessages(false);
        }
    }, [route, scrollToBottom, setChats]);

    // Handle pre-selected conversation on the initial load
    useEffect(() => {
        if (selectedConversation) {
            // Set the initial chat list, ensuring the selected one is at the top and not duplicated.
            setChats(prevChats => {
                const otherChats = prevChats.filter(chat => chat.id !== selectedConversation.id);
                return [selectedConversation, ...otherChats];
            });

            loadChatMessages(selectedConversation.id);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []); // Run only once on mount

    // Handle chat selection
    const handleChatSelect = useCallback((chat: Chat) => {
        if (selectedChat?.id === chat.id) return;

        setSelectedChat(chat);
        setConversationMode(chat.mode);
        setMessages([]);
        loadChatMessages(chat.id);
    }, [selectedChat?.id, loadChatMessages]);

    // Handle message sending
    const handleSendMessage = useCallback(async (e: React.FormEvent) => {
        e.preventDefault();

        if (!newMessage.trim() || !selectedChat || isSending) return;

        const messageContent = newMessage.trim();
        setIsSending(true);
        setNewMessage('');

        try {
            const response = await axios.post(
                route('chats.messages.store', { conversation: selectedChat.id }),
                {
                    content: messageContent,
                    content_type: 'text',
                }
            );

            // Add the message to the current conversation
            setMessages(prevMessages => [...prevMessages, response.data.message]);

            // Update chat list and reorder
            setChats(prevChats => {
                const updatedChats = prevChats.map(chat =>
                    chat.id === selectedChat.id
                        ? {
                            ...chat,
                            lastMessage: messageContent,
                            lastMessageTime: new Date().toISOString(),
                        }
                        : chat
                );
                return sortChatsByLastMessageTime(updatedChats);
            });
        } catch (error) {
            console.error('Failed to send message:', error);
            // Restore message on error
            setNewMessage(messageContent);
            // TODO: show an error notification
        } finally {
            setIsSending(false);
        }
    }, [newMessage, selectedChat, isSending, route, setChats]);

    const handleModeChange = useCallback(async (newMode: 'ai' | 'human') => {
        if (!selectedChat || conversationMode === newMode) return;

        try {
            const response = await axios.put(
                route('chats.mode.update', { conversation: selectedChat.id }),
                { mode: newMode }
            );

            if (response.data.success) {
                setConversationMode(newMode);
                // Update the chat in the list
                setChats(prevChats =>
                    prevChats.map(chat =>
                        chat.id === selectedChat.id
                            ? { ...chat, mode: newMode }
                            : chat
                    )
                );
            }
        } catch (error) {
            console.error('Failed to update conversation mode:', error);
            // TODO: show error notification
        }
    }, [selectedChat, conversationMode, route, setChats]);

    const handleAgentAssigned = useCallback((updatedChat: Chat) => {
        setSelectedChat(updatedChat);
        setChats(prevChats =>
            prevChats.map(chat =>
                chat.id === updatedChat.id ? updatedChat : chat
            )
        );
    }, [setChats]);

    const handleNewChatSuccess = useCallback((newChat: Chat) => {
        setChats(prevChats => {
            // Remove the chat if it already exists to avoid duplicates
            const filteredChats = prevChats.filter(chat => chat.id !== newChat.id);
            // Add the new/updated chat to the beginning and re-sort
            return sortChatsByLastMessageTime([newChat, ...filteredChats]);
        });

        handleChatSelect(newChat);
        setView('list');
    }, [setChats, handleChatSelect, setView]);

    const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setNewMessage(value);

        // Auto-disable AI mode when the user starts typing
        if (value.length === 1 && conversationMode === 'ai') {
            handleModeChange('human');
        }
    }, [conversationMode, handleModeChange]);

    // Auto-scroll when new messages arrive
    useEffect(() => {
        if (messages.length > 0) {
            scrollToBottom();
        }
    }, [messages.length, scrollToBottom]);

    // Memoized chat list to prevent unnecessary re-renders
    const chatList = useMemo(() => (
        chats.map((chat) => (
            <div
                key={chat.id}
                onClick={() => handleChatSelect(chat)}
                className={`cursor-pointer border-b border-gray-200 p-4 hover:bg-gray-200 dark:border-gray-700 dark:hover:bg-gray-600 transition-colors ${
                    selectedChat?.id === chat.id
                        ? 'bg-gray-300 dark:bg-gray-600 border-l-4 border-l-whatsapp'
                        : 'border-l-4 border-transparent'
                }`}
            >
                <div className="flex items-center space-x-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500 text-white flex-shrink-0">
                        {chat.avatar}
                    </div>
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between">
                            <h3 className="font-semibold truncate">{chat.name}</h3>
                            <span className="text-xs text-gray-500 flex-shrink-0 text-right">
                                {format(new Date(chat.lastMessageTime), 'dd/MM/yyyy HH:mm')}
                            </span>
                        </div>
                        <p className="text-sm text-gray-600 dark:text-gray-400 truncate">
                            {chat.lastMessage}
                        </p>
                        <div className="flex justify-between items-center mt-1 italic">
                            <Badge
                                variant="secondary"
                                className={`px-1 py-0 max-w-1/2 overflow-hidden whitespace-nowrap justify-start ${chat.assigned_user_name ? 'bg-blue-500 text-white dark:bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'}`}
                            >
                                @{chat.assigned_user_name?? "Unassigned"}
                            </Badge>
                            <small>
                                AI Response
                                <strong className="border border-gray-300 rounded-md px-1 py-1 text-xs font-medium ml-1 not-italic">
                                    {chat.mode === 'ai' ? (
                                        <span className="text-blue-600">ON</span>
                                    ) : (
                                        <span className="text-gray-500">OFF</span>
                                    )}
                                </strong>
                            </small>
                        </div>
                        <div>
                            <small>Received on: {formatPhoneNumber(chat.recipient || '')}</small>
                        </div>
                    </div>
                    {chat.unreadCount > 0 && (
                        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 text-xs text-white flex-shrink-0">
                            {chat.unreadCount > 99 ? '99+' : chat.unreadCount}
                        </span>
                    )}
                </div>
            </div>
        ))
    ), [chats, selectedChat?.id, handleChatSelect]);

    // Memoized message list
    const messageList = useMemo(() => (
        messages.map((message) => (
            <div
                key={message.id}
                className={`mb-4 flex ${message.type === 'outgoing' ? 'justify-end' : 'justify-start'}`}
            >
                <div
                    className={`${(message.contentType === 'ptt' || message.contentType === 'audio') ? 'w-[70%]' : 'max-w-[70%]'} rounded-[18px] px-4 py-2 ${
                        message.type === 'outgoing'
                            ? 'bg-[#2563EB] text-[#F9FAFB]'
                            : 'bg-[#F1F5F9] text-[#1F2937] dark:bg-[#374151] dark:text-[#E2E8F0]'
                    }`}
                >
                    {selectedChat?.type === 'group' && message.sender && (
                        <div className="text-xs font-semibold mb-1">
                            {message.sender}
                        </div>
                    )}
                    {message.contentType === 'text' ? (
                        <LinkifyText
                            text={message.content}
                            className={`break-words whitespace-pre-wrap ${
                                message.type === 'outgoing'
                                    ? '[&_a]:text-[#93C5FD] [&_a]:underline'
                                    : '[&_a]:text-[#1D4ED8] dark:[&_a]:text-[#93C5FD] [&_a]:underline'
                            }`}
                        />
                    ) : message.contentType === 'image' && message.mediaUrl ? (
                        <>
                            <img
                                src={message.mediaUrl}
                                alt="Message media"
                                className="max-w-sm max-h-96 rounded"
                                loading="lazy"
                            />
                            {message.content &&
                                (<LinkifyText
                                    text={message.content}
                                    className={`break-words whitespace-pre-wrap ${
                                        message.type === 'outgoing'
                                            ? '[&_a]:text-[#93C5FD] [&_a]:underline'
                                            : '[&_a]:text-[#1D4ED8] dark:[&_a]:text-[#93C5FD] [&_a]:underline'
                                    }`}
                                />)
                            }
                        </>
                    ) : (message.contentType === 'ptt' || message.contentType === 'audio') && message.mediaUrl ? (
                        <audio controls src={message.mediaUrl} className="w-full">
                            Tu navegador no soporta el elemento de audio.
                        </audio>
                    ) : (
                        <LinkifyText
                            text={message.content}
                            className={`break-words whitespace-pre-wrap ${
                                message.type === 'outgoing'
                                    ? '[&_a]:text-[#93C5FD] [&_a]:underline'
                                    : '[&_a]:text-[#1D4ED8] dark:[&_a]:text-[#93C5FD] [&_a]:underline'
                            }`}
                        />
                    )}
                    <span className="mt-1 text-xs flex items-center justify-end text-[#9CA3AF]">
                        <span className="mr-2">{format(new Date(message.timestamp), 'dd/MM/yyyy HH:mm')}</span>
                        <MessageStatus message={message} />
                    </span>
                </div>
            </div>
        ))
    ), [messages, selectedChat?.type]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chat" />
            <ChatLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    {/* Chat List */}
                    <div className={`${selectedChat ? 'hidden lg:flex' : 'flex'} w-full lg:w-1/3 border-r border-gray-200 dark:border-gray-700 flex-col`}>
                        {view === 'list' ? (
                            <>
                                <div className="border-b border-gray-200 px-4 py-1 dark:border-gray-700 flex-shrink-0 flex items-center justify-between">
                                    <h2 className="text-lg font-semibold">Chats</h2>
                                    <Button variant="ghost" size="icon" onClick={() => setView('new')}>
                                        <PlusCircle className="h-6 w-6" />
                                    </Button>
                                </div>
                                <div className="flex-1 overflow-y-auto">
                                    {chats.length > 0 ? (
                                        chatList
                                    ) : (
                                        <div className="flex items-center justify-center h-full">
                                            <p className="text-gray-500">No chats available</p>
                                        </div>
                                    )}
                                </div>
                            </>
                        ) : (
                            <NewConversationView
                                onBack={() => setView('list')}
                                onSuccess={handleNewChatSuccess}
                                chatbotChannels={chatbotChannels}
                            />
                        )}
                    </div>

                    {/* Chat Messages */}
                    {selectedChat ? (
                        <div className="flex w-full lg:w-2/3 flex-col">
                            <div className="flex lg:hidden items-center h-6 border-b border-gray-200 px-1 md:px-4 dark:border-gray-700 flex-shrink-0">
                                <button
                                    onClick={() => setSelectedChat(null)}
                                    className="text-blue-500 hover:underline flex flex-row items-center space-x-2"
                                >
                                    <ArrowLeft size={16} /> Back to Chats
                                </button>
                            </div>
                            {/* Chat Header */}
                            <div className="flex h-16 items-center justify-between border-b border-gray-200 px-1 md:px-4 dark:border-gray-700 flex-shrink-0 relative">
                                <div className="flex items-center space-x-1 md:space-x-4 w-1/2">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white">
                                        {selectedChat.avatar}
                                    </div>
                                    <div className="min-w-0">
                                        <h3 className="font-semibold truncate">{selectedChat.name}</h3>
                                        <div>
                                            <small>{formatPhoneNumber(selectedChat.phone)}</small>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex flex-col items-left space-x-1 md:space-x-4">
                                    <div className="justify-center flex space-x-1">
                                        <span className="text-sm text-gray-600 dark:text-gray-400">
                                            AI Response
                                        </span>
                                        <label className="relative inline-flex items-center cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={conversationMode === 'ai'}
                                                onChange={(e) => handleModeChange(e.target.checked ? 'ai' : 'human')}
                                                className="sr-only peer"
                                            />
                                            <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                        </label>
                                        <span className={`text-sm font-medium ${conversationMode === 'ai' ? 'text-blue-600' : 'text-gray-500'}`}>
                                            {conversationMode === 'ai' ? 'ON' : 'OFF'}
                                        </span>
                                    </div>
                                    <AgentAssignmentDropdown
                                        canAssign={canAssign}
                                        selectedChat={selectedChat}
                                        agents={agents}
                                        onAgentAssigned={handleAgentAssigned}
                                    />
                                </div>
                                <div className="absolute -bottom-6 left-1/2 -translate-x-1/2 -translate-y-1/2 text-xs bg-accent p-1 rounded">Received on: {formatPhoneNumber(selectedChat.recipient || '')}</div>
                            </div>

                            {/* Messages Container */}
                            <div
                                ref={messagesContainerRef}
                                className="flex-1 overflow-y-auto p-4 space-y-4 bg-[#F8FAFC] dark:bg-[#1d293d]"
                            >
                                {isLoadingMessages ? (
                                    <div className="flex items-center justify-center h-full">
                                        <p className="text-gray-500">Loading messages...</p>
                                    </div>
                                ) : messages.length > 0 ? (
                                    <>
                                        {messageList}
                                        <div ref={messagesEndRef} />
                                    </>
                                ) : (
                                    <div className="flex items-center justify-center h-full">
                                        <p className="text-gray-500">No messages yet</p>
                                    </div>
                                )}
                            </div>

                            {/* Message Input */}
                            <form
                                onSubmit={handleSendMessage}
                                className="border-t border-gray-200 p-4 dark:border-gray-700 flex-shrink-0"
                            >
                                <div className="flex space-x-4">
                                    <input
                                        type="text"
                                        value={newMessage}
                                        onChange={handleInputChange}
                                        placeholder="Type a message..."
                                        disabled={isSending}
                                        className="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                        autoComplete="off"
                                    />
                                    <button
                                        type="submit"
                                        disabled={isSending || !newMessage.trim()}
                                        className="rounded-lg bg-blue-500 px-6 py-2 text-white hover:bg-blue-600 focus:outline-none flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-500 transition-colors"
                                    >
                                        {isSending ? 'Sending...' : 'Send'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    ) : (
                        <div className="hidden lg:flex w-2/3 items-center justify-center">
                            <div className="text-center">
                                <p className="text-gray-500 text-lg mb-2">Select a chat to start messaging</p>
                                <p className="text-gray-400 text-sm">Choose a conversation from the list to view messages</p>
                            </div>
                        </div>
                    )}
                </div>
            </ChatLayout>
        </AppLayout>
    );
}
