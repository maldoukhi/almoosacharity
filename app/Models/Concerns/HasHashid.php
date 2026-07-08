<?php

namespace App\Models\Concerns;

use App\Support\Hashids;

/**
 * Makes a model's route key a hashid instead of its raw auto-increment id,
 * so URLs never expose sequential ids. Route generation
 * (route('aids.show', $aid)) emits the hashid, and implicit binding
 * (/aids/{aid}) decodes it — always pass the model (not its raw id) to
 * route()/redirectRoute() so the emitted URL carries the hashid.
 */
trait HasHashid
{
    public function getRouteKey(): string
    {
        return app(Hashids::class)->encode((int) $this->getKey());
    }

    public function getHashidAttribute(): string
    {
        return $this->getRouteKey();
    }

    public function resolveRouteBinding($value, $field = null)
    {
        // An explicit field (e.g. {aid:reference}) uses the normal path.
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        $id = app(Hashids::class)->decode((string) $value);

        if ($id === null) {
            return null;
        }

        return $this->resolveRouteBindingQuery($this, $id)->first();
    }
}
