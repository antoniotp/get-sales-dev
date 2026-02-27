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
    const [manualValues, setManualValues] = useState<{
        header: Record<string, string>;
        body: Record<string, string>;
    }>({header: {}, body: {}});
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
        if (!selectedTemplate?.variable_mappings) return { header: [], body: []};

        const header = selectedTemplate.variable_mappings.header?.source === 'manual'
            ? [selectedTemplate.variable_mappings.header]
            : [];

        const body = (selectedTemplate.variable_mappings.body || [])
            .filter(m => m.source === 'manual');

        return { header, body };
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
                        <Select
                            value={selectedTemplateId}
                            onValueChange={val => {
                                setSelectedTemplateId(val);
                                setManualValues({ header: {}, body: {} });
                                setPreview(null);
                            }}
                        >
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
                    <div className="space-y-3 p-4 border rounded-lg bg-gray-50 dark:bg-gray-900">
                        <p className="text-sm font-semibold mb-2">Required information:</p>
                        {/* Header Manual Vars */}
                        {manualVariables.header.map((v) => {
                            // Remove "Manual: " text if exists
                            const cleanLabel = v.label.replace(/^Manual:\s*/i, '');
                            return (
                                <div key={`header-${v.placeholder}`} className="flex items-center gap-4">
                                    <Label className="min-w-[120px] text-right text-sm">Header: {cleanLabel}</Label>
                                    <Input
                                        placeholder={`Value for ${v.placeholder}`}
                                        className="flex-1"
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            setManualValues((prev) => ({
                                                ...prev,
                                                header: {
                                                    ...prev.header,
                                                    [v.placeholder.replace(/[{}]/g, '')]: val,
                                                },
                                            }));
                                        }}
                                    />
                                </div>
                            );
                        })}

                        {/* Body Manual Vars */}
                        {manualVariables.body.map(v => {
                            const cleanLabel = v.label.replace(/^Manual:\s*/i, '');
                            return (
                                <div key={`body-${v.placeholder}`} className="flex items-center gap-4">
                                    <Label className="text-sm min-w-[120px] text-right">Body: {cleanLabel}</Label>
                                    <Input
                                        placeholder={`Value for ${v.placeholder}`}
                                        className="flex-1"
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            setManualValues(prev => ({
                                                ...prev,
                                                body: {
                                                    ...prev.body,
                                                    [v.placeholder.replace(/[{}]/g, '')]: val
                                                }
                                            }));
                                        }}
                                    />
                                </div>
                            )
                        })}
                    </div>

                    {/* Preview Area */}
                    {preview && (
                        <div className="space-y-2">
                            <Label>Preview</Label>
                            <div className="flex w-full justify-end rounded-lg bg-[#E5DDD5] p-6">
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
