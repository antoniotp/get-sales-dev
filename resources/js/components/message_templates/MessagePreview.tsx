import React from 'react';
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import LinkifyText from '@/components/chat/LinkifyText';

interface ButtonConfig {
    type: 'reply' | 'url' | 'call';
    text: string;
    url?: string;
}

interface MessagePreviewProps {
    templateData: {
        name: string;
        language: string;
        header_type: 'none' | 'text' | 'image' | 'video' | 'document';
        header_content?: string;
        body_content: string;
        footer_content?: string;
        button_config?: Array<ButtonConfig>;
        variables_schema?: Array<{ placeholder: string; example?: string }> | null;
    };
}

const MessagePreview = ({ templateData }: MessagePreviewProps) => {
    const {
        header_type,
        header_content,
        body_content,
        footer_content,
        button_config,
        variables_schema,
    } = templateData;

    const renderHeader = () => {
        if (!header_content) return null;

        switch (header_type) {
            case 'text':
                return (
                    <div className="font-semibold pb-2 border-b border-[#3B82F6]">
                        {header_content}
                    </div>
                );
            case 'image':
                return (
                    <div className="pb-2 border-b border-[#3B82F6]">
                        <img src={header_content} alt="Header" className="max-w-full h-auto rounded-md" />
                    </div>
                );
            case 'video':
                return (
                    <div className="text-sm text-gray-200 pb-2 border-b border-[#3B82F6]">
                        Video: {header_content}
                    </div>
                );
            case 'document':
                return (
                    <div className="text-sm text-gray-200 pb-2 border-b border-[#3B82F6]">
                        Document: {header_content}
                    </div>
                );
            default:
                return null;
        }
    };

    const renderBody = () => {
        let processedBody = body_content;
        variables_schema?.forEach(variable => {
            const varNumberMatch = variable.placeholder.match(/\{\{(\d+)}}/);
            const varNumber = varNumberMatch ? varNumberMatch[1] : null;

            if (varNumber) {
                const regex = new RegExp(`\\{\\{${varNumber}\\}}`, 'g');
                processedBody = processedBody.replace(regex, `${variable.example || variable.placeholder}`);
            } else {
                const regex = new RegExp(variable.placeholder.replace(/\{\{/g, '\\{\\{').replace(/}}/g, '\\}\\}'), 'g');
                processedBody = processedBody.replace(regex, `${variable.example || variable.placeholder}`);
            }
        });
        return (
            <LinkifyText
                text={processedBody}
                className="text-sm break-words whitespace-pre-wrap [&_a]:text-[#93C5FD] [&_a]:underline"
            />
        );
    };

    const renderFooter = () => {
        if (!footer_content) return null;
        return (
            <div className="pt-2 text-xs text-[#BFDBFE] border-t border-[#3B82F6]">
                {footer_content}
            </div>
        );
    };

    const renderButtons = () => {
        if (!button_config || button_config.length === 0) return null;
        return (
            <div className="flex flex-col gap-1 pt-2 border-t border-[#3B82F6]">
                {button_config.map((button, index) => (
                    <Badge key={index} className="justify-center text-xs p-1 bg-[#1D4ED8] text-white hover:bg-[#1D4ED8]">
                        {button.text} {button.type === 'url' && `(URL: ${button.url})`}
                    </Badge>
                ))}
            </div>
        );
    };

    return (
        <div className="mt-4 mb-4 flex justify-end">
            <Card className="max-w-[70%] rounded-[18px] bg-[#2563EB] text-[#F9FAFB] shadow-md px-4 py-2">

                {renderHeader()}
                {renderBody()}
                {renderFooter()}
                {renderButtons()}
            </Card>
        </div>
    );
};

export default MessagePreview;
