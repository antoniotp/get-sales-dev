import * as React from "react"
import { ChevronsUpDown, GalleryVerticalEnd, Plus } from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    /*DropdownMenuShortcut,*/
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from "@/components/ui/sidebar"
import { router, usePage } from '@inertiajs/react';



interface Organization {
    id: number;
    name: string;
}

interface Organizations {
    list: Organization[];
    current: Organization;
}

interface PageProps {
    organization: Organizations;
    [key: string]: unknown;
}

export function OrgSwitcher() {
    const { props } = usePage<PageProps>();
    const { organization } = props;
    const { isMobile } = useSidebar()

    const handleSwitch = (org: Organization) => {
        router.post('/organizations/switch', {
            organization_id: org.id,
        }, {
            onSuccess: () => {
                // Opcional: mostrar una notificación de éxito
            },
            onError: (errors) => {
                // Opcional: mostrar errores de validación
                console.error(errors);
            },
        });
    };

    if (!organization || !organization.list || organization.list.length === 0) {
        return null;
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <div className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg">
                                <GalleryVerticalEnd className="size-4" />
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">{organization.current.name}</span>
                                {/*<span className="truncate text-xs">{activeOrg.plan}</span>*/}
                            </div>
                            <ChevronsUpDown className="ml-auto" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={isMobile ? "bottom" : "right"}
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="text-muted-foreground text-xs">
                            Organizations
                        </DropdownMenuLabel>
                        {organization.list.map((org) => (
                            <DropdownMenuItem
                                key={org.name}
                                onClick={() => handleSwitch(org)}
                                className="gap-2 p-2"
                            >
                                {/*<div className="flex size-6 items-center justify-center rounded-md border">
                                    <org.logo className="size-3.5 shrink-0" />
                                </div>*/}
                                {org.name}
                                {/*<DropdownMenuShortcut>⌘{index + 1}</DropdownMenuShortcut>*/}
                            </DropdownMenuItem>
                        ))}
                        <DropdownMenuSeparator />
                        <DropdownMenuItem className="gap-2 p-2">
                            <div className="flex size-6 items-center justify-center rounded-md border bg-transparent">
                                <Plus className="size-4" />
                            </div>
                            <div className="text-muted-foreground font-medium">Add Organization</div>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    )
}
