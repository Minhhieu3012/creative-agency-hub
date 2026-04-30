<?php
if (!function_exists('renderBadge')) {
    function renderBadge(string $label, string $tone = 'primary'): string
    {
        return '<span class="badge badge-' . htmlspecialchars($tone) . '">' . htmlspecialchars($label) . '</span>';
    }
}