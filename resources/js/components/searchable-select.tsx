import {
    Combobox,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxList,
} from '@/components/ui/combobox';
import { type ReactNode, useEffect, useMemo, useState } from 'react';

type SearchableOption = {
    value: string;
    label: string;
};

const optionString = (value: unknown): string =>
    typeof value === 'string' ? value : '';

const normalizeOptionString = (value: unknown): string =>
    optionString(value).trim().toLocaleLowerCase();

export function SearchableSelect({
    options,
    value,
    onValueChange,
    inputId,
    placeholder,
    emptyMessage = 'No matches found.',
    allowClear = false,
    allowCustomInput = false,
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
    allowCustomInput?: boolean;
    disabled?: boolean;
    invalid?: boolean;
    renderOption?: (option: SearchableOption) => ReactNode;
}) {
    const selectedOption =
        options.find((option) => optionString(option.value) === value) ?? null;
    const [inputValue, setInputValue] = useState(
        optionString(selectedOption?.label) || (allowCustomInput ? value : ''),
    );

    const normalizedOptions = useMemo(
        () =>
            options.map((option) => ({
                option,
                normalizedLabel: normalizeOptionString(option.label),
                normalizedValue: normalizeOptionString(option.value),
            })),
        [options],
    );

    useEffect(() => {
        setInputValue(
            optionString(selectedOption?.label) || (allowCustomInput ? value : ''),
        );
    }, [allowCustomInput, selectedOption, value]);

    const findMatchingOption = (nextInputValue: string): SearchableOption | null => {
        const normalizedInputValue = nextInputValue.trim().toLocaleLowerCase();

        if (normalizedInputValue === '') {
            return null;
        }

        return (
            normalizedOptions.find(
                ({ normalizedLabel, normalizedValue }) =>
                    normalizedLabel === normalizedInputValue ||
                    normalizedValue === normalizedInputValue,
            )?.option ?? null
        );
    };

    const restoreDisplayValue = () => {
        setInputValue(
            optionString(selectedOption?.label) || (allowCustomInput ? value : ''),
        );
    };

    const commitInputValue = (nextInputValue: string) => {
        const matchedOption = findMatchingOption(nextInputValue);

        if (matchedOption) {
            onValueChange(optionString(matchedOption.value));
            setInputValue(optionString(matchedOption.label));

            return;
        }

        if (allowCustomInput) {
            onValueChange(nextInputValue.trim());
            setInputValue(nextInputValue.trim());

            return;
        }

        restoreDisplayValue();
    };

    return (
        <Combobox
            items={options}
            itemToStringValue={(option) => optionString(option?.label)}
            value={selectedOption}
            onValueChange={(option) => onValueChange(optionString(option?.value))}
            inputValue={inputValue}
            onInputValueChange={setInputValue}
        >
            <ComboboxInput
                id={inputId}
                placeholder={placeholder}
                aria-invalid={invalid || undefined}
                showClear={allowClear}
                disabled={disabled}
                onBlur={(event) => {
                    commitInputValue(event.target.value);
                }}
            />
            <ComboboxContent>
                <ComboboxEmpty>{emptyMessage}</ComboboxEmpty>
                <ComboboxList>
                    {(option) => (
                        <ComboboxItem
                            key={optionString(option.value)}
                            value={option}
                        >
                            {renderOption
                                ? renderOption(option)
                                : optionString(option.label)}
                        </ComboboxItem>
                    )}
                </ComboboxList>
            </ComboboxContent>
        </Combobox>
    );
}
