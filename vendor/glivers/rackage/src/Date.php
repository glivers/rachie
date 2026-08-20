<?php namespace Rackage;

/**
 * Date Helper Class
 *
 * Provides comprehensive date and time manipulation utilities with timezone support.
 *
 * Static Design:
 *   All methods are static - no instance creation required.
 *   Uses the timezone configured in config/settings.php (applied in bootstrap).
 *
 * Access Patterns:
 *
 *   In Controllers:
 *     use Rackage\Date;
 *
 *     class PostsController extends Controller {
 *         public function getIndex() {
 *             $posts = Posts::where('created_at >', Date::subtract(Date::now(), '1 week'))->all();
 *         }
 *     }
 *
 *   In Views:
 *     Date is automatically available (configured in view_helpers).
 *     No 'use' statement needed.
 *
 *     <time>{{ Date::format($post->created_at, 'F j, Y') }}</time>
 *     <span>{{ Date::ago($post->created_at) }}</span>
 *
 * Usage Categories:
 *
 *   1. CURRENT DATE/TIME
 *      - now()           Get current datetime in any format
 *
 *   2. FORMATTING
 *      - format()        Format dates/timestamps to any format
 *
 *   3. HUMAN-READABLE
 *      - ago()           Get "2 hours ago" style output
 *
 *   4. DATE ARITHMETIC
 *      - add()           Add days/hours/months/years to dates
 *      - subtract()      Subtract days/hours/months/years from dates
 *
 *   5. DATE COMPARISON
 *      - diff()          Calculate difference between dates in any unit
 *
 *   6. UTILITIES
 *      - weekend()       Check if date is weekend
 *      - parse()         Convert string to timestamp
 *
 *   7. DATE CHECKS
 *      - isToday()       Check if date is today
 *      - isPast()        Check if date is in the past
 *      - isFuture()      Check if date is in the future
 *
 *   8. DATE BOUNDARIES
 *      - startOfDay()    Get start of day (00:00:00)
 *      - endOfDay()      Get end of day (23:59:59)
 *      - startOfMonth()  Get first day of month
 *      - endOfMonth()    Get last day of month
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Date
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.2
 */
class Date {

    /**
     * Private constructor to prevent creating instances
     * @return void
     */
    private function __construct() {}

    /**
     * Private clone to prevent cloning
     * @return void
     */
    private function __clone() {}

    // =========================================================================
    // CURRENT DATE/TIME
    // =========================================================================

    /**
     * Get current datetime in specified format
     *
     * Returns the current date and time formatted according to your preference.
     * Uses the timezone configured in config/settings.php.
     *
     * Examples:
     *   Date::now()                      // "2024-01-15 14:30:45"
     *   Date::now('Y-m-d')               // "2024-01-15"
     *   Date::now('F j, Y')              // "January 15, 2024"
     *   Date::now('g:i A')               // "2:30 PM"
     *   Date::now('l, F j, Y g:i A')     // "Monday, January 15, 2024 2:30 PM"
     *
     * Common format characters:
     *   Y - 4-digit year (2024)
     *   m - Month with leading zeros (01-12)
     *   d - Day with leading zeros (01-31)
     *   H - 24-hour format (00-23)
     *   i - Minutes (00-59)
     *   s - Seconds (00-59)
     *   F - Full month name (January)
     *   M - Short month name (Jan)
     *   l - Full day name (Monday)
     *   D - Short day name (Mon)
     *
     * Usage in database inserts:
     *   Posts::save([
     *       'title' => 'New Post',
     *       'created_at' => Date::now()
     *   ]);
     *
     * @param string $format Date format (default: Y-m-d H:i:s)
     * @return string Formatted current datetime
     */
    public static function now($format = 'Y-m-d H:i:s') 
    {
        return date($format);
    }

    // =========================================================================
    // FORMATTING
    // =========================================================================

    /**
     * Format a date string or timestamp
     *
     * Converts dates/timestamps to any desired format.
     * Accepts both Unix timestamps and date strings.
     *
     * Examples:
     *   Date::format('2024-01-15')                    // "2024-01-15 00:00:00"
     *   Date::format('2024-01-15', 'F j, Y')          // "January 15, 2024"
     *   Date::format(1705334445, 'M j, Y')            // "Jan 15, 2024"
     *   Date::format($post->created_at, 'Y-m-d')      // "2024-01-15"
     *
     * Usage in views:
     *   <time>{{ Date::format($post->created_at, 'F j, Y') }}</time>
     *
     * Usage in controllers:
     *   $formattedDate = Date::format($user->birthday, 'm/d/Y');
     *
     * @param string|int $date Date string or Unix timestamp
     * @param string $format Desired output format (default: Y-m-d H:i:s)
     * @return string Formatted date
     */
    public static function format($date, $format = 'Y-m-d H:i:s') 
    {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }

