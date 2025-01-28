<?php

namespace HungryBus\CustomFields\Services;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Tables\Columns\TextColumn;
use HungryBus\CustomFields\Models\Field;
use HungryBus\CustomFields\Resolvers\CustomData\CustomColumnResolver;
use HungryBus\CustomFields\Resolvers\CustomData\CustomFieldResolver;
use HungryBus\CustomFields\Resolvers\CustomData\CustomInfolistEntryResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FieldsService
{
    public static function getCustomFields(string $model, ?\Closure $closure = null): \Illuminate\Support\Collection
    {
        return Field::model($model)
            ->when(
                config('custom-fields.use_tenants'),
                static fn (Builder $query): Builder => $query->where(
                    config('custom-fields.tenant_key'),
                    Filament::getTenant()->getKey()
                )
            )
            ->when(
                $closure,
                static fn (Builder $query): Builder => $closure($query)
            )
            ->orderBy('group_order')
            ->get()
            ->groupBy('group')
            ->each(static fn (Collection $fields) => $fields->sortBy('order'));
    }

    public static function getCustomTableColumns(string $model, ?\Closure $closure = null): \Illuminate\Support\Collection
    {
        return Field::model($model)
            ->when(
                config('custom-fields.use_tenants'),
                static fn ($query) => $query->where(
                    config('custom-fields.tenant_key'),
                    Filament::getTenant()->getKey()
                )
            )
            ->when(
                $closure,
                static fn (Builder $query): Builder => $closure($query)
            )
            ->where('is_table_visible', true)
            ->get();
    }

    public static function buildForm(string $model): array
    {
        $schema = [];
        foreach (self::getCustomFields($model) as $group => $fields) {
            $schema[] = Forms\Components\Section::make($group)
                ->columns($fields->count() > 1 ? 2 : 1)
                ->schema(static function () use ($fields): array {
                    $groupFields = [];

                    foreach ($fields as $field) {
                        $groupFields[] = CustomFieldResolver::make($field)->resolve();
                    }

                    return $groupFields;
                });
        }

        return $schema;
    }

    public static function buildInfolist(string $model): array
    {
        $schema = [];
        foreach (self::getCustomFields($model) as $group => $fields) {
            $schema[] = Section::make($group)
                ->columns()
                ->schema(static function () use ($fields) {
                    $groupFields = [];

                    /** @var Field $field */
                    foreach ($fields as $field) {
                        $groupFields[] = CustomInfolistEntryResolver::make($field)->resolve();
                    }

                    return $groupFields;
                });
        }

        return $schema;
    }

    /** @returns TextColumn[] */
    public static function buildTable($model, array $columns): array
    {
        if (! is_subclass_of($model, Model::class)) {
            throw new \InvalidArgumentException('Model must be an instance of ' . Model::class);
        }

        if (! array_key_exists($model, config('custom-fields.field_designations'))) {
            return $columns;
        }

        foreach (self::getCustomTableColumns($model) as $field) {
            $columns[] = CustomColumnResolver::make($field)->resolve();
        }

        return $columns;
    }
}
