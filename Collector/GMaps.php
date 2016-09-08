<?php

namespace Collector;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;

class GMaps
{
    const API_HOST = 'https://maps.googleapis.com';
    const API_KEY = 'AIzaSyD6AJiG9q9-rhfn35dWptZJFe91KJOxlZ8';

    /**
     * @var Client
     */
    private $guzzle;

    /**
     * Init Guzzle
     */
    private function initGuzzle()
    {
        $this->guzzle = new Client(
            [
                // Base URI is used with relative requests
                'base_uri' => self::API_HOST,
                // You can set any number of default request options.
                'timeout' => 10,
            ]
        );
    }

    /**
     * @param string $data
     * @return array
     * @throws \Exception
     */
    private function parseLocations(string $data):array
    {
        $data = json_decode($data, true);

        if ($data && $data['status'] === 'OK') {
            $first = $data['results'][0];

            return array(
                'latitude' => $first['geometry']['location']['lat'],
                'longitude' => $first['geometry']['location']['lng'],
            );
        }

        throw new \Exception('No valid location');
    }

    /**
     * @param string $address
     * @param int $iteration
     * @return array
     */
    public function getLocation(string $address, int $iteration = 0):array
    {
        if (!$this->guzzle) {
            $this->initGuzzle();
        }

        try {
            $request = new Request('GET', '/maps/api/geocode/json');
            $options['query'] = array(
                'address' => $address,
                'key' => self::API_KEY,
            );

            $response = $this->guzzle->send($request, $options);

            try {
                return $this->parseLocations($response->getBody()->getContents());
            } catch (\Exception $exception) {
                return array();
            }
        } catch (ServerException $exception) {
        } catch (ConnectException $exception) {
        }

        // retry 5 times
        if ($iteration < 5) {
            return $this->getLocation($address, ++$iteration);
        } else {
            return array();
        }
    }
}