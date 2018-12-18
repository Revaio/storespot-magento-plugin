<?php
namespace StoreSpot\Pixel\Cron;

use \Psr\Log\LoggerInterface;

class FeedGenerator {
    protected $_logger;
//    protected $_feed;

//    protected $_directoryList;
//    protected $_file;

    public function __construct(
        LoggerInterface $logger
//        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
//        \Magento\Framework\Filesystem\Io\File $file
    )
    {
        $this->_logger = $logger;
//        $this->_directoryList = $directoryList;
//        $this->_file = $file;
    }

    public function execute() {
        $this->_logger->debug('New feed generation');
        error_log('New feed generation');
//        $url = "https://webkul.com/wp-content/themes/webkul/inc/E-Commerce-Marketplace-Use-Case.pdf";
//        $pdfContent = file_get_contents($url);
//
//        $filePath = "/storespot/";
//        $pdfPath = $this->_directoryList->getPath('media').$filePath;
//        if (!is_dir($pdfPath))
//        {
//            $ioAdapter = $this->_file;
//            $ioAdapter->mkdir($pdfPath, 0775);
//        }
//
//        $fileName = "webkul.pdf";
//        $ioAdapter->open(array('path'=>$pdfPath));
//        $ioAdapter->write($fileName, $pdfContent, 0666);
    }

}