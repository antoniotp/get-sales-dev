import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

import esNavigation from './locales/es/navigation.json';
import esHome from './locales/es/home.json';
import enNavigation from './locales/en/navigation.json';
import enHome from './locales/en/home.json';
import esLogin from './locales/es/login.json';
import enLogin from './locales/en/login.json';
import esRegister from './locales/es/register.json';
import enRegister from './locales/en/register.json';
import esChatbot from './locales/es/chatbot.json';
import enChatbot from './locales/en/chatbot.json';
import esContact from './locales/es/contact.json';
import enContact from './locales/en/contact.json';
import esOrganization from './locales/es/organization.json';
import enOrganization from './locales/en/organization.json';
import esMessageTemplates from './locales/es/message_templates.json';
import enMessageTemplates from './locales/en/message_templates.json';
import esAppointments from './locales/es/appointments.json';
import enAppointments from './locales/en/appointments.json';
import esChat from './locales/es/chat.json';
import enChat from './locales/en/chat.json';
import esGeneral from './locales/es/general.json';
import enGeneral from './locales/en/general.json';
import esSideBar from './locales/es/sidebar.json';
import enSideBar from './locales/en/sidebar.json';
import esSettings from './locales/es/settings.json';
import enSettings from './locales/en/settings.json';

i18n
    .use(initReactI18next)
    .init({
        resources: {
            es: {
                navigation: esNavigation,
                home: esHome,
                login: esLogin,
                register: esRegister,
                chatbot: esChatbot,
                contact: esContact,
                organization: esOrganization,
                messageTemplates: esMessageTemplates,
                appointments: esAppointments,
                chat: esChat,
                general: esGeneral,
                sidebar: esSideBar,
                settings: esSettings,
            },
            en: {
                navigation: enNavigation,
                home: enHome,
                login: enLogin,
                register: enRegister,
                chatbot: enChatbot,
                contact: enContact,
                organization: enOrganization,
                messageTemplates: enMessageTemplates,
                appointments: enAppointments,
                chat: enChat,
                general: enGeneral,
                sidebar: enSideBar,
                settings: enSettings,
            },
        },

        ns: [
            'navigation',
            'home',
            'login',
            'register',
            'chatbot',
            'contact',
            'organization',
            'messageTemplates',
            'appointments',
            'chat',
            'general',
            'sidebar',
            'settings'
        ],

        defaultNS: 'navigation',
        fallbackNS: 'general',

        lng: 'es',
        fallbackLng: 'en',

        interpolation: {
            escapeValue: false,
        },

        react: {
            useSuspense: false,
        },
    });

export default i18n;