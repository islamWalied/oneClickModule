<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait Utils
{
    public function getTheNextOrder($table, $column)
    {
        return DB::table($table)->max($column) + 1;
    }
    public function attachFavoriteStatus($model, $modelClass, $userId)
    {
        if (!$userId) {
            return $model;
        }

        $modelIds = $model->pluck('id')->toArray();

        $favoriteModel = DB::table('favourites')
            ->where('user_id', $userId)
            ->where('favoritable_type', $modelClass)
            ->whereIn('favoritable_id', $modelIds)
            ->pluck('favoritable_id')
            ->toArray();

        foreach ($model as $m) {
            $m->is_favorite = in_array($m->id, $favoriteModel);
        }

        return $model;
    }

}
