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


	public function getSettings() {
		$pixel_id = $this->getPixelId();
		$feed_enabled = $this->getFeedEnabled();
		$product_category = $this->getProductCategory();

		$output = [[
			'settings' => [
			  'pixel_id'			=> $pixel_id,
			  'product_feed'		=> $feed_enabled,
			  'product_category'	=> $product_category,
			]
		]];

		return $output;
	}


	public function setSettings()
	{
		return true;
	}
}
