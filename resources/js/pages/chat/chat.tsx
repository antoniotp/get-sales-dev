import ChatLayout from '@/layouts/chat/layout';
import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState, useRef, useCallback, useMemo } from 'react';
import axios from 'axios';
import { format } from 'date-fns';
import { useRoute } from 'ziggy-js';
import Echo from 'laravel-echo';
import parsePhoneNumber from 'libphonenumber-js';
import type { BreadcrumbItem, Chatbot, PageProps, Organizations, Agent, Chat } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { ArrowLeft } from 'lucide-react';
import AgentAssignmentDropdown from '@/components/chat/AgentAssignmentDropdown';

interface Message {
    id: number;
    content: string;
    sender: string;
    senderId: number | string;
    timestamp: string;
    type: 'incoming' | 'outgoing';
    contentType: string;
    mediaUrl?: string;
    conversationId: number;
}

interface NewMessageEvent {
    message: Message;
}

interface NewConversationEvent {
    conversation: Chat;
}

interface ChannelInfo {
    phone_number: string;
}

const formatPhoneNumber = (phone: string): string => {
    if (!phone.startsWith('+')) {
        phone = '+' + phone;
    }
    if (phone.startsWith('+521')) {
        phone = phone.replace('+521', '+52');
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
    { chats: initialChats, channelInfo, organization, agents, canAssign }:
    {
        chats: Chat[];
        channelInfo: ChannelInfo;
        organization: Organizations;
        agents: Agent[];
        canAssign: boolean;
    }
) {
    const [chats, setChats] = useState<Chat[]>(initialChats);
    const [selectedChat, setSelectedChat] = useState<Chat | null>(null);
    const [messages, setMessages] = useState<Message[]>([]);
    const [newMessage, setNewMessage] = useState('');
    const [isSending, setIsSending] = useState(false);
    const [isLoadingMessages, setIsLoadingMessages] = useState(false);
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
                href: route('chatbots.index'),
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
                const exists = prevChats.some(chat => chat.id === e.conversation.id);
                if (!exists) {
                    const updatedChats = [e.conversation, ...prevChats];
                    return sortChatsByLastMessageTime(updatedChats);
                }
                return prevChats;
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
                            // Avoid duplicates
                            const exists = prevMessages.some(msg => msg.id === e.message.id);
                            if (!exists) {
                                return [...prevMessages, e.message];
                            }
                            return prevMessages;
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

    // Handle chat selection
    const handleChatSelect = useCallback((chat: Chat) => {
        if (selectedChat?.id === chat.id) return;

        setSelectedChat(chat);
        setConversationMode(chat.mode);
        setMessages([]); // Clear previous messages immediately
        loadChatMessages(chat.id);
    }, [selectedChat?.id, loadChatMessages]);

    // Handle message sending
    const handleSendMessage = useCallback(async (e: React.FormEvent) => {
        e.preventDefault();

        if (!newMessage.trim() || !selectedChat || isSending) return;

        const messageContent = newMessage.trim();
        setIsSending(true);
        setNewMessage(''); // Clear input immediately for better UX

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
                    className={`max-w-[70%] rounded-lg px-4 py-2 ${
                        message.type === 'outgoing'
                            ? 'bg-blue-500 text-white'
                            : 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white'
                    }`}
                >
                    {message.contentType === 'text' ? (
                        <p className="break-words whitespace-pre-wrap">{message.content}</p>
                    ) : message.contentType === 'image' && message.mediaUrl ? (
                        <img
                            src={message.mediaUrl}
                            alt="Message media"
                            className="max-w-full rounded"
                            loading="lazy"
                        />
                    ) : (
                        <p className="break-words whitespace-pre-wrap">{message.content}</p>
                    )}
                    <span className="mt-1 text-xs opacity-70 block text-right">
                        {format(new Date(message.timestamp), 'dd/MM/yyyy HH:mm')}
                    </span>
                </div>
            </div>
        ))
    ), [messages]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chat" />
            <ChatLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    {/* Chat List */}
                    <div className={`${selectedChat ? 'hidden lg:flex' : 'flex'} w-full lg:w-1/3 border-r border-gray-200 dark:border-gray-700 flex-col`}>
                        <div className="h-16 border-b border-gray-200 px-4 py-3 dark:border-gray-700 flex-shrink-0">
                            <h2 className="text-lg font-semibold">Chats</h2>
                            <p className="text-sm text-gray-900 mt-1">
                                Messages Received on : <strong>{formatPhoneNumber(channelInfo.phone_number)}</strong>
                            </p>
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
                            <div className="flex h-16 items-center justify-between border-b border-gray-200 px-1 md:px-4 dark:border-gray-700 flex-shrink-0">
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
                            </div>

                            {/* Messages Container */}
                            <div
                                ref={messagesContainerRef}
                                className="flex-1 overflow-y-auto p-4 space-y-4"
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