<?php
/**
 * Short product info
 *
 * @author :  komrakov
 * @date   :  10.10.14 10:11
 */

namespace helper;


class CentrifugeHelper {

    /**
     * Wrapper on forcePublish method. Auto-determines current client for channel name
     * @param string $channel
     * @param array  $data
     *
     * @return mixed
     */
    public static function clientPublish($channel, $data = [])
    {
        $channel = CDI()->clientResolver->getClient() . ":" . $channel;
        $result  = self::forcePublish($channel, $data);

        return $result;
    }

    /**
     * Wrapper on cent->publish method. Creates namespace on "namespace not found" error
     * @param string $channel
     * @param array $data
     *
     * @return mixed
     */
    public static function forcePublish($channel, $data = []) {
        $result = CDI()->centrifuge->publish($channel, $data);
        if (isset($result[0]['error']) && ($result[0]['error'] == "namespace not found")) {
            $namespace = explode(":", $channel)[0];
            CDI()->centrifuge->send("namespace_create", ["name" => $namespace]);
            $result = CDI()->centrifuge->publish($channel, $data);
        }
        return $result;
    }

    /**
     * Returns frontend user access data for centrifuge
     * @param string $userToken
     * @return array
     */
    public static function getAccess($userToken) {
        $timestamp = (string)time();
        $data = [
            "project"   => CDI()->config->centrifuge->project_id,
            "timestamp" => $timestamp,
            "url"       => trim(CDI()->config->centrifuge->host, "/") . "/connection",
            "user"      => $userToken,
            "token"     => CDI()->centrifuge->buildSign($userToken . $timestamp)
        ];
        return $data;
    }

} 