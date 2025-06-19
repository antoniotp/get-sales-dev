import ChatLayout from '@/layouts/chat/layout'
import { Head } from '@inertiajs/react'
import { useEffect, useState, useRef, useCallback, useMemo } from 'react'
import axios from 'axios'
import { format } from 'date-fns'
import { useRoute } from 'ziggy-js'
import Echo from 'laravel-echo'

interface Chat {
    id: number
    name: string
    avatar: string
    lastMessage: string
    lastMessageTime: string
    unreadCount: number
}

interface Message {
    id: number
    content: string
    sender: string
    senderId: number | string
    timestamp: string
    type: 'incoming' | 'outgoing'
    contentType: string
    mediaUrl?: string
    conversationId: number
}

interface NewMessageEvent {
    message: Message
}

interface NewConversationEvent {
    conversation: Chat
}

export default function Chat({ chats: initialChats }: { chats: Chat[] }) {
    const [chats, setChats] = useState<Chat[]>(initialChats)
    const [selectedChat, setSelectedChat] = useState<Chat | null>(null)
    const [messages, setMessages] = useState<Message[]>([])
    const [newMessage, setNewMessage] = useState('')
    const [isSending, setIsSending] = useState(false)
    const [isLoadingMessages, setIsLoadingMessages] = useState(false)

    const route = useRoute()
    const messagesEndRef = useRef<HTMLDivElement>(null)
    const messagesContainerRef = useRef<HTMLDivElement>(null)
    const echoRef = useRef<Echo<'pusher'> | null>(null)
    const activeChannelsRef = useRef<Set<string>>(new Set())
    const selectedChatRef = useRef<Chat | null>(null)

    // Keep selectedChatRef in sync with selectedChat
    useEffect(() => {
        selectedChatRef.current = selectedChat
    }, [selectedChat])

    // Optimized scroll to bottom
    const scrollToBottom = useCallback(() => {
        if (messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior: 'smooth' })
        }
    }, [])

    // Initialize Echo connection
    useEffect(() => {
        const initializeEcho = () => {
            if (echoRef.current) return echoRef.current

            const echoInstance = new Echo<'pusher'>({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
                forceTLS: true
            })

            echoRef.current = echoInstance
            return echoInstance
        }

        const echo = initializeEcho()

        return () => {
            // Cleanup all channels
            activeChannelsRef.current.forEach(channelName => {
                echo.leave(channelName)
            })
            activeChannelsRef.current.clear()

            if (echoRef.current) {
                echoRef.current.disconnect()
                echoRef.current = null
            }
        }
    }, [])

    // Setup organization channel for new conversations
    useEffect(() => {
        if (!echoRef.current) return

        const channelName = 'chat.organization.1'

        if (activeChannelsRef.current.has(channelName)) return

        const orgChannel = echoRef.current.channel(channelName)

        orgChannel.listen('.conversation.created', (e: NewConversationEvent) => {
            setChats(prevChats => {
                const exists = prevChats.some(chat => chat.id === e.conversation.id)
                if (!exists) {
                    return [e.conversation, ...prevChats]
                }
                return prevChats
            })
        })

        activeChannelsRef.current.add(channelName)

        return () => {
            if (echoRef.current && activeChannelsRef.current.has(channelName)) {
                echoRef.current.leave(channelName)
                activeChannelsRef.current.delete(channelName)
            }
        }
    }, [])

    // Setup chat channels for messages - optimized to avoid unnecessary re-subscriptions
    useEffect(() => {
        if (!echoRef.current || chats.length === 0) return

        const echo = echoRef.current
        const newChannels: string[] = []

        // Subscribe to new chat channels
        chats.forEach(chat => {
            const channelName = `chat.conversation.${chat.id}`

            if (!activeChannelsRef.current.has(channelName)) {
                const channel = echo.channel(channelName)

                channel.listen('.message.received', (e: NewMessageEvent) => {
                    // Update messages if this is the selected chat - using ref to get the current value
                    const currentSelectedChat = selectedChatRef.current
                    if (currentSelectedChat?.id === e.message.conversationId) {
                        setMessages(prevMessages => {
                            // Avoid duplicates
                            const exists = prevMessages.some(msg => msg.id === e.message.id)
                            if (!exists) {
                                return [...prevMessages, e.message]
                            }
                            return prevMessages
                        })
                    }

                    // Update chat list - this should always happen regardless of selected chat
                    setChats(prevChats => {
                        return prevChats.map(prevChat => {
                            if (prevChat.id === e.message.conversationId) {
                                const isCurrentChat = currentSelectedChat?.id === e.message.conversationId

                                return {
                                    ...prevChat,
                                    lastMessage: e.message.content,
                                    lastMessageTime: e.message.timestamp,
                                    unreadCount: isCurrentChat
                                        ? prevChat.unreadCount
                                        : prevChat.unreadCount + 1
                                }
                            }
                            return prevChat
                        })
                    })
                })

                activeChannelsRef.current.add(channelName)
                newChannels.push(channelName)
            }
        })

        // Cleanup function for this effect
        return () => {
            newChannels.forEach(channelName => {
                if (echo && activeChannelsRef.current.has(channelName)) {
                    echo.leave(channelName)
                    activeChannelsRef.current.delete(channelName)
                }
            })
        }
    }, [chats.map(chat => chat.id).join(',')])

    // Load messages when chat is selected
    const loadChatMessages = useCallback(async (chatId: number) => {
        setIsLoadingMessages(true)
        try {
            const response = await axios.get(route('chats.messages', { conversation: chatId }))
            setMessages(response.data.messages || [])

            // Mark chat as read
            setChats(prevChats =>
                prevChats.map(chat =>
                    chat.id === chatId
                        ? { ...chat, unreadCount: 0 }
                        : chat
                )
            )

            // Scroll to the bottom after messages load
            setTimeout(scrollToBottom, 100)
        } catch (error) {
            console.error('Failed to load messages:', error)
            setMessages([])
        } finally {
            setIsLoadingMessages(false)
        }
    }, [route, scrollToBottom])

    // Handle chat selection
    const handleChatSelect = useCallback((chat: Chat) => {
        if (selectedChat?.id === chat.id) return

        setSelectedChat(chat)
        setMessages([]) // Clear previous messages immediately
        loadChatMessages(chat.id)
    }, [selectedChat?.id, loadChatMessages])

    // Handle message sending
    const handleSendMessage = useCallback(async (e: React.FormEvent) => {
        e.preventDefault()

        if (!newMessage.trim() || !selectedChat || isSending) return

        const messageContent = newMessage.trim()
        setIsSending(true)
        setNewMessage('') // Clear input immediately for better UX

        try {
            const response = await axios.post(
                route('chats.messages.store', { conversation: selectedChat.id }),
                {
                    content: messageContent,
                    content_type: 'text',
                }
            )

            // Add message to current conversation
            setMessages(prevMessages => [...prevMessages, response.data.message])

            // Update chat list
            setChats(prevChats =>
                prevChats.map(chat =>
                    chat.id === selectedChat.id
                        ? {
                            ...chat,
                            lastMessage: messageContent,
                            lastMessageTime: new Date().toISOString(),
                        }
                        : chat
                )
            )

        } catch (error) {
            console.error('Failed to send message:', error)
            // Restore message on error
            setNewMessage(messageContent)
            // TODO: show an error notification
        } finally {
            setIsSending(false)
        }
    }, [newMessage, selectedChat, isSending, route])

    // Auto-scroll when new messages arrive
    useEffect(() => {
        if (messages.length > 0) {
            scrollToBottom()
        }
    }, [messages.length, scrollToBottom])

    // Memoized chat list to prevent unnecessary re-renders
    const chatList = useMemo(() => (
        chats.map((chat) => (
            <div
                key={chat.id}
                onClick={() => handleChatSelect(chat)}
                className={`cursor-pointer border-b border-gray-200 p-4 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors ${
                    selectedChat?.id === chat.id ? 'bg-gray-50 dark:bg-gray-800' : ''
                }`}
            >
                <div className="flex items-center space-x-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500 text-white flex-shrink-0">
                        {chat.avatar}
                    </div>
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between">
                            <h3 className="font-semibold truncate">{chat.name}</h3>
                            <span className="text-sm text-gray-500 flex-shrink-0">
                                {format(new Date(chat.lastMessageTime), 'HH:mm')}
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
    ), [chats, selectedChat?.id, handleChatSelect])

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
                    <span className="mt-1 text-xs opacity-70 block">
                        {format(new Date(message.timestamp), 'HH:mm')}
                    </span>
                </div>
            </div>
        ))
    ), [messages])

    return (
        <ChatLayout>
            <Head title="Chat" />
            <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                {/* Chat List */}
                <div className="w-1/3 border-r border-gray-200 dark:border-gray-700 flex flex-col">
                    <div className="h-16 border-b border-gray-200 px-4 py-3 dark:border-gray-700 flex-shrink-0">
                        <h2 className="text-lg font-semibold">Chats</h2>
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
                    <div className="flex w-2/3 flex-col">
                        {/* Chat Header */}
                        <div className="flex h-16 items-center border-b border-gray-200 px-4 dark:border-gray-700 flex-shrink-0">
                            <div className="flex items-center space-x-4">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white">
                                    {selectedChat.avatar}
                                </div>
                                <h3 className="font-semibold">{selectedChat.name}</h3>
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
                                    onChange={(e) => setNewMessage(e.target.value)}
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
                    <div className="flex w-2/3 items-center justify-center">
                        <div className="text-center">
                            <p className="text-gray-500 text-lg mb-2">Select a chat to start messaging</p>
                            <p className="text-gray-400 text-sm">Choose a conversation from the list to view messages</p>
                        </div>
                    </div>
                )}
            </div>
        </ChatLayout>
    )
}
