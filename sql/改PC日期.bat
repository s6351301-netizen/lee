@echo off
:: 1. 自動檢查並強制要求管理員權限
cls
mode con cols=60 lines=15
title 系統日期修改工具

openfiles >nul 2>&1
if %errorlevel% neq 0 (
    echo [提示] 正在嘗試取得管理員權限...
    powershell Start-Process -FilePath '%0' -ArgumentList 'am_admin' -Verb RunAs
    exit /b
)

:: 2. 核心修改：同時支援 2026/12/12 與 2026-12-12 格式
echo ====================================
echo   正在將系統日期修改為 2026-12-12...
echo ====================================
echo.

date 2026/12/12 >nul 2>&1
date 2026-12-12 >nul 2>&1

:: 3. 顯示結果驗證
echo 修改完成！目前系統日期顯示為：
echo ------------------------------------
date /t
echo ------------------------------------
echo.
echo [注意] 如果日期一秒內變回原樣，請至 Windows 設定關閉「自動設定時間」。
echo.
pause

