
import { useTranslation } from "react-i18next";

const Partnerships = () => {
    const { t } = useTranslation("home");

    const partners = [
        {
        name: "OpenAI",
        logo: "/images/OpenAI_logo.svg"
        },
        {
        name: "Meta",
        logo: "/images/Meta_logo.svg"
        },
        {
        name: "WhatsApp Business",
        logo: "/images/WhatsApp_logo.svg"
        },
        {
        name: "Google Cloud",
        logo: "/images/Google_Cloud_logo.svg"
        }
    ];

    return (
        <section className="section-padding bg-white">
        <div className="container-custom">
            <div className="text-center mb-12">
            <h2 className="text-2xl md:text-3xl font-bold mb-4">{t("partnerships.title")}</h2>
            <p className="text-gray-900">{t("partnerships.description")}</p>
            </div>
            
            <div className="flex flex-wrap justify-center items-center gap-12 md:gap-16">
            {partners.map((partner, index) => (
                <div 
                key={index} 
                className="flex items-center justify-center grayscale hover:grayscale-0 transition-all duration-300 opacity-60 hover:opacity-100"
                >
                <img 
                    src={partner.logo} 
                    alt={partner.name}
                    className="h-8 md:h-12 w-auto object-contain"
                />
                </div>
            ))}
            </div>
        </div>
        </section>
    );
};

export default Partnerships;
