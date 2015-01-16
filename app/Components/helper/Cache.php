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
    private static $set_prefix = "";

    /**
     * @var \Redis()
     */
    private $redis;

    public function __construct()
    {
        $client = CDI()->clientResolver->getClient();
        self::$set_prefix = empty($client) ? "" : $client."_";
        $this->redis = CDI()->redis->getInstance();
    }

    /**
     * Gets cache by key and arguments
     *
     * @param string $set
     * @param string $key used in hash fields
     * @param bool   $unpack
     *
     * @return bool|mixed
     */
    public function getCache($set, $key, $unpack = true)
    {
        if (!$set) return false;
        if (!is_string($key))
            return false;

        $set = $this->setPrefix($set);
        try {
            if ($this->redis->hExists($set, $key)) {
                $value = $this->redis->hGet($set, $key);
                return ($unpack) ? self::unpack($value) : $value;
            }

            return false;
        } catch (\Exception $e) {
            CDI()->devLog->log('Hash get in redis failed with message: '.$e->getMessage().
                "\n".$e->getTraceAsString(), 'error');
            return false;
        }
    }

    /**
     * @param string $set
     * @param bool   $unpack
     *
     * @return array|false
     */
    public function getAllCache($set, $unpack = true)
    {
        try {
            $set = $this->setPrefix($set);
            $result = $this->redis->hGetAll($set);
            if ($unpack) {
                foreach ($result as $k => $val) {
                    $result[$k] = self::unpack($val);
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
     * Sets cache by key and arguments
     *
     * @param string $set
     * @param string $key
     * @param mixed $value caching data
     *
     * @return bool
     */
    public function setCache($set, $key, $value)
    {
        $set   = $this->setPrefix($set);
        $value = self::pack($value);

        try {
            return $this->redis->hSet($set, $key, $value);
        } catch (\Exception $e) {
            CDI()->devLog->log('Hash set in redis failed with message: '.$e->getMessage().
                "\n".$e->getTraceAsString(), 'error');
            return false;
        }
    }

    /**
     * Deletes cache by key or only one field in key
     *
     * @param string $set
     * @param string $key
     *
     * @return int|false
     */
    public function deleteCache($set, $key=null)
    {
        try {
            $set = $this->setPrefix($set);

            return is_null($key) ? $this->redis->del($set) : $this->redis->hDel($set, $key);
        } catch (\Exception $e) {
            CDI()->devLog->log('Hash delete in redis failed with message: '.$e->getMessage().
                "\n".$e->getTraceAsString(), 'error');
            return false;
        }
    }

    /**
     * @param string $set
     * @param string $key
     *
     * @return bool If key exists in set, return TRUE, otherwise return FALSE.
     */
    public function keyExists($set, $key)
    {
        try{
            $set = $this->setPrefix($set);
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
     * @return int Value after incrementation
     */
    public function increment($set, $key = null)
    {
        try {
            $set = $this->setPrefix($set);
            return is_null($key) ? $this->redis->incr($set) : $this->redis->hIncrBy($set, $key, 1);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $key
     *
     * @return bool|string
     */
    public function getKey($key)
    {
        try {
            $key = $this->setPrefix($key);
            return $this->redis->get($key);
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return bool|int The new length of the list or false
     */
    public function lPush($key, $value)
    {
        try{
            $key = $this->setPrefix($key);
            $value = self::pack($value);
            return $this->redis->lPush($key, $value);
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * @param string $key
     * @param int    $start
     * @param int    $end
     *
     * @return array|bool
     */
    public function getRange($key, $start, $end)
    {
        try {
            $key = $this->setPrefix($key);
            $result = $this->redis->lRange($key, $start, $end);
            foreach ($result as $key => $val) {
                $result[$key] = self::unpack($val);
            }
            return $result;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * Set key to hold the string value and set key to timeout after a given number of seconds
     *
     * @param string $key
     * @param int    $lifetime
     * @param string $value
     *
     * @return bool
     */
    public function setEx($key, $value = "", $lifetime = null)
    {
        try {
            $key = $this->setPrefix($key);
            if (is_null($lifetime)) {
                $lifetime = self::$lifetime;
            }

            return $this->redis->setex($key, $lifetime, $value);
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * Set key to hold string value if key does not exist. When key already holds a value, no operation is performed.
     *
     * @param string $key
     * @param string $value
     *
     * @return bool True if the key was set. False if the key was not set
     */
    public function setNx($key, $value = "")
    {
        $key = $this->setPrefix($key);

        return $this->redis->setnx($key, $value);
    }

    /**
     * Sets an expiration date (a timeout) on an item.
     *
     * @param string $key
     * @param int    $lifetime
     *
     * @return bool
     */
    public function expire($key, $lifetime)
    {
        try {
            $key = $this->setPrefix($key);
            return $this->redis->expire($key, $lifetime);
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * @param string $key
     * @param int    $timestamp
     *
     * @return bool
     */
    public function expireAt($key, $timestamp)
    {
        try{
            $key = $this->setPrefix($key);
            return $this->redis->expireAt($key, $timestamp);
        } catch(\Exception $e){
            return false;
        }
    }

    public static function setPrefix($key)
    {
        return strtolower(self::$set_prefix . $key);
    }

    /**
     * Returns unique string for any type of argument
     * From: https://github.com/yiisoft/yii2/blob/master/framework/caching/Cache.php
     *
     * @param mixed $key
     *
     * @return string
     */
    public static function buildKey($key)
    {
        if (is_string($key)) {
            $key = ctype_alnum($key) && mb_strlen($key, '8bit') <= 32 ? $key : md5($key);
        } else {
            $key = md5(json_encode($key));
        }

        return $key;
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    public static function pack($data)
    {
        return serialize($data);
    }

    /**
     * @param string $data
     * 
     * @return mixed
     */
    public static function unpack($data)
    {
        return unserialize($data);
    }

}