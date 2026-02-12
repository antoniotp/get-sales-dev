import 'i18next';
import navigation from './locales/es/navigation.json';
import home from './locales/es/home.json';
import login from './locales/es/login.json';
import chatbot from './locales/es/chatbot.json';

declare module 'i18next' {
  interface CustomTypeOptions {
    resources: {
      navigation: typeof navigation;
      home: typeof home;
      login: typeof login;
      chatbot: typeof chatbot;
    };
  }
}