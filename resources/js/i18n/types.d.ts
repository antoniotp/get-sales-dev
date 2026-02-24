import 'i18next';
import navigation from './locales/es/navigation.json';
import home from './locales/es/home.json';
import login from './locales/es/login.json';
import register from './locales/es/register.json';
import chatbot from './locales/es/chatbot.json';
import contact from './locales/es/contact.json';
import organization from './locales/es/organization.json';
import messageTemplates from './locales/es/message_templates.json';
import appointments from './locales/es/appointments.json';
import chat from './locales/es/chat.json';
import general from './locales/es/general.json';
import sideBar from './locales/es/sidebar.json';
import settings from './locales/es/settings.json';

declare module 'i18next' {
  interface CustomTypeOptions {
    resources: {
      navigation: typeof navigation;
      home: typeof home;
      login: typeof login;
      register: typeof register;
      chatbot: typeof chatbot;
      contact: typeof contact;
      organization: typeof organization;
      messageTemplates: typeof messageTemplates;
      appointments: typeof appointments;
      chat: typeof chat;
      general: typeof general;
      sidebar: typeof sideBar;
      settings: typeof settings
    };
  }
}
