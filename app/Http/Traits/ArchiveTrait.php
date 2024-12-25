<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;

trait ArchiveTrait
{
    /**
     * Show archived (soft-deleted) records for the model.
     *
     * @param string|null $fieldName Field to filter by.
     * @param mixed|null $fieldValue Value of the field to filter by.
     * @return array
     */
    public static function showArchive(string $fieldName = null, $fieldValue = null)
    {
        // Use late static binding to get the calling model
        $model = new static();

        $query = $model::withTrashed()->whereNotNull('deleted_at');

        if ($fieldName && $fieldValue) {
            if (Schema::hasColumn($model->getTable(), $fieldName)) {
                $query->where($fieldName, $fieldValue);
            } else {
                return response()->json(['error' => 'Invalid field name'], 400);
            }
        }

        $archiveData = $query->orderBy('deleted_at', 'asc')->paginate();
        $totalArchived = $query->count();

        return [$archiveData, ['total number' => $totalArchived]];
    }

    /**
     * Restore archived (soft-deleted) records for the model.
     *
     * @param int|string|array|null $ids IDs of the records to restore.
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public static function restoreArchive(int|string|array $ids = null)
    {
        if (is_null($ids)) {
            return response()->json(['error' => 'The ID is invalid.'], 400);
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        // Use late static binding to get the calling model
        $model = new static();

        $records = $model::withTrashed()->whereIn('id', $ids)->get();

        if ($records->isEmpty()) {
            throw new ModelNotFoundException('No records found.');
        }

        foreach ($records as $record) {
            $record->restore();
        }

        return true;
    }
}
