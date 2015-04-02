<?php
/**
* Short product info
*
* @author :  komrakov
* @date   :  09.10.14 17:37
*/

namespace helper;


class ConfigHelper {

    /**
     * @return string "production" | "default"
     */
    public static function getStageStatus() {
        return (CDI()->config->stage == "production") ? "production" : "default";
    }

    public static function getStageByHostname() {
        $production_domains = ["ru", "net", "com", "рф"];
        $domain = array_reverse(explode(".", gethostname()))[0];
        return in_array($domain, $production_domains) ? "production" : "development";
    }

} 