$ErrorActionPreference = "Stop"

$Root = "D:\LTW_2026\creative-agency-hub"

Write-Host "== Creative Agency Hub - Refactor Phase 1 ==" -ForegroundColor Cyan
Write-Host "Root: $Root" -ForegroundColor Gray

if (!(Test-Path $Root)) {
    throw "Project root not found: $Root"
}

$DirectoriesToCreate = @(
    "app\View\admin",
    "app\View\admin\auth",
    "app\View\admin\dashboard",
    "app\View\admin\accounts",
    "app\View\admin\system",

    "app\View\staff",
    "app\View\staff\auth",
    "app\View\staff\dashboard",
    "app\View\staff\hrm",
    "app\View\staff\tasks",
    "app\View\staff\payroll",

    "app\View\client",
    "app\View\client\auth",

    "app\Controllers\Admin",
    "app\Controllers\Client",
    "app\Controllers\Project",

    "app\Services",
    "app\Services\Auth",
    "app\Services\HRM",
    "app\Services\Project",
    "app\Services\Task",
    "app\Services\Payroll",
    "app\Services\Core",

    "storage",
    "storage\logs",
    "storage\cache",

    "routes"
)

foreach ($Directory in $DirectoriesToCreate) {
    $FullPath = Join-Path $Root $Directory

    if (!(Test-Path $FullPath)) {
        New-Item -ItemType Directory -Path $FullPath | Out-Null
        Write-Host "Created: $Directory" -ForegroundColor Green
    } else {
        Write-Host "Exists:  $Directory" -ForegroundColor DarkGray
    }
}

$LegacyTaskUpload = Join-Path $Root "app\public\uploads\tasks"
$TargetTaskUpload = Join-Path $Root "public\uploads\tasks"

if (!(Test-Path $TargetTaskUpload)) {
    New-Item -ItemType Directory -Path $TargetTaskUpload -Force | Out-Null
    Write-Host "Created: public\uploads\tasks" -ForegroundColor Green
}

if (Test-Path $LegacyTaskUpload) {
    Write-Host "Found legacy task uploads: app\public\uploads\tasks" -ForegroundColor Yellow

    $Files = Get-ChildItem -Path $LegacyTaskUpload -Recurse -File -ErrorAction SilentlyContinue

    if ($Files.Count -gt 0) {
        Write-Host "Copying legacy task uploads to public\uploads\tasks..." -ForegroundColor Yellow

        Copy-Item -Path (Join-Path $LegacyTaskUpload "*") -Destination $TargetTaskUpload -Recurse -Force

        Write-Host "Copied task uploads successfully." -ForegroundColor Green
    } else {
        Write-Host "No task upload files found in legacy folder." -ForegroundColor DarkGray
    }
}

$GitkeepTargets = @(
    "app\View\admin\.gitkeep",
    "app\View\admin\auth\.gitkeep",
    "app\View\admin\dashboard\.gitkeep",
    "app\View\admin\accounts\.gitkeep",
    "app\View\admin\system\.gitkeep",

    "app\View\staff\.gitkeep",
    "app\View\staff\auth\.gitkeep",
    "app\View\staff\dashboard\.gitkeep",
    "app\View\staff\hrm\.gitkeep",
    "app\View\staff\tasks\.gitkeep",
    "app\View\staff\payroll\.gitkeep",

    "app\View\client\.gitkeep",
    "app\View\client\auth\.gitkeep",

    "app\Controllers\Admin\.gitkeep",
    "app\Controllers\Client\.gitkeep",
    "app\Controllers\Project\.gitkeep",

    "app\Services\Auth\.gitkeep",
    "app\Services\HRM\.gitkeep",
    "app\Services\Project\.gitkeep",
    "app\Services\Task\.gitkeep",
    "app\Services\Payroll\.gitkeep",
    "app\Services\Core\.gitkeep",

    "storage\logs\.gitkeep",
    "storage\cache\.gitkeep"
)

foreach ($Gitkeep in $GitkeepTargets) {
    $FullPath = Join-Path $Root $Gitkeep

    if (!(Test-Path $FullPath)) {
        New-Item -ItemType File -Path $FullPath -Force | Out-Null
    }
}

Write-Host ""
Write-Host "Phase 1 folder structure is ready." -ForegroundColor Cyan
Write-Host "Next safe delete candidate: app\public, only after confirming uploads were copied." -ForegroundColor Yellow