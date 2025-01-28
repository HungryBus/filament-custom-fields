<?php

namespace HungryBus\CustomFields\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait TenantHasCustomFields
{
    public function fields(): HasMany
    {
        return $this->hasMany(
            config('custom-fields.models.custom_field'),
            config('custom-fields.tenant_key')
        );
    }
}
