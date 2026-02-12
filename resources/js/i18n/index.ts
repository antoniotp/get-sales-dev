import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

import esNavigation from './locales/es/navigation.json';
import esHome from './locales/es/home.json';
import enNavigation from './locales/en/navigation.json';
import enHome from './locales/en/home.json';
import esLogin from './locales/es/login.json';
import enLogin from './locales/en/login.json';
import esChatbot from './locales/es/chatbot.json';
import enChatbot from './locales/en/chatbot.json';

i18n
  .use(initReactI18next)
  .init({
    resources: {
      es: {
        navigation: esNavigation,
        home: esHome,
        login: esLogin,
        chatbot: esChatbot,
      },
      en: {
        navigation: enNavigation,
        home: enHome,
        login: enLogin,
        chatbot: enChatbot,
      },
    },
    lng: 'es',
    fallbackLng: 'en',
    interpolation: {
      escapeValue: false,
    },
  });

export default i18n;
