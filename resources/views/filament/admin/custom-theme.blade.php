<style>
/* Force black text and icons on amber/primary buttons in both light and dark modes */
.fi-btn {
    /* Primary/amber button text color override */
}
.fi-btn.fi-color-primary,
.fi-btn[style*="--c-400:var(--primary-400)"],
.fi-btn[style*="--c-500:var(--primary-500)"],
.fi-btn[style*="--c-600:var(--primary-600)"] {
    --fi-btn-label-color: rgb(0, 0, 0) !important;
    color: rgb(0, 0, 0) !important;
}
/* Force black color on icons inside primary buttons */
.fi-btn.fi-color-primary svg,
.fi-btn[style*="--c-400:var(--primary-400)"] svg,
.fi-btn[style*="--c-500:var(--primary-500)"] svg,
.fi-btn[style*="--c-600:var(--primary-600)"] svg {
    color: rgb(0, 0, 0) !important;
    fill: rgb(0, 0, 0) !important;
}
.fi-btn.fi-color-primary:hover,
.fi-btn[style*="--c-400:var(--primary-400)"]:hover,
.fi-btn[style*="--c-500:var(--primary-500)"]:hover,
.fi-btn[style*="--c-600:var(--primary-600)"]:hover,
.fi-btn.fi-color-primary:focus,
.fi-btn[style*="--c-400:var(--primary-400)"]:focus,
.fi-btn[style*="--c-500:var(--primary-500)"]:focus,
.fi-btn[style*="--c-600:var(--primary-600)"]:focus,
.fi-btn.fi-color-primary:active,
.fi-btn[style*="--c-400:var(--primary-400)"]:active,
.fi-btn[style*="--c-500:var(--primary-500)"]:active,
.fi-btn[style*="--c-600:var(--primary-600)"]:active {
    --fi-btn-label-color: rgb(0, 0, 0) !important;
    color: rgb(0, 0, 0) !important;
}
/* Icons in hover/focus/active states */
.fi-btn.fi-color-primary:hover svg,
.fi-btn[style*="--c-400:var(--primary-400)"]:hover svg,
.fi-btn[style*="--c-500:var(--primary-500)"]:hover svg,
.fi-btn[style*="--c-600:var(--primary-600)"]:hover svg,
.fi-btn.fi-color-primary:focus svg,
.fi-btn[style*="--c-400:var(--primary-400)"]:focus svg,
.fi-btn[style*="--c-500:var(--primary-500)"]:focus svg,
.fi-btn[style*="--c-600:var(--primary-600)"]:focus svg,
.fi-btn.fi-color-primary:active svg,
.fi-btn[style*="--c-400:var(--primary-400)"]:active svg,
.fi-btn[style*="--c-500:var(--primary-500)"]:active svg,
.fi-btn[style*="--c-600:var(--primary-600)"]:active svg {
    color: rgb(0, 0, 0) !important;
    fill: rgb(0, 0, 0) !important;
}

/* Also target any amber/warning colored buttons */
.fi-btn-color-amber,
.fi-btn-color-warning,
.fi-btn-color-primary {
    --fi-btn-label-color: rgb(0, 0, 0) !important;
    color: rgb(0, 0, 0) !important;
}
.fi-btn-color-amber svg,
.fi-btn-color-warning svg,
.fi-btn-color-primary svg {
    color: rgb(0, 0, 0) !important;
    fill: rgb(0, 0, 0) !important;
}
.fi-btn-color-amber:hover,
.fi-btn-color-warning:hover,
.fi-btn-color-primary:hover,
.fi-btn-color-amber:focus,
.fi-btn-color-warning:focus,
.fi-btn-color-primary:focus,
.fi-btn-color-amber:active,
.fi-btn-color-warning:active,
.fi-btn-color-primary:active {
    --fi-btn-label-color: rgb(0, 0, 0) !important;
    color: rgb(0, 0, 0) !important;
}
.fi-btn-color-amber:hover svg,
.fi-btn-color-warning:hover svg,
.fi-btn-color-primary:hover svg,
.fi-btn-color-amber:focus svg,
.fi-btn-color-warning:focus svg,
.fi-btn-color-primary:focus svg,
.fi-btn-color-amber:active svg,
.fi-btn-color-warning:active svg,
.fi-btn-color-primary:active svg {
    color: rgb(0, 0, 0) !important;
    fill: rgb(0, 0, 0) !important;
}

/* Target buttons with amber background classes */
.bg-amber-500,
.bg-amber-600,
.bg-primary-500,
.bg-primary-600,
.dark\:bg-amber-500,
.dark\:bg-amber-600,
.dark\:bg-primary-500,
.dark\:bg-primary-600 {
    color: rgb(0, 0, 0) !important;
}

