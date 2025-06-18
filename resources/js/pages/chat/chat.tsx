import ChatLayout from '@/layouts/chat/layout'
import { Head } from '@inertiajs/react'
import { useEffect, useState, useRef } from 'react'
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
}

export default function Chat({ chats: initialChats }: { chats: Chat[] }) {
    const [chats, setChats] = useState<Chat[]>(initialChats);
    const [selectedChat, setSelectedChat] = useState<Chat | null>(null)
    const [messages, setMessages] = useState<Message[]>([])
    const [newMessage, setNewMessage] = useState('')
    const [isSending, setIsSending] = useState(false)
    const [echo, setEcho] = useState<Echo<'pusher'> | null>(null);
    const route = useRoute();

    // Ref para auto-scroll a los mensajes
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const messagesContainerRef = useRef<HTMLDivElement>(null);

    // Auto-scroll al último mensaje
    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    // Scroll al cargar mensajes o recibir nuevos
    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    useEffect(() => {
        const echoInstance = new Echo<'pusher'>({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS: true
        });

        setEcho(echoInstance);

        // Clean Echo when unmount
        return () => {
            echoInstance.disconnect();
        };
    }, []);

    // Manejar nuevas conversaciones y mensajes
    useEffect(() => {
        if (!echo) return;

        // Suscribirse al canal de la organización para nuevas conversaciones
        const orgChannel = echo.channel('chat.organization.1');
        orgChannel.listen('.conversation.created', (e: { conversation: Chat }) => {
            setChats(prevChats => {
                const exists = prevChats.some(chat => chat.id === e.conversation.id);
                if (!exists) {
                    return [e.conversation, ...prevChats];
                }
                return prevChats;
            });
        });

        // Suscribirse a los canales de cada chat para mensajes nuevos
        const chatChannels = chats.map(chat => {
            const channel = echo.channel(`chat.conversation.${chat.id}`);
            channel.listen('.message.received', (e: { message: Message }) => {
                // Actualizar la lista de mensajes si es el chat seleccionado
                if (selectedChat?.id === chat.id) {
                    setMessages(prevMessages => [...prevMessages, e.message]);
                }

                // Actualizar la información del chat en la lista
                setChats(prevChats => {
                    return prevChats.map(prevChat => {
                        if (prevChat.id === chat.id) {
                            return {
                                ...prevChat,
                                lastMessage: e.message.content,
                                lastMessageTime: e.message.timestamp,
                                unreadCount: selectedChat?.id === chat.id
                                    ? prevChat.unreadCount
                                    : prevChat.unreadCount + 1
                            };
                        }
                        return prevChat;
                    });
                });
            });
            return `chat.conversation.${chat.id}`;
        });

        // Limpiar suscripciones
        return () => {
            echo.leave('chat.organization.1');
            chatChannels.forEach(channel => echo.leave(channel));
        };
    }, [echo, chats, selectedChat]);

    // Cargar mensajes cuando se selecciona un chat
    useEffect(() => {
        if (selectedChat) {
            axios.get(route('chats.messages', { conversation: selectedChat.id }))
                .then((response) => {
                    setMessages(response.data.messages);
                });
        }
    }, [selectedChat, route]);

    const handleSendMessage = async (e: React.FormEvent) => {
        e.preventDefault()
        if (!newMessage.trim() || !selectedChat || isSending) return

        setIsSending(true) // Deshabilitar el botón

        try {
            // Send message to backend
            const response = await axios.post(route('chats.messages.store', { conversation: selectedChat.id }), {
                content: newMessage,
                content_type: 'text',
            });

            // Add the new message to the messages array
            setMessages(prevMessages => [...prevMessages, response.data.message]);

            // Update the selected chat's last message
            setSelectedChat({
                ...selectedChat,
                lastMessage: newMessage,
                lastMessageTime: new Date().toISOString(),
            });

            // Clear the input
            setNewMessage('');
        } catch (error) {
            console.error('Failed to send message:', error);
            // Here you might want to show an error toast/notification
        } finally {
            setIsSending(false) // Rehabilitar el botón
        }
    }

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
                        {chats.map((chat) => (
                            <div
                                key={chat.id}
                                onClick={() => setSelectedChat(chat)}
                                className={`cursor-pointer border-b border-gray-200 p-4 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 ${
                                    selectedChat?.id === chat.id ? 'bg-gray-50 dark:bg-gray-800' : ''
                                }`}
                            >
                                <div className="flex items-center space-x-4">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500 text-white flex-shrink-0">
                                        {chat.avatar}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between">
                                            <h3 className="font-semibold">{chat.name}</h3>
                                            <span className="text-sm text-gray-500 flex-shrink-0">
                                                {format(new Date(chat.lastMessageTime), 'HH:mm')}
                                            </span>
                                        </div>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {chat.lastMessage}
                                        </p>
                                    </div>
                                    {chat.unreadCount > 0 && (
                                        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 text-xs text-white flex-shrink-0">
                                            {chat.unreadCount}
                                        </span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Chat Messages */}
                {selectedChat ? (
                    <div className="flex w-2/3 flex-col">
                        <div className="flex h-16 items-center border-b border-gray-200 px-4 dark:border-gray-700 flex-shrink-0">
                            <div className="flex items-center space-x-4">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white">
                                    {selectedChat.avatar}
                                </div>
                                <h3 className="font-semibold">{selectedChat.name}</h3>
                            </div>
                        </div>

                        <div
                            ref={messagesContainerRef}
                            className="flex-1 overflow-y-auto p-4 space-y-4"
                        >
                            {messages.map((message) => (
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
                                            />
                                        ) : (
                                            <p className="break-words whitespace-pre-wrap">{message.content}</p>
                                        )}
                                        <span className="mt-1 text-xs opacity-70 block">
                                            {format(new Date(message.timestamp), 'HH:mm')}
                                        </span>
                                    </div>
                                </div>
                            ))}
                            <div ref={messagesEndRef} />
                        </div>

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
                                />
                                <button
                                    type="submit"
                                    disabled={isSending}
                                    className="rounded-lg bg-blue-500 px-6 py-2 text-white hover:bg-blue-600 focus:outline-none flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-500"
                                >
                                    {isSending ? 'Sending...' : 'Send'}
                                </button>
                            </div>
                        </form>
                    </div>
                ) : (
                    <div className="flex w-2/3 items-center justify-center">
                        <p className="text-gray-500">Select a chat to start messaging</p>
                    </div>
                )}
            </div>
        </ChatLayout>
    )
}
