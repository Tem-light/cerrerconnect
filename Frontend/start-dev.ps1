$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path

function Assert-Command($name) {
  if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
    throw "Required command '$name' not found in PATH. Please install it and restart your terminal."
  }
}

Assert-Command 'php'
Assert-Command 'npm'

Write-Host "Starting Career Connect (PHP backend + Vite frontend)..."
Write-Host "Backend: http://localhost:5000"
Write-Host "Frontend: http://localhost:5173"

# Start backend in a new PowerShell window
Start-Process powershell -WorkingDirectory $root -ArgumentList @(
  '-NoExit',
  '-Command',
  "php -S localhost:5000 -t `"$root\server-php\public`" `"$root\server-php\router.php`""
)

# Start frontend in a new PowerShell window
Start-Process powershell -WorkingDirectory $root -ArgumentList @(
  '-NoExit',
  '-Command',
  'npm run dev'
)

Write-Host "Two terminal windows should open (backend + frontend)."
Write-Host "If they do not, run the commands from QUICK-START.md manually."