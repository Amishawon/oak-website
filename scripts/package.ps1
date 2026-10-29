$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression
$sourceRoot = Split-Path $PSScriptRoot -Parent
$outputRoot = Join-Path $sourceRoot 'artifacts'
New-Item -ItemType Directory -Path $outputRoot -Force | Out-Null
function Write-Zip($destination, $basePath, $files, $prefix) {
    $stream = [System.IO.File]::Open($destination, [System.IO.FileMode]::Create)
    $zip = New-Object System.IO.Compression.ZipArchive($stream, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        foreach ($file in $files) {
            $relative = $file.FullName.Substring($basePath.Length).TrimStart('\','/').Replace('\','/')
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, ($prefix + $relative), [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
        }
    } finally { $zip.Dispose(); $stream.Dispose() }
}
$dist = Join-Path $sourceRoot 'dist'
$distFiles = Get-ChildItem -LiteralPath $dist -Recurse -File -Force | Where-Object { $_.FullName -notmatch '\\cms\\private\\(?!\.htaccess$)' -and $_.FullName -notmatch '\\uploads\\(?!\.htaccess$)' }
Write-Zip (Join-Path $outputRoot 'oak-website-production.zip') $dist $distFiles ''
$sourceFiles = Get-ChildItem -LiteralPath $sourceRoot -Recurse -File -Force | Where-Object { $_.FullName -notmatch '\\(node_modules|dist|test-results|artifacts|\.git)\\' -and $_.Name -notmatch '^\.env(?!\.example$)' -and $_.FullName -notmatch '\\cms\\private\\(?!\.htaccess$)' -and $_.FullName -notmatch '\\uploads\\(?!\.htaccess$)' }
Write-Zip (Join-Path $outputRoot 'oak-website-source.zip') $sourceRoot $sourceFiles 'oak-website-source/'
$requirements = @{
 'oak-website-production.zip' = @('index.html','index.php','.htaccess','config.json','assets/oak-logo.svg','admin/index.html','cms/api.php','cms/private/.htaccess','uploads/.htaccess')
 'oak-website-source.zip' = @('oak-website-source/package.json','oak-website-source/package-lock.json','oak-website-source/.env.example','oak-website-source/src/main.jsx','oak-website-source/QA_REPORT.md')
}
$results = foreach ($name in $requirements.Keys) {
 $file = Join-Path $outputRoot $name
 $zip = [System.IO.Compression.ZipFile]::OpenRead($file)
 try {
  $entries = @($zip.Entries | ForEach-Object { $_.FullName })
  foreach ($required in $requirements[$name]) { if ($required -notin $entries) { throw "Missing $required in $name" } }
  if ($entries -match 'node_modules/|\.env\.local$') { throw "Unexpected private/runtime files in $name" }
  [pscustomobject]@{archive=$name;entries=$entries.Count;bytes=(Get-Item -LiteralPath $file).Length;sha256=(Get-FileHash -LiteralPath $file -Algorithm SHA256).Hash;verified=$true}
 } finally { $zip.Dispose() }
}
$results | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $outputRoot 'ZIP_VERIFICATION.json')
$results | Format-Table
