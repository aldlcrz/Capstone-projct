# Lumbarong System Startup Script
# Detects VS Code environment and runs tasks inside integrated terminals.

Write-Host "Starting Lumbarong System..." -ForegroundColor Cyan

# Check if running inside VS Code integrated terminal
if ($env:TERM_PROGRAM -eq "vscode" -or $env:VSCODE_PID) {
    Write-Host "VS Code detected - running servers in integrated terminals..." -ForegroundColor Green

    # Start Backend in its own dedicated terminal
    $backendJob = Start-Job -ScriptBlock {
        Set-Location "$using:PSScriptRoot\backend"
        npm run dev
    }

    # Start Frontend in its own dedicated terminal
    $frontendJob = Start-Job -ScriptBlock {
        Set-Location "$using:PSScriptRoot\frontend"
        npm run dev
    }

    Write-Host ""
    Write-Host "Both servers are starting..." -ForegroundColor White
    Write-Host "  Backend:  http://localhost:5000" -ForegroundColor Yellow
    Write-Host "  Frontend: http://localhost:3000" -ForegroundColor Green
    Write-Host ""
    Write-Host "TIP: In VS Code, press Ctrl+Shift+B to run via Tasks for split terminal view." -ForegroundColor Cyan

    # Stream output from both jobs to the current terminal
    while ($backendJob.State -eq "Running" -or $frontendJob.State -eq "Running") {
        Receive-Job $backendJob | ForEach-Object { Write-Host "[Backend]  $_" -ForegroundColor Yellow }
        Receive-Job $frontendJob | ForEach-Object { Write-Host "[Frontend] $_" -ForegroundColor Green }
        Start-Sleep -Milliseconds 300
    }
} else {
    # Fallback: open two external windows
    Write-Host "Opening server windows..." -ForegroundColor Yellow
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "Set-Location '$PSScriptRoot\backend'; npm run dev"
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "Set-Location '$PSScriptRoot\frontend'; npm run dev"
    Write-Host "  Backend:  http://localhost:5000" -ForegroundColor Gray
    Write-Host "  Frontend: http://localhost:3000" -ForegroundColor Gray
}
