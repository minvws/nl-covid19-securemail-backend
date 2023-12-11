import i18n from '@/i18n';
import { isValidLocale, Locales } from '@/i18n/locales';
import { isValid, parseJSON } from 'date-fns';
import dateFnsFormat from 'date-fns/format';
import * as dateFnsLocale from 'date-fns/locale';

// Require all locales to have a translation file
const translations: Record<Locales, Locale> = {
    [Locales.EN]: dateFnsLocale.enGB,
    [Locales.NL]: dateFnsLocale.nl,
};

const getDateFnsLocale = () => {
    const locale = i18n.locale;

    if (!isValidLocale(locale)) {
        throw new Error(`Locale ${i18n.locale} not found in date-fns/locale`);
    }

    return translations[locale];
};

export const parseDate = (dateString: string) => {
    const date = parseJSON(dateString);
    return isValid(date) ? date : null;
};

export const formatDateTimeShort = (date: Date) =>
    dateFnsFormat(date, i18n.t('utils.date.datetimeShort').toString(), { locale: getDateFnsLocale() });
export const formatDateTimeLong = (date: Date) =>
    dateFnsFormat(date, i18n.t('utils.date.datetimeLong').toString(), { locale: getDateFnsLocale() });
