<?php
namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;

class IPStack
{
    CONST API_URL = 'http://api.ipstack.com';
    CONST API_KEY = 'ed09e98ccc0c3f163c4d575a764f3629';
    
    // Retrieve data about given array of ips
    public static function lookup(array $ips) {
        return self::request($ips);
    }

    private static function request(array $ips) {
        $client = new Client(['base_uri' => self::API_URL]);
        $promises = [];

        foreach($ips as $ip) {
            $promises[] = $client->requestAsync('GET', '/'.$ip.'?access_key='.self::API_KEY);
        }

        try {
            $responses = Utils::unwrap($promises);
            $res = collect();
            foreach($responses as $r) {
                $parsedData = json_decode($r->getBody()->getContents(), true);
                $res->push($parsedData);
            }

            return $res->keyBy('ip');
        } catch(\Exception $e) {
            return false;
        }
    }
}