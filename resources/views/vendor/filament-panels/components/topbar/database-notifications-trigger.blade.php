@props([
    'unreadNotificationsCount',
])

<x-filament::icon-button
    color="gray"
    icon="heroicon-o-bell"
    icon-alias="panels::topbar.open-database-notifications-button"
    icon-size="lg"
    :label="__('filament-panels::layout.actions.open_database_notifications.label')"
    :badge="$unreadNotificationsCount ? $unreadNotificationsCount : null"
    badge-color="danger"
    class="relative"
/>
