param(
    [Parameter(Mandatory = $true)][string]$MysqlBin,
    [string]$PhpBinary = 'php'
)

$ErrorActionPreference = 'Stop'
$skripsiWorkspace = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '../..'))
$skripsiTestingRoot = Join-Path $skripsiWorkspace 'storage/framework/testing'
$skripsiMarker = Join-Path $skripsiTestingRoot 'mysql-skripsi-path.txt'
$skripsiServer = Join-Path $MysqlBin 'mysqld.exe'
if (-not (Test-Path -LiteralPath $skripsiServer)) { throw 'mysqld.exe tidak ditemukan.' }
if (Test-Path -LiteralPath $skripsiMarker) { throw 'Ada penanda pengujian MySQL sebelumnya. Periksa sebelum melanjutkan.' }
New-Item -ItemType Directory -Path $skripsiTestingRoot -Force | Out-Null
$skripsiData = Join-Path $skripsiTestingRoot ('mysql-skripsi-' + [guid]::NewGuid().ToString('N'))
$skripsiProcess = $null
$skripsiPidFile = Join-Path $skripsiData 'mysql-test.pid'
Push-Location $skripsiWorkspace
try {
    New-Item -ItemType Directory -Path $skripsiData | Out-Null
    Set-Content -LiteralPath $skripsiMarker -Value $skripsiData
    & $skripsiServer --no-defaults --initialize-insecure --console "--datadir=$skripsiData"
    if ($LASTEXITCODE -ne 0) { throw 'Inisialisasi MySQL pengujian gagal.' }
    $skripsiProcess = Start-Process -FilePath $skripsiServer -WindowStyle Hidden -PassThru -ArgumentList @(
        '--no-defaults', ('--datadir="' + $skripsiData + '"'), '--bind-address=127.0.0.1',
        '--port=33316', '--mysqlx=OFF', '--skip-log-bin', '--innodb-buffer-pool-size=32M', ('--pid-file="' + $skripsiPidFile + '"')
    )
    & $PhpBinary (Join-Path $PSScriptRoot 'mysql-smoke.php')
    if ($LASTEXITCODE -ne 0) { throw 'Pengujian MySQL gagal.' }
} finally {
    # MySQL on Windows can spawn a child. Its PID file is inside this run's own datadir.
    if (Test-Path -LiteralPath $skripsiPidFile) {
        $skripsiServerPid = [int](Get-Content -LiteralPath $skripsiPidFile -Raw).Trim()
        $skripsiChild = Get-Process -Id $skripsiServerPid -ErrorAction SilentlyContinue
        if ($skripsiChild -and $skripsiChild.ProcessName -eq 'mysqld') {
            Stop-Process -InputObject $skripsiChild -Force
            $skripsiChild.WaitForExit()
        }
    }
    # Stop only processes created here; never stop Laragon's existing MySQL.
    if ($skripsiProcess -and -not $skripsiProcess.HasExited) {
        Stop-Process -Id $skripsiProcess.Id -Force
        $skripsiProcess.WaitForExit()
    }
    if (Test-Path -LiteralPath $skripsiData) {
        $skripsiResolved = (Resolve-Path -LiteralPath $skripsiData).Path
        $skripsiAllowed = (Resolve-Path -LiteralPath $skripsiTestingRoot).Path + '\mysql-skripsi-'
        if (-not $skripsiResolved.StartsWith($skripsiAllowed, [StringComparison]::OrdinalIgnoreCase)) { throw 'Target pembersihan pengujian tidak valid.' }
        for ($skripsiAttempt = 0; $skripsiAttempt -lt 20; $skripsiAttempt++) {
            try { Remove-Item -LiteralPath $skripsiResolved -Recurse -Force; break }
            catch { if ($skripsiAttempt -eq 19) { throw }; Start-Sleep -Milliseconds 200 }
        }
    }
    if (Test-Path -LiteralPath $skripsiMarker) { Remove-Item -LiteralPath $skripsiMarker }
    Pop-Location
}
