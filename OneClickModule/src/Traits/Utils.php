<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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

    protected function makeOTP($user, $otp = null)
    {
        try {
            if (!$otp)
                $otp = rand(100000, 999999);
            $user->otp = Hash::make($otp);
            $user->otp_valid_until = now()->addMinutes(60);
            $user->save();
            return $otp;
        } catch (\Exception $e) {
            Log::error("MakeOTP failed", [$e->getMessage()]);
            return $this->returnError(__("messages.otp_failed"), 400);

        }
    }

}
