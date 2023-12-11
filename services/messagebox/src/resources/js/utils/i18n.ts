import i18n, { translationFiles } from '@/i18n';

type i18nFiles = typeof translationFiles[keyof typeof translationFiles];

/**
 * Type guard to ensure the passed options is an array of strings
 */
export const isOptionsStringArray = (options: any): options is string[] =>
    Array.isArray(options) && options.every((option) => typeof option === 'string');

/**
 * Get the translated options of the given enum
 *
 * @param {string} name Name of the enum
 * @param {FormDropdownOptions | string[]} options Form options or array of values to translate
 * @returns Array<{label: string, value: string}>
 */
export const getTranslatedOptions = <TName extends keyof i18nFiles['options'], TEnum extends string>(
    name: TName,
    options: Record<TEnum, string> | { value: TEnum; label: string }[] | TEnum[]
) => {
    const i18nObject = Object.fromEntries(Object.entries(i18n.t(`options.${name}`)));

    // Map the enum values to the translated values
    let values: { label: string; value: TEnum }[];
    if (isOptionsStringArray(options)) {
        // When array of strings is passed, use them as values to translate
        values = options.map((option) => ({
            value: option,
            label: i18nObject[`OPTION_${option}`] || `__${option}__`,
        }));
    } else if (Array.isArray(options)) {
        // When array of objects is passed, translate the label and keep other properties on the options
        values = options.map((option) => ({
            ...option,
            ...(typeof i18nObject[`OPTION_${option.value}`] === 'object'
                ? // When translated value is an object, use that object instead of creating a new
                  i18nObject[`OPTION_${option.value}`]
                : { label: i18nObject[`OPTION_${option.value}`] || `__${option.value}__` }),
        }));
    } else {
        values = Object.entries(options).map(([key]) => ({
            value: key as TEnum,
            label: i18nObject[`OPTION_${key}`] || `__${key}__`,
        }));
    }

    // Sort by order in i18n file
    const i18nKeys = Object.keys(i18nObject);
    return values.sort((a, b) => i18nKeys.indexOf(`OPTION_${a.value}`) - i18nKeys.indexOf(`OPTION_${b.value}`));
};
