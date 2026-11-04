<style>
    /* ===== Global Font ===== */
    body, .fi-body {
        font-family: 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif !important;
    }

    /* ===== Sidebar ===== */
    .fi-sidebar {
        border-inline-end-width: 0 !important;
        box-shadow: 4px 0 6px -1px rgba(0, 0, 0, 0.05);
    }
    .dark .fi-sidebar {
        box-shadow: 4px 0 6px -1px rgba(0, 0, 0, 0.2);
        background-color: rgb(17 24 39) !important;
    }
    .fi-sidebar-item a {
        border-radius: 0.625rem;
        transition: all 0.15s ease;
        margin-inline: 0.25rem;
    }
    .fi-sidebar-group-label {
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        opacity: 0.6;
    }

    /* ===== Top Bar ===== */
    .fi-topbar {
        border-bottom-width: 0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    .dark .fi-topbar {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    /* ===== Stats Overview Cards ===== */
    .fi-wi-stats-overview-stat {
        border-radius: 1rem !important;
        border-width: 0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    }
    .dark .fi-wi-stats-overview-stat {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
    }
    .dark .fi-wi-stats-overview-stat:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    /* ===== Sections / Form Cards ===== */
    .fi-section {
        border-radius: 1rem !important;
        overflow: hidden;
        border-width: 0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }
    .dark .fi-section {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    /* ===== Tables ===== */
    .fi-ta {
        border-radius: 1rem !important;
        overflow: hidden;
        border-width: 0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }
    .dark .fi-ta {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
    }
    .fi-ta-row {
        transition: background-color 0.1s ease;
    }
    .fi-ta-header-cell {
        font-weight: 700 !important;
        text-transform: uppercase;
        font-size: 0.7rem !important;
        letter-spacing: 0.04em;
        opacity: 0.7;
    }

    /* ===== Chart Widgets ===== */
    .fi-wi-chart {
        border-radius: 1rem !important;
        border-width: 0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }
    .dark .fi-wi-chart {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    /* ===== Table Widget ===== */
    .fi-wi-table {
        border-radius: 1rem !important;
        border-width: 0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }
    .dark .fi-wi-table {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    /* ===== Badges ===== */
    .fi-badge {
        font-weight: 600;
        letter-spacing: 0.01em;
        border-radius: 9999px !important;
    }

    /* ===== Buttons ===== */
    .fi-btn {
        border-radius: 0.625rem !important;
        font-weight: 600;
        transition: all 0.15s ease;
    }
    .fi-btn:hover {
        transform: translateY(-1px);
    }

    /* ===== Modal ===== */
    .fi-modal-window {
        border-radius: 1.25rem !important;
    }

    /* ===== Form Inputs ===== */
    .fi-input-wrp {
        border-radius: 0.625rem !important;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }

    /* ===== Notification Banners ===== */
    .fi-no {
        border-radius: 0.75rem !important;
    }

    /* ===== Login Page ===== */
    .fi-simple-layout {
        background: #f1f5f9 !important;
    }
    .dark .fi-simple-layout {
        background: #0f172a !important;
    }

    /* ===== Action Group Dropdown ===== */
    .fi-dropdown-panel {
        border-radius: 0.75rem !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    }
    .dark .fi-dropdown-panel {
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    /* ===== Breadcrumbs ===== */
    .fi-breadcrumbs {
        font-size: 0.8rem;
    }

    /* ===== Page Header ===== */
    .fi-header-heading {
        font-weight: 800 !important;
    }

    /* ===== Empty State ===== */
    .fi-ta-empty-state {
        padding: 3rem 1rem !important;
    }

    /* ===== Smooth scrolling ===== */
    .fi-main {
        scroll-behavior: smooth;
    }

    /* ===== Custom scrollbar ===== */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.15);
        border-radius: 3px;
    }
    .dark ::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 0, 0, 0.25);
    }
    .dark ::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.25);
    }
</style>
