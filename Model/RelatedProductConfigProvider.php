<?php
declare(strict_types=1);

namespace PerfectShapes\CheckoutRelatedStep\Model;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Extends the checkout configuration with related products configuration.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse) - other config providers also use the session
 */
class RelatedProductConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var CartItemRepositoryInterface
     */
    private $quoteItemRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Session $checkoutSession
     * @param UrlInterface $urlBuilder
     * @param CartItemRepositoryInterface $quoteItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Escaper $escaper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Session $checkoutSession,
        UrlInterface $urlBuilder,
        CartItemRepositoryInterface $quoteItemRepository,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Escaper $escaper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->escaper = $escaper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Return the configuration for the related products.
     *
     * @return array
     */
    public function getConfig()
    {
        $isEnabled = $this->scopeConfig->getValue('checkout/options/related_products_enabled');
        $relatedProducts = [];

        if ($isEnabled) {
            $quote = $this->checkoutSession->getQuote();
            $items = $quote->getAllItems();
            foreach ($items as $item) {
                $relatedToItem = $this->getRelatedProducts($item);
                if (!empty($relatedToItem)) {
                    $relatedProducts[$item->getId()] = $this->processProducts($relatedToItem);
                }
            }
        }

        return [
            'relatedProducts' => [
                'isEnabled' => $isEnabled ? true : false,
                'relatedProducts' => $relatedProducts,
            ],
        ];
    }

    /**
     * Get related products for the given item.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string|null
     */
    private function getRelatedProducts($item)
    {
        $relatedProducts = null;
        $product = $item->getProduct();
        $relatedProductIds = $product->getRelatedProductIds();
        if (!empty($relatedProductIds)) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('entity_id', $relatedProductIds, 'in')
                ->create();
            $relatedProducts = $this->productRepository->getList($searchCriteria)->getItems();
        }
        return $relatedProducts;
    }

    /**
     * Generate the carousel url for the given products.
     *
     * @param array $relatedProducts
     * @return string|null
     */
    private function processProducts($relatedProducts)
    {
        $this->urlBuilder->setScope($this->checkoutSession->getQuote()->getStore()->getId());

        $ids = implode(',', array_map(function ($product) {
            return $product->getId();
        }, $relatedProducts));

        if (!empty($ids)) {
            return $this->urlBuilder->getUrl('related/products', ['ids' => '[' . $ids . ']']);
        }

        return null;
    }
}
