<?php

namespace StoreSpot\Pixel\Helper;


class Products extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_attributeSet;
    protected $_collectionFactory;
    protected $_productRepository;
    public $_storeManager;
    public $_productStatus;
    public $_productVisibility;

    protected $_searchCriteria;
    protected $_filterGroup;
    protected $_filterBuilder;
    protected $_stockItemRepository;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Eav\Model\AttributeSetRepository $attributeSet,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Api\SearchCriteriaInterface $criteria,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\CatalogInventory\Model\Stock\StockitemRepository $stockitemRepository
    )
    {
        $this->_attributeSet = $attributeSet;
        $this->_collectionFactory = $collectionFactory;
        $this->_storeManager = $storeManager;
        $this->_productStatus = $productStatus;
        $this->_productVisibility = $productVisibility;
        $this->_productRepository = $productRepository;
        $this->_searchCriteria = $criteria;
        $this->_filterGroup = $filterGroup;
        $this->_filterBuilder = $filterBuilder;
        $this->_stockItemRepository = $stockitemRepository;

        parent::__construct($context);
    }

    public function getProducts()
    {
        $this->_filterGroup->setFilters([
//            $this->_filterBuilder
//                ->setField('name')
//                ->setConditionType('=')
//                ->setValue('Strive Shoulder Pack')
//                ->create(),
        ]);

        $this->_searchCriteria->setFilterGroups([$this->_filterGroup]);
        $this->_searchCriteria->setPageSize(1);
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