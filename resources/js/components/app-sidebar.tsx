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
import { PageProps } from '@/types';
import { AgentSwitcher } from '@/components/agent-switcher';
import { useTranslation } from 'react-i18next';

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { props } = usePage<PageProps>();
    const { t } = useTranslation('sidebar');
    const chatbot = props.chatbot as { id: number };
    const user = props.auth.user;
    const userLevel = user?.level || 0;

    const mainNavItems: NavItems[] = [
        {
            title: t('side.agents'),
            href: route('chatbots.index'),
            icon: BotMessageSquare,
        },{
            title: t('side.all_contacts'),
            href: route('contacts.index'),
            icon: Users,
        },{
            title: t('side.organization_settings'),
            icon: Settings,
            href: '#',
            items: [
                {
                    title: t('side.general'),
                    href: route('organizations.edit'),
                    isActive: false,
                },
                {
                    title: t('side.members'),
                    href: route('organizations.members.index'),
                    isActive: false,
                },
            ],
        },
    ];

    const isChatbotContext = chatbot?.id > 0;

    const chatbotNavItems: NavItems[] = [
        {
            title: t('side.agent_switcher'),
            href: '#',
            component: AgentSwitcher,
        },
        {
            title: t('side.chats'),
            href: isChatbotContext ? route('chats', { chatbot: chatbot?.id || 0 }) : "disabled",
            icon: MessagesSquare,
        },
        {
            title: t('side.agenda'),
            href: isChatbotContext ? route('appointments.index', { chatbot: chatbot?.id || 0 }) : "disabled",
            icon: CalendarDays,
        },
        {
            title: t('side.template_messages'),
            href: isChatbotContext ? route('message-templates.index', { chatbot: chatbot?.id || 0 }) : "disabled",
            icon: Layers,
        },
        {
            title: t('side.integrations'),
            href: isChatbotContext ? route('chatbots.integrations', { chatbot: chatbot?.id || 0 }) : "disabled",
            icon: Unplug,
        },
        {
            title: t('side.agent_configuration'),
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
                <NavMain items={mainNavItems} groupLabel={t('side.organization')} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}