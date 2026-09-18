param([string]$ProjectRoot = (Get-Location).Path)
$ErrorActionPreference = 'Stop'
Set-Location $ProjectRoot
if (-not (Test-Path '.env')) { Copy-Item '.env.xampp.example' '.env' }
php .\tools\setup.php
composer install
composer dump-autoload
Write-Host ''
Write-Host 'Edit .env with the shared Careers database and JNG Mailer key.' -ForegroundColor Yellow
