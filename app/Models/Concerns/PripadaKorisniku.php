<?php

namespace App\Models\Concerns;

use App\Models\Scopes\VlasnikScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Zapis pripada korisniku: upiti su ograničeni na prijavljenog korisnika,
 * a novi zapis automatski dobiva njegov user_id.
 *
 * @mixin Model
 */
trait PripadaKorisniku
{
    public static function bootPripadaKorisniku(): void
    {
        static::addGlobalScope(new VlasnikScope);

        static::creating(function (Model $model): void {
            $model->user_id ??= Auth::id();
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
