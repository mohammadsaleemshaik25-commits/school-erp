param(
    [Parameter(Mandatory = $true)]
    [string]$Host,

    [Parameter(Mandatory = $true)]
    [int]$Port,

    [Parameter(Mandatory = $true)]
    [string]$Database,

    [Parameter(Mandatory = $true)]
    [string]$User,

    [Parameter(Mandatory = $true)]
    [string]$Password,

    [string]$OutputDir = ".\backups"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFile = Join-Path $OutputDir "$Database`_$timestamp.sql"

$env:MYSQL_PWD = $Password

Write-Host "Creating backup: $backupFile"
mysqldump -h $Host -P $Port -u $User --databases $Database --routines --triggers --events --single-transaction --quick --add-drop-table > $backupFile

if ($LASTEXITCODE -ne 0) {
    throw "Backup failed. mysqldump exit code: $LASTEXITCODE"
}

Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
Write-Host "Backup completed successfully."
