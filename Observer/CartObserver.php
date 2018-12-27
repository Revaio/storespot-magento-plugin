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
        $this->coreSession->start();
        $this->coreSession->setAddToCart(true);
        error_log(print_r(get_class_methods($observer->getEvent()), true));
        error_log($observer->getEventName());
        return $this;
    }
}