import React, { useState, useEffect, useMemo } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Loader2 } from "lucide-react";
import axios from 'axios';
import { ButtonConfig, HeaderType } from '@/types/message-template';
import { WhatsAppBubble, PreviewBody, PreviewHeader, PreviewFooter, PreviewButtons } from '@/components/message_templates/MessagePreview';

interface Template {
    id: number;
    name: string;
    header_type: HeaderType;
    display_name: string;
    variable_mappings: {
        header?: { placeholder: string; source: string; label: string };
        body: Array<{ placeholder: string; source: string; label: string }>;
    } | null;
    button_config?: ButtonConfig[] | null;
}

interface Props {
    isOpen: boolean;
    onClose: () => void;
    chatbotId: number;
    chatbotChannelId: number
    contactId: number | null;
    onSent: (message: string) => void;
    conversationId: number
}

export default function TemplateMessageSelector({ isOpen, onClose, chatbotId, chatbotChannelId, contactId, onSent, conversationId }: Props) {
    const [templates, setTemplates] = useState<Template[]>([]);
    const [selectedTemplateId, setSelectedTemplateId] = useState<string>('');
    const [manualValues, setManualValues] = useState<Record<string, string>>({});
    const [preview, setPreview] = useState<{ header: string; body: string; footer: string } | null>(null);
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

    // Identify Manual Variables
    const manualVariables = useMemo(() => {
        if (!selectedTemplate?.variable_mappings) return [];
        const all = [...(selectedTemplate.variable_mappings.body || [])];
        if (selectedTemplate.variable_mappings.header) {
            all.push(selectedTemplate.variable_mappings.header);
        }
        return all.filter(m => m.source === 'manual');
    }, [selectedTemplate]);

    // Resolve Preview when selection or manual values change
    useEffect(() => {
        if (selectedTemplate) {
            const timer = setTimeout(() => {
                axios.post(route('message-templates.resolve-preview', { template: selectedTemplate.id }), {
                    contact_id: contactId,
                    manual_values: manualValues
                }).then(res => setPreview(res.data.rendered));
            }, 500); // Debounce to avoid too many requests
            return () => clearTimeout(timer);
        }
    }, [selectedTemplate, manualValues, contactId]);

    const handleSend = async () => {
        if (!selectedTemplate) return;
        setIsSending(true);
        try {
            const response = await axios.post(route('chats.messages.send-template', { conversation: conversationId }), {
                template_id: selectedTemplate.id,
                manual_values: manualValues
            });
            onSent(response.data.message);
            onClose();
        } catch (error) {
            console.error(error);
        } finally {
            setIsSending(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[50%]">
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

                    {/* Manual Inputs */}
                    {manualVariables.length > 0 && (
                        <div className="space-y-3 p-4 border rounded-lg bg-gray-50 dark:bg-gray-900">
                            <p className="text-sm font-semibold mb-2">Required information:</p>
                            {manualVariables.map(v => {
                                // Remove "Manual: " text if exists
                                const cleanLabel = v.label.replace(/^Manual:\s*/i, '');
                                return (
                                    <div key={v.placeholder} className="flex items-center gap-4">
                                        <Label className="text-sm min-w-[120px] text-right">{cleanLabel}</Label>
                                        <Input
                                            placeholder={`Value for ${v.placeholder}`}
                                            className="flex-1"
                                            onChange={(e) => setManualValues(prev => ({
                                                ...prev,
                                                [v.placeholder.replace(/[{}]/g, '')]: e.target.value
                                            }))}
                                        />
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    {/* Preview Area */}
                    {preview && (
                        <div className="space-y-2">
                            <Label>Preview</Label>
                            <div className="w-full bg-[#E5DDD5] p-6 flex justify-end rounded-lg">
                                <WhatsAppBubble>
                                    <PreviewHeader type={selectedTemplate?.header_type || 'text'} content={preview.header} />
                                    <PreviewBody bodyContent={preview.body} />
                                    <PreviewFooter footerContent={preview.footer} />
                                    <PreviewButtons buttons={selectedTemplate?.button_config || []} />
                                </WhatsAppBubble>
                            </div>
                        </div>
                    )}
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
