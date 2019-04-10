<?php

namespace StoreSpot\Personalization\Block;

use Magento\Checkout\Model\Session;

class Display extends \Magento\Framework\View\Element\Template
{

    private $dataHelper;
    private $productsHelper;
    private $catalogHelper;
    private $product;
    private $checkoutSession;
    private $queryFactory;
    private $listHelper;

    /**
     * Display constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Helper\Data $catalogHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \StoreSpot\Personalization\Helper\Data $dataHelper
     * @param \StoreSpot\Personalization\Helper\Products $productsHelper
     * @param \Magento\Search\Model\QueryFactory $queryFactory
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Catalog\Helper\Product\ProductList $listHelper,
        \StoreSpot\Personalization\Helper\Data $dataHelper,
        \StoreSpot\Personalization\Helper\Products $productsHelper,
        \Magento\Search\Model\QueryFactory $queryFactory,
        Session $checkoutSession
    ) {
        $this->dataHelper = $dataHelper;
        $this->catalogHelper = $catalogHelper;
        $this->queryFactory = $queryFactory;
        $this->productsHelper = $productsHelper;
        $this->checkoutSession = $checkoutSession;
        $this->listHelper = $listHelper;
        parent::__construct($context);
    }

    /**
     * Return ID of pixel
     * @return mixed
     */
    public function getPixelId()
    {
        return $this->dataHelper->getGeneralConfig('pixel_id');
    }

    /**
     * Return feed ID of product
     * @return string
     */
    public function getContentId($product_id)
    {
        return 'stsp_' . $product_id;
    }

    /**
     * Get currency
     * @return string
     */
    public function getCurrency()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * Returns Facebook Pixel 'fbq' tracking code
     * @param $event
     * @param $parameters
     * @param string $method
     * @return string
     */
    private function facebookEventCode($event, $parameters, $method = 'track')
    {
        return sprintf(
            "fbq('%s', '%s', %s)",
            $method,
            $event,
            json_encode($parameters, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT)
        );
    }

    private function getProductVariations($product)
    {
        $used_attributes = [];
        foreach ($product->getTypeInstance()->getUsedProductAttributes($product) as $attr) {
            $used_attributes[$attr->getId()] = $attr->getAttributeCode();
        }

        $options = [];
        $variations = $product->getTypeInstance()->getUsedProducts($product);
        foreach ($variations as $option) {
            $variation = [
                'id'    => $this->getContentId($option->getId()),
                'price' => $this->productsHelper->getProductPrice($option),
            ];

            foreach ($used_attributes as $id => $value) {
                $variation[$id] = $option->getData($value);
            }

            $options[$option->getId()] = $variation;
        }

        return $options;
    }

