@echo off
setlocal
rem Arranca o sistema (aceita livretes/fotos ate 10 MB). Feche com Ctrl+C.
set "RAIZ=%~dp0"
set "ROUTER=%RAIZ%vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"
if not exist "%RAIZ%public\index.php" goto erro
echo.
echo  Abra no navegador:  http://localhost:8000
echo  (deixe esta janela aberta enquanto usa o sistema; Ctrl+C para parar)
echo.
if not exist "%ROUTER%" goto alternativo
cd /d "%RAIZ%public"
php -d upload_max_filesize=10M -d post_max_size=12M -d variables_order=EGPCS -S 127.0.0.1:8000 "%ROUTER%"
goto fim
:alternativo
echo  (modo alternativo: uploads limitados a 2 MB)
cd /d "%RAIZ%"
php artisan serve
goto fim
:erro
echo Pasta "public" nao encontrada. Coloque este ficheiro dentro da pasta do sistema (moztransport-app).
pause
:fim
endlocal
