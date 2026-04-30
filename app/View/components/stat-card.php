<?php
if (!function_exists('renderStatCard')) {
    function renderStatCard(array $card): string
    {
        $title = htmlspecialchars($card['title'] ?? 'Chỉ số');
        $value = htmlspecialchars($card['value'] ?? '0');
        $note = htmlspecialchars($card['note'] ?? '');
        $icon = htmlspecialchars($card['icon'] ?? '▦');
        $tone = htmlspecialchars($card['tone'] ?? 'primary');

        ob_start();
        ?>
        <article class="stat-card stat-card-<?php echo $tone; ?>">
            <div class="stat-card-icon"><?php echo $icon; ?></div>
            <div class="stat-card-body">
                <span><?php echo $title; ?></span>
                <strong><?php echo $value; ?></strong>
                <?php if ($note !== ''): ?>
                    <small><?php echo $note; ?></small>
                <?php endif; ?>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }
}