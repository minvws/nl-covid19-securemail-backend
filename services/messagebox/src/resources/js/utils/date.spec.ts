import i18n from '@/i18n';
import { Locales } from '@/i18n/locales';
import * as date from './date';

jest.unmock('@/i18n');

it('should throw an error when a translation is missing', () => {
    // Disable i18n warn of missing translation key
    jest.spyOn(console, 'warn').mockImplementation(() => {});

    const datetime = new Date('2022-06-03T09:34:00');
    i18n.locale = 'nonexisting';
    expect(() => date.formatDateTimeShort(datetime)).toThrowError('Locale nonexisting not found in date-fns/locale');
});

describe('parseDate', () => {
    it('should return null if the date is invalid', () => {
        expect(date.parseDate('aaa')).toBe(null);
        expect(date.parseDate('2022-01-1T00:00:00')).toBe(null);
    });

    it('should return Date object is valid', () => {
        expect(date.parseDate('2022-06-03T09:34:00')?.toISOString()).toEqual('2022-06-03T09:34:00.000Z');
    });
});

describe('formatDateTimeShort', () => {
    it('should return the day (without leading zero), shortened month, hour without leading zero and minutes (d MMM H:mm)', () => {
        const datetime = new Date('2022-06-03T09:34:00');
        i18n.locale = Locales.NL;
        expect(date.formatDateTimeShort(datetime)).toBe('3 jun. 9:34');
    });

    it('should translate when switching locale', () => {
        i18n.locale = Locales.EN;
        const datetime = new Date('2022-06-03T09:34:00');
        expect(date.formatDateTimeShort(datetime)).toBe('3 Jun 9:34');
    });
});

describe('formatDateTimeLong', () => {
    it('should return the day (without leading zero), full month, full year, "om", hour without leading zero and minutes (d MMMM yyyy om H:mm)', () => {
        const datetime = new Date('2022-06-03T09:34:00');
        i18n.locale = Locales.NL;
        expect(date.formatDateTimeLong(datetime)).toBe('3 juni 2022 om 9:34');
    });

    it('should translate when switching locale', async () => {
        const datetime = new Date('2022-06-03T09:34:00');
        i18n.locale = Locales.EN;
        expect(date.formatDateTimeLong(datetime)).toBe('3 June 2022 at 9:34');
    });
});
