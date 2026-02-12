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

interface ButtonsSectionProps {
    control: Control<TemplateFormValues>;
}

export function ButtonsSection({ control }: ButtonsSectionProps) {
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
                    Buttons <span className="text-muted-foreground">(optional)</span>
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
                        <SelectValue placeholder="Select a button type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">No buttons</SelectItem>
                        <SelectItem value="QUICK_REPLY">Quick Reply</SelectItem>
                        <SelectItem value="URL">URL</SelectItem>
                    </SelectContent>
                </Select>
                <p className="mt-1 text-sm text-muted-foreground">
                    {isReplyType && 'You can add up to 3 quick reply buttons'}
                    {isUrlType && 'You can only add 1 URL button'}
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
                                    Button text {fields.length > 1 ? `#${index + 1}` : ''}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Controller
                                    control={control}
                                    name={`button_config.${index}.text`}
                                    rules={{
                                        required: 'Button text is required',
                                        maxLength: {
                                            value: 25,
                                            message: 'Text cannot exceed 25 characters',
                                        },
                                    }}
                                    render={({ field: inputField, fieldState }) => (
                                        <div className="flex flex-col gap-1">
                                            <Input
                                                {...inputField}
                                                id={`button-text-${index}`}
                                                placeholder="e.g., Learn more, Contact us, etc."
                                                maxLength={25}
                                            />
                                            {fieldState.error && (
                                                <p className="text-sm text-red-500">{fieldState.error.message}</p>
                                            )}
                                            <p className="text-xs text-muted-foreground">
                                                {inputField.value?.length || 0}/25 characters
                                            </p>
                                        </div>
                                    )}
                                />
                            </div>

                            {isUrlType && (
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor={`button-url-${index}`}>
                                        URL <span className="text-red-500">*</span>
                                    </Label>
                                    <Controller
                                        control={control}
                                        name={`button_config.${index}.url`}
                                        rules={{
                                            required: isUrlType ? 'URL is required' : false,
                                            pattern: {
                                                value: /^https?:\/\/.+/,
                                                message: 'Must be a valid URL (http:// or https://)',
                                            },
                                        }}
                                        render={({ field: inputField, fieldState }) => (
                                            <div className="flex flex-col gap-1">
                                                <Input
                                                    {...inputField}
                                                    id={`button-url-${index}`}
                                                    type="url"
                                                    placeholder="https://example.com"
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
                            Add another button ({fields.length}/3)
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}
