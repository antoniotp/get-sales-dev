import React from 'react';
import { Card } from "@/components/ui/card";
import { ExternalLink, CornerDownLeft } from 'lucide-react';
import LinkifyText from '@/components/chat/LinkifyText';
import { Template } from '@/types/message-template';


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

    const renderHeader = () => {
        if (!header_content) return null;

        switch (header_type) {
            case 'text': {
                let processedHeader = header_content;
                if (header_variable && header_variable.placeholder) {
                    const regex = new RegExp(
                        header_variable.placeholder.replace(/\{\{/g, '\\{\\{').replace(/}}/g, '\\}\\}'),
                        'g'
                    );
                    processedHeader = processedHeader.replace(
                        regex,
                        `${header_variable.example || header_variable.placeholder}`
                    );
                }
                return (
                    <div className="text-xs font-semibold pb-0 text-[#111B21]">
                        {processedHeader}
                    </div>
                );
            }
            case 'image':
                return (
                    <div className="mb-2">
                        <img src={header_content} alt="Header" className="max-w-full h-auto max-h-[120px] ml-auto" />
                    </div>
                );
            case 'video':
                return (
                    <div className="text-xs text-[#667781] pb-0 overflow-hidden">
                        Video: {header_content}
                    </div>
                );
            case 'document':
                return (
                    <div className="text-xs text-[#667781] pb-2 overflow-hidden">
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
                className="text-xs break-words whitespace-pre-wrap text-[#111B21] [&_a]:text-[#027EB5] [&_a]:underline"
            />
        );
    };

    const renderFooter = () => {
        if (!footer_content) return null;
        return (
            <div className="pt-1 text-xs text-[#667781]">
                {footer_content}
            </div>
        );
    };

    const renderButtons = () => {
        if (!button_config || button_config.length === 0) return null;
        return (
            <div className="flex flex-col gap-0 pt-2 -mx-4 -mb-2">
                {button_config.map((button, index) => {
                    const isUrl = button.type === 'URL';
                    const isReply = button.type === 'QUICK_REPLY';

                    return (
                        <button
                            key={index}
                            type="button"
                            className="flex items-center justify-center gap-1.5 text-xs font-medium p-3 bg-transparent text-[#027EB5] hover:bg-[#F0F2F5] border-t border-[#E9EDEF] rounded-none first:border-t last:rounded-b-[18px] transition-colors cursor-pointer"
                        >
                            {isUrl && <ExternalLink className="w-3.5 h-3.5" />}
                            {isReply && <CornerDownLeft className="w-3.5 h-3.5" />}
                            <span>{button.text}</span>
                        </button>
                    );
                })}
            </div>
        );
    };

    return (
        <div
            className="mx-auto h-[550px] w-full max-w-sm bg-contain bg-center bg-no-repeat flex flex-col items-end pt-25 pr-6 gap-1"
            style={{ backgroundImage: "url('/images/chat-template.webp')" }}
        >
            <Card className="max-w-[80%] rounded-[18px] gap-1 bg-[#D9FDD3] px-4 py-2 text-[#111B21] shadow-sm border-0">
                {renderHeader()}
                {renderBody()}
                {renderFooter()}
                {renderButtons()}
            </Card>
        </div>
    );
};

export default MessagePreview;
