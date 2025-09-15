import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { type NavItems, NavItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { Layers, /*LayoutGrid, */ MessagesSquare, Settings, BotMessageSquare, Users, Unplug } from 'lucide-react';
import { OrgSwitcher } from '@/components/org-switcher';
import {PageProps} from '@/types';
import { AgentSwitcher } from '@/components/agent-switcher';

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { props } = usePage<PageProps>()
    const chatbot = props.chatbot as { id: number };

    const mainNavItems: NavItems[] = [
        {
            title: 'Agents',
            href: route('chatbots.index'),
            icon: BotMessageSquare,
        },{
            title: 'All Contacts',
            href: route('contacts.index'),
            icon: Users,
        },{
            title: 'Settings',
            icon: Settings,
            href: '#',
            items: [
                {
                    title: 'General',
                    href: route('organizations.edit'),
                    isActive: false,
                },
                {
                    title: 'Members',
                    href: route('organizations.members.index'),
                    isActive: false,
                },
            ],
        },
    ];

    const chatbotNavItems: NavItems[] = [
        {
            title: 'Agent Switcher',
            href: '#',
            component: AgentSwitcher,
        },
        {
            title: 'Chats',
            href: route('chats', { chatbot: chatbot?.id || 0 }),
            icon: MessagesSquare,
        },
        {
            title: 'Template Messages',
            href: route('message-templates.index', { chatbot: chatbot?.id || 0 }),
            icon: Layers,
        },
        {
            title: 'Integrations',
            href: route('chatbots.integrations', { chatbot: chatbot?.id || 0 }),
            icon: Unplug,
        },
        {
            title: 'Settings',
            href: route('chatbots.edit', { chatbot: chatbot?.id || 0 }),
            icon: Settings,
        },
        /*{
            title: 'Contacts',
            href: route('contacts.index'),
            icon: Users,
        },*/
    ];

    const isChatbotContext = chatbot?.id > 0;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader className="relative border-b">
                <OrgSwitcher />
            </SidebarHeader>

            <SidebarContent className="mt-3">
                {isChatbotContext && (
                    <>
                    <NavMain items={chatbotNavItems} groupLabel='' />
                    </>
                )}
                <NavMain items={mainNavItems} groupLabel='Organization' />

            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
