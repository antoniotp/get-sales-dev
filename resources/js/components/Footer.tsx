import { /*Facebook, Twitter, Instagram, Linkedin,*/ MessageSquare } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useTranslation } from "react-i18next";

const Footer = () => {
  const { t } = useTranslation("navigation");

  return (
    <footer className="bg-gray-900 text-white pt-16 pb-8">
      <div className="container-custom px-4 sm:px-6 lg:px-0">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
          <div>
            <img src="/images/getsales-logo.png" alt="GetSales" className="h-8 mb-6 brightness-0 invert" />
            <p className="text-gray-400 mb-6">
              {t("footer.description")}
            </p>
            <div className="flex space-x-4">
              {/*<a href="#" className="bg-gray-800 hover:bg-brandBlue p-2 rounded-full transition-colors">
                <Facebook size={18} />
              </a>
              <a href="#" className="bg-gray-800 hover:bg-brandBlue p-2 rounded-full transition-colors">
                <Twitter size={18} />
              </a>
              <a href="#" className="bg-gray-800 hover:bg-brandBlue p-2 rounded-full transition-colors">
                <Instagram size={18} />
              </a>
              <a href="#" className="bg-gray-800 hover:bg-brandBlue p-2 rounded-full transition-colors">
                <Linkedin size={18} />
              </a>*/}
            </div>
          </div>

          <div>
            <h3 className="font-heading font-bold text-lg mb-6">{t("footer.links.title")}</h3>
            <ul className="space-y-3">
              <li>
                <a href="/#home" className="text-gray-400 hover:text-white transition-colors">{t("footer.links.items.home")}</a>
              </li>
              <li>
                <a href="/#features" className="text-gray-400 hover:text-white transition-colors">{t("footer.links.items.features")}</a>
              </li>
              <li>
                <a href="/#how-it-works" className="text-gray-400 hover:text-white transition-colors">{t("footer.links.items.howItWorks")}</a>
              </li>
              <li>
                <a href="/#testimonials" className="text-gray-400 hover:text-white transition-colors">{t("footer.links.items.testimonials")}</a>
              </li>
              <li>
                <a href="/#contact" className="text-gray-400 hover:text-white transition-colors">{t("footer.links.items.contact")}</a>
              </li>
            </ul>
          </div>

          <div>
            <h3 className="font-heading font-bold text-lg mb-6">Legal</h3>
            <ul className="space-y-3">
              <li>
                <a href="/policies" className="text-gray-400 hover:text-white transition-colors">{t("footer.legal.items.terms")}</a>
              </li>
              <li>
                <a href="/policies" className="text-gray-400 hover:text-white transition-colors">{t("footer.legal.items.privacy")}</a>
              </li>
              <li>
                <a href="#" className="text-gray-400 hover:text-white transition-colors">{t("footer.legal.items.cookies")}</a>
              </li>
              <li>
                <a href="#" className="text-gray-400 hover:text-white transition-colors">{t("footer.legal.items.faq")}</a>
              </li>
            </ul>
          </div>

          <div>
            <h3 className="font-heading font-bold text-lg mb-6">{t("footer.newsletter.title")}</h3>
            <p className="text-gray-400 mb-4">
              {t("footer.newsletter.description")}
            </p>
            <div className="flex gap-2">
              <input
                type="email"
                placeholder={t("footer.newsletter.placeholder")}
                className="bg-gray-800 rounded-md px-4 py-2 text-white flex-grow focus:outline-none focus:ring-2 focus:ring-brandBlue"
              />
              <Button className="bg-brandRed hover:bg-brandRed/90">
                {t("footer.newsletter.button")}
              </Button>
            </div>
          </div>
        </div>

        <div className="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
          <p className="text-gray-500 text-sm mb-4 md:mb-0">
            {t("footer.copyright")}
          </p>

          <div className="flex items-center">
            <span className="text-gray-500 text-sm mr-2">{t("footer.whatsapp.label")}</span>
            <a
              href="https://wa.me/34600123456"
              className="inline-flex items-center gap-2 bg-whatsapp/20 hover:bg-whatsapp text-whatsapp hover:text-white px-4 py-2 rounded-md transition-colors"
            >
              <MessageSquare size={16} />
              <span>WhatsApp</span>
            </a>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
