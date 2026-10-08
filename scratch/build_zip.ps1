$src = "C:\xampp\htdocs\job-portal"
$dest = "C:\xampp\htdocs\job-portal\careernexaa-production.zip"
$altDest = "C:\xampp\htdocs\careernexaa-production.zip"

if (Test-Path $dest) {
    Remove-Item $dest -Force
}
if (Test-Path $altDest) {
    Remove-Item $altDest -Force
}

Write-Host "Gathering production files..."
$items = Get-ChildItem -Path $src -Force | Where-Object {
    $_.Name -notin @('.git', 'scratch', 'careernexaa-production.zip', 'cookie.txt', 'test_cookie.txt')
}

Write-Host "Compressing to $dest..."
$items | Compress-Archive -DestinationPath $dest -Force

Write-Host "Copying to $altDest..."
Copy-Item -Path $dest -Destination $altDest -Force

$zipItem = Get-Item $dest
$sizeMb = [math]::Round($zipItem.Length / 1MB, 2)
$sizeKb = [math]::Round($zipItem.Length / 1KB, 2)

Write-Host "======================================================"
Write-Host "SUCCESS! Production ZIP Archive Created."
Write-Host "File Name: $($zipItem.Name)"
Write-Host "File Size: $sizeMb MB ($sizeKb KB)"
Write-Host "Location 1: $dest"
Write-Host "Location 2: $altDest"
Write-Host "======================================================"
