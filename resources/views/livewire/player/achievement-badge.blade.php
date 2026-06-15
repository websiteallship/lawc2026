<x-filament::icon-button
    icon="heroicon-o-trophy"
    tag="a"
    href="/player/achievements"
    label="Danh hiệu & Nhiệm vụ"
    color="gray"
    tooltip="Danh hiệu & Nhiệm vụ"
    :badge="$unreadCount > 0 ? ($unreadCount > 99 ? '99+' : $unreadCount) : null"
    badge-color="danger"
    wire:poll.30s="loadUnreadCount"
/>
