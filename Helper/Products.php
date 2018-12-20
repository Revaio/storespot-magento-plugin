<?php

namespace StoreSpot\Personalization\Helper;


class Products extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $_storeManager;
    private $_productRepository;
    private $_searchCriteria;
    private $_filterGroup;
    private $_filterBuilder;
    private $_productStatus;
    private $_stockItemRepository;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\CatalogInventory\Model\Stock\StockitemRepository $stockItemRepository
    )
    {
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->_searchCriteria = $searchCriteria;
        $this->_filterGroup = $filterGroup;
        $this->_filterBuilder = $filterBuilder;
        $this->_productStatus = $productStatus;
        $this->_stockItemRepository = $stockItemRepository;

        parent::__construct($context);
    }

    public function getProducts()
    {
        $this->_filterGroup->setFilters([
            $this->_filterBuilder
                ->setField('status')
                ->setConditionType('in')
                ->setValue($this->_productStatus->getVisibleStatusIds())
                ->create(),
        ]);

        $this->_searchCriteria->setFilterGroups([$this->_filterGroup]);
        $products = $this->_productRepository->getList($this->_searchCriteria);
        $productItems = $products->getItems();

        return $productItems;
    }

    public function getProduct($productId)
    {
        return $this->_productRepository->getById($productId);
    }

    public function getProductDescription($product)
    {
        if ($product->getShortDescription()) {
            $description = $product->getShortDescription();
        } else {
            $description = $product->getDescription();
        }
        $encode = mb_detect_encoding($description);
        $description = mb_convert_encoding($description, 'UTF-8', $encode);
        $description = strip_tags($description);
        return $description;
    }

    public function getProductAvailability($product)
    {
        $stockItem = $this->_stockItemRepository->get($product->getId());

        if ($stockItem->getIsInStock()) {
            return 'in stock';
        } else {
            return 'out of stock';
        }
    }

    public function getProductImage($product)
    {
       $url = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true) . 'catalog/product' . $product->getImage();
       return $url;
    }
}