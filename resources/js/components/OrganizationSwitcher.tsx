import { usePage, router } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

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

export default function OrganizationSwitcher() {
    const { props } = usePage<PageProps>();
    const { organization } = props;

    const handleSwitch = (newOrganizationId: string) => {
        router.post('/organizations/switch', {
            organization_id: parseInt(newOrganizationId),
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
        <Select onValueChange={handleSwitch} defaultValue={organization.current.id.toString()}>
            <SelectTrigger className="w-full overflow-ellipsis no-wrap whitespace-nowrap text-left">
                <SelectValue placeholder="Select an organization" />
            </SelectTrigger>
            <SelectContent>
                {organization.list.map((org: Organization) => (
                    <SelectItem key={org.id} value={org.id.toString()}>
                        {org.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