    /**
     * Render view product event
     * @return string
     */
    private function renderViewContentEvent()
    {
        $product = $this->catalogHelper->getProduct();
        $product_id = $product->getId();
        $product_type = $product->getTypeId();
        $product_price = $this->productsHelper->getProductPrice($product);

        $type = 'product';
        if( $product_type == 'configurable' ) {
            $type = 'product_group';
            $options = $this->getProductVariations( $product );

            $add_to_cart = sprintf("
            require(['jquery'], function($){
                $('#product_addtocart_form').submit(function() {
                    let products = %s;
                    let item = $(this).find('input[name=\"selected_configurable_option\"]').val();
                    const qty = $(this).find('input[name=\"qty\"]').val();
                    if( !item ) {
                        Object.filter = (obj, predicate) => Object.keys(obj).filter( key => predicate(obj[key]) ).reduce( (res, key) => (res[key] = obj[key], res), {} );
                        $(this).find('.swatch-attribute').each(function() {
                            products = Object.filter(products, p => p[$(this).attr('attribute-id')] == $(this).attr('option-selected'))
                        });

                        item = Object.keys(products)[0];
                    }

                    fbq('track', 'AddToCart', {
                        content_ids: [products[item]['id']],
                        content_type: 'product',
                        value: products[item]['price'] * qty,
                        currency: '%s'
                    });
                })
            })", json_encode( $options ), $this->getCurrency());
        }

        else {
            $add_to_cart = sprintf("
                require(['jquery'], function($){
                    $('#product_addtocart_form').submit(function() {
                        const qty = $(this).find('input[name=\"qty\"]').val();
                        fbq('track', 'AddToCart', {
                            content_ids: ['stsp_%s'],
                            content_type: 'product',
                            value: %s * qty,
                            currency: '%s'
                        });
                    })
                })", $product_id, $product_price, $this->getCurrency());
        }

        $view_content = $this->facebookEventCode('ViewContent', [
            'content_type'  => $type,
            'content_ids'   => json_encode([$this->getContentId($product_id)]),
            'value'         => $product_price,
            'currency'      => $this->getCurrency()
        ]);


        return sprintf("%s\n%s", $view_content, $add_to_cart);
    }

    /**
     * Render initiate checkout event
     * @return string
     */
    private function renderInitiateCheckoutEvent()
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllItems();

        $contents = [];
        foreach ($items as $item) {
            $content = [];
            $content['id'] = $this->getContentId($item->getProductId());
            $content['quantity'] = (int) $item->getQty();
            $content['item_price'] = round($item->getPrice(), 2);
            $contents[] = $content;
        }

        $params = [
            'content_type'  => 'product',
            'contents'      => json_encode($contents),
            'currency'      => $this->getCurrency()
        ];

        return $this->facebookEventCode('InitiateCheckout', $params);
    }

    /**
     * Render purchase event
     * @return string
     */
    private function renderPurchaseEvent()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $items = $order->getAllVisibleItems();
        $contents = [];
        foreach ($items as $item) {
            $content = [];
            $content['id'] = $this->getContentId($item->getProductId());
            $content['quantity'] = (int) $item->getQtyOrdered();
            $content['item_price'] = round($item->getPrice(), 2);
            $contents[] = $content;
        }

        $params = [
            'content_type'  => 'product',
            'contents'      => json_encode($contents),
            'value'         => round($order->getGrandTotal(), 2),
            'currency'      => $order->getOrderCurrencyCode()
        ];

        return $this->facebookEventCode('Purchase', $params);
    }

