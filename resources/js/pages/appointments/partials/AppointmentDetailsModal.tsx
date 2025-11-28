import { useState, useEffect, forwardRef } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Appointment } from '@/types';
import { toZonedTime } from 'date-fns-tz';
import { toast } from 'sonner';

// Zod Schema for the form
const formSchema = z.object({
    appointment_at: z.string({ required_error: "Appointment date is required." }),
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
                toast.success("Appointment updated successfully.");
            } catch (error) {
                console.error('Failed to update appointment:', error);
                toast.error("Failed to update appointment.");
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
                toast.success("Appointment deleted successfully.");
                setDeleteDialogOpen(false);
                onClose();
            } catch (error) {
                console.error('Failed to delete appointment:', error);
                toast.error("Failed to delete appointment.");
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
                            <DialogTitle>Appointment Details</DialogTitle>
                            <DialogDescription>View contact details and update the appointment time.</DialogDescription>
                        </DialogHeader>

                        <div className="py-4 space-y-4">
                            <div className="p-3 border rounded-md bg-muted/50">
                                <p className="text-sm font-medium text-muted-foreground">Contact</p>
                                <p className="font-semibold">{`${appointment.contact.first_name} ${appointment.contact.last_name || ''}`.trim()}</p>
                                <p className="text-sm">{appointment.contact.phone_number}</p>
                            </div>

                            <Form {...form}>
                                <form id="update-appointment-form" onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                                    <FormField control={form.control} name="appointment_at" render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Appointment Time</FormLabel>
                                            <FormControl><Input type="datetime-local" {...field} /></FormControl>
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
                                Delete
                            </Button>
                            <div className="flex gap-2">
                                <Button variant="ghost" onClick={onClose}>Cancel</Button>
                                <Button type="submit" form="update-appointment-form" disabled={isSubmitting}>
                                    {isSubmitting ? 'Updating...' : 'Update'}
                                </Button>
                            </div>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Delete confirmation Dialog */}
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Are you absolutely sure?</DialogTitle>
                        <DialogDescription>
                            This action cannot be undone. This will permanently delete the appointment for {appointment.contact.first_name} {appointment.contact.last_name}.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="flex gap-2 sm:justify-end">
                        <Button variant="ghost" onClick={() => setDeleteDialogOpen(false)} disabled={isDeleting}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={isDeleting}>
                            {isDeleting ? 'Deleting...' : 'Delete Appointment'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        );
    }
);

AppointmentDetailsModal.displayName = "AppointmentDetailsModal";
