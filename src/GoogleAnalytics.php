<?php namespace Huebacca\GoogleAnalytics;

/**
 * This is for sending data to Google Analytics via the Measurement Protocol.
 * More details: https://developers.google.com/analytics/devguides/collection/protocol/v1/
 *
 * Ewan Thompson
 * github.com/huebacca
 * December 2017
 */

use GuzzleHttp\Client;

class GoogleAnalytics
{

    private $baseUri = 'https://www.google-analytics.com/';
    private $version = '1';
    private $batchLimit = 20;

    private $trackingId;
    private $events = [];

    /**
     * GoogleAnalytics constructor.
     *
     * @param string $trackingId
     */
    public function __construct($trackingId)
    {
        $this->trackingId = $trackingId;
    }

    /**
     * Track an event
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
            'v' => $this->version,
            'tid' => $this->trackingId,
            'cid' => $clientId,
            't' => 'event',
            'ec' => $category,
            'ea' => $action,
            'el' => $label,
            'ev' => $value
        ];
        $this->events[] = $eventData;
    }

    /**
     * Track a purchase
     *
     * @param string $clientId
     * @param string $transactionId
     * @param string $affiliation
     * @param float $revenue
     * @param float $tax
     * @param float $shipping
     * @param string $coupon
     * @param array $productId
     * @param array $productName
     * @param array $productCategory
     * @param array $productBrand
     * @param array $productVariant
     * @param array $productPosition
     */
    public function purchase($clientId, $transactionId, $affiliation, $revenue, $tax, $shipping, $coupon, $productId, $productName, $productCategory = [], $productBrand = [], $productVariant = [], $productPosition = [])
    {
        $eventData = [
            'v' => $this->version,
            'tid' => $this->trackingId,
            'cid' => $clientId,
            't' => 'event',
            'ti' => $transactionId,
            'ta' => $affiliation,
            'tr' => $revenue,
            'tt' => $tax,
            'ts' => $shipping,
            'tcc' => $coupon,
            'pa' => 'purchase'
        ];

        // Turn everything into an array if not already. Shouldn't be necessary but...
        $productId = is_array($productId) ? $productId : [$productId];
        $productName = is_array($productName) ? $productName : [$productName];
        $productCategory = is_array($productCategory) ? $productCategory : [$productCategory];
        $productBrand = is_array($productBrand) ? $productBrand : [$productBrand];
        $productVariant = is_array($productVariant) ? $productVariant : [$productVariant];
        $productPosition = is_array($productPosition) ? $productPosition : [$productPosition];

        // Build product array(s), add to data
        foreach( $productId as $key => $item ) {
            $index = $key + 1;
            $product = [
                'pr' . $index . 'id' => $item,
                'pr' . $index . 'nm' => empty($productName[$key]) ? null : $productName[$key],
                'pr' . $index . 'ca' => empty($productCategory[$key]) ? null : $productCategory[$key],
                'pr' . $index . 'br' => empty($productBrand[$key]) ? null : $productBrand[$key],
                'pr' . $index . 'va' => empty($productVariant[$key]) ? null : $productVariant[$key],
                'pr' . $index . 'ps' => empty($productPosition[$key]) ? null : $productPosition[$key],
            ];
            $eventData = array_merge($eventData, $product);
        }
        $this->events[] = $eventData;
    }

    /**
     * Track a refund
     *
     * @param string $clientId
     * @param string $transactionId
     * @param array $productId
     * @param array $productQty
     */
    public function refund($clientId, $transactionId, $productId, $productQty )
    {
        $eventData = [
            'v' => $this->version,
            'tid' => $this->trackingId,
            'cid' => $clientId,
            't' => 'event',
            'ti' => $transactionId,
            'pa' => 'refund'
        ];

        // Turn everything into an array if not already. Shouldn't be necessary but...
        $productId = is_array($productId) ? $productId : [$productId];
        $productQty = is_array($productQty) ? $productQty : [$productQty];

        // Build product array(s), add to data
        foreach( $productId as $key => $item ) {
            $index = $key + 1;
            $product = [
                'pr' . $index . 'id' => $item,
                'pr' . $index . 'qt' => empty($productQty[$key]) ? null : $productQty[$key],
            ];
            $eventData = array_merge($eventData, $product);
        }
        $this->events[] = $eventData;
    }

    /**
     * Track a page view
     *
     * @param $clientId
     * @param $host
     * @param $page
     * @param $title
     */
    public function track($clientId, $host, $page, $title)
    {
        $eventData = [
            'v' => $this->version,
            'tid' => $this->trackingId,
            'cid' => $clientId,
            't' => 'pageview',
            'dh' => $host,
            'dp' => $page,
            'dt' => $title
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

        // This is so we can batch series of items rather than one at a time.
        $postBody = [];
        foreach( $data as $item ) {
            $postItem = [];
            foreach( $item as $key => $value ) {
                $postItem[] = $key . '=' . urlencode($value);
            }
            $postBody[] = implode('&', $postItem);

        }
        $postBody = implode("\r\n",$postBody);

        try {
            $request = $client->request('POST', $uri,
                [
                    'body' => $postBody
                ]
            );
        } Catch( \Exception $e ) {
            return false;
        }

        // Google doesn't return any meaningful data; so boolean response it is
        return true;

    }

    public function __destruct()
    {
        // On the way out, submit data
        $this->submit();
    }

}