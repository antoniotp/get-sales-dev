import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;
    
    interface Window {
    grecaptcha: {
      execute: (
        siteKey: string,
        options: { action: string }
      ) => Promise<string>;
    };
  }
}
