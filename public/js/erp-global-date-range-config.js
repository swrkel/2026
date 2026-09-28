/**
 * ERP global date-range rule.
 *
 * enabled:
 *   true  = show the built-in Custom Range option globally.
 *   false = remove it globally.
 *
 * excludedPathPrefixes:
 *   Pages whose URL begins with one of these prefixes will not receive the
 *   global Custom Range option. MPCS is excluded by default as requested.
 *
 * This file may be edited later without changing common.js.
 */
window.ERP_DATE_RANGE_GLOBAL_CONFIG = {
    enabled: true,
    label: 'Custom Range',
    excludedPathPrefixes: [
        '/mpcs'
    ]
};
