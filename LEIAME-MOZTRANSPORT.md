# Sistema de venda de bilhetes para transportes rodoviários de Moçambique

Sistema web em **Laravel (PHP) + MySQL**, conforme o documento do projecto final (arquitectura em camadas:
apresentação → Controllers/regras de negócio → Models Eloquent → MySQL; pagamentos M-Pesa / e-Mola por HTTPS/API).

Esta pasta **não é o Laravel completo**: contém só o código do projecto (modelos, migrations, controllers, views, rotas, CSS/JS).
O script de instalação descarrega o Laravel e copia estes ficheiros por cima.

> **Estado:** o código foi executado e testado com o **Laravel 12** (PHP 8.3) em **SQLite** e **MariaDB/MySQL**:
> `php artisan test` passa (10 testes) e o teste de navegação abre todas as páginas dos 5 perfis.
> Não foram testados no navegador a câmara de QR e os pagamentos reais (M-Pesa / e-Mola são simulados).

## 1. Requisitos
- PHP 8.2 ou superior (extensões: mbstring, openssl, pdo_sqlite **ou** pdo_mysql, xml, curl, zip)
- Composer 2
- (Opcional) MySQL 8 / MariaDB — **XAMPP** ou **Laragon** servem. Sem MySQL, o sistema usa SQLite automaticamente.

## 2. Instalação rápida
**Linux / macOS / Git Bash:** `bash instalar.sh`  
**Windows (CMD):** `instalar.bat`

O script cria a pasta `moztransport-app` **ao lado** da pasta do projecto (nunca dentro), instala o Laravel 12, copia os ficheiros, gera a chave, cria a base de dados com dados de
demonstração e deixa tudo pronto. Depois, para arrancar o sistema (Windows: duplo clique em `iniciar.bat` dentro de `moztransport-app`):

```
cd moztransport-app
iniciar.bat            (Linux/Mac: bash iniciar.sh)
```
Abra http://localhost:8000

> Use o `iniciar` em vez de `php artisan serve`: ele já aceita livretes/fotos até 10 MB (o `artisan serve` normal só aceita 2 MB).

### Já tenho o sistema instalado — como actualizar?
Extraia esta versão e, dentro da pasta `moztransport`, execute **`atualizar.bat`** (Linux/Mac: `bash atualizar.sh`).
Ele copia o código novo para `..\moztransport-app`, corre `php artisan migrate` (cria as tabelas novas **sem apagar** os dados) e limpa a cache.
Se a sua pasta for outra: `atualizar.bat C:\caminho\moztransport-app`.

### Instalação manual (se preferir)
```
composer create-project "laravel/laravel:^12.0" moztransport-app
# copie o conteúdo desta pasta (menos instalar.*) para dentro de moztransport-app, aceitando substituir ficheiros
cd moztransport-app
copy .env.example .env        # (cp no Linux/Mac)
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```
Se `DB_CONNECTION=sqlite` crie primeiro o ficheiro vazio `database/database.sqlite`.

