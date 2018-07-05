<?php

interface QueryManager {
    
    const QUERY_TYPE_WRITE = 1;
    const QUERY_TYPE_READ = 2;

    public static function generateSetQueriesForChannel($nodeId, $channelId, $data);
    public static function readQueriesForChannel($queries);

}