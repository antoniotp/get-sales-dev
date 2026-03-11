import { Control, Controller, useFieldArray, useWatch } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, Trash2 } from 'lucide-react';
import { TemplateFormValues } from '@/pages/message_templates/form';
import { useTranslation } from 'react-i18next';

interface ButtonsSectionProps {
    control: Control<TemplateFormValues>;
}

export function ButtonsSection({ control }: ButtonsSectionProps) {
    const { t } = useTranslation('messageTemplates');

    const { fields, append, remove, replace } = useFieldArray({
        control,
        name: 'button_config',
    });

    const buttonConfigs = useWatch({
        control,
        name: 'button_config',
    });

    const buttonType = buttonConfigs?.[0]?.type;
    const isUrlType = buttonType === 'URL';
    const isReplyType = buttonType === 'QUICK_REPLY';
    const canAddMore = isReplyType && fields.length < 3;

    const handleTypeChange = (newType: 'QUICK_REPLY' | 'URL') => {
        // Replace all buttons with a single new button of the selected type
        replace([{ type: newType, text: '', url: '', phone_number: '' }]);
    };

    const addButton = () => {
        if (canAddMore) {
            append({ type: 'QUICK_REPLY', text: '', url: '', phone_number: '' });
        }
    };

    return (
        <div className="flex flex-col gap-4">
            <div>
                <Label htmlFor="button-type">
                    {t('form.buttons_section.label')} <span className="text-muted-foreground">{t('form.buttons_section.optional')}</span>
                </Label>
                <Select
                    value={buttonType || 'none'}
                    onValueChange={(value) => {
                        if (value === 'none') {
                            replace([]);
                        } else {
                            handleTypeChange(value as 'QUICK_REPLY' | 'URL');
                        }
                    }}
                >
                    <SelectTrigger id="button-type" className="w-full">
                        <SelectValue placeholder={t('form.buttons_section.select_placeholder')} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">{t('form.buttons_section.types.none')}</SelectItem>
                        <SelectItem value="QUICK_REPLY">{t('form.buttons_section.types.quick_reply')}</SelectItem>
                        <SelectItem value="URL">{t('form.buttons_section.types.url')}</SelectItem>
                    </SelectContent>
                </Select>
                <p className="mt-1 text-sm text-muted-foreground">
                    {isReplyType && t('form.buttons_section.notices.quick_reply')}
                    {isUrlType && t('form.buttons_section.notices.url')}
                </p>
            </div>

            {fields.length > 0 && (
                <div className="flex flex-col gap-4">
                    {fields.map((field, index) => (
                        <div key={field.id} className="relative flex flex-col gap-3 rounded-lg border p-4">
                            {fields.length > 1 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="absolute top-2 right-2"
                                    onClick={() => remove(index)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            )}

                            <div className="flex flex-col gap-2">
                                <Label htmlFor={`button-text-${index}`}>
                                    {t('form.buttons_section.button_text_label')} {fields.length > 1 ? `#${index + 1}` : ''}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Controller
                                    control={control}
                                    name={`button_config.${index}.text`}
                                    rules={{
                                        required: t('form.buttons_section.validation.text_required'),
                                        maxLength: {
                                            value: 25,
                                            message: t('form.buttons_section.validation.text_max'),
                                        },
                                    }}
                                    render={({ field: inputField, fieldState }) => (
                                        <div className="flex flex-col gap-1">
                                            <Input
                                                {...inputField}
                                                id={`button-text-${index}`}
                                                placeholder={t('form.buttons_section.placeholders.text')}
                                                maxLength={25}
                                            />
                                            {fieldState.error && (
                                                <p className="text-sm text-red-500">{fieldState.error.message}</p>
                                            )}
                                            <p className="text-xs text-muted-foreground">
                                                {inputField.value?.length || 0}/25 {t('form.buttons_section.characters')}
                                            </p>
                                        </div>
                                    )}
                                />
                            </div>

                            {isUrlType && (
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor={`button-url-${index}`}>
                                        {t('form.buttons_section.url_label')} <span className="text-red-500">*</span>
                                    </Label>
                                    <Controller
                                        control={control}
                                        name={`button_config.${index}.url`}
                                        rules={{
                                            required: isUrlType ? t('form.buttons_section.validation.url_required') : false,
                                            pattern: {
                                                value: /^https?:\/\/.+/,
                                                message: t('form.buttons_section.validation.url_invalid'),
                                            },
                                        }}
                                        render={({ field: inputField, fieldState }) => (
                                            <div className="flex flex-col gap-1">
                                                <Input
                                                    {...inputField}
                                                    id={`button-url-${index}`}
                                                    type="url"
                                                    placeholder={t('form.buttons_section.placeholders.url')}
                                                    value={inputField.value ?? ""}
                                                />
                                                {fieldState.error && (
                                                    <p className="text-sm text-red-500">{fieldState.error.message}</p>
                                                )}
                                            </div>
                                        )}
                                    />
                                </div>
                            )}
                        </div>
                    ))}

                    {canAddMore && (
                        <Button type="button" variant="outline" className="w-full" onClick={addButton}>
                            <Plus className="mr-2 h-4 w-4" />
                            {t('form.buttons_section.add_button', { current: fields.length, max: 3 })}
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}
