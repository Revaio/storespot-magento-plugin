<?php
namespace StoreSpot\Personalization\Cron;

class FeedGenerator {
    protected $feed;
    protected $helperData;
    protected $logger;

    public function __construct(
        \StoreSpot\Personalization\Model\Feed $feed,
        \StoreSpot\Personalization\Helper\Data $helperData,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->feed = $feed;
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    public function execute() {
        $this->feed->createFeed();
        $last = date('Y-m-d H:i:s');
        $next = date('Y-m-d H:i:s', (time() + 60 * 60));
        $this->helperData->saveCronConfig('last_execution', $last);
        $this->helperData->saveCronConfig('next_execution', $next);
    }

}
