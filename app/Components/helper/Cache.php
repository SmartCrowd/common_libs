<?php
/**
 * Class working with redis cache
 *
 * @author : nikolaev
 * @date : 8/26/14 3:25 PM
 */

namespace helper;

/**
 * Class Cache
 * @package helper
 */
class Cache
{

    private static $lifetime = 86400;
    private $set_prefix;
    private $redis;

    public function __construct()
    {
        $client = CDI()->clientResolver->getClient();
        $this->set_prefix = (empty($client)) ? "" : $client."_";
        $this->redis = CDI()->redis->getInstance();
    }

    /**
     * Gets cache by key and arguments
     *
     * @param string $set
     * @param string $key used in hash fields
     * @param bool   $unserialize
     *
     * @return bool|mixed
     */
    public function getCache($set, $key, $unserialize = true)
    {
        if (!$set) return false;
        if (!is_string($key))
            return false;

        $set = strtolower($this->set_prefix . $set);
        try {
            if ($this->redis->hExists($set, $key)) {
                $value = $this->redis->hGet($set, $key);
                return ($unserialize) ? unserialize($value) : $value;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            CDI()->devLog->log('Hash get in redis failed with message: '.$e->getMessage().
                "\n".$e->getTraceAsString(), 'error');
            return false;
        }
    }

    /**
     * @param string $set
     * @param bool   $unserialize
     *
     * @return array | false
     */
    public function getAllCache($set, $unserialize = true)
    {
        try {
            $set = strtolower($this->set_prefix . $set);
            $result = $this->redis->hGetAll($set);
            if ($unserialize) {
                foreach ($result as $k => $val) {
                    $result[$k] = unserialize($val);
                }
            }
            return $result;
        } catch (\Exception $e) {
            CDI()->devLog->log('GetAllCache failed in \helper\Cache with message: '.$e->getMessage().
                "\n".$e->getTraceAsString(), 'error');
            return false;
        }
    }


    /**
     * sets cache by key and arguments
     * @param string $set
     * @param string $key
     * @param mixed $value caching data
     * @param null $lifetime lifetime of cache
     * @return bool
     */
    public function setCache($set, $key, $value, $lifetime=null)
    {
        if (!is_string($key))
            return false;
        if (is_null($lifetime))
            $lifetime = self::$lifetime;

        $set = strtolower($this->set_prefix . $set);
        $value = serialize($value);

        try {
            $result = $this->redis->hSet($set, $key, $value);
            $this->redis->expire($set, $lifetime);
            return $result;
        } catch (\Exception $e) {
            CDI()->devLog->log('Hash set in redis failed with message: '.$e->getMessage().
                "\n".$e->getTraceAsString(), 'error');
            return false;
        }
    }

    /**
     * deletes cache by key or only one field in key
     * @param string $set
     * @param string $key
     * @return int|false
     */
    public function deleteCache($set, $key=null)
    {
        try {
            $set = strtolower($this->set_prefix . $set);
            if (is_null($key))
                return $this->redis->del($set);

            if (is_string($key))
                return $this->redis->hDel($set, $key);

            return false;
        } catch (\Exception $e) {
            CDI()->devLog->log('Hash delete in redis failed with message: '.$e->getMessage().
                "\n".$e->getTraceAsString(), 'error');
            return false;
        }
    }

    /**
     * @param $set string
     * @param $key string
     *
     * @return bool If key exists in set, return TRUE, otherwise return FALSE.
     */
    public function keyExists($set, $key)
    {
        try{
            $set = strtolower($this->set_prefix . $set);
            return $this->redis->hExists($set, $key);
        }
        catch(\Exception $e){
            return false;
        }

    }

    /**
     * @param string $set
     * @param string $key
     *
     * @return int value after incrementation
     */
    public function increment($set, $key = null)
    {
        try {
            $set = strtolower($this->set_prefix . $set);

            return is_null($key) ? $this->redis->incr($set) : $this->redis->hIncrBy($set, $key, 1);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $key string
     * @param $timestamp int
     *
     * @return bool
     */
    public function expireAt($key, $timestamp)
    {
        try{
            $key = strtolower($this->set_prefix . $key);
            return $this->redis->expireAt($key, $timestamp);
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * @param $key string
     *
     * @return bool|string
     */
    public function getKey($key)
    {
        try{
            $key = strtolower($this->set_prefix . $key);
            return $this->redis->get($key);
        } catch(\Exception $e){
            return false;
        }

    }

    /**
     * @param $key string
     * @param $value mixed
     *
     * @return bool|int The new length of the list or false
     */
    public function lPush($key, $value)
    {
        try{
            $key = strtolower($this->set_prefix . $key);
            $value = serialize($value);
            return $this->redis->lPush($key, $value);
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * @param $key string
     * @param $start int
     * @param $stop int
     *
     * @return array|bool
     */
    public function getRange($key, $start, $stop)
    {
        try{
            $key = strtolower($this->set_prefix . $key);
            $result = $this->redis->lRange($key, $start, $stop);
            foreach ($result as $key => $val) {
                $result[$key] = unserialize($val);
            }
            return $result;
        } catch(\Exception $e){
            return false;
        }
    }

}