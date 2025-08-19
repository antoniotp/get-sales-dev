import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import type { Chatbot } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

// Interfaces for Facebook SDK responses
interface FacebookAuthResponse {
    code: string;
    granted_scopes: string;
}

interface FacebookLoginResponse {
    authResponse?: FacebookAuthResponse;
    status: string;
}

interface FacebookSDK {
    init(params: {
        appId: string;
        autoLogAppEvents: boolean;
        xfbml: boolean;
        version: string;
    }): void;
    login(
        callback: (response: FacebookLoginResponse) => void,
        options?: {
            config_id?: string;
            response_type?: string;
            override_default_response_type?: boolean;
            extras?: {
                setup?: Record<string, unknown>;
                feature?: string;
                sessionInfoVersion: string;
            };
        }
    ): void;
}

declare global {
    interface Window {
        fbAsyncInit: () => void;
        FB: FacebookSDK;
    }
}

interface Props {
    onSuccess: () => void;
}

interface PageProps {
    chatbot: Chatbot;
    [key: string]: never | Chatbot;
}

axios.defaults.withCredentials = true

export function FacebookEmbeddedSignUpBtn({ onSuccess }: Props) {
    const { chatbot } = usePage<PageProps>().props as { chatbot: Chatbot };
    const [isLoading, setIsLoading] = useState(false);
    const [authCode, setAuthCode] = useState<string | null>(null);
    const [phoneNumberId, setPhoneNumberId] = useState<string | null>(null);

    useEffect(() => {
        window.fbAsyncInit = () => {
            window.FB.init({
                appId: import.meta.env.VITE_FACEBOOK_APP_ID,
                autoLogAppEvents: true,
                xfbml: true,
                version: 'v23.0'
            });
        };

        const handleMessage = (event: MessageEvent) => {
            if (!event.origin.endsWith('facebook.com')) return;
            try {
                const data = JSON.parse(event.data);
                if (data.type === 'WA_EMBEDDED_SIGNUP' && data.data.phone_number_id) {
                    console.log('message event: ', data); // remove after testing
                    setPhoneNumberId(data.data.phone_number_id);
                }
            } catch {
                console.log('message event: ', event.data); // remove after testing
            }
        };

        window.addEventListener('message', handleMessage);

        const script = document.createElement('script');
        script.src = 'https://connect.facebook.net/en_US/sdk.js';
        script.async = true;
        script.defer = true;
        script.crossOrigin = 'anonymous';
        document.body.appendChild(script);

        return () => {
            document.body.removeChild(script);
            window.removeEventListener('message', handleMessage);
        };
    }, []);

    const handleBackendCallback = async (code: string, pId: string | null) => {
        setIsLoading(true);
        try {
            const { data } = await axios.post(
                route("facebook.callback", chatbot.id),
                { code, phone_number_id: pId } // pId can be null
            );

            toast.success("Account connected successfully!");
            console.log('Backend response:', data);
            onSuccess();
        } catch (error) {
            console.error('Error sending code to backend:', error);
            const errorMessage = axios.isAxiosError(error) && error.response?.data?.message
                ? error.response.data.message
                : 'Failed to connect account.';
            toast.error(errorMessage);
        } finally {
            setIsLoading(false);
            setAuthCode(null);
            setPhoneNumberId(null);
        }
    };

    useEffect(() => {
        // If we get an auth code, we are ready to proceed.
        if (authCode) {
            // We wait for a short moment to give the 'message' event a chance to be processed.
            const timer = setTimeout(() => {
                handleBackendCallback(authCode, phoneNumberId);
            }, 500);

            // If the component unmounts or dependencies change, we clear the timer.
            return () => clearTimeout(timer);
        }
    }, [authCode, phoneNumberId]);

    const handleClick = () => {
        if (!window.FB) {
            toast.error('Facebook SDK not loaded yet.');
            return;
        }

        // Reset state for a clean attempt
        setAuthCode(null);
        setPhoneNumberId(null);

        window.FB.login(
            (response: FacebookLoginResponse) => {
                if (response.authResponse?.code) {
                    setAuthCode(response.authResponse.code);
                } else {
                    console.log('User cancelled login or did not fully authorize.');
                    toast.info('The connection process was cancelled.');
                }
            },
            {
                config_id: import.meta.env.VITE_FACEBOOK_CONFIG_ID,
                response_type: 'code',
                override_default_response_type: true,
                extras: {
                    feature: 'EMBEDDED_SIGNUP',
                    sessionInfoVersion: '3',
                    setup: {},
                },
            }
        );
    };

    return (
        <Button onClick={handleClick} disabled={isLoading} className="btn-whatsapp">
            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Connect Your WhatsApp
        </Button>
    );
}
