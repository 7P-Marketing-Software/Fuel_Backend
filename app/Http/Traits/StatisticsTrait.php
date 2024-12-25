<?php

namespace App\Http\Traits;

trait StatisticsTrait
{

    protected function getModelInstance()
    {
        return new static();
    }

    public static function countModel()
    {
        return (new self())->count();
    }

    public static function countTrashedModel()
    {
        return (new self())->onlyTrashed()->count();
    }

    public static function countCreatedBetween($startDate, $endDate)
    {
        return (new self())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }
}