    // =========================================================================
    // HUMAN-READABLE
    // =========================================================================

    /**
     * Get human-readable time difference
     *
     * Converts dates to friendly "time ago" format perfect for social media,
     * comments, posts, and activity feeds.
     *
     * Examples:
     *   Date::ago('2024-01-15 14:29:00')  // "just now" (if < 1 min)
     *   Date::ago('2024-01-15 14:00:00')  // "30 minutes ago"
     *   Date::ago('2024-01-15 10:00:00')  // "4 hours ago"
     *   Date::ago('2024-01-14')           // "1 day ago"
     *   Date::ago('2024-01-08')           // "1 week ago"
     *   Date::ago('2023-12-15')           // "1 month ago"
     *   Date::ago('2023-01-15')           // "1 year ago"
     *
     * Usage in views (blog/social feed):
     *   @foreach($posts as $post)
     *       <div class="post">
     *           <h3>{{ $post->title }}</h3>
     *           <small>{{ Date::ago($post->created_at) }}</small>
     *       </div>
     *   @endforeach
     *
     * Usage in comments:
     *   <span class="time">{{ Date::ago($comment->created_at) }}</span>
     *
     * @param string|int $date Date string or Unix timestamp
     * @return string Human-readable time difference (e.g., "2 hours ago")
     */
    public static function ago($date) 
    {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($diff / 31536000);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    }

    // =========================================================================
    // DATE ARITHMETIC
    // =========================================================================

    /**
     * Add time interval to a date
     *
     * Adds days, hours, months, years, or combinations to a date.
     * Supports both numeric (days only, backward compatible) and string intervals.
     *
     * Examples - Backward compatible (numeric = days):
     *   Date::add('2024-01-15', 7)                    // "2024-01-22 00:00:00"
     *   Date::add(Date::now(), 30)                    // 30 days from now
     *
     * Examples - String intervals (new):
     *   Date::add('2024-01-15', '3 months')           // "2024-04-15 00:00:00"
     *   Date::add('2024-01-15', '2 hours')            // "2024-01-15 02:00:00"
     *   Date::add('2024-01-15', '1 year')             // "2025-01-15 00:00:00"
     *   Date::add('2024-01-15', '2 weeks')            // "2024-01-29 00:00:00"
     *   Date::add('2024-01-15', '5 days 3 hours')     // "2024-01-20 03:00:00"
     *
     * Supported units:
     *   - seconds, minutes, hours
     *   - days, weeks
     *   - months, years
     *   - Combinations: '1 month 2 days', '3 hours 30 minutes'
     *
     * Usage - Trial expiration:
     *   $trial = [
     *       'user_id' => $user->id,
     *       'expires_at' => Date::add(Date::now(), '14 days')
     *   ];
     *
     * Usage - Scheduling:
     *   $reminder = Date::add($event->start_date, '1 hour');
     *
     * @param string|int $date Date string or Unix timestamp
     * @param int|string $amount Number of days (int) or interval string ('3 months')
     * @param string $format Output format (default: Y-m-d H:i:s)
     * @return string New date
     */
    public static function add($date, $amount, $format = 'Y-m-d H:i:s') 
    {
        $timestamp = is_numeric($date) ? $date : strtotime($date);

        // Backward compatible: numeric = days
        if (is_numeric($amount)) {
            $interval = "+{$amount} days";
        } else {
            // String interval: pass directly to strtotime
            // Ensure it has + prefix if not already present
            $interval = (strpos($amount, '+') === 0 || strpos($amount, '-') === 0)
                ? $amount
                : '+' . $amount;
        }

        $newTimestamp = strtotime($interval, $timestamp);
        return date($format, $newTimestamp);
    }

