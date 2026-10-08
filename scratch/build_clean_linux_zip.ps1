Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$sourceDir = "C:\xampp\htdocs\job-portal"
$tempZip = "C:\xampp\htdocs\careernexaa-production.zip"
$copyInside = "C:\xampp\htdocs\job-portal\careernexaa-production.zip"

if (Test-Path $tempZip) { Remove-Item $tempZip -Force }
if (Test-Path $copyInside) { Remove-Item $copyInside -Force }

Write-Host "Creating Linux/cPanel compatible production ZIP with forward slashes (/)..."

$zipStream = [System.IO.File]::Open($tempZip, [System.IO.FileMode]::Create)
$archive = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)

$allFiles = Get-ChildItem -Path $sourceDir -Recurse -Force | Where-Object { -not $_.PSIsContainer }

$excludePatterns = @(
    '^\.git',
    '^scratch',
    'careernexaa-production\.zip$',
    'cookie\.txt$',
    'test_cookie\.txt$'
)

$fileCount = 0

foreach ($file in $allFiles) {
    # Get relative path
    $relative = $file.FullName.Substring($sourceDir.Length + 1)
    
    # Normalize to forward slash for Linux / cPanel compatibility
    $normalized = $relative.Replace('\', '/')

    # Check exclude patterns
    $skip = $false
    foreach ($p in $excludePatterns) {
        if ($normalized -match $p) {
            $skip = $true
            break
        }
    }

    if (-not $skip) {
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $file.FullName, $normalized, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
        $fileCount++
    }
}

$archive.Dispose()
$zipStream.Dispose()

# Also place a copy inside job-portal for convenience
Copy-Item -Path $tempZip -Destination $copyInside -Force

$zipItem = Get-Item $tempZip
$sizeMb = [math]::Round($zipItem.Length / 1MB, 2)

Write-Host "======================================================"
Write-Host "SUCCESS! Linux/cPanel Compliant ZIP Created."
Write-Host "Total Files: $fileCount"
Write-Host "Archive Size: $sizeMb MB"
Write-Host "Location 1: $copyInside"
Write-Host "Location 2: $tempZip"
Write-Host "======================================================"
