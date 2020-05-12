<?php


namespace App\Http\Services;


use App\Models\AdminSetting;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    /**
     * @param $request
     * @return array
     */
    public function SaveSuperAdminSettings($request)
    {
        try {
            DB::beginTransaction();
            foreach ($request->except('_token') as $key => $value) {
                if ($key == 'main_logo') {
                    $logo = uploadFile($request->main_logo, logoPath());
                    AdminSetting::updateOrCreate(['slug' => $key], ['value' => $logo]);
                } elseif ($key == 'main_image') {
                    $image = uploadFile($request->main_image, imagePath());
                    AdminSetting::updateOrCreate(['slug' => $key], ['value' => $image]);
                } else {
                    AdminSetting::updateOrCreate(['slug' => $key], ['value' => $value]);
                }
            }
            DB::commit();

            return [
                'success' =>  true,
                'message' => __('Settings has been updated successfully'),
                'data' => null
            ];
        } catch (\Exception $exception) {
            DB::rollBack();

            return [
                'success' =>  false,
                'message' => __('Something went wrong. Please try again.') . $exception->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * @param $request
     * @return array
     */
    public function SaveAdminSettings($request)
    {
        try {
            DB::beginTransaction();
            foreach ($request->except('_token') as $key => $value) {
                if ($request->hasFile($key)) {
                    $value = uploadFile($value, appImagePath());
                }
                AdminSetting::updateOrCreate(['slug' => $key], ['value' => $value]);
            }
            DB::commit();
            return [
                'success' =>  true,
                'message' => __('Settings has been updated successfully'),
                'data' => null
            ];
        } catch (\Exception $exception) {
            DB::rollBack();

            return [
                'success' =>  false,
                'message' => __('Something went wrong. Please try again.') . $exception->getMessage(),
                'data' => null
            ];
        }
    }
}