    /**
     * Subtract time interval from a date
     *
     * Subtracts days, hours, months, years, or combinations from a date.
     * Supports both numeric (days only, backward compatible) and string intervals.
     *
     * Examples - Backward compatible (numeric = days):
     *   Date::subtract('2024-01-15', 7)               // "2024-01-08 00:00:00"
     *   Date::subtract(Date::now(), 30)               // 30 days ago
     *
     * Examples - String intervals (new):
     *   Date::subtract('2024-01-15', '3 months')      // "2023-10-15 00:00:00"
     *   Date::subtract('2024-01-15', '2 hours')       // "2024-01-14 22:00:00"
     *   Date::subtract('2024-01-15', '1 year')        // "2023-01-15 00:00:00"
     *   Date::subtract('2024-01-15', '2 weeks')       // "2024-01-01 00:00:00"
     *   Date::subtract('2024-01-15', '5 days 3 hours') // "2024-01-09 21:00:00"
     *
     * Supported units:
     *   - seconds, minutes, hours
     *   - days, weeks
     *   - months, years
     *   - Combinations: '1 month 2 days', '3 hours 30 minutes'
     *
     * Usage - Get posts from last week:
     *   $lastWeek = Date::subtract(Date::now(), '1 week');
     *   $posts = Posts::where('created_at >', $lastWeek)->all();
     *
     * Usage - Archive old data:
     *   $archiveDate = Date::subtract(Date::now(), '6 months');
     *   $oldPosts = Posts::where('created_at <', $archiveDate)->all();
     *
     * @param string|int $date Date string or Unix timestamp
     * @param int|string $amount Number of days (int) or interval string ('3 months')
     * @param string $format Output format (default: Y-m-d H:i:s)
     * @return string New date
     */
    public static function subtract($date, $amount, $format = 'Y-m-d H:i:s') 
    {
        $timestamp = is_numeric($date) ? $date : strtotime($date);

        // Backward compatible: numeric = days
        if (is_numeric($amount)) {
            $interval = "-{$amount} days";
        } else {
            // String interval: ensure it has - prefix
            // If it already has + or -, use as-is, otherwise add -
            if (strpos($amount, '+') === 0) {
                // Convert + to -
                $interval = '-' . substr($amount, 1);
            } elseif (strpos($amount, '-') === 0) {
                // Already has -, use as-is
                $interval = $amount;
            } else {
                // Add - prefix
                $interval = '-' . $amount;
            }
        }

        $newTimestamp = strtotime($interval, $timestamp);
        return date($format, $newTimestamp);
    }

    // =========================================================================
    // DATE COMPARISON
    // =========================================================================

