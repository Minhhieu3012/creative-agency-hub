<?php
/**
 * Generic Table Toolbar Component - Creative Agency Hub
 *
 * Cách dùng:
 *
 * $toolbarTitle = 'Danh sách nhân sự';
 * $toolbarSubtitle = 'Quản lý tài khoản và hồ sơ nhân sự.';
 * $toolbarSearchPlaceholder = 'Tìm kiếm...';
 * $toolbarSearchName = 'search';
 * $toolbarFilters = [
 *     [
 *         'name' => 'status',
 *         'label' => 'Trạng thái',
 *         'options' => [
 *             'all' => 'Tất cả trạng thái',
 *             'active' => 'Đang hoạt động',
 *             'inactive' => 'Tạm khóa',
 *         ],
 *         'value' => 'all',
 *     ],
 * ];
 * $toolbarActions = '<button class="btn btn-primary">+ Tạo mới</button>';
 * require __DIR__ . '/../components/table-toolbar.php';
 */

$toolbarTitle = $toolbarTitle ?? 'Danh sách dữ liệu';
$toolbarSubtitle = $toolbarSubtitle ?? '';
$toolbarSearchPlaceholder = $toolbarSearchPlaceholder ?? 'Tìm kiếm...';
$toolbarSearchName = $toolbarSearchName ?? 'search';
$toolbarSearchValue = $toolbarSearchValue ?? ($_GET[$toolbarSearchName] ?? '');
$toolbarFilters = $toolbarFilters ?? [];
$toolbarActions = $toolbarActions ?? '';
$toolbarMethod = $toolbarMethod ?? 'GET';
$toolbarAction = $toolbarAction ?? '';
$toolbarId = $toolbarId ?? 'tableToolbar';
$toolbarResetUrl = $toolbarResetUrl ?? strtok($_SERVER['REQUEST_URI'] ?? '', '?');
$toolbarExtraClass = $toolbarExtraClass ?? '';
?>

<section
    id="<?php echo htmlspecialchars($toolbarId); ?>"
    class="table-toolbar <?php echo htmlspecialchars($toolbarExtraClass); ?>"
    data-table-toolbar
>
    <div class="table-toolbar-main">
        <div class="table-toolbar-heading">
            <h2><?php echo htmlspecialchars($toolbarTitle); ?></h2>

            <?php if (!empty($toolbarSubtitle)): ?>
                <p><?php echo htmlspecialchars($toolbarSubtitle); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($toolbarActions)): ?>
            <div class="table-toolbar-actions">
                <?php echo $toolbarActions; ?>
            </div>
        <?php endif; ?>
    </div>

    <form
        class="table-toolbar-form"
        method="<?php echo htmlspecialchars($toolbarMethod); ?>"
        action="<?php echo htmlspecialchars($toolbarAction); ?>"
        data-table-toolbar-form
    >
        <label class="table-toolbar-search">
            <span>⌕</span>
            <input
                type="search"
                name="<?php echo htmlspecialchars($toolbarSearchName); ?>"
                value="<?php echo htmlspecialchars((string) $toolbarSearchValue); ?>"
                placeholder="<?php echo htmlspecialchars($toolbarSearchPlaceholder); ?>"
                data-table-toolbar-search
            >
        </label>

        <?php if (!empty($toolbarFilters)): ?>
            <div class="table-toolbar-filters">
                <?php foreach ($toolbarFilters as $filter): ?>
                    <?php
                    $filterName = $filter['name'] ?? '';
                    $filterLabel = $filter['label'] ?? '';
                    $filterValue = $filter['value'] ?? ($_GET[$filterName] ?? '');
                    $filterOptions = $filter['options'] ?? [];
                    ?>

                    <?php if (!empty($filterName) && !empty($filterOptions)): ?>
                        <label class="table-toolbar-filter">
                            <?php if (!empty($filterLabel)): ?>
                                <span><?php echo htmlspecialchars($filterLabel); ?></span>
                            <?php endif; ?>

                            <select
                                name="<?php echo htmlspecialchars($filterName); ?>"
                                data-table-toolbar-filter
                            >
                                <?php foreach ($filterOptions as $optionValue => $optionLabel): ?>
                                    <option
                                        value="<?php echo htmlspecialchars((string) $optionValue); ?>"
                                        <?php echo ((string) $filterValue === (string) $optionValue) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars((string) $optionLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="table-toolbar-buttons">
            <button class="btn btn-soft" type="submit">
                Lọc dữ liệu
            </button>

            <?php if (!empty($toolbarResetUrl)): ?>
                <a class="btn btn-light" href="<?php echo htmlspecialchars($toolbarResetUrl); ?>">
                    Đặt lại
                </a>
            <?php endif; ?>
        </div>
    </form>
</section>