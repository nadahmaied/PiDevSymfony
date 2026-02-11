# Demarre le serveur Symfony avec le PHP qui a pdo_mysql active
# (php.ini dans C:\php-8.3.29-nts-Win32-vs16-x64)

$phpDir = "C:\php-8.3.29-nts-Win32-vs16-x64"
if (-not (Test-Path "$phpDir\php.exe")) {
    Write-Host "PHP non trouve dans $phpDir. Ajustez la variable phpDir dans ce script." -ForegroundColor Red
    exit 1
}

$env:Path = "$phpDir;$env:Path"
Set-Location $PSScriptRoot
Write-Host "Demarrage du serveur Symfony avec PHP: $phpDir\php.exe" -ForegroundColor Green
symfony server:start