    /**
     * Calculate difference between two dates
     *
     * Returns the absolute difference between two dates in the specified unit.
     * Default unit is days (backward compatible).
     *
     * Examples - Days (default, backward compatible):
     *   Date::diff('2024-01-15', '2024-01-20')        // 5 (days)
     *   Date::diff('2024-01-01', '2024-02-01')        // 31 (days)
     *
     * Examples - Other units:
     *   Date::diff($date1, $date2, 'hours')           // 120 (hours)
     *   Date::diff($date1, $date2, 'minutes')         // 7200 (minutes)
     *   Date::diff($date1, $date2, 'seconds')         // 432000 (seconds)
     *   Date::diff($date1, $date2, 'weeks')           // 2 (weeks)
     *   Date::diff($date1, $date2, 'months')          // 1 (months, approximate)
     *   Date::diff($date1, $date2, 'years')           // 0 (years)
     *
     * Supported units:
     *   - 'seconds', 'minutes', 'hours'
     *   - 'days' (default)
     *   - 'weeks'
     *   - 'months' (approximate: 30 days)
     *   - 'years' (approximate: 365 days)
     *
     * Usage - Check subscription status:
     *   $daysLeft = Date::diff(Date::now(), $subscription->expires_at);
     *   if ($daysLeft <= 7) {
     *       // Send renewal reminder
     *   }
     *
     * Usage - Calculate hours worked:
     *   $hoursWorked = Date::diff($timesheet->start, $timesheet->end, 'hours');
     *
     * Note: Month/year calculations are approximate (30/365 days).
     *       For precise calendar math, use add()/subtract() instead.
     *
     * @param string|int $date1 First date string or timestamp
     * @param string|int $date2 Second date string or timestamp
     * @param string $unit Unit to return ('seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years')
     * @return int|float Difference in specified unit (absolute value)
     */
    public static function diff($date1, $date2, $unit = 'days') 
    {
        $timestamp1 = is_numeric($date1) ? $date1 : strtotime($date1);
        $timestamp2 = is_numeric($date2) ? $date2 : strtotime($date2);

        $diff = abs($timestamp1 - $timestamp2);

        switch (strtolower($unit)) {
            case 'seconds':
                return $diff;
            case 'minutes':
                return floor($diff / 60);
            case 'hours':
                return floor($diff / 3600);
            case 'days':
                return floor($diff / 86400);
            case 'weeks':
                return floor($diff / 604800);
            case 'months':
                // Approximate: 30 days per month
                return floor($diff / 2592000);
            case 'years':
                // Approximate: 365 days per year
                return floor($diff / 31536000);
            default:
                // Default to days if unknown unit
                return floor($diff / 86400);
        }
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    /**
     * Check if date is a weekend
     *
     * Returns true if the date falls on Saturday or Sunday.
     *
     * Examples:
     *   Date::weekend('2024-01-15')       // false (Monday)
     *   Date::weekend('2024-01-20')       // true (Saturday)
     *   Date::weekend('2024-01-21')       // true (Sunday)
     *   Date::weekend(Date::now())        // true/false based on today
     *
     * Usage - Weekend pricing:
     *   if (Date::weekend(Date::now())) {
     *       $price = $basePrice * 1.5;  // Weekend premium
     *   }
     *
     * Usage - Business days only:
     *   if (!Date::weekend($appointment->date)) {
     *       // Process appointment
     *   }
     *
     * @param string|int $date Date string or Unix timestamp
     * @return bool True if weekend (Saturday or Sunday)
     */
    public static function weekend($date) 
    {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        $dayOfWeek = date('N', $timestamp);
        return $dayOfWeek >= 6;
    }

    /**
     * Parse a date string to Unix timestamp
     *
     * Converts any valid date string to a Unix timestamp.
     * Useful for date comparisons and calculations.
     *
     * Examples:
     *   Date::parse('2024-01-15')         // 1705276800
     *   Date::parse('next monday')        // timestamp for next Monday
     *   Date::parse('+1 week')            // timestamp for 1 week from now
     *   Date::parse('last day of month')  // timestamp for month's last day
     *
     * Usage - Date comparison:
     *   if (Date::parse($post->published_at) < time()) {
     *       // Post is published
     *   }
     *
     * @param string $date Date string (any format recognized by strtotime)
     * @return int Unix timestamp
     */
    public static function parse($date) {
        return strtotime($date);
    }

    // =========================================================================
    // DATE CHECKS
    // =========================================================================

    /**
     * Check if date is today
     *
     * Returns true if the given date is the same calendar day as today.
     *
     * Examples:
     *   Date::isToday('2024-01-15')           // true if today is Jan 15
     *   Date::isToday('2024-01-15 23:59:59')  // true if today is Jan 15
     *   Date::isToday('2024-01-14')           // false
     *   Date::isToday($post->created_at)      // true if created today
     *
     * Usage - Highlight today's items:
     *   @foreach($events as $event)
     *       <div class="{{ Date::isToday($event->date) ? 'highlight' : '' }}">
     *           {{ $event->title }}
     *       </div>
     *   @endforeach
     *
     * @param string|int $date Date string or Unix timestamp
     * @return bool True if date is today
     */
    public static function isToday($date) 
    {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date('Y-m-d', $timestamp) === date('Y-m-d');
    }

    /**
     * Check if date is in the past
     *
     * Returns true if the given date/time is before the current moment.
     *
     * Examples:
     *   Date::isPast('2023-01-15')            // true
     *   Date::isPast('2025-01-15')            // false
     *   Date::isPast($subscription->expires_at) // true if expired
     *
     * Usage - Show expired subscriptions:
     *   if (Date::isPast($subscription->expires_at)) {
     *       echo "Subscription expired";
     *   }
     *
     * Usage - Filter old events:
     *   $pastEvents = array_filter($events, function($event) {
     *       return Date::isPast($event->end_date);
     *   });
     *
     * @param string|int $date Date string or Unix timestamp
     * @return bool True if date is in the past
     */
    public static function isPast($date) 
    {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return $timestamp < time();
    }

    /**
     * Check if date is in the future
     *
     * Returns true if the given date/time is after the current moment.
     *
     * Examples:
     *   Date::isFuture('2025-01-15')          // true
     *   Date::isFuture('2023-01-15')          // false
     *   Date::isFuture($event->start_date)    // true if not started yet
     *
     * Usage - Upcoming events only:
     *   $upcomingEvents = array_filter($events, function($event) {
     *       return Date::isFuture($event->start_date);
     *   });
     *
     * Usage - Show countdown:
     *   if (Date::isFuture($launch->date)) {
     *       $days = Date::diff(Date::now(), $launch->date);
     *       echo "$days days until launch!";
     *   }
     *
     * @param string|int $date Date string or Unix timestamp
     * @return bool True if date is in the future
     */
    public static function isFuture($date) 
    {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return $timestamp > time();
    }

    // =========================================================================
    // DATE BOUNDARIES
    // =========================================================================

    /**
     * Get start of day (00:00:00)
     *
     * Returns the given date at midnight (00:00:00).
     * Useful for date range queries.
     *
     * Examples:
     *   Date::startOfDay('2024-01-15 14:30:45')   // "2024-01-15 00:00:00"
     *   Date::startOfDay(Date::now())             // Today at 00:00:00
     *   Date::startOfDay('2024-01-15')            // "2024-01-15 00:00:00"
     *
     * Usage - Get today's posts:
     *   $todayStart = Date::startOfDay(Date::now());
     *   $todayPosts = Posts::where('created_at >', $todayStart)->all();
     *
     * Usage - Date range query:
     *   $dayStart = Date::startOfDay($selectedDate);
     *   $dayEnd = Date::endOfDay($selectedDate);
     *   $orders = Orders::where('created_at >=', $dayStart)
     *                   ->where('created_at <=', $dayEnd)
     *                   ->all();
     *
     * @param string|int $date Date string or Unix timestamp (default: today)
     * @param string $format Output format (default: Y-m-d H:i:s)
     * @return string Date at 00:00:00
     */
    public static function startOfDay($date = null, $format = 'Y-m-d H:i:s') 
    {
        $date = $date ?? date('Y-m-d');
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, strtotime(date('Y-m-d', $timestamp) . ' 00:00:00'));
    }

