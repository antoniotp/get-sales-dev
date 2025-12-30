import 'i18next';
import navigation from './locales/es/navigation.json';
import home from './locales/es/home.json';

declare module 'i18next' {
  interface CustomTypeOptions {
    resources: {
      navigation: typeof navigation;
      home: typeof home;
    };
  }
}