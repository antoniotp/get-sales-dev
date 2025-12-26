import React from 'react';
import { Button } from '@/components/ui/button';
import { type View, type ToolbarProps, type Event } from 'react-big-calendar';
import { ChevronLeft, ChevronRight, PlusCircle } from 'lucide-react';

// Define the type for the custom onCreate prop, making it generic
interface CustomToolbarProps<TEvent extends Event = Event> extends ToolbarProps<TEvent> {
    onCreate: () => void;
}

export const CalendarToolbar = <TEvent extends Event>({
    label,
    localizer,
    onNavigate,
    onView,
    view,
    views,
    onCreate,
}: CustomToolbarProps<TEvent>) => {
    const navigate = (action: 'PREV' | 'NEXT' | 'TODAY') => {
        onNavigate(action);
    };

    const handleView = (view: View) => {
        onView(view);
    };

    return (
        <div className="flex justify-between items-center mb-4 p-2 border-b">
            {/* Left side: Nav buttons */}
            <div className="flex items-center gap-2">
                <Button variant="ghost" size="icon" onClick={() => navigate('PREV')} title={String(localizer.messages.previous)}>
                    <ChevronLeft className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => navigate('TODAY')}>
                    {localizer.messages.today}
                </Button>
                <Button variant="ghost" size="icon" onClick={() => navigate('NEXT')} title={String(localizer.messages.next)}>
                    <ChevronRight className="h-4 w-4" />
                </Button>
            </div>

            {/* Center: Label */}
            <span className="text-lg font-bold capitalize text-center">
                {label}
            </span>

            {/* Right side: View buttons and Create */}
            <div className="flex items-center gap-4">
                <div className="flex items-center gap-1 rounded-md border p-1">
                    {(views as string[]).map((viewName) => (
                        <Button
                            key={viewName}
                            onClick={() => handleView(viewName as View)}
                            variant={view === viewName ? 'secondary' : 'ghost'}
                            size="sm"
                            className="h-7 capitalize"
                        >
                            {String(localizer.messages[viewName as keyof typeof localizer.messages])}
                        </Button>
                    ))}
                </div>
                <Button onClick={onCreate}>
                    <PlusCircle className="mr-2 h-4 w-4" />
                    Crear
                </Button>
            </div>
        </div>
    );
};
