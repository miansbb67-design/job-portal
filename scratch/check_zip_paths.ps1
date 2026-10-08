Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead('C:\xampp\htdocs\careernexaa-production.zip')
$sample = $zip.Entries | Select-Object -First 25 FullName
foreach ($s in $sample) {
    Write-Host $s.FullName
}
$zip.Dispose()
