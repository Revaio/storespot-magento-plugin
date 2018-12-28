<?php
namespace StoreSpot\Personalization\Model;

use StoreSpot\Personalization\Helper\Data;
use StoreSpot\Personalization\Api\StoreSpotSettingsInterface as ApiInterface;

class StoreSpotSettings implements ApiInterface {

	protected $helperData;


	public function __construct(Data $helperData)
	{
		$this->helperData = $helperData;
	}

	private function getPixelId()
	{
		return $this->helperData->getGeneralConfig('pixel_id');
	}

	private function setPixelId($pixel_id)
	{
		return $this->helperData->setGeneralConfig('pixel_id', $pixel_id);
	}

	private function getFeedEnabled()
	{
		return (bool) $this->helperData->getGeneralConfig('enabled');
	}

	private function setFeedEnabled($enabled)
	{
		return $this->helperData->setGeneralConfig('enabled', $enabled);
	}

	private function getProductCategory()
	{
		return $this->helperData->getGeneralConfig('google_product_category');
	}

	private function setProductCategory($category)
	{
		return $this->helperData->setGeneralConfig('google_product_category', $category);
	}


	/**
	 * Returns the StoreSpot settings.
	 *
	 * @api
	 * @return string $pixel_id Facebook pixel id
	 * @return boolean $pixel_enabled Enable pixel
	 * @return boolean $product_feed_enabled Enable product feed
	 * @return string $product_category Google product category
	 */
	public function getSettings() {
		$pixel_id = $this->getPixelId();
		$feed_enabled = $this->getFeedEnabled();
		$pixel_enabled = $this->getFeedEnabled();
		$product_category = $this->getProductCategory();

		$output = [[
			'pixel_id'				=> $pixel_id,
			'pixel_enabled'			=> $pixel_enabled,
			'product_feed_enabled'	=> $feed_enabled,
			'product_category'		=> $product_category,
		]];

		return $output;
	}


	/**
	 * Sets the StoreSpot settings.
	 *
	 * @api
	 * @param string $pixel_id Facebook pixel id
	 * @param boolean $pixel_enabled Enable pixel
	 * @param boolean $product_feed_enabled Enable product feed
	 * @param string $product_category Google product category
	 * @return boolean
	 */
	public function setSettings( $pixel_id, $pixel_enabled, $product_feed_enabled, $product_category )
	{
		return true;
	}
}
