import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { type ReactNode } from 'react';

type SearchableOption = {
    value: string;
    label: string;
};

const CLEAR_VALUE = '__clear_selection__';

const optionString = (value: unknown): string =>
    typeof value === 'string' ? value : '';

export function SearchableSelect({
    options,
    value,
    onValueChange,
    inputId,
    placeholder,
    emptyMessage = 'No options available.',
    allowClear = false,
    disabled = false,
    invalid = false,
    renderOption,
}: {
    options: SearchableOption[];
    value: string;
    onValueChange: (value: string) => void;
    inputId?: string;
    placeholder: string;
    emptyMessage?: string;
    allowClear?: boolean;
    disabled?: boolean;
    invalid?: boolean;
    renderOption?: (option: SearchableOption) => ReactNode;
}) {
    return (
        <Select
            value={value === '' ? undefined : value}
            onValueChange={(nextValue) =>
                onValueChange(nextValue === CLEAR_VALUE ? '' : nextValue)
            }
            disabled={disabled}
        >
            <SelectTrigger
                id={inputId}
                className="w-full"
                aria-invalid={invalid || undefined}
            >
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {allowClear && value !== '' ? (
                    <SelectGroup>
                        <SelectItem value={CLEAR_VALUE}>Clear selection</SelectItem>
                    </SelectGroup>
                ) : null}

                {options.length === 0 ? (
                    <SelectGroup>
                        <SelectLabel>{emptyMessage}</SelectLabel>
                    </SelectGroup>
                ) : (
                    <SelectGroup>
                        {options.map((option) => (
                            <SelectItem
                                key={optionString(option.value)}
                                value={optionString(option.value)}
                            >
                                {renderOption
                                    ? renderOption(option)
                                    : optionString(option.label)}
                            </SelectItem>
                        ))}
                    </SelectGroup>
                )}
            </SelectContent>
        </Select>
    );
}
