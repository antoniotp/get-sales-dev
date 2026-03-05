import React from "react"
import { Languages, Check } from "lucide-react"
import { useTranslation } from "react-i18next"
import { Button } from "@/components/ui/button"
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from "@/components/ui/dropdown-menu"
import { cn } from "@/lib/utils"

type LanguageSwitcherProps = {
    className?: string
}

const languages = [
{ code: "es", label: "Español" },
{ code: "en", label: "English" },
]

export function LanguageSwitcher({ className }: LanguageSwitcherProps) {
const { i18n } = useTranslation()

const changeLanguage = (lang: string) => {
    i18n.changeLanguage(lang)
}

return (
    <DropdownMenu>
        <DropdownMenuTrigger asChild>
            <Button
            variant="ghost"
            className={cn("flex items-center gap-2 h-8 px-2", className)}
            >
            <Languages className="size-4" />
            <span className="text-xs font-medium uppercase">
                {i18n.language}
            </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end">
            {languages.map((lang) => (
            <DropdownMenuItem
                key={lang.code}
                onClick={() => changeLanguage(lang.code)}
                className="flex items-center justify-between"
            >
                {lang.label}
                {i18n.language === lang.code && (
                <Check className="size-4 ml-2" />
                )}
            </DropdownMenuItem>
            ))}
        </DropdownMenuContent>
    </DropdownMenu>
)
}
