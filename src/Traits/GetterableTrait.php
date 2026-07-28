<?php

namespace Maarsson\EloquentGetter\Traits;

use Illuminate\Support\Str;
use Maarsson\EloquentGetter\Getters\AbstractEloquentGetter;

trait GetterableTrait
{
    /**
     * Relations to eager load on the next getter query.
     *
     * @var array<int|string, mixed>
     */
    private array $getterRelations = [];

    /**
     * The required model attributes and/or relations´ attributes,
     * even with column specifications.
     * Note that the subrelations´ id must be given when querying subrelation.
     *
     * @var array<string>
     */
    private array $withAttributes = [];

    /**
     * Get a subset of the model's attributes and relations.
     *
     * @param null|array<int, string> $attributes
     *
     * @return array<string, mixed>
     */
    public function withAttributes(?array $attributes = []): array
    {
        return empty($attributes) ?
            $this->toArray()
            :
            $this->loadMissing($this->getValidRelations($attributes))
                ->only($this->getValidAttributes($attributes));
    }

    /**
     * Set valid relations to eager load on the next getter query.
     *
     * @param array<int, string> $relations
     */
    public function withRelations(array $relations): self
    {
        $this->getterRelations = $this->getValidRelations($relations);

        return $this;
    }

    /**
     * Scope a query to apply given filter.
     *
     * @param \Maarsson\EloquentGetter\Getters\AbstractEloquentGetter $filter
     * @param array $relations
     *
     * @return \Maarsson\EloquentGetter\Getters\AbstractEloquentGetter
     */
    public function filter(?AbstractEloquentGetter $getter = null, array $relations = []): AbstractEloquentGetter
    {
        $relations = array_merge(
            $this->getterRelations,
            $relations
        );

        $this->getterRelations = [];

        return $getter->apply($this->query()->with($relations))->filter();
    }

    /**
     * Scope a query to apply given sorter.
     *
     * @param \Maarsson\EloquentGetter\Getters\AbstractEloquentGetter $filter
     *
     * @return \Maarsson\EloquentGetter\Getters\AbstractEloquentGetter
     */
    public function order(?AbstractEloquentGetter $getter = null): AbstractEloquentGetter
    {
        return $getter->apply($this->query())->order();
    }

    /**
     * Return only valid model relations.
     *
     * @param array<int, string> $attributes
     *
     * @return array<int, string>
     */
    private function getValidRelations(array $attributes): array
    {
        return collect($attributes)
                ->filter(function (string $attribute): bool {
                    $relation = Str::before($attribute, ':');
                    $rootRelation = Str::before($relation, '.');

                    return $rootRelation !== ''
                        && $this->isRelation($rootRelation);
                })
                ->values()
                ->all();
    }

    /**
     * Return the root attributes requested from the model.
     *
     * @param array<int, string> $attributes
     *
     * @return array<int, string>
     */
    private function getValidAttributes(array $attributes): array
    {
        return collect($attributes)
            ->map(
                fn (string $attribute): string =>
                    explode('.', explode(':', $attribute, 2)[0], 2)[0]
            )
            ->unique()
            ->values()
            ->all();
    }
}
