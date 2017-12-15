<?php namespace Huebacca\GoogleAnalytics;
/**
 * Ewan Thompson
 * github.com/huebacca
 * December 2017
 */

class GoogleAnalytics
{

    private $trackingCode = '';

    public function __construct($trackingCode)
    {
        $this->trackingCode = $trackingCode;
    }

}