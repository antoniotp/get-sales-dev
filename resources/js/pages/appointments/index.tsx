import React, { useState, useCallback, useEffect } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { type PageProps as GlobalPageProps, type ChatbotChannel, Appointment } from '@/types';
import AppContentDefaultLayout from "@/layouts/app/app-content-default-layout";
import { Card } from "@/components/ui/card";
import { Calendar, dateFnsLocalizer, Event as CalendarEvent, EventProps } from 'react-big-calendar';
import { format, parse, startOfWeek, getDay, startOfMonth, endOfMonth, addDays } from 'date-fns';
import { es } from 'date-fns/locale/es';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import { toZonedTime } from 'date-fns-tz';
import axios from 'axios';
import { NewAppointmentModal } from './partials/NewAppointmentModal';
import { AppointmentDetailsModal } from './partials/AppointmentDetailsModal';
import { CalendarToolbar } from './partials/CalendarToolbar';

// Set up the localizer by providing the date-fns functions
// to the correct localizer.
const locales = {
    'es': es,
};

const mondayStartOfWeek = (date: Date) => {
    return startOfWeek(date, { weekStartsOn: 1 }); // 1 = Monday
};

const localizer = dateFnsLocalizer({
    format,
    parse,
    startOfWeek: mondayStartOfWeek,
    getDay,
    locales,
});

interface FormattedEvent extends CalendarEvent {
    resource: Appointment;
}

interface ChatbotChannelType extends ChatbotChannel{
    id: number;
    name: string;
    phone_number: string | null;
    credentials: {
        phone_number: string;
    } | null;
}

interface PageProps extends GlobalPageProps {
    chatbotChannels: ChatbotChannelType[];
}


