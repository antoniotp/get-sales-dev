import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Agent, Chat } from '@/types';
import axios from 'axios';
import React, { useCallback } from 'react';
import { toast } from 'sonner';
import { useRoute } from 'ziggy-js';

interface Props {
    canAssign: boolean;
    selectedChat: Chat;
    agents: Agent[];
    onAgentAssigned: (updatedChat: Chat) => void;
}

const AgentAssignmentDropdown: React.FC<Props> = ({ canAssign, selectedChat, agents, onAgentAssigned }) => {
    const route = useRoute();

    const handleAssignAgent = useCallback(
        async (newAgentIdValue: string) => {
            const newAgentId = newAgentIdValue !== 'unassigned' ? parseInt(newAgentIdValue, 10) : null;

            try {
                const response = await axios.put(route('chats.assignment.update', { conversation: selectedChat.id }), { user_id: newAgentId });

                if (response.data.success) {
                    const { assigned_user_id, assigned_user_name } = response.data;
                    const updatedChat = { ...selectedChat, assigned_user_id, assigned_user_name };
                    onAgentAssigned(updatedChat);
                    toast.success('Agent assigned successfully.');
                }
            } catch (error) {
                console.error('Failed to assign agent:', error);
                toast.error('Failed to assign agent.');
            }
        },
        [selectedChat, route, onAgentAssigned],
    );

    if (!selectedChat) {
        return null;
    }

    return (
        <div className="text-sm">
            {canAssign ? (
                <Select value={selectedChat.assigned_user_id?.toString() || 'unassigned'} onValueChange={handleAssignAgent}>
                    <SelectTrigger className="w-full py-0 h-7 mt-1">
                        <SelectValue placeholder="Unassigned" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="unassigned">Unassigned</SelectItem>
                        {agents.map((agent) => (
                            <SelectItem key={agent.id} value={agent.id.toString()}>
                                {agent.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ) : (
                <span>{selectedChat.assigned_user_name || 'Unassigned'}</span>
            )}
        </div>
    );
};

export default AgentAssignmentDropdown;
