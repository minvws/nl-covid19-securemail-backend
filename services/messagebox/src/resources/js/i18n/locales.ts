export enum Locales {
    NL = 'nl',
    EN = 'en',
}

// Type guard: check if locale is valid, if so: then it will be typed as Locales
export function isValidLocale(locale: string): locale is Locales {
    return Object.values(Locales).includes(locale as Locales);
}
