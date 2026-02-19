import React, { useState, useEffect, useMemo } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import { Loader2 } from "lucide-react";
import axios from 'axios';

interface Template {
    id: number;
    name: string;
    display_name: string;
    variable_mappings: {
        header?: { placeholder: string; source: string; label: string };
        body: Array<{ placeholder: string; source: string; label: string }>;
    } | null;
}

interface Props {
    isOpen: boolean;
    onClose: () => void;
    chatbotId: number;
    chatbotChannelId: number
    onSent: (message: string) => void;
}

export default function TemplateMessageSelector({ isOpen, onClose, chatbotId, chatbotChannelId }: Props) {
    const [templates, setTemplates] = useState<Template[]>([]);
    const [selectedTemplateId, setSelectedTemplateId] = useState<string>('');
    const [isLoading, setIsLoading] = useState(false);
    const [isSending, setIsSending] = useState(false);

    // Fetch Approved Templates
    useEffect(() => {
        if (isOpen) {
            setIsLoading(true);
            axios.get(route('message-templates.approved', { chatbot: chatbotId, chatbot_channel_id: chatbotChannelId }))
                .then(res => setTemplates(res.data))
                .finally(() => setIsLoading(false));
        }
    }, [isOpen, chatbotId, chatbotChannelId]);

    const selectedTemplate = useMemo(() =>
            templates.find(t => t.id.toString() === selectedTemplateId),
        [templates, selectedTemplateId]);

    const handleSend = async () => {
        if (!selectedTemplate) return;
        setIsSending(true);
        console.log('sending template:', selectedTemplate);
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Send Message Template</DialogTitle>
                    <DialogDescription></DialogDescription>
                </DialogHeader>
                <div className="space-y-4 py-4">
                    {/* Template Selection */}
                    <div className="space-y-2">
                        <Label>Select a Template</Label>
                        <Select value={selectedTemplateId} onValueChange={setSelectedTemplateId}>
                            <SelectTrigger>
                                <SelectValue placeholder={isLoading ? 'Loading...' : 'Choose template'} />
                            </SelectTrigger>
                            <SelectContent>
                                {templates.map((t) => (
                                    <SelectItem key={t.id} value={t.id.toString()}>
                                        {t.display_name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button disabled={!selectedTemplate || isSending} onClick={handleSend} className="btn-whatsapp">
                        {isSending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Send Template
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
