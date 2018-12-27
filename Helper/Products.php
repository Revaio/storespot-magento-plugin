<?php

namespace StoreSpot\Personalization\Helper;


class Products extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $storeManager;
    private $productRepository;
    private $searchCriteria;
    private $filterGroup;
    private $filterBuilder;
    private $productStatus;
    private $stockItemRepository;
    private $configurableProductType;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\CatalogInventory\Model\Stock\StockitemRepository $stockItemRepository,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableProductType
    )
    {
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->searchCriteria = $searchCriteria;
        $this->filterGroup = $filterGroup;
        $this->filterBuilder = $filterBuilder;
        $this->productStatus = $productStatus;
        $this->stockItemRepository = $stockItemRepository;
        $this->configurableProductType = $configurableProductType;

        parent::__construct($context);
    }

    public function getProducts()
    {
        $this->filterGroup->setFilters([
            $this->filterBuilder
                ->setField('status')
                ->setConditionType('in')
                ->setValue($this->productStatus->getVisibleStatusIds())
                ->create(),
        ]);

        $this->searchCriteria->setFilterGroups([$this->filterGroup]);
        $products = $this->productRepository->getList($this->searchCriteria);
        $productItems = $products->getItems();

        return $productItems;
    }

    public function getProduct($productId)
    {
        return $this->productRepository->getById($productId);
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
        $stockItem = $this->stockItemRepository->get($product->getId());

        if ($stockItem->getIsInStock()) {
            return 'in stock';
        } else {
            return 'out of stock';
        }
    }

    public function getProductImage($product)
    {
       $url = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true) . 'catalog/product' . $product->getImage();
       return $url;
    }

    public function getParentProduct($product)
    {
        $parent = $this->configurableProductType->getParentIdsByChild($product->getId());
        if ($parent) {
            return $this->getProduct($parent[0])->getSku();
        }
        return null;
    }
}