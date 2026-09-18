param(
    [string]$ProjectRoot = "C:\xampp\htdocs\JNG-Apply-Portal-V3"
)

$ErrorActionPreference = "Stop"

Set-Location $ProjectRoot

$sessionPath = Join-Path $ProjectRoot "writable\session"
$cachePath   = Join-Path $ProjectRoot "writable\cache"

New-Item -ItemType Directory -Force -Path $sessionPath | Out-Null
New-Item -ItemType Directory -Force -Path $cachePath | Out-Null

Get-ChildItem $sessionPath -Force -ErrorAction SilentlyContinue |
    Remove-Item -Force -Recurse -ErrorAction SilentlyContinue

Get-ChildItem $cachePath -Force -ErrorAction SilentlyContinue |
    Remove-Item -Force -Recurse -ErrorAction SilentlyContinue

composer dump-autoload
php spark cache:clear

Write-Host ""
Write-Host "CSRF hotfix installed." -ForegroundColor Green
Write-Host "Close all portal tabs and delete localhost cookies before signing in again." -ForegroundColor Yellow
