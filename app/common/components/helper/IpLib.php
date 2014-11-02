<?php
/**
 * Helper for remote actions
 *
 * @author :  ignat
 * @date   :  17.10.14 15:53
 */


namespace helper;

class IpLib {

    /**
     * Get remote or forwarded address
     * @return string
     */
    public static function getRemoteAddress(){

        $ip = getenv('HTTP_CLIENT_IP')?:
            getenv('HTTP_X_FORWARDED_FOR')?:
                getenv('HTTP_X_FORWARDED')?:
                    getenv('HTTP_FORWARDED_FOR')?:
                        getenv('HTTP_FORWARDED')?:
                            getenv('REMOTE_ADDR');

        return $ip;
    }

} 