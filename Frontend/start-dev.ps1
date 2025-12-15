$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path

function Assert-Command($name) {
  if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
    throw "Required command '$name' not found in PATH. Please install it and restart your terminal."
  }
}

Assert-Command 'php'
Assert-Command 'npm'

Write-Host "Starting Career Connect (XAMPP backend + Vite frontend)..."
Write-Host "Backend (Apache/PHP): http://localhost/careerconnect/Backend/api"
Write-Host "Frontend (Vite): http://localhost:5173"
Write-Host ""
Write-Host "IMPORTANT: Start Apache + MySQL from the XAMPP Control Panel first."
Write-Host ""

# Start frontend in a new PowerShell window
Start-Process powershell -WorkingDirectory $root -ArgumentList @(
  '-NoExit',
  '-Command',
  'npm run dev'
)

Write-Host "One terminal window should open (frontend)."
Write-Host "Backend is served by XAMPP (no separate PHP dev server needed)."
