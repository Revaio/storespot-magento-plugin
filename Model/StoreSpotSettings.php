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
        $product_category = $this->helperData->getGeneralConfig('google_product_category');

        $cron = $this->helperData->getCronConfig('next_execution');

        $output = [[
            'cronjob'   => $cron,
            'settings'  => [
              'pixel_id'          => $pixel_id,
              'product_feed'      => (bool) $feed_enabled,
              'product_category'  => $product_category,
            ]
        ]];

        return $output;
    }
}
