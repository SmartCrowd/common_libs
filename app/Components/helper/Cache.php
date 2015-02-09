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

    private static $lifetime = 86400; // 24*60*60
    private static $prefix   = "";

    /**
     * @var \Redis()
     */
    private $redis;

    /**
     * @param \Redis $redis
     * @param string $prefix
     */
    public function __construct(\Redis $redis, $prefix = "")
    {
        $this->redis  = $redis;
        self::$prefix = $prefix;
    }

    /**
     * Gets cache by key and arguments
     *
     * @param string $key
     * @param string $hashKey
     * @param bool   $unpack
     *
     * @return bool|mixed
     */
    public function getCache($key, $hashKey, $unpack = true)
    {
        try {
            $key = $this->setPrefix($key);

            if ($this->redis->hExists($key, $hashKey)) {
                $value = $this->redis->hGet($key, $hashKey);
                return ($unpack) ? self::unpack($value) : $value;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param bool   $unpack
     *
     * @return array|false
     */
    public function getAllCache($key, $unpack = true)
    {
        try {
            $key    = $this->setPrefix($key);
            $result = $this->redis->hGetAll($key);
            if ($unpack) {
                foreach ($result as $k => $val) {
                    $result[$k] = self::unpack($val);
                }
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Sets cache by key and arguments
     *
     * @param string $key
     * @param string $hashKey
     * @param mixed  $value caching data
     *
     * @return bool
     */
    public function setCache($key, $hashKey, $value)
    {
        try {
            $key   = $this->setPrefix($key);
            $value = self::pack($value);

            return $this->redis->hSet($key, $hashKey, $value);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Deletes cache by key or only one field in key
     *
     * @param string $key
     * @param string $hashKey
     *
     * @return int|false
     */
    public function deleteCache($key, $hashKey = null)
    {
        try {
            $key = $this->setPrefix($key);

            return is_null($hashKey) ? $this->redis->del($key) : $this->redis->hDel($key, $hashKey);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if cache exists in storage
     * @param string $key
     * @param string|null $hashKey
     *
     * @return bool If key exists in set, return TRUE, otherwise return FALSE.
     */
    public function keyExists($key, $hashKey = null)
    {
        try {
            $key = $this->setPrefix($key);

            if ($hashKey){
                $cache = $this->redis->hExists($key, $hashKey);
            } else {
                $cache = $this->redis->exists($key);
            }

            return $cache;
        }
        catch(\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param string $hashKey
     *
     * @return int Value after incrementation
     */
    public function increment($key, $hashKey = null)
    {
        try {
            $key = $this->setPrefix($key);

            return is_null($hashKey) ? $this->redis->incr($key) : $this->redis->hIncrBy($key, $hashKey, 1);
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
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool|string
     */
    public function setKey($key, $value)
    {
        try {
            $key = $this->setPrefix($key);

            return $this->redis->set($key, $value);
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return bool|int The new length of the list or false
     */
    public function lPush($key, $value)
    {
        try {
            $key = $this->setPrefix($key);

            return $this->redis->lPush($key, $value);
        } catch(\Exception $e) {
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

            return $result;
        } catch(\Exception $e) {
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
        } catch(\Exception $e) {
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
        } catch(\Exception $e) {
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
        try {
            $key = $this->setPrefix($key);

            return $this->redis->expireAt($key, $timestamp);
        } catch(\Exception $e) {
            return false;
        }
    }

    public static function setPrefix($string)
    {
        return strtolower(self::$prefix . $string);
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