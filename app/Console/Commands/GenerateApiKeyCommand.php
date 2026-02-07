<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class GenerateApiKeyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apikey:generate
                            {name? : Nombre descriptivo para la API Key}
                            {--days= : Días hasta la expiración (opcional)}
                            {--inactive : Crear la key como inactiva}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera una nueva API Key para autenticación';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name') ?? text(
            label: '¿Cuál es el nombre de la aplicación?',
            placeholder: 'Ej: App Móvil, Dashboard, etc.',
            required: true
        );

        $days = $this->option('days');
        $expiresAt = null;

        if ($days) {
            $expiresAt = now()->addDays((int) $days);
        } elseif (confirm('¿Deseas establecer una fecha de expiración?', false)) {
            $days = text(
                label: '¿Cuántos días de validez?',
                placeholder: '365',
                required: true,
                validate: fn ($value) => is_numeric($value) && $value > 0
                    ? null
                    : 'Debe ser un número mayor a 0'
            );
            $expiresAt = now()->addDays((int) $days);
        }

        $active = ! $this->option('inactive');

        $apiKey = ApiKey::generate(
            name: $name,
            active: $active,
            expiresAt: $expiresAt
        );

        info('✅ API Key generada exitosamente!');
        $this->newLine();
        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $apiKey->id],
                ['Nombre', $apiKey->name],
                ['Key', $apiKey->key],
                ['Estado', $apiKey->active ? '✅ Activa' : '❌ Inactiva'],
                ['Expira', $apiKey->expires_at?->format('Y-m-d H:i:s') ?? 'Nunca'],
                ['Creada', $apiKey->created_at->format('Y-m-d H:i:s')],
            ]
        );

        $this->newLine();
        $this->warn('⚠️  Guarda esta API Key de forma segura. No podrás verla nuevamente.');
        $this->newLine();

        return self::SUCCESS;
    }
}
