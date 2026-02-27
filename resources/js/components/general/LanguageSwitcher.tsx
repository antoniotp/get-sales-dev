import React from "react"
import { Languages } from "lucide-react"
import { useTranslation } from "react-i18next"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"

type LanguageSwitcherProps = {
  className?: string
}

export function LanguageSwitcher({ className }: LanguageSwitcherProps) {
  const { i18n } = useTranslation()

  const toggleLanguage = () => {
    const newLang = i18n.language === "es" ? "en" : "es"
    i18n.changeLanguage(newLang)
  }

  return (
    <Button
      variant="ghost"
      onClick={toggleLanguage}
      className={cn("flex items-center gap-2 h-8 px-2", className)}
      aria-label="Toggle language"
    >
      <Languages className="size-4" />
      <span className="text-xs font-medium uppercase">
        {i18n.language}
      </span>
    </Button>
  )
}