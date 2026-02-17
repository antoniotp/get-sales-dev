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
        message_templates: esMessageTemplates,
        appointments: esAppointments,
      },
      en: {
        navigation: enNavigation,
        home: enHome,
        login: enLogin,
        register: enRegister,
        chatbot: enChatbot,
        contact: enContact,
        organization: enOrganization,
        message_templates: enMessageTemplates,
        appointments: enAppointments,
      },
    },
    lng: 'es',
    fallbackLng: 'en',
    interpolation: {
      escapeValue: false,
    },
  });

export default i18n;
