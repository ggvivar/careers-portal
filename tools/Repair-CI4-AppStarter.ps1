param(
    [string]$ProjectRoot = (Get-Location).Path
)

$ErrorActionPreference = 'Stop'

function Write-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

$ProjectRoot = (Resolve-Path $ProjectRoot).Path
$ConfigPath = Join-Path $ProjectRoot 'app\Config'
$ComposerJson = Join-Path $ProjectRoot 'composer.json'

if (-not (Test-Path $ComposerJson)) {
    throw "composer.json was not found in: $ProjectRoot"
}

if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    throw 'Composer is not available in PATH.'
}

Write-Step 'Reading the installed CodeIgniter framework version'

$frameworkJson = & composer show codeigniter4/framework --format=json |
    Out-String |
    ConvertFrom-Json

$version = $null

if ($frameworkJson.versions) {
    $version = @($frameworkJson.versions) |
        Where-Object { $_ -match '^v?\d+\.\d+\.\d+' } |
        Select-Object -First 1
}

if (-not $version -and $frameworkJson.version) {
    $version = $frameworkJson.version
}

if (-not $version) {
    throw 'Unable to determine the installed codeigniter4/framework version.'
}

$version = $version.ToString().TrimStart('v')

Write-Host "Installed framework version: $version"

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupPath = Join-Path $ProjectRoot "app\Config.backup-$timestamp"
$tempPath = Join-Path $env:TEMP "jng-ci4-appstarter-$([guid]::NewGuid().ToString('N'))"

Write-Step 'Backing up the current application configuration'
Copy-Item $ConfigPath $backupPath -Recurse -Force
Write-Host "Backup created: $backupPath"

$preserve = @(
    'Careers.php',
    'Database.php',
    'Email.php',
    'Filters.php',
    'Routes.php',
    'Services.php',
    'Session.php'
)

try {
    Write-Step "Creating an official CodeIgniter AppStarter $version template"

    & composer create-project `
        codeigniter4/appstarter `
        $tempPath `
        $version `
        --no-interaction `
        --prefer-dist

    if ($LASTEXITCODE -ne 0) {
        throw "composer create-project failed with exit code $LASTEXITCODE."
    }

    $officialConfig = Join-Path $tempPath 'app\Config'

    if (-not (Test-Path $officialConfig)) {
        throw "Official AppStarter Config directory was not created: $officialConfig"
    }

    Write-Step 'Installing the complete official AppStarter configuration baseline'
    Copy-Item (Join-Path $officialConfig '*') $ConfigPath -Recurse -Force

    Write-Step 'Restoring the Careers-specific configuration files'
    foreach ($file in $preserve) {
        $savedFile = Join-Path $backupPath $file

        if (Test-Path $savedFile) {
            Copy-Item $savedFile (Join-Path $ConfigPath $file) -Force
            Write-Host "Preserved: $file"
        }
    }

    Write-Step 'Regenerating Composer autoload files'
    Push-Location $ProjectRoot

    try {
        & composer dump-autoload

        if ($LASTEXITCODE -ne 0) {
            throw "composer dump-autoload failed with exit code $LASTEXITCODE."
        }

        Write-Step 'Testing the CodeIgniter CLI bootstrap'
        & php spark list --no-header

        if ($LASTEXITCODE -ne 0) {
            throw "php spark list failed with exit code $LASTEXITCODE."
        }
    }
    finally {
        Pop-Location
    }

    Write-Host ""
    Write-Host 'CI4 AppStarter configuration repair completed.' -ForegroundColor Green
    Write-Host "Backup retained at: $backupPath"
    Write-Host ""
}
finally {
    if (Test-Path $tempPath) {
        Remove-Item $tempPath -Recurse -Force
    }
}
