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

    [Parameter(Mandatory = $true)]
    [string]$BackupFile
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $BackupFile)) {
    throw "Backup file not found: $BackupFile"
}

$env:MYSQL_PWD = $Password

Write-Host "Restoring backup $BackupFile into database $Database ..."
Get-Content $BackupFile | mysql -h $Host -P $Port -u $User $Database

if ($LASTEXITCODE -ne 0) {
    throw "Restore failed. mysql exit code: $LASTEXITCODE"
}

Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
Write-Host "Restore completed successfully."
