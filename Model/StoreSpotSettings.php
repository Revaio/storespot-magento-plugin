<?php
namespace StoreSpot\Personalization\Model;

use StoreSpot\Personalization\Api\StoreSpotSettingsInterface as ApiInterface;

class StoreSpotSettings implements ApiInterface
{

    private $helperData;
    private $cacheTypeList;

    public function __construct(
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \StoreSpot\Personalization\Helper\Data $helperData
    ) {
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

    /**
     * Returns the StoreSpot settings.
     *
     * @api
     * @return string $pixel_id Facebook pixel id
     * @return boolean $pixel_enabled Enable pixel
     */
    public function getSettings()
    {
        $pixel_id = $this->getPixelId();
        $pixel_enabled = $this->getPixelEnabled();

        $output = [[
            'pixel_id'          => $pixel_id,
            'pixel_enabled'     => $pixel_enabled,
        ]];

        return $output;
    }

    /**
     * Sets the StoreSpot settings.
     *
     * @api
     * @param string $pixel_id Facebook pixel id
     * @param boolean $pixel_enabled Enable pixel
     * @return boolean $updated
     */
    public function setSettings($pixel_id, $pixel_enabled)
    {
        $this->setPixelId($pixel_id);
        $this->setPixelEnabled($pixel_enabled);
        $this->cacheTypeList->cleanType(
            \Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER
        );
        $this->cacheTypeList->cleanType('full_page');
        return [['updated' => true]];
    }
}