export default function AppointmentsIndex(){
    const { chatbot, chatbotChannels, organization } = usePage<PageProps>().props;
    const [events, setEvents] = useState<FormattedEvent[]>([]);

    // State for Create Modal
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [newAppointmentDate, setNewAppointmentDate] = useState<Date | null>(null);

    // State for Details/Edit Modal
    const [isDetailsModalOpen, setIsDetailsModalOpen] = useState(false);
    const [selectedAppointment, setSelectedAppointment] = useState<Appointment | null>(null);

    const breadcrumbs = [
        { title: 'Chatbots', href: route('chatbots.index') },
        { title: chatbot.name, href: route('chatbots.edit', { chatbot: chatbot.id }) },
        { title: 'Agenda', href: route('appointments.index', { chatbot: chatbot.id }) },
    ];

    const organizationTimezone = organization.current.timezone || 'UTC';

    // Calculates the end Date for a calendar event
    const getEventEndDate = (appointment: Appointment, organizationTimezone: string): Date => {
        if (appointment.end_at) {
            // Apply timezone conversion to end_at as well
            return toZonedTime(new Date(appointment.end_at), organizationTimezone);
        }
        // Fallback to 1-hour duration if end_at is not provided
        const appointmentDate = toZonedTime(new Date(appointment.appointment_at), organizationTimezone);
        return new Date(appointmentDate.getTime() + 60 * 60 * 1000);
    };

    const fetchAppointments = useCallback(async (start: Date, end: Date) => {
        try {
            const response = await axios.get(route('appointments.list', {
                chatbot: chatbot.id,
                start_date: start.toISOString().slice(0, 10),
                end_date: end.toISOString().slice(0, 10),
            }));

            const appointments: Appointment[] = response.data;

            const formattedEvents: FormattedEvent[] = appointments.map((apt: Appointment) => {
                const appointmentDate = toZonedTime(new Date(apt.appointment_at), organizationTimezone);
                const eventEndDate = getEventEndDate(apt, organizationTimezone);

                return {
                    title: `${apt.contact.first_name} ${apt.contact.last_name || ''}`.trim(),
                    start: appointmentDate,
                    end: eventEndDate,
                    resource: apt,
                };
            });
            setEvents(formattedEvents);
        } catch (error) {
            console.error("Failed to fetch appointments:", error);
        }
    }, [chatbot.id, organizationTimezone]);

    // ... (handleRangeChange and initial load useEffect remain the same)
    const handleRangeChange = useCallback((range: Date[] | { start: Date; end: Date; }) => {
        let start: Date, end: Date;
        if (Array.isArray(range)) {
            start = range[0];
            end = range[range.length - 1];
        } else {
            start = range.start;
            end = range.end;
        }
        fetchAppointments(start, end);
    }, [fetchAppointments]);

    // Initial load: fetch appointments for the current month
    useEffect(() => {
        const today = new Date();
        const start = startOfMonth(today);
        const end = endOfMonth(today);
        fetchAppointments(start, end);
    }, [fetchAppointments]);

    // --- Modal and Event Handlers ---
    const handleSelectSlot = useCallback(({ start }: { start: Date }) => {
        setNewAppointmentDate(start);
        setIsCreateModalOpen(true);
    }, []);

    const handleSelectEvent = useCallback((event: FormattedEvent) => {
        setSelectedAppointment(event.resource);
        setIsDetailsModalOpen(true);
    }, []);

    const handleCloseModals = () => {
        setIsCreateModalOpen(false);
        setIsDetailsModalOpen(false);
        setNewAppointmentDate(null);
        setSelectedAppointment(null);
    };

    const handleCreateSuccess = (newAppointment: Appointment) => {
        const appointmentDate = toZonedTime(new Date(newAppointment.appointment_at), organizationTimezone);
        const eventEndDate = getEventEndDate(newAppointment, organizationTimezone);

        const newEvent: FormattedEvent = {
            title: `${newAppointment.contact.first_name} ${newAppointment.contact.last_name || ''}`.trim(),
            start: appointmentDate,
            end: eventEndDate,
            resource: newAppointment,
        };
        setEvents(prevEvents => [...prevEvents, newEvent]);
    };

    const handleUpdateSuccess = (updatedAppointment: Appointment) => {
        setEvents(prevEvents => prevEvents.map(event => {
            if (event.resource.id === updatedAppointment.id) {
                const appointmentDate = toZonedTime(new Date(updatedAppointment.appointment_at), organizationTimezone);
                const eventEndDate = getEventEndDate(updatedAppointment, organizationTimezone);

                return {
                    ...event,
                    title: `${updatedAppointment.contact.first_name} ${updatedAppointment.contact.last_name || ''}`.trim(),
                    start: appointmentDate,
                    end: eventEndDate,
                    resource: updatedAppointment,
                };
            }
            return event;
        }));
    };

    const handleDeleteSuccess = (appointmentId: number) => {
        setEvents(prevEvents => prevEvents.filter(event => event.resource.id !== appointmentId));
    };

    const handleCreate = useCallback(() => {
        const tomorrow = addDays(new Date(), 1);
        setNewAppointmentDate(tomorrow);
        setIsCreateModalOpen(true);
    }, []);

    const CustomMonthEvent = ({ event } : EventProps<FormattedEvent>) => {
        const startHour = event.start? format(event.start, 'HH:mm') : '';
        return (
            <span className="text-xs">
              {startHour && `${startHour} `}{event.title}
            </span>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Agenda" />
            <AppContentDefaultLayout>
                <div className="flex h-[calc(100vh-8rem)] w-full overflow-hidden">
                    <Card className="w-full p-3 flex flex-col overflow-auto">
                        <div className="flex-grow">
                             <Calendar
                                localizer={localizer}
                                events={events}
                                startAccessor="start"
                                endAccessor="end"
                                onRangeChange={handleRangeChange}
                                culture='es'
                                selectable={true}
                                onSelectSlot={handleSelectSlot}
                                onSelectEvent={handleSelectEvent}
                                messages={{
                                    next: ">",
                                    previous: "<",
                                    today: "Hoy",
                                    month: "Mes",
                                    week: "Semana",
                                    day: "Día",
                                    agenda: "Agenda",
                                    date: "Fecha",
                                    time: "Hora",
                                    event: "Evento",
                                    noEventsInRange: "No hay citas en este rango.",
                                }}
                                components={{
                                    month: { event: CustomMonthEvent },
                                    toolbar: (toolbarProps) => (
                                        <CalendarToolbar
                                            {...toolbarProps}
                                            onCreate={handleCreate}
                                        />
                                    ),
                                }}
                            />
                        </div>
                    </Card>
                </div>
            </AppContentDefaultLayout>
            <NewAppointmentModal
                isOpen={isCreateModalOpen}
                onClose={handleCloseModals}
                onSuccess={handleCreateSuccess}
                initialDate={newAppointmentDate}
                chatbotChannels={chatbotChannels}
            />
            <AppointmentDetailsModal
                isOpen={isDetailsModalOpen}
                onClose={handleCloseModals}
                onUpdate={handleUpdateSuccess}
                onDelete={handleDeleteSuccess}
                appointment={selectedAppointment}
                organizationTimezone={organizationTimezone}
            />
        </AppLayout>
    );
};