### Usar MySQL (como no documento)
1. Crie a base: `CREATE DATABASE moztransport CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. No `.env`: comente a linha `DB_CONNECTION=sqlite`, descomente o bloco MySQL e ponha o utilizador/palavra-passe.
3. `php artisan migrate:fresh --seed`

## 3. Contas iniciais (palavra-passe de todas: `Moz@2026`)
| Perfil | Entrada | Email |
|---|---|---|
| Passageiro | `/entrar` (aplicação móvel) | ioramo.satar@email.co.mz |
| Bilheteiro | `/painel/entrar` | manuel.zoca@moztransport.co.mz |
| Cobrador | `/painel/entrar` | mikael.chongo@moztransport.co.mz |
| Gestor (Lalgy) | `/painel/entrar` | p.nhalicolo@moztransport.co.mz |
| Administrador | `/painel/entrar` | nelson.nhavene@moztransport.co.mz |

O utilizador entra só com as suas credenciais: não há botões de demonstração. A página de entrada da equipa **não tem link** na página principal — o endereço é `/painel/entrar`.
Se não houver email configurado, o código de recuperação de palavra-passe fica em `storage/logs/laravel.log` (ou ponha `MOZ_DEMO=true` no `.env` só para testes, para o código aparecer no ecrã).
O passageiro pode comprar **até 5 bilhetes** de cada vez (mesma viagem, um só pagamento). O administrador define o que cada perfil pode fazer em **Permissões**.
O passageiro também pode criar conta em `/criar-conta`.

## 3.1 Novidades desta versão
- **QR code no bilhete, a funcionar sem internet.** O QR é gerado no navegador (biblioteca local em `public/js/vendor`) e leva um código **assinado** (`MT-2026-000123.AB12CD34`),
  por isso não se consegue fabricar um bilhete só adivinhando o número. O passageiro pode **Guardar QR** (imagem PNG), partilhar ou imprimir.
- **Validação pelo cobrador:** câmara (lê o QR), leitor USB de QR/código de barras (escreve o código no campo e prime Enter) ou código escrito à mão.
  O cobrador vê as viagens **da sua transportadora** em qualquer dia (as atribuídas a ele em primeiro lugar) e a lista de passageiros de cada uma;
  se a viagem não tinha cobrador, passa a ser dele ao validar o primeiro bilhete.
- **Matrícula do autocarro** no bilhete, na página de pagamento, na lista de bilhetes, no comprovativo da bilheteira e para o cobrador.
- **Gestor — CRUD completo** (menu à esquerda):
  - **Autocarros:** criar, editar, activar/desactivar, eliminar (só sem viagens) e **upload do livrete** (PDF, JPG ou PNG até 5 MB; guardado em `storage/app/private/livretes`, só acessível a quem tem sessão e é da mesma transportadora).
  - **Motoristas:** criar, editar, activar/desactivar, eliminar (só sem viagens), com aviso de carta de condução a caducar. O motorista pode ser atribuído ao programar viagens.
  - **Cobradores:** criar (palavra-passe temporária), editar, bloquear/desbloquear, repor palavra-passe e eliminar (só sem histórico).
  - Permissões novas na matriz do administrador: *Gerir autocarros e livretes* e *Gerir motoristas e cobradores* (ligadas por omissão ao gestor).
- **Nome do sistema:** «Sistema de venda de bilhetes para transportes rodoviários de Moçambique» (alterável em `config/moz.php`).

> **Câmara no telemóvel:** os navegadores só deixam usar a câmara em `http://localhost` ou em `https://`. Num telemóvel ligado ao computador pela rede (`http://192.168...`) a câmara fica
> bloqueada: nesse caso use o leitor USB, escreva o código, ou publique o sistema com HTTPS.

## 4. O que está implementado (mapa para o documento)
| Requisito | Onde |
|---|---|
| RF01 criar conta; RF02 login; RF03 recuperar palavra-passe (código de 6 dígitos, 5 min, só por email); RF04 dados pessoais | `Auth/*`, `PerfilController` |
| RF06–RF09 pesquisar rotas/horários/preços e escolher lugar no mapa | `Passageiro/PassageiroController` |
| RF10 pagamento M-Pesa / e-Mola / cartão; RF11 bilhete com QR; RF12 cancelar/remarcar; RF13 histórico | idem + `ReservaService` |
| RF14–RF16 bilheteiro: viagens, lugares em tempo real, venda presencial em 3 passos, comprovativo, SMS | `Bilheteiro/BilheteiraController` |
| RF17–RF18 cobrador: validar QR (câmara ou código), lista de passageiros | `Cobrador/CobradorController` |
| RF19–RF22 gestor: dashboard, relatórios (CSV/PDF por impressão), estatísticas, rotas e horários | `Gestao/GestaoController` |
| RF23 administrador: utilizadores, permissões, transportadoras, auditoria, segurança/backup | `Admin/AdminController` |
| RN01 só viagens futuras · RN02 lugar não vendido duas vezes (índice único na BD + transacção) | `Viagem::futuras`, `ReservaService`, migration `reservas` |
| RN03/RN04 bloqueio temporário do lugar e libertação se não pagar | `ReservaService::libertarExpiradas` |
| RN05 QR único · RN11 QR só valida uma vez | `Bilhete::emitir`, `CobradorController::validar` |
| RN06 prazo para cancelar/remarcar (definível pelo admin) | `ReservaService::dentroDoPrazo` |
| RN13 gestor só vê a sua transportadora · RN16 desactivar rota não cancela reservas | `GestaoController` |
| RNF06 perfis e permissões (matriz editável) · RNF10 auditoria · RNF11 backup | `Permissao`, `Auditoria`, `BackupService` |
| Bloqueio de login após N tentativas falhadas | `LoginController` |