    /**
     * Render add to cart event in form mode
     * @return string
     */
    private function renderCategoryEvent()
    {
        $products = $this->getLayout()
            ->getBlock('category.products.list')
            ->getLoadedProductCollection()
            ->addFinalPrice();

        $option_list = [];
        foreach ($products as $product) {
            if($product->getTypeId() == 'configurable') {
                $add = $this->getProductVariations($product);
            } else {
                $add = [
                    'price' => $this->productsHelper->getProductPrice($product)
                ];
            }
            $option_list[$product->getId()] = $add;
        }

        return sprintf("

            require(['jquery'], function($) {
                'use strict';
                $('form[data-role=\"tocart-form\"]').submit(function () {
                    var products = %s;
                    var parent = $(this).find('input[name=\"product\"]').val();
                    var options = $('[data-role=\"swatch-option-' + parent + '\"]');
                    if ( options.length ) {
                        Object.filter = (obj, predicate) => Object.keys(obj).filter( key => predicate(obj[key]) ).reduce( (res, key) => (res[key] = obj[key], res), {} );
                        var swatch = products[parent];
                        options.children().each(function () {
                            swatch = Object.filter(swatch, p => p[$(this).attr('attribute-id')] == $(this).attr('option-selected'));
                        });
                        var oKey = Object.keys(swatch)[0];
                        products[oKey]= {price: swatch[oKey]['price']};
                        parent = oKey;
                    }
                    fbq('track', 'AddToCart', {
                        content_ids: ['stsp_' + parent],
                        content_type: 'product',
                        value: products[parent]['price'],
                        currency: '%s'
                    });
                });
            });

        ", json_encode($option_list), $this->getCurrency());

        // LEGACY
        // $mode = $this->getRequest()->getParam('product_list_mode') ? $this->getRequest()->getParam('product_list_mode') : $this->listHelper->getDefaultViewMode();
        // $sort = $this->getRequest()->getParam('product_list_dir') ? $this->getRequest()->getParam('product_list_dir') : $this->listHelper::DEFAULT_SORT_DIRECTION;
        // $page = $this->getRequest()->getParam('p') ? $this->getRequest()->getParam('p') : 1;
        // $size = $this->getRequest()->getParam('product_list_limit') ? $this->getRequest()->getParam('product_list_limit') : $this->listHelper->getDefaultLimitPerPageValue($mode);
        // $order = $this->getRequest()->getParam('product_list_order') ? $this->getRequest()->getParam('product_list_order') : $this->listHelper->getDefaultSortField();
        //
        // $category = $this->catalogHelper->getCategory();
        // $products = $category->getProductCollection()->setPageSize($size)->setCurPage($page)->addFinalPrice()->setOrder($order, $sort);
        // $product_list = [];
        // $currency = $this->getCurrency();
        //
        // foreach ($products as $product) {
        //     $product_id = $product->getId();
        //     $product_type = $product->getTypeId();
        //     $type = 'product';
        //     if( $product_type == 'configurable' ) {
        //         $type = 'product_group';
        //         $options = $this->getProductVariations( $product );
        //
        //         foreach( $options as $id => $option ) {
        //             $product_list[$id] = [
        //                 'value'         => $option['price'],
        //                 'content_type'  => 'product',
        //                 'content_ids'   => $option['id'],
        //                 'currency'      => $currency,
        //                 'option'        => $option,
        //             ];
        //         }
        //     }
        //
        //     $product_list[$product_id] = [
        //         'value'         => $this->productsHelper->getProductPrice($product),
        //         'content_type'  => $type,
        //         'content_ids'   => [$this->getContentId($product->getId())],
        //         'currency'      => $currency
        //     ];
        // }
        // return sprintf("
        //
        //     require(['jquery'], function($) {
        //         'use strict';
        //         $('form[data-role=\"tocart-form\"]').submit(function () {
        //             var products = %s;
        //             var product = $(this).find('input[name=\"product\"]').val();
        //             if (products[product] && products[product].content_type === 'product_group') {
        //                 var item = $(this).find('input[name=\"selected_configurable_option\"]').val();
        //                 if (item) {
        //                     product = item;
        //                 } else {
        //                     Object.filter = (obj, predicate) => Object.keys(obj).filter( key => predicate(obj[key]) ).reduce( (res, key) => (res[key] = obj[key], res), {} );
        //                     $('.swatch-opt-' + product).find('.swatch-attribute').each(function() {
        //                         products = Object.filter(products, p => p.option && p.option[$(this).attr('attribute-id')] == $(this).attr('option-selected'));
        //                     });
        //                     product = Object.keys(products)[0];
        //                     delete products[product].option;
        //                 }
        //             }
        //             fbq('track', 'AddToCart', products[product]);
        //         });
        //     })
        //
        // ", json_encode($product_list));
    }

    /**
     * Render add to cart event in button mode
     * @return string
     */
    public function RenderPageEvent()
    {
        return sprintf("

            require(['jquery'], function($){
                $('form[data-role=\"tocart-form\"]').submit(function () {
                    var product = $(this).find('input[name=\"product\"]').val();
                    var price = $('[data-role=\"priceBox\"][data-product-id=\"' + product + '\"]').find(
                        '[data-price-amount]'
                    ).attr('data-price-amount');
                    fbq('track', 'AddToCart', {
                        content_ids: ['stsp_' + product],
                        content_type: 'product',
                        value: price,
                        currency: '%s'
                    });
                });
            });

        ", $this->getCurrency());
    }

    /**
     * Returns Facebook Pixel event code if necessary
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getEventCode()
    {
        $action = $this->getRequest()->getFullActionName();
        switch ($action) {
            case 'catalog_product_view':
                return $this->renderViewContentEvent();

            case 'checkout_index_index':
            case 'onepagecheckout_index_index':
            case 'onestepcheckout_index_index':
                return $this->renderInitiateCheckoutEvent();

            case 'checkout_onepage_success':
                return $this->renderPurchaseEvent();

            case 'catalogsearch_result_index':
                $params['search_string'] = $this->queryFactory->get()->getQueryText();
                return $this->facebookEventCode('Search', $params);

            case 'catalogsearch_advanced_result':
                return $this->facebookEventCode('Search', $params);

            case 'catalog_category_view':
                return $this->renderCategoryEvent();

            default:
                return $this->RenderPageEvent();
        }
    }
}
