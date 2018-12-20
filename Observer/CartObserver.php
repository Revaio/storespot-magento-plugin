<?php

namespace StoreSpot\Personalization\Observer;

use Magento\Framework\Event\ObserverInterface;

class CartObserver implements ObserverInterface
{
    private $coreSession;

    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $coreSession
    )
    {
        $this->coreSession = $coreSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        error_log("test");
        $this->coreSession->setAddToCart(true);
        echo 'Event';
    }
}