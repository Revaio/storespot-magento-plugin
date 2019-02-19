<?php
namespace StoreSpot\Personalization\Api;

interface StoreInfoInterface
{
    /**
     * Returns default settings.
     *
     * @api
     * @return string $currency Default currency
     * @return string $country Default country
     */
    public function getStoreInfo();
}
