import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface DeleteContactDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    contactId: number;
}

export function DeleteContactDialog({ open, onOpenChange, contactId }: DeleteContactDialogProps) {

    const { t } = useTranslation('contact');

    const { delete: destroy, processing } = useForm();

    const handleDelete = () => {
        destroy(route('contacts.destroy', contactId), {
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {t('deleteDialog.title')}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {t('deleteDialog.description')}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>
                        {t('deleteDialog.cancel')}
                    </AlertDialogCancel>
                    <AlertDialogAction onClick={handleDelete} disabled={processing}>
                        {t('deleteDialog.delete')}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}