<?php
/**
 * Base Helper class for time operations
 *
 * @author :  ignat
 * @date   :  25.07.14 11:33
 */


namespace helper;


abstract class Time {

    /**
     * @param string $utc UTC time
     * @param string $format
     * @return bool|string
     */
    public static function ts2date($utc = '', $format = 'Y-m-d H:i:s'){
        return date($format, (!$utc ? time() : $utc));
    }

    /**
     * @param string $date
     * @return int UTC time
     */
    public static function formatOffsetUTC($date){

        $default = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $time = strtotime($date);
        date_default_timezone_set($default);

        return $time;
    }

    /**
     * Get offset from passed time
     *
     * @param int|string $time
     * @param int|string $offset may be positive or negative
     * @param string $mode - available mode ('d', 'h', 'm', 's')
     * @return int|string
     */
    public static function getTimeOffset($time = '', $offset = 0, $mode = 'h'){

        if (!$time)
            $time = time();

        if (!$offset)
            return $time;

        switch($mode){
            case 's': $offset *= 1             ; break;
            case 'm': $offset *= 60            ; break;
            case 'h': $offset *= (60 * 60)     ; break;
            case 'd': $offset *= (60 * 60 * 24); break;

            default:
                echo "Undefined offset passed into " . __METHOD__;
                $offset = 0;
                break;
        }

        return $time + $offset;
    }

    /**
     * Get start and end of information day from config
     * @param bool $begin
     * @param string|null $project_id if it needs to get inf_day parameters from projects collection
     * @return mixed
     */
    public static function getInfoDayConfig($begin = true, $project_id = null){
        if ($project_id) {
            //trying to get inf_day for project_id from database
            $project = CDI()->mongo->selectCollection('projects')->findOne(['_id'=>$project_id]);
            if (isset($project['inf_day_start']) && isset($project['inf_day_end'])) {
                return $begin ? $project['inf_day_start'] : $project['inf_day_end'];
            }
        }
        return $begin ? CDI()->config->time->inf_day_start : CDI()->config->time->inf_day_end;
    }

    /**
     * Generates theme completion_time for input timestamp. Takes inf_day_start and inf_day_end params from config
     * @param $time int Unix timestamp.
     * @param $dayStart bool If false, inf_day_start = inf_day_end
     * @param string|null $project_id if it needs to get inf_day parameters from projects collection
     * @return int Unix timestamp
     */
    public static function getCompletionTime($time = null, $dayStart = true, $project_id = null)
    {
        if ($time === null)
            $time = time();

        $now    = getdate($time);
        if ($project_id) {
            //trying to get inf_day for project_id from database
            $project = CDI()->mongo->selectCollection('projects')->findOne(['_id'=>$project_id]);

            $dayEnd = isset($project['inf_day_end']) ?
                      getdate( strtotime($project['inf_day_end']) ) :
                      getdate( strtotime(self::getInfoDayConfig(false)) );
        } else {
            $dayEnd = getdate( strtotime(self::getInfoDayConfig(false)) );
        }
        if ($dayStart == false) {
            $inf_day_start = mktime($dayEnd['hours'], $dayEnd['minutes'], $dayEnd['seconds'], 0, 0, 0);
        } else {
            $inf_day_start = ($project_id && isset($project['inf_day_start'])) ?
                explode(':', $project['inf_day_start']) :
                explode(':', self::getInfoDayConfig(true)) ;
            $inf_day_start = mktime($inf_day_start[0], $inf_day_start[1], $inf_day_start[2], 0, 0, 0);
        }
        $theme_time = mktime($now['hours'], $now['minutes'], $now['seconds'], 0, 0, 0);

        return mktime(
            $dayEnd['hours'],
            $dayEnd['minutes'],
            $dayEnd['seconds'],
            $now['mon'],
            ($theme_time < $inf_day_start) ? $now['mday'] :  $now['mday'] + 1,
            $now['year']
        );
    }

    /**
     * Returns timestamp rounded by hour
     * @param int $time timestamp
     *
     * @return int
     */
    public static function getLastHour($time = null){
        if (is_null($time))
            $time = time();
        $hour = date("H", $time);
        return mktime($hour, 0, 0);
    }


    /**
     * Returns timestamp depends on hours
     * if current timestamp greater than today's date+hours returns current timestamp
     * else returns yesterday's timestamp
     * @param string $hours hours format H:i:s
     *
     * @return int
     */
    public static function getInfoDateFromHours($hours)
    {
        $time = strtotime(date('Y-m-d').' '.(string)$hours);
        if ($time <= time()) return time();
        else return time()-24*60*60;
    }

}