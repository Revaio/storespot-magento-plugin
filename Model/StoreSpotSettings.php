<?php
namespace StoreSpot\Personalization\Model;
use StoreSpot\Personalization\Api\StoreSpotSettingsInterface as ApiInterface;


class StoreSpotSettings implements ApiInterface {

	protected $helperData;
	protected $cacheTypeList;

	public function __construct(
		\Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
		\StoreSpot\Personalization\Helper\Data $helperData
	)
	{
		$this->helperData = $helperData;
		$this->cacheTypeList = $cacheTypeList;
	}

	private function getPixelId()
	{
		return $this->helperData->getGeneralConfig('pixel_id');
	}

	private function setPixelId($pixel_id)
	{
		return $this->helperData->setGeneralConfig('pixel_id', $pixel_id);
	}

	private function getPixelEnabled()
	{
		return (bool) $this->helperData->getGeneralConfig('pixel_enabled');
	}

	private function setPixelEnabled($pixel_enabled)
	{
		return $this->helperData->setGeneralConfig('pixel_enabled', $pixel_enabled);
	}

	private function getFeedEnabled()
	{
		return (bool) $this->helperData->getGeneralConfig('feed_enabled');
	}

	private function setFeedEnabled($feed_enabled)
	{
		return $this->helperData->setGeneralConfig('feed_enabled', $feed_enabled);
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
		$feed_enabled = $this->getPixelEnabled();
		$pixel_enabled = $this->getFeedEnabled();
		$product_category = $this->getProductCategory();

		$output = [[
			'pixel_id'			=> $pixel_id,
			'pixel_enabled'		=> $pixel_enabled,
			'feed_enabled'		=> $feed_enabled,
			'product_category'	=> $product_category,
		]];

		return $output;
	}


	/**
	 * Sets the StoreSpot settings.
	 *
	 * @api
	 * @param string $pixel_id Facebook pixel id
	 * @param boolean $pixel_enabled Enable pixel
	 * @param boolean $feed_enabled Enable product feed
	 * @param string $product_category Google product category
	 * @return boolean $updated
	 */
	public function setSettings( $pixel_id, $pixel_enabled, $feed_enabled, $product_category )
	{
		$this->setPixelId( $pixel_id );
		$this->setPixelEnabled( $pixel_enabled );
		$this->setFeedEnabled( $feed_enabled );
		$this->setProductCategory( $product_category );
		$this->cacheTypeList->cleanType(
			\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER
		);
		return [['updated' => true]];
	}
}
