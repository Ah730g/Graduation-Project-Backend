# Laravel Backend Setup Script
# This script will install dependencies and run migrations

Write-Host "=== Laravel Backend Setup ===" -ForegroundColor Cyan
Write-Host ""

# Check for PHP
Write-Host "Checking for PHP..." -ForegroundColor Yellow
$phpPath = $null

# Try to find PHP in common locations
$phpLocations = @(
    "php",
    "C:\xampp\php\php.exe",
    "C:\wamp64\bin\php\php8.2\php.exe",
    "C:\wamp64\bin\php\php8.3\php.exe",
    "$env:LOCALAPPDATA\Programs\Laravel Herd\bin\php.exe",
    "C:\php\php.exe",
    "C:\Program Files\PHP\php.exe"
)

foreach ($location in $phpLocations) {
    try {
        if ($location -eq "php") {
            $result = Get-Command php -ErrorAction SilentlyContinue
            if ($result) {
                $phpPath = "php"
                break
            }
        } else {
            if (Test-Path $location) {
                $phpPath = $location
                break
            }
        }
    } catch {
        continue
    }
}

if (-not $phpPath) {
    Write-Host "ERROR: PHP not found!" -ForegroundColor Red
    Write-Host "Please install PHP 8.2+ first:" -ForegroundColor Yellow
    Write-Host "  - Laravel Herd: https://herd.laravel.com/windows" -ForegroundColor Cyan
    Write-Host "  - Or download from: https://windows.php.net/download/" -ForegroundColor Cyan
    Write-Host ""
    exit 1
}

Write-Host "PHP found: $phpPath" -ForegroundColor Green
& $phpPath -v
Write-Host ""

# Check for Composer
Write-Host "Checking for Composer..." -ForegroundColor Yellow
$composerPath = $null

$composerLocations = @(
    "composer",
    "$env:LOCALAPPDATA\Programs\Laravel Herd\bin\composer.exe",
    "$env:APPDATA\Composer\vendor\bin\composer.bat",
    "C:\ProgramData\ComposerSetup\bin\composer.bat"
)

foreach ($location in $composerLocations) {
    try {
        if ($location -eq "composer") {
            $result = Get-Command composer -ErrorAction SilentlyContinue
            if ($result) {
                $composerPath = "composer"
                break
            }
        } else {
            if (Test-Path $location) {
                $composerPath = $location
                break
            }
        }
    } catch {
        continue
    }
}

if (-not $composerPath) {
    Write-Host "ERROR: Composer not found!" -ForegroundColor Red
    Write-Host "Please install Composer first:" -ForegroundColor Yellow
    Write-Host "  Download from: https://getcomposer.org/download/" -ForegroundColor Cyan
    Write-Host ""
    exit 1
}

Write-Host "Composer found: $composerPath" -ForegroundColor Green
& $composerPath --version
Write-Host ""

# Install dependencies
Write-Host "Installing Laravel dependencies..." -ForegroundColor Yellow
& $composerPath install
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to install dependencies!" -ForegroundColor Red
    exit 1
}
Write-Host "Dependencies installed successfully!" -ForegroundColor Green
Write-Host ""

# Check for .env file
if (-not (Test-Path ".env")) {
    Write-Host "Creating .env file..." -ForegroundColor Yellow
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host ".env file created from .env.example" -ForegroundColor Green
    } else {
        Write-Host "WARNING: .env.example not found. You may need to create .env manually." -ForegroundColor Yellow
    }
    Write-Host ""
}

# Generate application key if needed
Write-Host "Generating application key..." -ForegroundColor Yellow
& $phpPath artisan key:generate
Write-Host ""

# Run migrations
Write-Host "Running database migrations..." -ForegroundColor Yellow
& $phpPath artisan migrate
if ($LASTEXITCODE -ne 0) {
    Write-Host "WARNING: Migrations may have failed. Check your database configuration in .env" -ForegroundColor Yellow
} else {
    Write-Host "Migrations completed successfully!" -ForegroundColor Green
}
Write-Host ""

Write-Host "=== Setup Complete ===" -ForegroundColor Cyan
Write-Host "Your Laravel backend is ready!" -ForegroundColor Green

