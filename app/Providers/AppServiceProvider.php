<?php

namespace App\Providers;

use App\Services\Pagamentos\PagamentoGateway;
use App\Services\Pagamentos\SimuladoGateway;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PagamentoGateway::class, SimuladoGateway::class);
    }

    public function boot(): void
    {
        date_default_timezone_set('Africa/Maputo');
        config(['app.timezone' => 'Africa/Maputo']);

        Paginator::defaultView('partials.paginacao');

        // @mt($valor) => 2.500 MT
        Blade::directive('mt', fn ($expr) => "<?php echo \\App\\Support\\Fmt::mt({$expr}); ?>");
    }
}
