<?php

namespace StoreSpot\Personalization\Helper;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;


class Data extends AbstractHelper
{
	const XML_PATH_STORESPOT = 'storespot/';

	protected $configWriter;
	protected $scope;

	public function __construct(
		Context $context,
		array $data = [],
		WriterInterface $configWriter
	)
	{
		parent::__construct($context, $data);
		$this->configWriter = $configWriter;
		$this->scope = ScopeInterface::SCOPE_STORE;
	}

	public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue($field, $this->scope, $storeId);
	}

	public function setConfigValue($field, $value)
	{
		return $this->configWriter->save($field, $value, $this->scope);
	}

	// General settings
	public function getGeneralConfig($field, $storeId = null)
	{
		return $this->getConfigValue(
			self::XML_PATH_STORESPOT . 'general/' . $field,
			$storeId
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
