import React, { useCallback } from 'react';
import axios from 'axios';
import { useRoute } from 'ziggy-js';
import type { Agent, Chat } from '@/types';

interface Props {
    canAssign: boolean;
    selectedChat: Chat;
    agents: Agent[];
    onAgentAssigned: (updatedChat: Chat) => void;
}

const AgentAssignmentDropdown: React.FC<Props> = ({ canAssign, selectedChat, agents, onAgentAssigned }) => {
    const route = useRoute();

    const handleAssignAgent = useCallback(async (e: React.ChangeEvent<HTMLSelectElement>) => {
        const newAgentId = e.target.value ? parseInt(e.target.value, 10) : null;

        try {
            const response = await axios.put(
                route('chats.assignment.update', { conversation: selectedChat.id }),
                { user_id: newAgentId }
            );

            if (response.data.success) {
                const { assigned_user_id, assigned_user_name } = response.data;
                const updatedChat = { ...selectedChat, assigned_user_id, assigned_user_name };
                onAgentAssigned(updatedChat);
            }
        } catch (error) {
            console.error('Failed to assign agent:', error);
            // TODO: show error notification
        }
    }, [selectedChat, route, onAgentAssigned]);

    if (!selectedChat) {
        return null;
    }

    return (
        <div className="text-sm">
            {canAssign ? (
                <select
                    value={selectedChat.assigned_user_id || ''}
                    onChange={handleAssignAgent}
                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600"
                >
                    <option value="">Unassigned</option>
                    {agents.map(agent => (
                        <option key={agent.id} value={agent.id}>
                            {agent.name}
                        </option>
                    ))}
                </select>
            ) : (
                <span>{selectedChat.assigned_user_name || 'Unassigned'}</span>
            )}
        </div>
    );
};

export default AgentAssignmentDropdown;
