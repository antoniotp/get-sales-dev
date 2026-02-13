import 'i18next';
import navigation from './locales/es/navigation.json';
import home from './locales/es/home.json';
import login from './locales/es/login.json';
import register from './locales/es/register.json';
import chatbot from './locales/es/chatbot.json';
import contact from './locales/es/contact.json';

declare module 'i18next' {
  interface CustomTypeOptions {
    resources: {
      navigation: typeof navigation;
      home: typeof home;
      login: typeof login;
      register: typeof register;
      chatbot: typeof chatbot;
      contact: typeof contact;
    };
  }
}