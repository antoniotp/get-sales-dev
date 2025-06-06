
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Menu, X } from "lucide-react";
import { Link, usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

const Navigation = () => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const { auth } = usePage<SharedData>().props;

  return (
    <nav className="fixed w-full bg-white/90 backdrop-blur-sm z-50 shadow-sm">
      <div className="container-custom py-4 px-4 flex items-center justify-between">
        <div className="flex items-center">
            <Link href={route('home')} className={"font-heading text-xl font-bold text-brandBlue"}>WhatsAgents</Link>
        </div>

        {/* Desktop Navigation */}
        <div className="hidden md:flex items-center gap-8">
          <a href="/#inicio" className="text-gray-700 hover:text-brandBlue font-medium transition-colors">Inicio</a>
          <a href="/#funciones" className="text-gray-700 hover:text-brandBlue font-medium transition-colors">Funciones</a>
          <a href="/#como-funciona" className="text-gray-700 hover:text-brandBlue font-medium transition-colors">Cómo Funciona</a>
          <a href="/#testimonios" className="text-gray-700 hover:text-brandBlue font-medium transition-colors">Testimonios</a>
          <a href="/#contacto" className="text-gray-700 hover:text-brandBlue font-medium transition-colors">Contacto</a>
        </div>

        <div className="hidden md:flex items-center gap-4">
            {auth.user ? (
                <Link
                    href={route('dashboard')}
                >
                    <Button className="bg-brandBlue hover:bg-brandBlue/90 px-4">Dashboard</Button>
                </Link>
            ) : (
                <>
                    <Link
                        href={route('login')}
                    >
                        <Button variant="outline" className="px-4 dark:text-white">Iniciar Sesión</Button>
                    </Link>
                    <Link
                        href={route('register')}
                    >
                        <Button className="bg-brandBlue hover:bg-brandBlue/90 px-4 dark:text-white">Registrarse</Button>
                    </Link>
                </>
            )}
        </div>

        {/* Mobile Navigation Toggle */}
        <div className="md:hidden">
          <button
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            className="p-2 text-gray-700"
          >
            {isMenuOpen ? <X /> : <Menu />}
          </button>
        </div>
      </div>

      {/* Mobile Menu */}
      {isMenuOpen && (
        <div className="md:hidden bg-white border-t py-4 px-4">
          <div className="flex flex-col space-y-4">
            <a
              href="#inicio"
              className="text-gray-700 hover:text-brandBlue font-medium transition-colors px-4 py-2"
              onClick={() => setIsMenuOpen(false)}
            >
              Inicio
            </a>
            <a
              href="#funciones"
              className="text-gray-700 hover:text-brandBlue font-medium transition-colors px-4 py-2"
              onClick={() => setIsMenuOpen(false)}
            >
              Funciones
            </a>
            <a
              href="#como-funciona"
              className="text-gray-700 hover:text-brandBlue font-medium transition-colors px-4 py-2"
              onClick={() => setIsMenuOpen(false)}
            >
              Cómo Funciona
            </a>
            <a
              href="#testimonios"
              className="text-gray-700 hover:text-brandBlue font-medium transition-colors px-4 py-2"
              onClick={() => setIsMenuOpen(false)}
            >
              Testimonios
            </a>
            <a
              href="#contacto"
              className="text-gray-700 hover:text-brandBlue font-medium transition-colors px-4 py-2"
              onClick={() => setIsMenuOpen(false)}
            >
              Contacto
            </a>
            <div className="flex flex-col gap-2 pt-4">
              <Button variant="outline" className="w-full">Iniciar Sesión</Button>
              <Button className="bg-brandBlue hover:bg-brandBlue/90 w-full">Registrarse</Button>
            </div>
          </div>
        </div>
      )}
    </nav>
  );
};

export default Navigation;
