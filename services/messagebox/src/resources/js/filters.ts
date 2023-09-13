import Vue from 'vue';
import { formatDateTimeShort, formatDateTimeLong } from './utils/date';

// 3 dec. 9:10
Vue.filter('formatDateTimeShort', (value: string | null) => {
    if (!value) return '';

    const date = new Date(value);
    return formatDateTimeShort(date);
});

// 3 december 2022 om 9:10
Vue.filter('formatDateTimeLong', (value: string | null) => {
    if (!value) return '';

    const date = new Date(value);
    return formatDateTimeLong(date);
});
