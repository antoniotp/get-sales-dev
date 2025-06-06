
import { Facebook, Twitter, Instagram, Linkedin, MessageSquare } from "lucide-react";
import { Button } from "@/components/ui/button";

const Footer = () => {
  return (
    <footer className="bg-gray-900 text-white pt-16 pb-8">
      <div className="container-custom">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
          <div>
            <h3 className="font-heading text-xl font-bold text-white mb-6">WhatsAgents</h3>
            <p className="text-gray-400 mb-6">
              Conectamos empresas con agentes profesionales para maximizar ventas a través de WhatsApp Business.
            </p>
            <div className="flex space-x-4">
              <a href="#" className="bg-gray-800 hover:bg-brandBlue p-2 rounded-full transition-colors">
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
              </a>
            </div>
          </div>

          <div>
            <h3 className="font-heading font-bold text-lg mb-6">Enlaces útiles</h3>
            <ul className="space-y-3">
              <li>
                <a href="/#inicio" className="text-gray-400 hover:text-white transition-colors">Inicio</a>
              </li>
              <li>
                <a href="/#funciones" className="text-gray-400 hover:text-white transition-colors">Funciones</a>
              </li>
              <li>
                <a href="/#como-funciona" className="text-gray-400 hover:text-white transition-colors">Cómo funciona</a>
              </li>
              <li>
                <a href="/#testimonios" className="text-gray-400 hover:text-white transition-colors">Testimonios</a>
              </li>
              <li>
                <a href="/#contacto" className="text-gray-400 hover:text-white transition-colors">Contacto</a>
              </li>
            </ul>
          </div>

          <div>
            <h3 className="font-heading font-bold text-lg mb-6">Legal</h3>
            <ul className="space-y-3">
              <li>
                <a href="/policies" className="text-gray-400 hover:text-white transition-colors">Términos y condiciones</a>
              </li>
              <li>
                <a href="/policies" className="text-gray-400 hover:text-white transition-colors">Política de privacidad</a>
              </li>
              <li>
                <a href="#" className="text-gray-400 hover:text-white transition-colors">Política de cookies</a>
              </li>
              <li>
                <a href="#" className="text-gray-400 hover:text-white transition-colors">FAQ</a>
              </li>
            </ul>
          </div>

          <div>
            <h3 className="font-heading font-bold text-lg mb-6">Newsletter</h3>
            <p className="text-gray-400 mb-4">
              Suscríbete para recibir noticias y actualizaciones.
            </p>
            <div className="flex gap-2">
              <input
                type="email"
                placeholder="Tu email"
                className="bg-gray-800 rounded-md px-4 py-2 text-white flex-grow focus:outline-none focus:ring-2 focus:ring-brandBlue"
              />
              <Button className="bg-brandBlue hover:bg-brandBlue/90">
                Enviar
              </Button>
            </div>
          </div>
        </div>

        <div className="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
          <p className="text-gray-500 text-sm mb-4 md:mb-0">
            © 2025 WhatsAgents. Todos los derechos reservados.
          </p>

          <div className="flex items-center">
            <span className="text-gray-500 text-sm mr-2">¿Prefieres hablar directamente?</span>
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
