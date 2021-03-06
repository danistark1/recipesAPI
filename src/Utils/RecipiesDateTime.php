<?php
/**
 * @author Dani Stark.
 */
namespace App\Utils;

use DateTimeZone;

/**
 * Class RecipiesDateTime
 *
 * @package App\Utils
 */
class RecipiesDateTime {

    /**
     * Return date now with preset format/timezone
     * Ex. Format:
     * DateTime Y-m-d H:i:s
     * Date Y-m-d
     * Time H:i:s
     *
     * @param string $duration
     * @param bool $formatted If True, Return Date Or Time formatted string, otherwise Return DateTime formated DateTime object
     * @param string $format
     * @param string $timeZone
     * @return string
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public static function dateNow(string $duration = '', bool $formatted = false, $format = 'Y-m-d H:i:s', string $timeZone = 'America/Toronto') {
        if ($duration !== '') {
            $period = new \DateInterval($duration);
        }
//TODO Fix get config cache
//        $timezoneConfig = $cacheConfig->getConfigKey('application-timezone');
        //     $timeZone = new DateTimeZone($timezoneConfig ?? $timeZone);
        $timeZone = new DateTimeZone($timeZone);
        $currentDateTime = new \DateTime('now', $timeZone);
        if (isset($period) && $period instanceof \DateInterval) {
            $currentDateTime->sub($period);
        }
        $formatted = $formatted === true ? $currentDateTime->format($format): $currentDateTime;
        return $formatted;
    }
}
