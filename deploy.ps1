<#
.SYNOPSIS
    Builds a client deployment package for the HRIS application.

.DESCRIPTION
    Produces a clean, ship-ready Laravel app in the output directory:
      1. Compiles frontend assets   (npm run build)
      2. Encrypts PHP source        (php artisan encrypt-source -> dist/)
      3. Assembles the package      (encrypted source + runtime folders)

    The encrypted folders (app, routes, database) come from dist/.
    Everything else is copied in plaintext. Dev-only files (node_modules,
    tests, .git, IDE config, etc.) are never copied.

    The server running the package MUST have the phpBolt extension loaded,
    otherwise the encrypted PHP will not execute.

.PARAMETER OutputDir
    Folder to write the package to. Default: deploy

.PARAMETER SkipBuild
    Skip the "npm run build" step (use the existing public/build).

.PARAMETER SkipEncrypt
    Skip "php artisan encrypt-source" and reuse the existing dist/ folder.

.PARAMETER IncludeEnv
    Copy the local .env into the package. Off by default — configure .env
    on the server instead. .env.example is always copied as a reference.

.PARAMETER NoZip
    Skip creating the .zip archive. By default the assembled package is
    compressed into a timestamped zip in the project root.

.EXAMPLE
    .\deploy.ps1
    Full build: npm build + encrypt + assemble into .\deploy

.EXAMPLE
    .\deploy.ps1 -SkipBuild
    Reuse existing compiled assets, re-encrypt, reassemble.
#>
[CmdletBinding()]
param(
    [string]$OutputDir = 'deploy',
    [switch]$SkipBuild,
    [switch]$SkipEncrypt,
    [switch]$IncludeEnv,
    [switch]$NoZip
)

$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
Set-Location $root

function Write-Step  { param($m) Write-Host "`n==> $m" -ForegroundColor Cyan }
function Write-Info  { param($m) Write-Host "    $m" -ForegroundColor Gray }
function Write-Ok    { param($m) Write-Host "    $m" -ForegroundColor Green }

# Folders that hold encrypted PHP (produced in dist/ by encrypt-source).
# These replace their plaintext counterparts in the package.
# Must match config/source-encrypter.php 'source'. Only app/ is encrypted.
# NOT encrypted, on purpose:
#   - database/  Laravel's migrator needs the migration file's require() to
#                return the migration object; bolt rewrites it to "return 0;".
#   - routes/    if route:cache ever runs, closure routes are serialized by
#                reading PHP source, which is unreadable once encrypted.
$encryptedDirs = @('app')

# Plaintext folders shipped as-is.
$plaintextDirs = @('vendor', 'public', 'resources', 'bootstrap', 'config', 'storage', 'database', 'routes')

# Plaintext root files shipped as-is.
$rootFiles = @('artisan', 'composer.json', 'composer.lock')

# ---------------------------------------------------------------------------
# 1. Build frontend assets
# ---------------------------------------------------------------------------
if ($SkipBuild) {
    Write-Step 'Skipping npm build (-SkipBuild)'
    if (-not (Test-Path (Join-Path $root 'public\build'))) {
        throw 'public\build not found. Run without -SkipBuild first.'
    }
} else {
    Write-Step 'Building frontend assets (npm run build)'
    npm run build
    if ($LASTEXITCODE -ne 0) { throw 'npm run build failed.' }
    Write-Ok 'Assets compiled into public\build'
}

# ---------------------------------------------------------------------------
# 2. Encrypt PHP source
# ---------------------------------------------------------------------------
if ($SkipEncrypt) {
    Write-Step 'Skipping encryption (-SkipEncrypt)'
    if (-not (Test-Path (Join-Path $root 'dist'))) {
        throw 'dist\ not found. Run without -SkipEncrypt first.'
    }
} else {
    Write-Step 'Encrypting PHP source (php artisan encrypt-source)'
    php artisan encrypt-source --force
    if ($LASTEXITCODE -ne 0) {
        throw 'encrypt-source failed. Is the phpBolt extension loaded? (https://phpBolt.com)'
    }
    Write-Ok 'Encrypted source written to dist\'
}

