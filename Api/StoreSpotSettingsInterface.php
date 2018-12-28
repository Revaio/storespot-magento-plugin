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

	/**
	 * Sets the StoreSpot settings.
	 *
	 * @api
	 * @param string $pixel_id Facebook pixel id
	 * @param boolean $pixel_enabled Enable pixel
	 * @param boolean $feed_enabled Enable product feed
	 * @param string $product_category Google product category
	 * @return boolean
	 */
	public function setSettings( $pixel_id, $pixel_enabled, $feed_enabled, $product_category );
}
