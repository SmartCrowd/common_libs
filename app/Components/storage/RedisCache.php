<?php
/**
 * Redis IO module
 *
 * @version:  $Id:$
 * @author :  ignat
 * @date   :  24.06.14 9:43
 */

namespace storage;

use Phalcon\Cache\Backend\Redis,
    Phalcon\Cache\Frontend\Data;


class RedisCache {

    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 6379;
    const DEFAULT_LIFETIME = 3600;

    private $redis = null;
    private $cacheInstance = null;

    public function __construct($host = '', $port = '', $lifetime = 3600)
    {
        if (empty($host))     $host = self::DEFAULT_HOST;
        if (empty($port))     $port = self::DEFAULT_PORT;
        if (empty($lifetime)) $lifetime = self::DEFAULT_LIFETIME;

        $this->redis = new \Redis();
        $this->redis->connect($host, $port);

        // test for server available
        $this->redis->ping();

        //Create a Data frontend and set a default lifetime to 1 hour
        $frontend = new Data(['lifetime' => $lifetime]);

        //Create the cache passing the connection
        $this->cacheInstance = new Redis($frontend, [ 'redis' => $this->redis ]);
    }

    /**
     * Get instance of Phalcon\Cache\Backend\Redis cache
     * @return null|Redis
     */
    public function getCacheInstance(){
        return $this->cacheInstance;
    }
    public function getInstance(){
        $instance = null;
            $instance = $this->redis;
            return $instance;
    }

}