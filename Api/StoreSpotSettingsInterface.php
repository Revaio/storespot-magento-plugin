<?php
namespace StoreSpot\Personalization\Api;

interface StoreSpotSettingsInterface {
    /**
     * Returns the StoreSpot settings.
     *
     * @api
     * @return boolean
     */
    public function getSettings();
	public function setSettings();
}
