#!/usr/bin/env bash
# Arranca o sistema (aceita livretes/fotos até 10 MB). Feche com Ctrl+C.
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROUTER="$RAIZ/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"
echo "Abra no navegador:  http://localhost:8000   (Ctrl+C para parar)"
if [ -f "$ROUTER" ]; then
  cd "$RAIZ/public" || exit 1
  exec php -d upload_max_filesize=10M -d post_max_size=12M -d variables_order=EGPCS -S 127.0.0.1:8000 "$ROUTER"
fi
cd "$RAIZ" && exec php artisan serve
