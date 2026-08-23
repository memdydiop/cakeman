<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Réglages clé-valeur de la console (Module Configuration) — schéma central,
 * lus avec cache mémoire (invalidé à chaque écriture). Les valeurs par défaut
 * restent fournies par les appelants (`Setting::get('billing.grace_days', 7)`),
 * ce qui garantit une rétrocompatibilité même avant le premier enregistrement.
 */
class Setting extends Model
{
    use CentralConnection;

    public const UPDATED_AT = null;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Cache des réglages — une seule requête pour tout le module. Le cache
     * stocke un tableau associatif (et non une Collection) : le désérialisage
     * ne dépend d'aucune classe du projet, aucun risque d'`__PHP_Incomplete_Class`.
     *
     * `Cache::store()` contourne le CacheManager tenancy (stancl) : en contexte
     * tenant, son bootstrapper tagge chaque appel (`->tags([tenant])`), ce que
     * le store `database` refuse (« This cache store does not support tagging »).
     * Les réglages sont centraux : leur cache ne doit jamais être taggé.
     *
     * @return Collection<string, string|null>
     */
    public static function allCached(): Collection
    {
        $values = Cache::store()->rememberForever(
            'settings.all',
            fn (): array => self::query()->pluck('value', 'key')->all(),
        );

        return new Collection($values);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::allCached()->get($key);

        return $value === null ? $default : $value;
    }

    /**
     * @param  scalar|null  $value
     */
    public static function set(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);

        self::forgetCache();
    }

    public static function forgetCache(): void
    {
        Cache::store()->forget('settings.all');
    }

    /**
     * Enregistre un lot de réglages (un seul flush de cache).
     *
     * @param  array<string, scalar|null>  $values
     */
    public static function setMany(array $values): void
    {
        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                self::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
            }
        });

        self::forgetCache();
    }
}
