<?php

require_once("QueryManager.php");

class DINQueryManager implements QueryManager {

    const MODE_DISABLE = 0;
    const MODE_STATUS = 1;
    const MODE_COUNTER = 2;

    private static $configList = [
        "mode" => 24,
        "multiplier" => 25,
        "sensor" => 26,
    ];

    /**
     * @param integer|string $nodeId
     * @param integer|string $adcId
     * @param array $data
     * @return array
     */
    public static function generateSetQueriesForChannel($nodeId, $channelId, $data) {
        $queries = [];
        foreach ($data as $type => $value) {
            if (isset(static::$configList[$type])) {
                switch ($type) {
                    case "multiplier":
                        $value = $value[1] . "," . $value[0];
                        $queries[2][] = "2;{$channelId};" . self::QUERY_TYPE_WRITE . ";0;" . static::$configList[$type] . ";{$value}";
                        break;
                    case "mode":
                        switch ($value) {
                            case static::MODE_DISABLE:
                                $queries[1][] = "1;{$channelId};" . self::QUERY_TYPE_WRITE . ";0;" . static::$configList[$type] . ";0";
                                $queries[2][] = "2;{$channelId};" . self::QUERY_TYPE_WRITE . ";0;" . static::$configList[$type] . ";0";
                                break;
                            case static::MODE_STATUS:
                                $queries[1][] = "1;{$channelId};" . self::QUERY_TYPE_WRITE . ";0;" . static::$configList[$type] . ";1";
                                $queries[2][] = "2;{$channelId};" . self::QUERY_TYPE_WRITE . ";0;" . static::$configList[$type] . ";0";
                                break;
                            case static::MODE_COUNTER:
                                $queries[1][] = "1;{$channelId};" . self::QUERY_TYPE_WRITE . ";0;" . static::$configList[$type] . ";0";
                                $queries[2][] = "2;{$channelId};" . self::QUERY_TYPE_WRITE . ";0;" . static::$configList[$type] . ";1";
                                break;
                        }
                        break;
                    default:
                        $queries[2][] = "2;{$channelId};" . self::QUERY_TYPE_WRITE . ";0;" . static::$configList[$type] . ";{$value}";
                        break;
                }
            }
        }
        return $queries;
    }

    /**
     * @param array $queries
     * @return array
     */
    public static function readQueriesForChannel($queries) {
        static $invertedConfigList = null;
        if (is_null($invertedConfigList)) {
            $invertedConfigList = array_flip(static::$configList);
        }
        $data = [];
        foreach ($queries as $query) {
            $queryParts = explode(";", $query);
            if (count($queryParts) < 6 || $queryParts[2] != self::QUERY_TYPE_WRITE) {
                continue;
            }
            if (!isset($data[$queryParts[1]])) {
                $data[$queryParts[1]] = [];
            }
            if (isset($invertedConfigList[$queryParts[4]])) {
                $configName = $invertedConfigList[$queryParts[4]];
                switch ($configName) {
                    case "multiplier":
                        $value = explode(",", $queryParts[5]);
                        if (count($value) < 2) {
                            continue 2;
                        }
                        $data[$queryParts[1]][$configName] = [ $value[1], $value[0] ];
                        break;
                    case "mode":
                        if (!isset($data[$queryParts[1]][$configName])) {
                            $data[$queryParts[1]][$configName] = 0;
                        }
                        /* @ACHTUNG! Very dungerous code */
                        if ($queryParts[5] == 1) {
                            $data[$queryParts[1]][$configName] += 1 << ($queryParts[0] - 1);
                        }
                        break;
                    default:
                        $data[$queryParts[1]][$configName] = $queryParts[5];
                        break;
                }
            }
        }
        return $data;
    }
}