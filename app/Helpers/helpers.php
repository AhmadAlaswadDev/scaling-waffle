<?php

use App\Enums\VatType;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function saveImage($path, $file)
{
    $filename = time() . '_' . mt_rand(111111 , 999999) . '.' . $file->getClientOriginalExtension();
    $full_stored_image_path = Storage::disk('public')->putFileAs($path, $file, $filename);
    return $full_stored_image_path;
}

function storeImage($path, $file)
{
    $imageName = Str::random() . '.' . $file->getClientOriginalExtension();
    Storage::disk('public')->putFileAs($path, $file, $imageName);
    return $imageName;
}

function editImage($path, $file, $oldImage)
{
    deleteImage($oldImage);

    $imageName = Str::random() . '.' . $file->getClientOriginalExtension();
    Storage::disk('public')->putFileAs($path, $file, $imageName);
    return $imageName;
}

function deleteImage($oldImage)
{
    $exists = Storage::disk('public')->exists($oldImage);
    if ($exists) {
        $exists = Storage::disk('public')->delete($oldImage);
        return true;
    }
}


function getImageUrl($image)
{
    $exists = Storage::disk('public')->exists($image);
    if ($exists) {
        return Storage::url($image);
    } else {
        return asset('dist/img/product-placeholder.webp');
    }
}


/**
 * Generate Response
 * @param  bool $status
 * @param string $redirect
 * @param string $modal_to_hide
 * @param  string $row_to_delete
 * @param  bool $reset_form
 */
if (!function_exists('generateResponse')) {
    function generateResponse(
        $status,
        $redirect = null,
        $modal_to_hide = null,
        $row_to_delete = null,
        $reset_form = false,
        $reload = false,
        $table_reload = false,
        $table = null,
        $is_deleted = false,
        $message = null
    ) {
        $response_message = !is_null($message) ? ($message) : ($status ? __('general.response_messages.success') : __('general.response_messages.error'));
        return [
            'status' => $status,
            'message' => $response_message,
            'redirect' => $redirect ? $redirect : null,
            'modal_to_hide' => $modal_to_hide,
            'row_to_delete' => $row_to_delete,
            'reset_form' => $reset_form,
            'code' => $status ? 200 : 500,
            'reload' => $reload,
            'reload_table' => $table_reload,
            'table' => $table,
            'is_deleted' => $is_deleted,
        ];
    }
}



/**
 * get avilable locales
 * @return array
 */
if (!function_exists('getAvilableLocales')) {
    function getAvilableLocales()
    {
        return config('translatable.locales') ?? [];
    }
}
/**
 * Hash the given value
 * @param mixed $value
 * @return mixed
 */
if (!function_exists('makeHash')) {
    function makeHash($value)
    {
        return Hash::make($value);
    }
}


/**
 * Hash the given value
 * @param mixed $value
 * @return mixed
 */
if (!function_exists('getAuthUser')) {
    function getAuthUser($guard = 'web')
    {
        return Auth::guard($guard)->user();
    }
}


if (!function_exists('generateUniqueRandomNumber')) {

    function generateUniqueRandomNumber($model, $column, $length)
    {
        // Generate a random number based on current year and random digits
        $current_year = date('Y');
        $remaining_length = $length - strlen($current_year);
        mt_srand();
        $random_number = $current_year . generateRandomDigits($remaining_length);
        if (columnValueExists($model, $column, $random_number)) {
            mt_srand();
            return generateUniqueRandomNumber($model, $column, $length);
        }
        return $random_number;
    }
}


if (!function_exists('generateRandomDigits')) {
    function generateRandomDigits($length)
    {
        $digits = '';
        for ($i = 0; $i < $length; $i++) {
            $digits .= mt_rand(0, 9);
        }
        return str_shuffle(str_shuffle($digits));
    }
}


if (!function_exists('columnValueExists')) {
    function columnValueExists($model, $column, $value)
    {
        return $model::where($column, $value)->exists();
    }
}

if (!function_exists('get_setting')) {
    function get_setting($key, $default = null, $lang = false)
    {
        $settings = DB::table('business_settings')->get();


        if ($lang == false) {
            $setting = $settings->where('type', $key)->first();
        } else {
            $setting = $settings->where('type', $key)->where('lang', $lang)->first();
            $setting = !$setting ? $settings->where('type', $key)->first() : $setting;
        }
        return $setting == null ? $default : $setting->value;
    }
}


/**
 * get system only currency "SAR"
 * @return string
 */
if (!function_exists('getSystemCurrency')) {
    function getSystemCurrency()
    {
        return app()->getLocale() == 'sa' ? 'ريال' : 'SAR';
    }
}


/**
 * Generate Google Api Location Link To Chekc On Google Maps
 */
if (!function_exists('generateGoogleMapsLink')) {
    function generateGoogleMapsLink($latitude, $longitude)
    {
        $url = 'https://www.google.com/maps/search/?api=1&query=' . $latitude . ',' . $longitude;
        return $url;
    }
}


/**
 * Generate Google Api Location Link To Chekc On Google Maps
 */
if (!function_exists('getStockDiscount')) {
    function getStockDiscount($total_price, $discount_amount, $discount_type, $with_currency = false)
    {
        if ($discount_type == 'percent') {
            $total_discount = ($discount_amount / 100) * $total_price;
        } else {
            $total_discount = $discount_amount;
        }
        // return total discount with currency if needed
        return ($total_price - $total_discount);
    }
}


//return file uploaded via uploader
if (!function_exists('uploaded_asset')) {
    function uploaded_asset($id)
    {
        if (($asset = \App\Models\Upload::find($id)) != null) {
            return $asset->external_link == null ? my_asset($asset->file_name) : $asset->external_link;
        }
        return asset('assets/img/placeholder.jpg');
    }
}

if (!function_exists('my_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function my_asset($path, $secure = null)
    {
        if (env('FILESYSTEM_DRIVER') == 's3') {
            return Storage::disk('s3')->url($path);
        } else {
            // $base_path = @explode('/' , $path)[0];
            // if($base_path == 'storage')
            // {
            //     return app('url')->asset($path, $secure);
            // }
            return ('https://dev.zaheed.sa/public/' . $path);
        }
    }

}





//return file uploaded via uploader
if (!function_exists('uploaded_asset')) {
    function uploaded_asset($id)
    {
        if (($asset = \App\Models\Upload::find($id)) != null) {
            return $asset->external_link == null ? my_asset($asset->file_name) : $asset->external_link;
        }
        return asset('assets/img/placeholder.jpg');
    }
}



//get user agent
if (!function_exists('getUserAgent')) {
    function getUserAgent()
    {
        $user_agent = request()->userAgent();
        if (stripos($user_agent, 'Chrome') !== false) {
            return "Chrome";
        } elseif (stripos($user_agent, 'Safari') !== false) {
            return "Safari";
        }
    }
}


if (!function_exists('static_asset')) {
    /**
     * Generate an asset path for the application.
     * Change static assets call . "better way"
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function static_asset($path, $secure = null)
    {
        // return asset('/' . $path, $secure);
        return app('url')->asset('public/' . $path, $secure);
    }
}

