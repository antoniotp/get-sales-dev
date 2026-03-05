import { useState, useEffect, forwardRef } from 'react';
import { usePage } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import * as z from 'zod';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Appointment, Chatbot, PageProps } from '@/types';
import { toZonedTime } from 'date-fns-tz';
import { toast } from 'sonner';
import { formatPhoneNumber } from '@/lib/utils';

// Zod Schema for the form
const formSchema = z.object({
    appointment_at: z.string({ required_error: "Appointment date is required." }),
    end_at: z.string().nullable().optional(), // New
    remind_at: z.string().nullable().optional(), // New
});

type FormData = z.infer<typeof formSchema>;

// Helper to format a Date object into a YYYY-MM-DDTHH:mm string
function toLocalISOString(date: Date): string {
    const pad = (num: number) => (num < 10 ? '0' + num : '' + num);
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
}

interface Props {
    isOpen: boolean;
    onClose: () => void;
    onUpdate: (updatedAppointment: Appointment) => void;
    onDelete: (appointmentId: number) => void;
    appointment: Appointment | null;
    organizationTimezone: string;
}

export const AppointmentDetailsModal = forwardRef<HTMLDivElement, Props>(
    ({ isOpen, onClose, onUpdate, onDelete, appointment, organizationTimezone }, ref) => {
        const { chatbot } = usePage<PageProps>().props as { chatbot: Chatbot };
        const { t } = useTranslation('appointments');
        const [isSubmitting, setIsSubmitting] = useState(false);
        const [isDeleting, setIsDeleting] = useState(false);
        const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

        const form = useForm<FormData>({
            resolver: zodResolver(formSchema),
        });

        const { reset } = form;

        // Populate form when an appointment is selected
        useEffect(() => {
            if (appointment) {
                const zonedDate = toZonedTime(new Date(appointment.appointment_at), organizationTimezone);
                reset({
                    appointment_at: toLocalISOString(zonedDate),
                    end_at: appointment.end_at ? toLocalISOString(toZonedTime(new Date(appointment.end_at), organizationTimezone)) : '',
                    remind_at: appointment.remind_at ? toLocalISOString(toZonedTime(new Date(appointment.remind_at), organizationTimezone)) : '',
                });
            }
        }, [appointment, reset, organizationTimezone]);

        // Form submission handler for UPDATE
        const onSubmit = async (data: FormData) => {
            if (!appointment) return;
            setIsSubmitting(true);
            try {
                const response = await axios.put(route('appointments.update', { appointment: appointment.id }), data);
                onUpdate(response.data);
                onClose();
                toast.success(t('detailsModal.toast.updateSuccess'));
            } catch (error) {
                console.error('Failed to update appointment:', error);
                toast.error(t('detailsModal.toast.updateError'));
            } finally {
                setIsSubmitting(false);
            }
        };

        // Handler for DELETE action
        const handleDelete = async () => {
            if (!appointment) return;
            setIsDeleting(true);
            try {
                await axios.delete(route('appointments.destroy', { appointment: appointment.id }));
                onDelete(appointment.id);
                toast.success(t('detailsModal.toast.deleteSuccess'));
                setDeleteDialogOpen(false);
                onClose();
            } catch (error) {
                console.error('Failed to delete appointment:', error);
                toast.error(t('detailsModal.toast.deleteError'));
            } finally {
                setIsDeleting(false);
            }
        };

        if (!appointment) return null;

        return (
            <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
                    <DialogContent
                        ref={ref}
                        className="sm:max-w-md"
                        onPointerDownOutside={(e) => {
                            //prevent click propagation
                            e.preventDefault();
                            e.stopPropagation();
                            // Close after the current event cycle completes
                            setTimeout(() => onClose(), 0);
                        }}
                    >
                        <DialogHeader>
                            <DialogTitle>{t('detailsModal.title')}</DialogTitle>
                            <DialogDescription>{t('detailsModal.description')}</DialogDescription>
                        </DialogHeader>

                        <div className="py-4 space-y-4">
                            <div className="p-3 border rounded-md bg-muted/50">
                                <p className="text-sm font-medium text-muted-foreground">{t('detailsModal.contact')}</p>
                                <p className="font-semibold">{`${appointment.contact.first_name} ${appointment.contact.last_name || ''}`.trim()}</p>
                                <p className="text-sm">{formatPhoneNumber(appointment.contact.phone_number)}</p>
                            </div>

                            <Form {...form}>
                                <form id="update-appointment-form" onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                                    <FormField control={form.control} name="appointment_at" render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>{t('detailsModal.startTime')}</FormLabel>
                                            <FormControl><Input type="datetime-local" {...field} /></FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )} />

                                    <FormField control={form.control} name="end_at" render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>{t('detailsModal.endTimeOptional')}</FormLabel>
                                            <FormControl>
                                                <Input
                                                    type="datetime-local"
                                                    {...field}
                                                    value={field.value || ''} // Fix: Provide empty string for null/undefined
                                                />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )} />

                                    <FormField control={form.control} name="remind_at" render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>{t('detailsModal.reminderTimeOptional')}</FormLabel>
                                            <FormControl>
                                                <Input
                                                    type="datetime-local"
                                                    {...field}
                                                    value={field.value || ''} // Fix: Provide empty string for null/undefined
                                                />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )} />
                                </form>
                            </Form>
                        </div>

                        <DialogFooter className="flex justify-between w-full">
                            <Button
                                variant="destructive"
                                onClick={(e) => {
                                    e.preventDefault();
                                    setDeleteDialogOpen(true);
                                }}
                            >
                                {t('detailsModal.delete')}
                            </Button>
                            <div className="flex gap-2">
                                <Button variant="ghost" onClick={onClose}>{t('detailsModal.cancel')}</Button>
                                <Button type="submit" form="update-appointment-form" disabled={isSubmitting}>
                                    {isSubmitting ? t('detailsModal.updating') : t('detailsModal.update')}
                                </Button>
                            </div>
                        </DialogFooter>
                        {appointment?.contact.phone_number && (
                            <div className="mt-4 pt-4 border-t flex justify-end">
                                <a
                                    href={route('chats.start', {
                                        chatbot: chatbot.id,
                                        phone_number: appointment.contact.phone_number,
                                        cc_id: appointment.chatbot_channel_id
                                    })}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 bg-blue-500 text-primary-foreground hover:bg-blue-600"
                                >
                                    {t('detailsModal.sendMessageToClient')}
                                </a>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>

                {/* Delete confirmation Dialog */}
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('detailsModal.deleteDialog.title')}</DialogTitle>
                        <DialogDescription>
                            {t('detailsModal.deleteDialog.description', {
                                firstName: appointment.contact.first_name,
                                lastName: appointment.contact.last_name,
                            })}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="flex gap-2 sm:justify-end">
                        <Button variant="ghost" onClick={() => setDeleteDialogOpen(false)} disabled={isDeleting}>
                            {t('detailsModal.cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={isDeleting}>
                            {isDeleting ? t('detailsModal.deleteDialog.deleting') : t('detailsModal.deleteDialog.deleteAppointment')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        );
    }
);

AppointmentDetailsModal.displayName = "AppointmentDetailsModal";