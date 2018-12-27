<?php

namespace StoreSpot\Personalization\Helper;


class Products extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $storeManager;
    private $dataHelper;
    private $productRepository;
    private $searchCriteria;
    private $filterGroup;
    private $filterBuilder;
    private $productStatus;
    private $stockItemRepository;
    private $configurableProductType;
    private $taxConfig;
    private $catalogHelper;

    private $displayIncludingTax = null;
    private $catalogIncludingTax = null;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \StoreSpot\Personalization\Helper\Data $dataHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\CatalogInventory\Model\Stock\StockitemRepository $stockItemRepository,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableProductType,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Tax\Model\Config $taxConfig
    )
    {
        $this->dataHelper = $dataHelper;
        $this->storeManager = $storeManager;
        $this->catalogHelper = $catalogHelper;
        $this->productRepository = $productRepository;
        $this->searchCriteria = $searchCriteria;
        $this->filterGroup = $filterGroup;
        $this->filterBuilder = $filterBuilder;
        $this->productStatus = $productStatus;
        $this->stockItemRepository = $stockItemRepository;
        $this->configurableProductType = $configurableProductType;
        $this->taxConfig = $taxConfig;

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

    public function getProductPrice($product)
    {
        $price = $product->getFinalPrice();
        $final = $this->catalogHelper->getTaxPrice(
            $product,
            $price,
            $this->getDisplayIncludingTax(),
            null,
            null,
            null,
            $this->getStoreId(),
            $this->getCatalogIncludingTax(),
            true
        );
        return $final;
    }

    private function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    private function getDisplayIncludingTax()
    {
        if ($this->displayIncludingTax == null) {
            $priceDisplayType = $this->taxConfig->getPriceDisplayType($this->getStoreId());
            if ($priceDisplayType == 1) {
                // Catalog displays prices excluding taxes.
                $this->displayIncludingTax = false;
            } else {
                $this->displayIncludingTax = true;
            }
        }
        return $this->displayIncludingTax;
    }

    private function getCatalogIncludingTax()
    {
        if ($this->catalogIncludingTax == null) {
            $catalogTaxType = (int)$this->dataHelper->getConfigValue(
                'tax/calculation/price_includes_tax',
                $this->getStoreId()
            );
            if ($catalogTaxType == 0) {
                $this->catalogIncludingTax = false;
            } else {
                $this->catalogIncludingTax = true;
            }
        }
        return $this->catalogIncludingTax;
    }
}