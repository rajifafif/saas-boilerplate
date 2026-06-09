<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FilterScope implements Scope
{
    public function __construct(
        private array  $searchableColumns = [],
        private string $searchOperator = 'LIKE',
        private array  $filterableColumns = [],
        private array  $filterOperators = []
    )
    {
    }

    public function apply(Builder $builder, Model $model): void
    {
        $request = request();

        if ($search = $request->input('search')) {
            $builder->where(function ($query) use ($search) {
                foreach ($this->searchableColumns as $column) {
                    $query->orWhere($column, $this->searchOperator, $this->formatSearchValue($search));
                }
            });
        }

        foreach ($this->filterableColumns as $column) {
            $operator = $this->filterOperators[$column] ?? '=';
            $value = $request->input($column);
            $builder->when(
                $request->filled($column),
                fn($query) => $query->where($column, $operator, $operator === 'LIKE' ? "%{$value}%" : $value)
            );
        }
    }

    private function formatSearchValue(string $search): string
    {
        return $this->searchOperator === 'LIKE' ? "%{$search}%" : $search;
    }
}
