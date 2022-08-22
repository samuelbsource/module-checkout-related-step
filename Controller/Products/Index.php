<?php
declare(strict_types=1);

namespace PerfectShapes\CheckoutRelatedStep\Controller\Products;

use PerfectShapes\CheckoutRelatedStep\Block\Product\ProductsListFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Index implements HttpGetActionInterface
{
    /**
     * @var ProductsListFactory
     */
    private $productsListFactory;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Http
     */
    protected $http;

    /**
     * Constructor
     *
     * @param ProductsListFactory $productsListFactory
     * @param Request $request
     * @param PageFactory $resultPageFactory
     * @param Json $json
     * @param LoggerInterface $logger
     * @param Http $http
     */
    public function __construct(
        ProductsListFactory $productsListFactory,
        Request $request,
        PageFactory $resultPageFactory,
        Json $json,
        LoggerInterface $logger,
        Http $http
    ) {
        $this->productsListFactory = $productsListFactory;
        $this->request = $request;
        $this->resultPageFactory = $resultPageFactory;
        $this->serializer = $json;
        $this->logger = $logger;
        $this->http = $http;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $relatedIds = $this->request->getParam('ids');
            if (!$relatedIds) {
                throw new LocalizedException(__('No related products specified'));
            }

            $relatedIds = $this->serializer->unserialize($relatedIds);

            $block = $this->productsListFactory->create();
            $block->setTemplate('Magento_PageBuilder::catalog/product/widget/content/carousel.phtml');
            $collection = $block->createCollection();
            $collection->addIdFilter($relatedIds);

            return $this->htmlResponse($block->toHtml());
        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @param string $response
     * @return ResultInterface
     */
    public function jsonResponse($response = '')
    {
        $this->http->getHeaders()->clearHeaders();
        $this->http->setHeader('Content-Type', 'application/json');
        $this->http->setHeader('Cache-Control', 'no-cache');
        return $this->http->setBody(
            $this->serializer->serialize($response)
        );
    }

    /**
     * Create html response
     *
     * @param string $response
     * @return ResultInterface
     */
    public function htmlResponse($response = '')
    {
        $this->http->getHeaders()->clearHeaders();
        $this->http->setHeader('Content-Type', 'text/html');
        $this->http->setHeader('Cache-Control', 'no-cache');
        return $this->http->setBody($response);
    }
}
