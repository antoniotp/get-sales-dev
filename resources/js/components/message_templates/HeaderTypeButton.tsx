import React from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { HeaderTypeButtonProps } from '@/types/message-template.d';

const HeaderTypeButton = ({ field, value, icon, label, currentType }: HeaderTypeButtonProps) => (
    <Button
        type="button"
        variant="outline"
        className={cn("flex items-center gap-2", currentType === value && "ring-2 ring-primary")}
        onClick={() => field.onChange(value)}
    >
        {icon}
        <span>{label}</span>
    </Button>
);

export default HeaderTypeButton;
