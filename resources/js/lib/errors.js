/**
 * Helpers for turning an axios failure into something worth showing a user.
 *
 * The API answers with `{"success": false, "message": "...", "errors": {...}}`;
 * without these, every 422 collapses into the same generic sentence.
 */

/**
 * The first validation message of every field the API complained about.
 *
 * @param {unknown} exception
 * @returns {Record<string, string>}
 */
export function fieldErrors(exception) {
    const errors = exception?.response?.data?.errors;

    if (! errors || typeof errors !== 'object') {
        return {};
    }

    const entries = Object.entries(errors)
        .map(([field, messages]) => [
            field,
            Array.isArray(messages) ? (messages[0] ?? '') : String(messages),
        ])
        .filter(([, message]) => message !== '');

    return Object.fromEntries(entries);
}

/**
 * The most specific message the API returned, or null when it sent nothing.
 *
 * @param {unknown} exception
 * @param {string} fallback
 * @returns {string|null}
 */
export function readableError(exception, fallback = 'Terjadi kesalahan pada server.') {
    const payload = exception?.response?.data;

    if (! payload) {
        return null;
    }

    const first = Object.values(payload.errors ?? {}).flat()[0];

    return first ?? payload.message ?? fallback;
}
