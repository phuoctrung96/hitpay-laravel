<?php

namespace App\Console\Commands\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Date;

/**
 * Trait HasDateTimeRange, best to use with command.
 *
 * A helper to extract start and end datetime with the given options.
 *
 * It is recommended to use the "getDateTimeRange". Options accepted:
 *
 * "date"       - An exact date.
 * "start_date" - A start date, will be ignored if an exact date is given.
 * "range"      - The range from the given start date.
 * "end_date"   - The end date, will be ignored if a range is given.
 * "period"     - A selected period, will be ignored if a date or start date is given.
 *
 * @author Wong Ban Korh <me@bankorh.com>
 */
trait HasDateTimeRange
{
    /**
     * Get a date range with the given options.
     *
     * @param  array  $options
     *
     * @return \Carbon\Carbon[]
     * @throws \Exception
     */
    protected function getDateTimeRange(array $options) : array
    {
        if (isset($options['date'])) {
            $date = Date::createFromFormat('Y-m-d', $options['date'])->startOfDay();

            return $this->getDateTimeRangeOfDate($date);
        }

        if (isset($options['start_date'])) {
            return $this->getDateTimeRangeFromStartDate($options['start_date'], $options);
        }

        if (isset($options['period'])) {
            return $this->getDateTimeRangeByPeriod($options['period']);
        }

        throw new Exception('The given options are invalid.');
    }

    /**
     * Get a date range by the given start date and range / end date.
     *
     * @param  string  $startDate
     * @param  array  $options
     *
     * @return \Carbon\Carbon[]
     * @throws \Exception
     */
    protected function getDateTimeRangeFromStartDate(string $startDate, array $options) : array
    {
        $startDate = Date::createFromFormat('Y-m-d', $startDate)->startOfDay();

        if (isset($options['range'])) {
            if ($options['range'] === 'month') {
                return [ $startDate, $startDate->clone()->addMonth()->subDay()->endOfDay() ];
            } elseif ($options['range'] === 'week') {
                return [ $startDate, $startDate->clone()->addWeek()->subDay()->endOfDay() ];
            }

            throw new Exception('The given "range" value is invalid.');
        } elseif (isset($options['end_date'])) {
            $endDate = Date::createFromFormat('Y-m-d', $options['end_date'])->endOfDay();

            return [ $startDate, $endDate ];
        }

        throw new Exception('A range or an end date is expected with a start date.');
    }

    /**
     * Get a date range by the given period.
     *
     * @param  string  $period
     *
     * @return \Carbon\Carbon[]
     * @throws \Exception
     */
    protected function getDateTimeRangeByPeriod(string $period) : array
    {
        if ($period === 'last_month') {
            $startDate = Date::now()->startOfMonth()->subMonth();

            return [ $startDate, $startDate->clone()->endOfMonth() ];
        }

        if ($period === 'last_week') {
            $startDate = Date::now()->startOfWeek()->subWeek();

            return [ $startDate, $startDate->clone()->endOfWeek() ];
        }

        if ($period === 'yesterday') {
            return $this->getDateTimeRangeOfDate(Date::yesterday());
        }

        if ($period === 'today') {
            return $this->getDateTimeRangeOfDate(Date::today());
        }

        throw new Exception('The given "period" value is invalid.');
    }

    /**
     * Get a date range of the given date.
     *
     * @param  \Carbon\Carbon  $date
     *
     * @return \Carbon\Carbon[]
     */
    protected function getDateTimeRangeOfDate(Carbon $date) : array
    {
        return [ $date, $date->clone()->endOfDay() ];
    }
}
