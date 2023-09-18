import * as i18nUtil from './i18n';

enum Test {
    VALUE_option1 = 'option1',
    VALUE_option2 = 'option2',
    VALUE_option3 = 'option3',
    VALUE_option4 = 'option4',
}

const testOptions = {
    [Test.VALUE_option1]: 'Option 1',
    [Test.VALUE_option2]: 'Option 2',
    [Test.VALUE_option3]: 'Option 3',
};

jest.mock('@/i18n/languages/nl.json', () => ({
    options: {
        OTPType: {
            OPTION_option3: 'CCC',
            OPTION_option2: 'BBB',
            OPTION_option1: {
                label: 'AAA',
                description: 'test',
            },
        },
    },
}));

describe('isOptionsStringArray', () => {
    it('should return false if a non-array is passed', () => {
        expect(i18nUtil.isOptionsStringArray(123)).toBe(false);
        expect(i18nUtil.isOptionsStringArray('a')).toBe(false);
        expect(i18nUtil.isOptionsStringArray(true)).toBe(false);
    });

    it('should return false if an array with non-string values is passed', () => {
        expect(i18nUtil.isOptionsStringArray([123])).toBe(false);
        expect(i18nUtil.isOptionsStringArray(['a', 123, 'c'])).toBe(false);
    });

    it('should return true if an array only string values is passed', () => {
        expect(i18nUtil.isOptionsStringArray(['a', 'b', 'c'])).toBe(true);
    });
});

describe('getTranslatedOptions', () => {
    it('[ENUM OPTIONS] should return translated options in the order of the translation file and keep extra i18n values', () => {
        expect(i18nUtil.getTranslatedOptions('OTPType', testOptions)).toEqual([
            {
                label: 'CCC',
                value: Test.VALUE_option3,
            },
            {
                label: 'BBB',
                value: Test.VALUE_option2,
            },
            {
                label: {
                    label: 'AAA',
                    description: 'test',
                },
                value: Test.VALUE_option1,
            },
        ]);
    });

    it('[CUSTOM OBJECT OPTIONS] should translate label and add other values in case of extra i18n values', () => {
        expect(
            i18nUtil.getTranslatedOptions('OTPType', [
                {
                    value: Test.VALUE_option1,
                    label: 'abcdef',
                },
            ])
        ).toEqual([
            {
                label: 'AAA',
                description: 'test',
                value: Test.VALUE_option1,
            },
        ]);
    });

    it('[CUSTOM OBJECT OPTIONS] should default label to __value__ if missing', () => {
        expect(
            i18nUtil.getTranslatedOptions('OTPType', [
                {
                    value: Test.VALUE_option4,
                    label: 'abcdef',
                },
            ])
        ).toEqual([
            {
                label: `__${Test.VALUE_option4}__`,
                value: Test.VALUE_option4,
            },
        ]);
    });

    it('[CUSTOM STRING OPTIONS] should return the translated options of a passed string options', () => {
        expect(i18nUtil.getTranslatedOptions('OTPType', ['option3'])).toEqual([
            {
                label: 'CCC',
                value: Test.VALUE_option3,
            },
        ]);
    });

    it('[CUSTOM STRING OPTIONS] should default label to __value__ if missing', () => {
        expect(i18nUtil.getTranslatedOptions('OTPType', { nonexisting: 'nonexisting' })).toEqual([
            {
                label: '__nonexisting__',
                value: 'nonexisting',
            },
        ]);

        expect(i18nUtil.getTranslatedOptions('OTPType', ['nonexisting'])).toEqual([
            {
                label: '__nonexisting__',
                value: 'nonexisting',
            },
        ]);
    });
});
