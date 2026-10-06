@echo off
title UniControl - Servidor Local
echo ===================================================================
echo             UniControl - Sistema de Estoque de Uniformes
echo ===================================================================
echo.
echo Abrindo seu navegador em: http://localhost:8000
echo.
echo Credenciais para teste e capturas de tela:
echo - Administrador: admin@unicontrol.com  ^| Senha: admin123
echo - Operador:      operador@unicontrol.com ^| Senha: operador123
echo.
echo Para parar o servidor, feche esta janela a qualquer momento.
echo ===================================================================
echo.
timeout /t 2 /nobreak >nul
start http://localhost:8000/login.php
"C:\xampp\php\php.exe" -S localhost:8000
pause