    /**
     * Get end of day (23:59:59)
     *
     * Returns the given date at one second before midnight (23:59:59).
     * Useful for date range queries.
     *
     * Examples:
     *   Date::endOfDay('2024-01-15 14:30:45')     // "2024-01-15 23:59:59"
     *   Date::endOfDay(Date::now())               // Today at 23:59:59
     *   Date::endOfDay('2024-01-15')              // "2024-01-15 23:59:59"
     *
     * Usage - Get today's activity:
     *   $dayEnd = Date::endOfDay(Date::now());
     *   $activity = Activity::where('created_at <=', $dayEnd)->all();
     *
     * @param string|int $date Date string or Unix timestamp (default: today)
     * @param string $format Output format (default: Y-m-d H:i:s)
     * @return string Date at 23:59:59
     */
    public static function endOfDay($date = null, $format = 'Y-m-d H:i:s') 
    {
        $date = $date ?? date('Y-m-d');
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, strtotime(date('Y-m-d', $timestamp) . ' 23:59:59'));
    }

    /**
     * Get first day of month
     *
     * Returns the first day of the month for the given date.
     *
     * Examples:
     *   Date::startOfMonth('2024-01-15')          // "2024-01-01 00:00:00"
     *   Date::startOfMonth('2024-02-29')          // "2024-02-01 00:00:00"
     *   Date::startOfMonth(Date::now())           // First day of current month
     *
     * Usage - Monthly reports:
     *   $monthStart = Date::startOfMonth(Date::now());
     *   $monthlySales = Sales::where('created_at >=', $monthStart)->all();
     *
     * @param string|int $date Date string or Unix timestamp (default: today)
     * @param string $format Output format (default: Y-m-d H:i:s)
     * @return string First day of month at 00:00:00
     */
    public static function startOfMonth($date = null, $format = 'Y-m-d H:i:s') 
    {
        $date = $date ?? date('Y-m-d');
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, strtotime(date('Y-m', $timestamp) . '-01 00:00:00'));
    }

    /**
     * Get last day of month
     *
     * Returns the last day of the month for the given date.
     * Handles leap years and month lengths automatically.
     *
     * Examples:
     *   Date::endOfMonth('2024-01-15')            // "2024-01-31 23:59:59"
     *   Date::endOfMonth('2024-02-15')            // "2024-02-29 23:59:59" (leap year)
     *   Date::endOfMonth('2023-02-15')            // "2023-02-28 23:59:59"
     *   Date::endOfMonth(Date::now())             // Last day of current month
     *
     * Usage - Monthly deadline:
     *   $deadline = Date::endOfMonth(Date::now());
     *   if (Date::isPast($deadline)) {
     *       // Month has ended
     *   }
     *
     * @param string|int $date Date string or Unix timestamp (default: today)
     * @param string $format Output format (default: Y-m-d H:i:s)
     * @return string Last day of month at 23:59:59
     */
    public static function endOfMonth($date = null, $format = 'Y-m-d H:i:s') 
    {
        $date = $date ?? date('Y-m-d');
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        $lastDay = date('t', $timestamp);  // Get number of days in month
        return date($format, strtotime(date('Y-m', $timestamp) . "-{$lastDay} 23:59:59"));
    }
    
}
