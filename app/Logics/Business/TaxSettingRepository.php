<?php

namespace App\Logics\Business;

use App\Business;
use App\Helpers\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaxSettingRepository
{
    /**
     * Create a new tax settings.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business\TaxSetting
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business) : Business\TaxSetting
    {
        $data = Validator::validate($request->all(), [
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|between:0,99.99',
        ]);

        return DB::transaction(function () use ($business, $data) : Business\TaxSetting {
            $taxSettings = $business->tax_settings()->create($data);

            return $taxSettings;
        }, 3);
    }

    /**
     * Update an existing tax.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\TaxSetting $tax
     *
     * @return \App\Business\TaxSetting
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function update(Request $request, Business\TaxSetting $tax) : Business\TaxSetting
    {
        $data = Validator::validate($request->all(), [
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|between:0,99.99',
        ]);

        $tax = DB::transaction(function () use ($tax, $data) : Business\TaxSetting {
            $tax->update($data);

            return $tax;
        }, 3);

        return $tax;
    }

    /**
     * Delete an existing tax setting.
     *
     * @param \App\Business\TaxSetting $tax
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(Business\TaxSetting $tax) : ?bool
    {
        return DB::transaction(function () use ($tax) : ?bool {
            return $tax->delete();
        }, 3);
    }

    /**
     * Get list tax settings
     *
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getList(Request $request, Business $business)
    {
        $paginator = $business->tax_settings();

        $keywords = $request->get('keywords', null);

        if (strlen($keywords) !== 0) {
            $keywords = is_array($keywords) ? $keywords : explode(' ', $keywords);

            $keywords = array_map(function ($value) {
                return trim($value);
            }, $keywords);

            $keywords = array_filter($keywords);

            $keywords = array_unique($keywords);

            if (count($keywords)) {
                foreach ($keywords as $keyword) {
                    $paginator->where('name', 'like', '%'.$keyword.'%');
                }
            }
        }

        $paginateNumber = Pagination::getDefaultPerPage();

        if ($request->has('per_page')) {
            $paginateNumber = $request->per_page;
        }

        $paginator = $paginator->orderByDesc('created_at')->paginate($paginateNumber);

        return $paginator;
    }
}
