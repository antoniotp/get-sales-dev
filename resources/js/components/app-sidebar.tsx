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
import {
    Layers, /*LayoutGrid, */ MessagesSquare, Settings, BotMessageSquare, Users, Unplug, CalendarDays
} from 'lucide-react';
import { OrgSwitcher } from '@/components/org-switcher';
import {PageProps} from '@/types';
import { AgentSwitcher } from '@/components/agent-switcher';

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { props } = usePage<PageProps>();
    const chatbot = props.chatbot as { id: number };
    const user = props.auth.user;
    const userLevel = user?.level || 0;

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
            title: 'Organization Settings',
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

    const isChatbotContext = chatbot?.id > 0;

    const chatbotNavItems: NavItems[] = [
        {
            title: 'Agent Switcher',
            href: '#',
            component: AgentSwitcher,
        },
        {
            title: 'Chats',
            href: isChatbotContext ? route('chats', { chatbot: chatbot?.id || 0 }) : "disabled",
            icon: MessagesSquare,
        },
        {
            title: 'Agenda',
            href: isChatbotContext ? route('appointments.index', { chatbot: chatbot?.id || 0 }) : "disabled",
            icon: CalendarDays,
        },
        {
            title: 'Template Messages',
            href: isChatbotContext ? route('message-templates.index', { chatbot: chatbot?.id || 0 }) : "disabled",
            icon: Layers,
        },
        {
            title: 'Integrations',
            href: isChatbotContext ? route('chatbots.integrations', { chatbot: chatbot?.id || 0 }) : "disabled",
            icon: Unplug,
        },
        {
            title: 'Agent Configuration',
            href: isChatbotContext && userLevel > 40 ? route('chatbots.edit', { chatbot: chatbot?.id || 0 }) : "disabled",
            icon: Settings,
        },
        /*{
            title: 'Contacts',
            href: route('contacts.index'),
            icon: Users,
        },*/
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader className="relative border-b">
                <OrgSwitcher />
            </SidebarHeader>

            <SidebarContent className="mt-3">
                <NavMain items={chatbotNavItems} groupLabel='' />
                <NavMain items={mainNavItems} groupLabel='Organization' />

            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
