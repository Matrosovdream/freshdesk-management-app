const DATE_OPTIONS = { year: 'numeric', month: '2-digit', day: '2-digit' };
const DATETIME_OPTIONS = {
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: 'numeric', minute: '2-digit', hour12: true,
};

export function formatDate(value, fallback = '—') {
    if (value === null || value === undefined || value === '') return fallback;

    const d = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(d.getTime())) return value;

    const hasTime = typeof value === 'string'
        ? /T|\d{1,2}:\d{2}/.test(value)
        : (d.getHours() !== 0 || d.getMinutes() !== 0 || d.getSeconds() !== 0);

    return d.toLocaleString('en-US', hasTime ? DATETIME_OPTIONS : DATE_OPTIONS);
}
