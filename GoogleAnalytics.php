<?php namespace Huebacca\GoogleAnalytics;

/**
 * Ewan Thompson
 * github.com/huebacca
 * December 2017
 */

use GuzzleHttp\Client;

class GoogleAnalytics
{

    private $baseUri = 'https://www.google-analytics.com/';
    private $version = '1';
    private $userAgentString = 'PHP';
    private $batchLimit = 20;

    private $trackingId;
    private $events = [];

    /**
     * GoogleAnalytics constructor.
     * @param string $trackingId
     */
    public function __construct($trackingId)
    {
        $this->trackingId = $trackingId;
    }

    /**
     * Construct an event and add it to the events array
     *
     * @param string $clientId
     * @param string $category
     * @param string $action
     * @param string $label
     * @param float $value
     */
    public function event($clientId, $category, $action, $label = null, $value = null)
    {
        $eventData = [
            'v' => urlencode($this->version),
            'tid' => urlencode($this->trackingId),
            'cid' => urlencode($clientId),
            't' => urlencode('event'),
            'ec' => urlencode($category),
            'ea' => urlencode($action),
            'el' => urlencode($label),
            'ev' => urlencode($value)
        ];
        $this->events[] = $eventData;
    }

    /**
     * Submit batch to Google
     *
     * @return bool
     */
    public function submit()
    {
        if( empty($this->events) ) {
            return true;
        }
        $events = [];

        if( count($this->events) <= $this->batchLimit ) {
            return $this->request('batch', $events);
        }

        $result = 0;
        while( count($this->events) > 0 ) {
            $events = array_splice($this->events, 0, $this->batchLimit);
            $result += $this->request('batch', $events);
        }

        return $result;
    }

    /**
     * Send request to Google via POST.
     *
     * @param string $path
     * @param array $data
     * @return bool
     */
    private function request($path, $data)
    {
        $client = new Client();

        $uri = $this->baseUri . $path;

        try {
            $request = $client->request('POST', $uri,
                [
                    'headers' => [
                        'User-Agent' => $this->userAgentString
                    ],
                    'body' => implode("\r\n",$data)
                ]
            );
        } Catch( \Exception $e ) {
            return false;
        }

        echo $request->getBody();

        return true;

    }

    public function __destruct()
    {
        $this->submit();
    }

}