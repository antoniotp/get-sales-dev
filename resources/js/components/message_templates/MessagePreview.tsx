import React from 'react';
import { Card } from "@/components/ui/card";
import { ExternalLink, CornerDownLeft } from 'lucide-react';
import LinkifyText from '@/components/chat/LinkifyText';
import { ButtonConfig, Template } from '@/types/message-template';

export const WhatsAppBubble = ({ children, className = '' }: { children: React.ReactNode; className?: string }) => (
    <Card className={`relative max-w-[80%] gap-1 rounded-[18px] rounded-tr-none border-0 bg-[#D9FDD3] px-4 py-2 text-[#111B21] shadow-sm ${className}`}>
        {children}
        <div className="absolute top-0 -right-1 h-0 w-0 border-t-[10px] border-r-[10px] border-t-[#D9FDD3] border-r-transparent dark:border-t-[#005c4b]" />
    </Card>
);

export const PreviewHeader = ({type, content} : {type: string, content?: string}) => {
    switch (type) {
        case 'text':
            return <div className="pb-0 text-xs font-semibold text-[#111B21]">{content}</div>;
        case 'image':
            return (
                <div className="mb-2">
                    <img src={content} alt="Header" className="ml-auto h-auto max-h-[120px] max-w-full" />
                </div>
            );
        case 'video':
            return <div className="overflow-hidden pb-0 text-xs text-[#667781]">Video: {content}</div>;
        case 'document':
            return <div className="overflow-hidden pb-2 text-xs text-[#667781]">Document: {content}</div>;
        default:
            return null;
    }
}

export const PreviewBody = ({ bodyContent }: { bodyContent: string }) => (
    <LinkifyText text={bodyContent} className="text-xs break-words whitespace-pre-wrap text-[#111B21] [&_a]:text-[#027EB5] [&_a]:underline" />
);

export const PreviewFooter = ({ footerContent }: { footerContent?: string }) => {
    if (!footerContent) return null;
    return <div className="pt-1 text-xs text-[#667781]">{footerContent}</div>;
};

export const PreviewButtons = ({ buttons }: { buttons?: ButtonConfig[] | null }) => {
    if (!buttons || buttons.length === 0) return null;
    return (
        <div className="-mx-4 -mb-2 flex flex-col gap-0 pt-2">
            {buttons.map((button, index) => {
                const isUrl = button.type === 'URL';
                const isReply = button.type === 'QUICK_REPLY';

                return (
                    <button
                        key={index}
                        type="button"
                        className="flex cursor-pointer items-center justify-center gap-1.5 rounded-none border-t border-[#E9EDEF] bg-transparent p-3 text-xs font-medium text-[#027EB5] transition-colors first:border-t last:rounded-b-[18px] hover:bg-[#F0F2F5]"
                    >
                        {isUrl && <ExternalLink className="h-3.5 w-3.5" />}
                        {isReply && <CornerDownLeft className="h-3.5 w-3.5" />}
                        <span>{button.text}</span>
                    </button>
                );
            })}
        </div>
    );
}

interface MessagePreviewProps {
    templateData:  Template;
}

const MessagePreview = ({ templateData }: MessagePreviewProps) => {
    const {
        header_type,
        header_content,
        header_variable,
        body_content,
        footer_content,
        button_config,
        variables_schema,
    } = templateData;

    const getProcessedHeader = () => {
        if (header_type !== 'text' || !header_content) return header_content;
        let processed = header_content;
        if (header_variable?.placeholder) {
            const regex = new RegExp(header_variable.placeholder.replace(/\{\{/g, '\\{\\{').replace(/}}/g, '\\}\\}'), 'g');
            processed = processed.replace(regex, `${header_variable.example || header_variable.placeholder}`);
        }
        return processed;
    }

    const getProcessedBody = () => {
        let processed = body_content;
        variables_schema?.forEach((variable) => {
            const varNumberMatch = variable.placeholder.match(/\{\{(\d+)}}/);
            const varNumber = varNumberMatch ? varNumberMatch[1] : null;
            if (varNumber) {
                const regex = new RegExp(`\\{\\{${varNumber}\\}}`, 'g');
                processed = processed.replace(regex, `${variable.example || variable.placeholder}`);
            } else {
                const regex = new RegExp(variable.placeholder.replace(/\{\{/g, '\\{\\{').replace(/}}/g, '\\}\\}'), 'g');
                processed = processed.replace(regex, `${variable.example || variable.placeholder}`);
            }
        });
        return processed;
    };

    return (
        <div
            className="mx-auto h-[550px] w-full max-w-sm bg-contain bg-center bg-no-repeat flex flex-col items-end pt-25 pr-6 gap-1"
            style={{ backgroundImage: "url('/images/chat-template.webp')" }}
        >
            <WhatsAppBubble>
                <PreviewHeader type={header_type} content={getProcessedHeader()} />
                <PreviewBody bodyContent={getProcessedBody()} />
                <PreviewFooter footerContent={footer_content} />
                <PreviewButtons buttons={button_config} />
            </WhatsAppBubble>
        </div>
    );
};

export default MessagePreview;
