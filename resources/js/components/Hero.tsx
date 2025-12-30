import { MessageSquare, Bot, Zap } from 'lucide-react';
import { useTranslation } from "react-i18next";

const Hero = () => {
    const { t } = useTranslation("home");

    return (
        <section id="home" className="pt-32 pb-20 md:pt-40 md:pb-32 px-4">
            <div className="container-custom">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div className="flex flex-col space-y-8">
                        <div>
                            <div className="flex flex-col space-y-8">
                                <div>
                                <div className="bg-brandRed/10 text-brandRed inline-flex items-center px-4 py-2 rounded-full mb-4">
                                    <Bot size={16} className="mr-2" />
                                    <span className="text-sm font-medium">{t("hero.badge")}</span>
                                </div>
                                <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-4">
                                    {t("hero.title.prefix")} <span className="text-brandRed">{t("hero.title.highlight")}</span>
                                </h1>
                                <p className="text-lg text-gray-600 mb-8">
                                    {t("hero.description")}
                                </p>
                                
                                <div className="flex flex-col sm:flex-row gap-4">
                                    <a href="#" className="btn-brandRed text-base py-6 px-8 flex items-center gap-2">
                                        <span>{t("hero.cta.start")}</span>
                                    </a>
                                    <a href="#contact"
                                    className="btn-outline text-center py-6 px-8 inline-flex items-center justify-center gap-2">
                                        <span>{t("hero.cta.contact")}</span>
                                    </a>
                                </div>
                                </div>
                                
                                <div className="grid grid-cols-2 md:grid-cols-3 gap-6 pt-4">
                                <div className="flex items-center gap-2">
                                    <Bot className="text-brandRed" />
                                    <span className="text-sm font-medium">{t("hero.features.ai")}</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <MessageSquare className="text-whatsapp" />
                                    <span className="text-sm font-medium">{t("hero.features.whatsapp")}</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Zap className="text-brandRed" />
                                    <span className="text-sm font-medium">{t("hero.features.sales")}</span>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="relative">
                        <div className="relative z-10">
                            <img
                                src="/images/getsales-hero.png"
                                alt="WhatsApp Business Agents"
                                className="rounded-lg shadow-2xl w-full"
                            />
                        </div>

                        <div
                            className="absolute bottom-12 -left-12 bg-white p-4 rounded-lg shadow-lg z-20 max-w-[260px] animate-bounce-slow hidden md:block">
                            <div className="flex items-start gap-4">
                                <div className="bg-whatsapp rounded-full p-2">
                                    <MessageSquare size={20} className="text-white" />
                                </div>
                                <div>
                                    <h3 className="font-medium text-sm dark:text-gray-900">{t("hero.floating_card.title")}</h3>
                                    <p className="text-xs text-gray-500 mt-1">{t("hero.floating_card.description")}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default Hero;
