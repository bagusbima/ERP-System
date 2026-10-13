/**
 * Indonesian formatting helpers shared by every module.
 */

/**
 * Format a number as Indonesian Rupiah.
 *
 * @param {number|string|null|undefined} value
 * @param {{currency?: string, decimals?: number}} [options]
 * @returns {string}
 */
export function formatCurrency(value, options = {}) {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: options.currency ?? 'IDR',
        minimumFractionDigits: options.decimals ?? 0,
        maximumFractionDigits: options.decimals ?? 0,
    }).format(Number.isFinite(amount) ? amount : 0);
}

/**
 * Format an ISO date for display in Indonesia.
 *
 * @param {string|Date|null|undefined} value
 * @returns {string}
 */
export function formatDate(value) {
    if (! value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

/**
 * Format a number with the Indonesian thousands separator.
 *
 * @param {number|string|null|undefined} value
 * @returns {string}
 */
export function formatNumber(value) {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('id-ID').format(Number.isFinite(amount) ? amount : 0);
}
