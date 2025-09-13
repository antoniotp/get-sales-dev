import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';
import { PageProps } from '@/types';
import { useChatbots } from '@/context/ChatbotProvider';
import { usePage } from '@inertiajs/react';
import { BotMessageSquare, ChevronsUpDown } from 'lucide-react';
import * as React from 'react';

export function AgentSwitcher() {
    const { chatbot } = usePage<PageProps>().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const { switcherChatbots, isLoading } = useChatbots();

    return (
        <SidebarMenuItem key='agent-switcher'>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <SidebarMenuButton size="lg" className="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent p-0 h-8 pr-0 gap-0 border-1">
                        <div className="flex aspect-square size-8 items-center justify-center rounded-lg">
                            <BotMessageSquare className="size-4" />
                        </div>
                        <div className="grid flex-1 text-left text-sm overflow-hidden whitespace-nowrap">
                            {chatbot.name}
                        </div>
                        <ChevronsUpDown className="ml-auto" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    align="start"
                    side={isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'}
                >
                    <DropdownMenuLabel className="text-muted-foreground text-xs">
                        Agents
                    </DropdownMenuLabel>
                    {isLoading ? (
                        <DropdownMenuItem disabled>Cargando...</DropdownMenuItem>
                    ) : (
                        switcherChatbots && switcherChatbots.map((chatbotOption) => (
                            <DropdownMenuItem
                                key={chatbotOption.id}
                                /*onClick={() => handleSwitch(chatbotOption)}*/
                                className="gap-2 p-2"
                            >

                                {chatbotOption.name}
                            </DropdownMenuItem>
                        ))
                    )}
                    <DropdownMenuSeparator />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    );
}
