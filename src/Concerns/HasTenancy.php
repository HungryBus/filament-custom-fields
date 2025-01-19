<?php

namespace HungryBus\CustomFields\Concerns;

use Illuminate\Database\Eloquent\RelationNotFoundException;

trait HasTenancy
{
    public function tenantRelationship()
    {
        if (! config('custom-fields.tenant_model')) {
            throw new RelationNotFoundException('Tenant model not found');
        }

        return $this->belongsTo(
            config('custom-fields.models.tenant_model'),
            config('custom-fields.models.tenant_model')::getForeignKey(),
        );
    }
}
