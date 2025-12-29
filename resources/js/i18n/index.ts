import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

import esNavigation from './locales/es/navigation.json';
import esHome from './locales/es/home.json';
import enNavigation from './locales/en/navigation.json';
import enHome from './locales/en/home.json';

i18n
  .use(initReactI18next)
  .init({
    resources: {
      es: {
        navigation: esNavigation,
        home: esHome,
      },
      en: {
        navigation: enNavigation,
        home: enHome,
      },
    },
    lng: 'es',
    fallbackLng: 'en',
    interpolation: {
      escapeValue: false,
    },
  });

export default i18n;
