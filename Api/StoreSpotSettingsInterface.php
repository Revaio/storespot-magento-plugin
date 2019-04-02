<?php
namespace StoreSpot\Personalization\Api;

interface StoreSpotSettingsInterface
{
    /**
     * Returns the StoreSpot settings.
     *
     * @api
     * @return boolean $updated
     */
    public function getSettings();

    /**
     * Sets the StoreSpot settings.
     *
     * @api
     * @param string $pixel_id Facebook pixel id
     * @param boolean $pixel_enabled Enable pixel
     * @return boolean
     */
    public function setSettings($pixel_id, $pixel_enabled);
}
