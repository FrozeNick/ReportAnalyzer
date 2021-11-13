<?php
namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;

class IPStack
{
    CONST API_URL = 'http://api.ipstack.com';
    
    // Retrieve data about given array of ips
    public static function lookup(array $ips) {
        return self::request($ips);
    }

    private static function request(array $ips) {
        $client = new Client(['base_uri' => self::API_URL]);
        $promises = [];

        foreach($ips as $ip) {
            $promises[] = $client->requestAsync('GET', '/'.$ip.'?access_key='.self::getApiKey());
        }

        try {
            $responses = Utils::unwrap($promises);
        } catch(\Exception $e) {
            return false;
        }

        $res = collect();
        foreach($responses as $r) {
            $parsedData = json_decode($r->getBody()->getContents(), true);

            if(!$parsedData['success']) {
                throw new \Exception('Error occured when using IPStack API: "'.$parsedData['error']['info'].'"');
            }
            $res->push($parsedData);
        }

        return $res->keyBy('ip');
    }

    private static function getApiKey() {
        if(isset($_SERVER['IPSTACK_API_KEY'])) {
            if(strlen($_SERVER['IPSTACK_API_KEY']) > 0) {
                return $_SERVER['IPSTACK_API_KEY'];
            }
        }

        throw new \Exception('Please provide a working API key for IPStack in the .env file named "IPSTACK_API_KEY".');
    }
}