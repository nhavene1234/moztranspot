<?php

namespace App\Http\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Models\Assento;
use App\Models\Auditoria;
use App\Models\Autocarro;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Autocarros (CRUD) e livrete do carro (upload / ver / substituir / remover). */
class FrotaController extends Controller
{
    use ContextoTransportadora;

    private const DISCO = 'local'; // storage/app/private — não é acessível por URL pública

    public function index(Request $r)
    {
        $tid = $this->tid($r);
        $autocarros = Autocarro::with('transportadora')
            ->withCount(['viagens', 'viagens as viagens_futuras_count' => fn ($q) => $q->futuras()])
            ->when($tid, fn ($q) => $q->where('transportadora_id', $tid))
            ->when($r->filled('q'), function ($q) use ($r) {
                $t = '%' . trim($r->query('q')) . '%';
                $q->where(fn ($w) => $w->where('matricula', 'like', $t)->orWhere('modelo', 'like', $t));
            })
            ->orderBy('matricula')->get();

        return view('gestao.frota', $this->contexto($r, $tid) + compact('autocarros'));
    }

    private function validar(Request $r, ?Autocarro $actual = null): array
    {
        $r->merge(['matricula' => strtoupper(trim((string) $r->input('matricula')))]);

        return $r->validate([
            'transportadora_id' => [$actual === null && $r->user()->tem('ver_todas_transportadoras') ? 'required' : 'nullable', 'exists:transportadoras,id'],
            'matricula' => ['required', 'string', 'max:20', Rule::unique('autocarros', 'matricula')->ignore($actual?->id)],
            'modelo' => ['nullable', 'string', 'max:60'],
            'ano' => ['nullable', 'integer', 'between:1980,' . (now()->year + 1)],
            'livrete' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'matricula.unique' => 'Já existe um autocarro com esta matrícula.',
            'livrete.mimes' => 'O livrete tem de ser PDF, JPG ou PNG.',
            'livrete.max' => 'O livrete não pode ter mais de 5 MB.',
            'ano.between' => 'Ano inválido.',
        ]);
    }

    private function guardarFicheiro(UploadedFile $f): array
    {
        $caminho = $f->storeAs('livretes', Str::random(40) . '.' . $f->extension(), self::DISCO);

        return [
            'livrete_caminho' => $caminho,
            'livrete_nome' => Str::limit(preg_replace('/[^\w .\-()]/u', '_', $f->getClientOriginalName()), 140, ''),
            'livrete_em' => now(),
        ];
    }

    private function apagarFicheiro(?string $caminho): void
    {
        if ($caminho) {
            Storage::disk(self::DISCO)->delete($caminho);
        }
    }

    public function guardar(Request $r)
    {
        $d = $this->validar($r);
        $empresa = $this->empresaDoNovo($r);

        $bus = DB::transaction(function () use ($d, $empresa, $r) {
            $bus = Autocarro::create([
                'matricula' => $d['matricula'], 'modelo' => $d['modelo'] ?? null, 'ano' => $d['ano'] ?? null,
                'capacidade_total' => 44, 'transportadora_id' => $empresa,
            ]);
            $assentos = [];
            for ($n = 1; $n <= 44; $n++) {
                $assentos[] = ['numero' => $n, 'tipo' => in_array(($n - 1) % 4, [0, 3]) ? 'janela' : 'corredor', 'autocarro_id' => $bus->id];
            }
            Assento::insert($assentos);

            return $bus;
        });
        if ($r->hasFile('livrete')) {
            $bus->update($this->guardarFicheiro($r->file('livrete')));
        }
        Auditoria::registar('Autocarro registado', $bus->matricula . ($r->hasFile('livrete') ? ' (com livrete)' : ''), 'laranja');

        return redirect()->route('gestao.autocarros')->with('estado', "Autocarro {$bus->matricula} registado com 44 lugares.");
    }

    public function actualizar(Request $r, Autocarro $autocarro)
    {
        $this->permitido($r, $autocarro);
        $d = $this->validar($r, $autocarro);

        $autocarro->fill(['matricula' => $d['matricula'], 'modelo' => $d['modelo'] ?? null, 'ano' => $d['ano'] ?? null]);
        if ($r->hasFile('livrete')) {
            $antigo = $autocarro->livrete_caminho;
            $autocarro->fill($this->guardarFicheiro($r->file('livrete')));
            $this->apagarFicheiro($antigo);
        } elseif ($r->boolean('remover_livrete') && $autocarro->livrete_caminho) {
            $this->apagarFicheiro($autocarro->livrete_caminho);
            $autocarro->fill(['livrete_caminho' => null, 'livrete_nome' => null, 'livrete_em' => null]);
        }
        $autocarro->save();
        Auditoria::registar('Autocarro actualizado', $autocarro->matricula, 'laranja');

        return redirect()->route('gestao.autocarros')->with('estado', "Autocarro {$autocarro->matricula} actualizado.");
    }

    public function estado(Request $r, Autocarro $autocarro)
    {
        $this->permitido($r, $autocarro);
        $autocarro->update(['estado' => $autocarro->estado === 'activo' ? 'inactivo' : 'activo']);
        Auditoria::registar($autocarro->estado === 'activo' ? 'Autocarro activado' : 'Autocarro desactivado', $autocarro->matricula, 'laranja');

        return back()->with('estado', "Autocarro {$autocarro->matricula}: " . ($autocarro->estado === 'activo' ? 'activo.' : 'desactivado (não aparece ao programar novas viagens).'));
    }

    public function eliminar(Request $r, Autocarro $autocarro)
    {
        $this->permitido($r, $autocarro);
        if ($autocarro->viagens()->exists()) {
            return back()->with('erro', "O autocarro {$autocarro->matricula} já tem viagens registadas e não pode ser eliminado (isso apagaria o histórico de vendas). Desactive-o.");
        }
        $this->apagarFicheiro($autocarro->livrete_caminho);
        $matricula = $autocarro->matricula;
        $autocarro->delete(); // os lugares apagam-se em cascata
        Auditoria::registar('Autocarro eliminado', $matricula, 'laranja');

        return back()->with('estado', "Autocarro {$matricula} eliminado.");
    }

    /** Mostra o livrete (só gestor da mesma transportadora / administrador). */
    public function livrete(Request $r, Autocarro $autocarro)
    {
        $this->permitido($r, $autocarro);
        abort_unless($autocarro->livrete_caminho && Storage::disk(self::DISCO)->exists($autocarro->livrete_caminho), 404, 'Este autocarro não tem livrete.');

        return Storage::disk(self::DISCO)->response($autocarro->livrete_caminho, $autocarro->livrete_nome ?: 'livrete', ['X-Content-Type-Options' => 'nosniff'], 'inline');
    }
}