## 5. O que é SIMULADO (e como ligar ao real)
- **M-Pesa / e-Mola:** `App\Services\Pagamentos\SimuladoGateway`. Aprova, excepto números inválidos para o operador ou a terminar em `00`.
  Para as APIs reais, crie uma classe que implemente `PagamentoGateway` (com as credenciais de comerciante da Vodacom/Movitel) e mude a ligação em `AppServiceProvider`.
- **SMS** do bilheteiro: regista no `storage/logs/laravel.log`. **Email** de recuperação: `MAIL_MAILER=log` escreve no mesmo ficheiro; configure SMTP no `.env` para enviar a sério.
- **QR code:** gerado no navegador (biblioteca qrcodejs) e lido com a câmara (jsQR), ambas carregadas de `cdnjs.cloudflare.com`. Sem internet, o código do bilhete aparece como texto e pode ser digitado.

## 6. Produção
- `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `MOZ_DEMO=false`, MySQL, SMTP e `APP_URL` com HTTPS.
- Servidor: Nginx + PHP-FPM, com HTTPS (como no diagrama de implantação).
- Cron para o backup diário às 03:00: `* * * * * cd /caminho && php artisan schedule:run >> /dev/null 2>&1` (backup MySQL precisa do `mysqldump`).
- Altere as palavras-passe de demonstração (ou não corra o `DemoSeeder`: use apenas `php artisan db:seed --class=ConfiguracaoSeeder` e crie o primeiro administrador).

## 7. Testes
`php artisan test` — cobre: lugar nunca vendido duas vezes, pagar e receber bilhete, pagamento recusado, QR validado uma só vez,
controlo de acesso por perfil, login/bloqueio/recuperação de palavra-passe, cancelar e remarcar, gestão de rotas e viagens,
administração (utilizadores, permissões, definições), backup e a abertura de todas as páginas de cada perfil.

### Problemas comuns
| Sintoma | Solução |
|---|---|
| `could not find driver` | Active `pdo_sqlite` (ou `pdo_mysql`) no `php.ini` (XAMPP: `C:\xampp\php\php.ini`) |
| `No application encryption key` | `php artisan key:generate` |
| Página sem estilos | Abra pelo `php artisan serve` (http://localhost:8000), não o ficheiro directamente |
| `429 Too Many Requests` | Demasiados pedidos seguidos: aguarde 1 minuto |
| Upload do livrete falha com «Não foi possível enviar o ficheiro» | Arranque com `iniciar.bat` (aceita 10 MB) ou aumente `upload_max_filesize` e `post_max_size` no `php.ini` |
| QR não aparece | Precisa de internet (biblioteca no cdnjs); o código do bilhete continua visível como texto |

## 8. Estrutura
```
app/Models            Eloquent (Utilizador, Transportadora, Autocarro, Assento, Rota, Viagem, Reserva, Pagamento, Bilhete, ...)
app/Services          ReservaService (regras de reserva), Pagamentos/ (gateway), BackupService
app/Http/Controllers  Auth, Passageiro, Bilheteiro, Cobrador, Gestao, Admin
app/Http/Middleware   ExigePerfil, ExigePermissao, ContaActiva
database/migrations   MER do documento + tabelas de sistema
database/seeders      ConfiguracaoSeeder (permissões/definições) e DemoSeeder (dados fictícios)
resources/views       layouts, partials, auth, app (passageiro), bilheteira, cobrador, gestao, admin
public/css, public/js estilos e JavaScript (sem build, sem Node)
```
