Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead('C:\xampp\htdocs\careernexaa-production.zip')
Write-Host "Total entries in ZIP:" $zip.Entries.Count

$keyFiles = @('.htaccess', 'database.sql', '.env.example', 'index.php', 'robots.txt', 'sitemap.xml', 'admin/login.php', 'admin/index.php', 'api/comment.php', 'uploads/.htaccess')
foreach ($kf in $keyFiles) {
    $found = $zip.Entries | Where-Object { $_.FullName -eq $kf }
    $status = if ($found) { "EXISTS (OK)" } else { "MISSING" }
    Write-Host "  $kf : $status"
}

$scratch = $zip.Entries | Where-Object { $_.FullName -like 'scratch*' }
Write-Host "Scratch entries (should be 0):" $scratch.Count

$git = $zip.Entries | Where-Object { $_.FullName -like '.git*' }
Write-Host "Git entries (should be 0):" $git.Count

$zip.Dispose()