# Sanity-check that each encrypted folder actually landed in dist/
foreach ($d in $encryptedDirs) {
    if (-not (Test-Path (Join-Path $root "dist\$d"))) {
        throw "Expected dist\$d after encryption but it is missing. Is '$d' listed in config/source-encrypter.php 'source'?"
    }
}

# ---------------------------------------------------------------------------
# 3. Assemble the package
# ---------------------------------------------------------------------------
$out = Join-Path $root $OutputDir
Write-Step "Assembling package into $OutputDir\"

if (Test-Path $out) {
    Write-Info 'Removing previous package...'
    Remove-Item $out -Recurse -Force
}
New-Item -ItemType Directory -Path $out | Out-Null

# 3a. Encrypted folders (from dist/)
foreach ($d in $encryptedDirs) {
    Write-Info "Copying encrypted $d\ ..."
    Copy-Item (Join-Path $root "dist\$d") -Destination $out -Recurse -Force
}

# 3b. Plaintext folders
foreach ($d in $plaintextDirs) {
    if (-not (Test-Path (Join-Path $root $d))) {
        Write-Info "Skipping $d\ (not found)"
        continue
    }
    Write-Info "Copying $d\ ..."
    Copy-Item (Join-Path $root $d) -Destination $out -Recurse -Force
}

# 3c. Root files
foreach ($f in $rootFiles) {
    Copy-Item (Join-Path $root $f) -Destination $out -Force
}

# 3d. .env handling
Copy-Item (Join-Path $root '.env.example') -Destination $out -Force -ErrorAction SilentlyContinue
if ($IncludeEnv) {
    Write-Info 'Including local .env (-IncludeEnv)'
    Copy-Item (Join-Path $root '.env') -Destination $out -Force
}

# ---------------------------------------------------------------------------
# 4. Scrub runtime artifacts from the package
# ---------------------------------------------------------------------------
Write-Step 'Cleaning runtime artifacts'

# Clear cached config/routes/views and bootstrap cache so the client rebuilds them.
$scrub = @(
    'bootstrap\cache\*.php',
    'storage\framework\cache\data\*',
    'storage\framework\sessions\*',
    'storage\framework\views\*.php',
    'storage\logs\*.log'
)
foreach ($pattern in $scrub) {
    $target = Join-Path $out $pattern
    Get-ChildItem $target -Force -ErrorAction SilentlyContinue | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
}

# ---------------------------------------------------------------------------
# 5. Compress the package into a zip
# ---------------------------------------------------------------------------
$zipPath = $null
if ($NoZip) {
    Write-Step 'Skipping zip (-NoZip)'
} else {
    $stamp   = Get-Date -Format 'yyyyMMdd-HHmm'
    $zipPath = Join-Path $root "hris-deploy-$stamp.zip"
    Write-Step "Compressing package -> $(Split-Path $zipPath -Leaf)"

    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
    # Zip the *contents* of the package (so files sit at the zip root, not under deploy\)
    Compress-Archive -Path (Join-Path $out '*') -DestinationPath $zipPath -CompressionLevel Optimal
    Write-Ok 'Archive created'
}

# ---------------------------------------------------------------------------
# Done
# ---------------------------------------------------------------------------
$sizeMB = [math]::Round(((Get-ChildItem $out -Recurse -File | Measure-Object Length -Sum).Sum / 1MB), 1)
Write-Step 'Deployment package ready'
Write-Ok   "Location: $out"
Write-Ok   "Size:     $sizeMB MB"
if ($zipPath) {
    $zipMB = [math]::Round(((Get-Item $zipPath).Length / 1MB), 1)
    Write-Ok "Archive:  $zipPath ($zipMB MB)"
}
Write-Host ''
Write-Host 'On the server, after copying the package:' -ForegroundColor Yellow
Write-Host '    1. Configure .env (DB, APP_KEY, APP_URL, license)' -ForegroundColor Yellow
Write-Host '    2. php artisan migrate --force        (first install: migrate:fresh --seed --force)' -ForegroundColor Yellow
Write-Host '    3. php artisan config:cache           (config only)' -ForegroundColor Yellow
Write-Host '    4. php artisan optimize:clear         (NEVER run route:cache - it breaks on' -ForegroundColor Yellow
Write-Host '                                           encrypted closure routes)' -ForegroundColor Yellow
Write-Host '    5. Ensure the phpBolt extension is loaded in PHP' -ForegroundColor Yellow