/* Ensure this applies in dark mode as well */
.dark .fi-btn.fi-color-primary,
.dark .fi-btn[style*="--c-400:var(--primary-400)"],
.dark .fi-btn[style*="--c-500:var(--primary-500)"],
.dark .fi-btn[style*="--c-600:var(--primary-600)"] {
    --fi-btn-label-color: rgb(0, 0, 0) !important;
    color: rgb(0, 0, 0) !important;
}
.dark .fi-btn.fi-color-primary svg,
.dark .fi-btn[style*="--c-400:var(--primary-400)"] svg,
.dark .fi-btn[style*="--c-500:var(--primary-500)"] svg,
.dark .fi-btn[style*="--c-600:var(--primary-600)"] svg {
    color: rgb(0, 0, 0) !important;
    fill: rgb(0, 0, 0) !important;
}
.dark .fi-btn.fi-color-primary:hover,
.dark .fi-btn[style*="--c-400:var(--primary-400)"]:hover,
.dark .fi-btn[style*="--c-500:var(--primary-500)"]:hover,
.dark .fi-btn[style*="--c-600:var(--primary-600)"]:hover,
.dark .fi-btn.fi-color-primary:focus,
.dark .fi-btn[style*="--c-400:var(--primary-400)"]:focus,
.dark .fi-btn[style*="--c-500:var(--primary-500)"]:focus,
.dark .fi-btn[style*="--c-600:var(--primary-600)"]:focus,
.dark .fi-btn.fi-color-primary:active,
.dark .fi-btn[style*="--c-400:var(--primary-400)"]:active,
.dark .fi-btn[style*="--c-500:var(--primary-500)"]:active,
.dark .fi-btn[style*="--c-600:var(--primary-600)"]:active {
    --fi-btn-label-color: rgb(0, 0, 0) !important;
    color: rgb(0, 0, 0) !important;
}
.dark .fi-btn.fi-color-primary:hover svg,
.dark .fi-btn[style*="--c-400:var(--primary-400)"]:hover svg,
.dark .fi-btn[style*="--c-500:var(--primary-500)"]:hover svg,
.dark .fi-btn[style*="--c-600:var(--primary-600)"]:hover svg,
.dark .fi-btn.fi-color-primary:focus svg,
.dark .fi-btn[style*="--c-400:var(--primary-400)"]:focus svg,
.dark .fi-btn[style*="--c-500:var(--primary-500)"]:focus svg,
.dark .fi-btn[style*="--c-600:var(--primary-600)"]:focus svg,
.dark .fi-btn.fi-color-primary:active svg,
.dark .fi-btn[style*="--c-400:var(--primary-400)"]:active svg,
.dark .fi-btn[style*="--c-500:var(--primary-500)"]:active svg,
.dark .fi-btn[style*="--c-600:var(--primary-600)"]:active svg {
    color: rgb(0, 0, 0) !important;
    fill: rgb(0, 0, 0) !important;
}

.dark .fi-btn-color-amber,
.dark .fi-btn-color-warning,
.dark .fi-btn-color-primary {
    --fi-btn-label-color: rgb(0, 0, 0) !important;
    color: rgb(0, 0, 0) !important;
}
.dark .fi-btn-color-amber svg,
.dark .fi-btn-color-warning svg,
.dark .fi-btn-color-primary svg {
    color: rgb(0, 0, 0) !important;
    fill: rgb(0, 0, 0) !important;
}
.dark .fi-btn-color-amber:hover,
.dark .fi-btn-color-warning:hover,
.dark .fi-btn-color-primary:hover,
.dark .fi-btn-color-amber:focus,
.dark .fi-btn-color-warning:focus,
.dark .fi-btn-color-primary:focus,
.dark .fi-btn-color-amber:active,
.dark .fi-btn-color-warning:active,
.dark .fi-btn-color-primary:active {
    --fi-btn-label-color: rgb(0, 0, 0) !important;
    color: rgb(0, 0, 0) !important;
}
.dark .fi-btn-color-amber:hover svg,
.dark .fi-btn-color-warning:hover svg,
.dark .fi-btn-color-primary:hover svg,
.dark .fi-btn-color-amber:focus svg,
.dark .fi-btn-color-warning:focus svg,
.dark .fi-btn-color-primary:focus svg,
.dark .fi-btn-color-amber:active svg,
.dark .fi-btn-color-warning:active svg,
.dark .fi-btn-color-primary:active svg {
    color: rgb(0, 0, 0) !important;
    fill: rgb(0, 0, 0) !important;
}
</style>