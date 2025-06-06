import ChatLayout from '@/layouts/chat/layout'
import { Head } from '@inertiajs/react'
import { useEffect, useState } from 'react'
import axios from 'axios'
import { format } from 'date-fns'
import { useRoute } from 'ziggy-js'

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
}

export default function Chat({ chats: initialChats }: { chats: Chat[] }) {
    const [selectedChat, setSelectedChat] = useState<Chat | null>(null)
    const [messages, setMessages] = useState<Message[]>([])
    const [newMessage, setNewMessage] = useState('')
    const route = useRoute();

    useEffect(() => {
        if (selectedChat) {
            axios.get(route('chats.messages', selectedChat.id )).then((response) => {
                setMessages(response.data.messages)
            })
        }
    }, [selectedChat])

    const handleSendMessage = (e: React.FormEvent) => {
        e.preventDefault()
        if (!newMessage.trim() || !selectedChat) return

        const message: Message = {
            id: messages.length + 1,
            content: newMessage,
            sender: 'You',
            senderId: 'me',
            timestamp: new Date().toISOString(),
        }

        setMessages([...messages, message])
        setNewMessage('')
    }

    return (
        <ChatLayout>
            <Head title="Chat" />
            <div className="flex h-full w-full">
                {/* Chat List */}
                <div className="w-1/3 border-r border-gray-200 dark:border-gray-700">
                    <div className="h-16 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                        <h2 className="text-lg font-semibold">Chats</h2>
                    </div>
                    <div className="h-[calc(100%-4rem)] overflow-y-auto">
                        {initialChats.map((chat) => (
                            <div
                                key={chat.id}
                                onClick={() => setSelectedChat(chat)}
                                className={`cursor-pointer border-b border-gray-200 p-4 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 ${
                                    selectedChat?.id === chat.id ? 'bg-gray-50 dark:bg-gray-800' : ''
                                }`}
                            >
                                <div className="flex items-center space-x-4">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500 text-white">
                                        {chat.avatar}
                                    </div>

                                    <div className="flex-1">
                                        <div className="flex items-center justify-between">
                                            <h3 className="font-semibold">{chat.name}</h3>
                                            <span className="text-sm text-gray-500">
                        {format(new Date(chat.lastMessageTime), 'HH:mm')}
                      </span>
                                        </div>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">{chat.lastMessage}</p>
                                    </div>
                                    {chat.unreadCount > 0 && (
                                        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 text-xs text-white">
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
                        <div className="flex h-16 items-center border-b border-gray-200 px-4 dark:border-gray-700">
                            <div className="flex items-center space-x-4">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white">
                                    {selectedChat.avatar}
                                </div>

                                <h3 className="font-semibold">{selectedChat.name}</h3>
                            </div>
                        </div>
                        <div className="flex-1 overflow-y-auto p-4">
                            {messages.map((message) => (
                                <div
                                    key={message.id}
                                    className={`mb-4 flex ${message.senderId === 'me' ? 'justify-end' : 'justify-start'}`}
                                >
                                    <div
                                        className={`max-w-[70%] rounded-lg px-4 py-2 ${
                                            message.senderId === 'me'
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white'
                                        }`}
                                    >
                                        <p>{message.content}</p>
                                        <span className="mt-1 text-xs opacity-70">
                      {format(new Date(message.timestamp), 'HH:mm')}
                    </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <form onSubmit={handleSendMessage} className="border-t border-gray-200 p-4 dark:border-gray-700">
                            <div className="flex space-x-4">
                                <input
                                    type="text"
                                    value={newMessage}
                                    onChange={(e) => setNewMessage(e.target.value)}
                                    placeholder="Type a message..."
                                    className="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800"
                                />
                                <button
                                    type="submit"
                                    className="rounded-lg bg-blue-500 px-6 py-2 text-white hover:bg-blue-600 focus:outline-none"
                                >
                                    Send
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
