<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use Illuminate\Console\Command;

class PurgeDeletedAdminUsers extends Command
{
    protected $signature = 'admin-users:purge {--days=30 : Nombre de jours après suppression logique avant purge définitive}';

    protected $description = 'Purge définitive des comptes console supprimés logiquement au-delà du délai de rétention';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = AdminUser::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->forceDelete();

        $this->info("Purged {$count} compte(s) supprimé(s) depuis plus de {$days} jours.");

        return self::SUCCESS;
    }
}
