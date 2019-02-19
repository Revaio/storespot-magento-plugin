<?php
namespace StoreSpot\Personalization\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_STORESPOT = 'storespot/';

    private $configWriter;
    private $scope;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->scope = 'default';
    }

    public function getConfigValue($field)
    {
        return $this->scopeConfig->getValue($field, $this->scope);
    }

    public function setConfigValue($field, $value)
    {
        return $this->configWriter->save($field, $value, $this->scope);
    }

    // General settings
    public function getGeneralConfig($field)
    {
        return $this->getConfigValue(
            self::XML_PATH_STORESPOT . 'general/' . $field
        );
    }

    public function setGeneralConfig($field, $value)
    {
        return $this->setConfigValue(
            self::XML_PATH_STORESPOT . 'general/' . $field,
            $value
        );
    }
}
