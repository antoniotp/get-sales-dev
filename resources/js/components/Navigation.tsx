import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Menu, X } from "lucide-react";
import { Link, usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { useTranslation } from "react-i18next";

const Navigation = () => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const { auth } = usePage<SharedData>().props;
  const { t } = useTranslation("navigation");

  return (
    <nav className="fixed w-full bg-white/90 backdrop-blur-sm z-50 shadow-xs">
      <div className="container-custom py-4 px-4 flex items-center justify-between">
        <div className="flex items-center">
            <Link href={route('home')} className="h-12"><img src="/images/getsales-logo.png" alt="GetSales" className="h-12" /></Link>
        </div>

        {/* Desktop Navigation */}
        <div className="hidden md:flex items-center gap-8">
          <a href="/#home" className="text-gray-800 hover:text-brandRed font-medium transition-colors">{t("header.menu.home")}</a>
          <a href="/#features" className="text-gray-800 hover:text-brandRed font-medium transition-colors">{t("header.menu.features")}</a>
          <a href="/#how-it-works" className="text-gray-800 hover:text-brandRed font-medium transition-colors">{t("header.menu.how_it_works")}</a>
          <a href="/#testimonials" className="text-gray-800 hover:text-brandRed font-medium transition-colors">{t("header.menu.testimonials")}</a>
          <a href="/#contact" className="text-gray-800 hover:text-brandRed font-medium transition-colors">{t("header.menu.contact")}</a>
        </div>

        <div className="hidden md:flex items-center gap-4">
            {auth.user ? (
                <Link
                    href={route('dashboard')}
                >
                    <Button className="bg-brandBlue hover:bg-brandBlue/90 px-4">{t("header.actions.dashboard")}</Button>
                </Link>
            ) : (
                <>
                    <Link
                        href={route('login')}
                    >
                        <Button variant="outline" className="px-4 py-5 font-bold border-brandRed text-brandRed hover:bg-brandRed/10">{t("header.actions.login")}</Button>
                    </Link>
                    <Link
                        href={route('register')}
                    >
                        <Button className="bg-brandRed hover:bg-brandRed/90 px-4 py-5 font-bold">{t("header.actions.register")}</Button>
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
              href="#home"
              className="text-gray-700 hover:text-brandRed font-medium transition-colors px-4 py-2"
              onClick={() => setIsMenuOpen(false)}
            >
              {t("header.menu.home")}
            </a>
            <a
              href="#features"
              className="text-gray-700 hover:text-brandRed font-medium transition-colors px-4 py-2"
              onClick={() => setIsMenuOpen(false)}
            >
              {t("header.menu.features")}
            </a>
            <a
              href="#how-it-works"
              className="text-gray-700 hover:text-brandRed font-medium transition-colors px-4 py-2"
              onClick={() => setIsMenuOpen(false)}
            >
              {t("header.menu.how_it_works")}
            </a>
            <a
              href="#testimonials"
              className="text-gray-700 hover:text-brandRed font-medium transition-colors px-4 py-2"
              onClick={() => setIsMenuOpen(false)}
            >
              {t("header.menu.testimonials")}
            </a>
            <a
              href="#contact"
              className="text-gray-700 hover:text-brandRed font-medium transition-colors px-4 py-2"
              onClick={() => setIsMenuOpen(false)}
            >
              {t("header.menu.contact")}
            </a>
            <div className="flex flex-col gap-2 pt-4">
                {auth.user ? (
                    <Link
                        href={route('dashboard')}
                    >
                        <Button className="bg-brandRed hover:bg-brandRed/90 w-full">{t("header.actions.dashboard")}</Button>
                    </Link>
                ) : (
                    <>
                        <Link
                            href={route('login')}
                        >
                            <Button variant="outline" className="w-full">{t("header.actions.login")}</Button>
                        </Link>
                        <Link
                            href={route('register')}
                        >
                            <Button className="bg-brandRed hover:bg-brandRed/90 w-full">{t("header.actions.register")}</Button>
                        </Link>
                    </>
                )}
            </div>
          </div>
        </div>
      )}
    </nav>
  );
};

export default Navigation;
