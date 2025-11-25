import React, { useState, useCallback, useEffect } from 'react';
import AppLayout from '@/layouts/app-layout';
import { type PageProps as GlobalPageProps, type ChatbotChannel } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import AppContentDefaultLayout from "@/layouts/app/app-content-default-layout";
import { Card } from "@/components/ui/card";
import { Calendar, dateFnsLocalizer, Event as CalendarEvent } from 'react-big-calendar';
import { format, parse, startOfWeek, getDay, startOfMonth, endOfMonth } from 'date-fns';
import { es } from 'date-fns/locale/es';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import axios from 'axios';

// Set up the localizer by providing the date-fns functions
// to the correct localizer.
const locales = {
    'es': es,
};

const localizer = dateFnsLocalizer({
    format,
    parse,
    startOfWeek,
    getDay,
    locales,
});

interface Appointment {
    id: number;
    appointment_at: string;
    contact: {
        first_name: string;
        last_name: string;
    };
}

interface FormattedEvent extends CalendarEvent {
    resource: Appointment;
}


interface PageProps extends GlobalPageProps {
    whatsAppChannel: ChatbotChannel | null;
    whatsAppWebChatbotChannel: ChatbotChannel | null;
}


export default function AppointmentsIndex(){
    const { chatbot } = usePage<PageProps>().props;
    const [events, setEvents] = useState<FormattedEvent[]>([]);

    const breadcrumbs = [
        { title: 'Chatbots', href: route('chatbots.index') },
        { title: chatbot.name, href: route('chatbots.edit', { chatbot: chatbot.id }) },
        { title: 'Agenda', href: route('appointments.index', { chatbot: chatbot.id }) },
    ];

    const fetchAppointments = useCallback(async (start: Date, end: Date) => {
        try {
            const response = await axios.get(route('appointments.list', {
                chatbot: chatbot.id,
                start_date: start.toISOString().slice(0, 10),
                end_date: end.toISOString().slice(0, 10),
            }));

            const appointments: Appointment[] = response.data;

            const formattedEvents: FormattedEvent[] = appointments.map((apt: Appointment) => {
                const appointmentDate = new Date(apt.appointment_at);
                return {
                    title: `${apt.contact.first_name} ${apt.contact.last_name || ''}`.trim(),
                    start: appointmentDate,
                    // Assuming a 1-hour duration for each appointment for now
                    end: new Date(appointmentDate.getTime() + 60 * 60 * 1000),
                    resource: apt,
                };
            });
            setEvents(formattedEvents);
        } catch (error) {
            console.error("Failed to fetch appointments:", error);
            // Consider adding a user-facing error message here (e.g., a toast)
        }
    }, [chatbot.id]);

    // Handler for when the calendar's visible range changes
    const handleRangeChange = useCallback((range: Date[] | { start: Date; end: Date; }) => {
        let start: Date, end: Date;

        if (Array.isArray(range)) {
            // This is for week or day view
            start = range[0];
            end = range[range.length - 1];
        } else {
            // This is for month view
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
                                messages={{
                                    next: "Siguiente",
                                    previous: "Anterior",
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
                            />
                        </div>
                    </Card>
                </div>
            </AppContentDefaultLayout>
        </AppLayout>
    );
};
