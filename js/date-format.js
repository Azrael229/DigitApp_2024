'use strict';

// Normaliza todas las fechas visibles de DigitApp 2024 al orden AAAA-MM-DD.
window.DigitAppDate = {
    date(value) {
        if (!value) { return '—'; }
        const match = String(value).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? `${match[1]}-${match[2]}-${match[3]}` : String(value);
    },
    dateTime(value) {
        if (!value) { return '—'; }
        const text = String(value).trim();
        const date = this.date(text);
        const time = text.match(/[ T](\d{2}:\d{2})(?::\d{2})?/);
        return time ? `${date} ${time[1]}` : date;
    }
};
