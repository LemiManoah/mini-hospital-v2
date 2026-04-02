import {
    Combobox,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxList,
} from '@/components/ui/combobox';
import { type ReactNode } from 'react';

type SearchableOption = {
    value: string;
    label: string;
};

export function SearchableSelect({
    options,
    value,
    onValueChange,
    placeholder,
    emptyMessage = 'No matches found.',
    allowClear = false,
    disabled = false,
    invalid = false,
    renderOption,
}: {
    options: SearchableOption[];
    value: string;
    onValueChange: (value: string) => void;
    placeholder: string;
    emptyMessage?: string;
    allowClear?: boolean;
    disabled?: boolean;
    invalid?: boolean;
    renderOption?: (option: SearchableOption) => ReactNode;
}) {
    const selectedOption =
        options.find((option) => option.value === value) ?? null;

    return (
        <Combobox
            items={options}
            itemToStringValue={(option) => option.label}
            value={selectedOption}
            onValueChange={(option) => onValueChange(option?.value ?? '')}
        >
            <ComboboxInput
                placeholder={placeholder}
                aria-invalid={invalid || undefined}
                showClear={allowClear}
                disabled={disabled}
            />
            <ComboboxContent>
                <ComboboxEmpty>{emptyMessage}</ComboboxEmpty>
                <ComboboxList>
                    {(option) => (
                        <ComboboxItem key={option.value} value={option}>
                            {renderOption ? renderOption(option) : option.label}
                        </ComboboxItem>
                    )}
                </ComboboxList>
            </ComboboxContent>
        </Combobox>
    );
}
