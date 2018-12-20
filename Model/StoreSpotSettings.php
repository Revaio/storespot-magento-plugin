<?php
namespace StoreSpot\Personalization\Model;

use StoreSpot\Personalization\Helper\Data;
use StoreSpot\Personalization\Api\StoreSpotSettingsInterface as ApiInterface;

class StoreSpotSettings implements ApiInterface {

    protected $helperData;

    /**
     * @param \StoreSpot\Personalization\Helper\Data $helperData
     */
    public function __construct(
        \StoreSpot\Personalization\Helper\Data $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * Returns the StoreSpot settings.
     *
     * @api
     * @return object
     */
    public function getSettings() {
        $pixel_id = $this->helperData->getGeneralConfig('pixel_id');
        $feed_enabled = $this->helperData->getGeneralConfig('enabled');

        $output = [[
            'cronjob'   => true,
            'settings'  => [
              'pixel_id'      => $pixel_id,
              'product_feed'  => (bool) $feed_enabled,
            ]
        ]];

        return $output;
    }
}
