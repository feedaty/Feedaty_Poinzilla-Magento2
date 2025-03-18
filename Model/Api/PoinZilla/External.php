<?php

namespace Zoorate\PoinZilla\Model\Api\PoinZilla;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zoorate\PoinZilla\Helper\Data;
use Zoorate\PoinZilla\Model\Api\PoinZilla;


class External extends PoinZilla
{
    /**
     * @var CouponRepositoryInterface
     */
    protected CouponRepositoryInterface $couponRepository;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $couponCollectionFactory;

    /**
     * @var CategoryRepository
     */
    private CategoryRepository $categoryRepository;

    /**
     * External constructor.
     * @param Data $helper
     * @param Curl $client
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     * @param CouponRepositoryInterface $couponRepository
     * @param CollectionFactory $couponCollectionFactory
     * @param CategoryRepository $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Data                      $helper,
        Curl                      $client,
        LoggerInterface             $logger,
        ProductRepository         $productRepository,
        CouponRepositoryInterface $couponRepository,
        CollectionFactory         $couponCollectionFactory,
        CategoryRepository        $categoryRepository,
        StoreManagerInterface     $storeManager,
        ScopeConfigInterface      $scopeConfig
    )
    {
        $this->couponRepository = $couponRepository;
        $this->couponCollectionFactory = $couponCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($helper, $client, $logger,  $productRepository);
    }


    /**
     * @param $customer
     * @return bool
     */
    public function createConsumer($customer): bool
    {
        // Ottieni l'ID del negozio associato al cliente
        $storeId = $customer->getStoreId();

        // Ottieni la lingua del negozio
        $localeCode = $this->scopeConfig->getValue(
            'general/locale/code',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        // Mappatura per ottenere solo valori accettati
        $acceptedCultures = ['it', 'en', 'es', 'fr', 'de'];

        // Estrai la parte della lingua prima del "_"
        $culture = substr($localeCode, 0, 2);

        // Verifica che sia un valore accettato, altrimenti imposta un default
        if (!in_array($culture, $acceptedCultures)) {
            $culture = 'en'; // Default a "en" se il valore non è tra quelli validi
        }

        $postData = json_encode([
            "email" => $customer->getEmail(),
            "firstName" => $customer->getFirstName(),
            "lastName" => $customer->getLastName(),
            "merchantCode" => $this->helper->getMerchantCode($storeId),
            "externalId" => $customer->getId(),
            "birthDate" => null,
            "cultureId" => $culture, // Lingua validata
            "group" => [$customer->getGroupId()]
        ]);

        return $this->postRequest('externalConsumer', $postData, $storeId);
    }


    /**
     * @param $order
     * @return bool
     */
    public function createOrder($order, $storeId = null): bool
    {
        $postData = json_encode([
            "id" => $order->getId(),
            "status" => $order->getStatus(),
            "customer_id" => $order->getCustomerId(),
            "line_items" => $this->getOrderItems($order),
            "coupon_lines" => $this->getCouponLines($order)
        ]);
        return $this->postRequest('externalOrder', $postData, $storeId);
    }

    /**
     * @param $order
     * @return array
     * @throws NoSuchEntityException
     */
    private function getOrderItems($order): array
    {
        $items = $order->getAllVisibleItems();
        $products = [];

        foreach ($items as $item) {

            $product = $this->productRepository->getById($item->getProductId());
            $categoryIds = $product->getCategoryIds();

            // Recupera l'alberatura completa delle categorie, incluse le categorie padre
            $allCategoryIds = $this->getAllCategoryTree($categoryIds);

            if ($item->getRowTotalInclTax()) {
                $products[] = [
                    'id' => $item->getProductId(),
                    'product_id' => $item->getProductId(),
                    'productCat' => $allCategoryIds,
                    'name' => $item->getName(),
                    'total' => $item->getRowTotalInclTax()
                ];
            }

        }

        return $products;
    }

    /**
     * @param $categoryIds
     * @return array
     */
    private function getAllCategoryTree($categoryIds): array
    {
        $allCategoryIds = [];

        foreach ($categoryIds as $categoryId) {
            // Recupera l'alberatura completa della categoria corrente
            $categoryPath = $this->getCategoryPath($categoryId);

            // Unisci tutti gli ID di categoria al risultato
            $allCategoryIds = array_merge($allCategoryIds, $categoryPath);
        }

        // Elimina eventuali duplicati
        $allCategoryIds = array_unique($allCategoryIds);

        // Filtra per escludere Root Catalog (ID 1) e Default Category (ID 2)
        $filteredCategoryIds = array_filter($allCategoryIds, function ($categoryId) {
            return $categoryId != 1 && $categoryId != 2;
        });

        // Riorganizza array per rimuovere le chiavi numeriche
        return array_values($filteredCategoryIds);
    }


    /**
     * @param $categoryId
     * @return array
     */
    private function getCategoryPath($categoryId): array
    {
        $categoryPath = [];

        try {
            // Recupera la categoria tramite l'ID
            $category = $this->categoryRepository->get($categoryId);

            $this->logger->info("Category with ID $categoryId found: " . $category->getName());

            // Ottieni il percorso completo della categoria (array ID)
            $pathIds = $category->getPathIds();

            // Aggiungi ogni ID di categoria al percorso
            foreach ($pathIds as $id) {
                $categoryPath[] = $id;
            }
        } catch (NoSuchEntityException $e) {
            // Gestione dell'errore se la categoria non viene trovata
            $this->logger->error("Category with ID $categoryId not found: " . $e->getMessage());
        }

        return $categoryPath;
    }

    /**
     * @param $order
     * @return array
     */
    private function getCouponLines($order): array
    {
        $couponLines = [];

        // Recupera il codice coupon dall'ordine
        $couponCode = $order->getCouponCode();
        // Se esiste un coupon applicato
        if ($couponCode) {
            $couponLines[] = [
                'id' => $this->getCouponIdByCode($couponCode), // Metodo per recuperare l'ID del coupon
                'code' => $couponCode,
                'discount' => $this->getCouponDiscountAmount($order) // Metodo per recuperare lo sconto del coupon
            ];
        }

        return $couponLines;
    }

    /**
     * @param $couponCode
     * @return int|null
     */
    private function getCouponIdByCode($couponCode): ?int
    {
        $couponCollection = $this->couponCollectionFactory->create()
            ->addFieldToFilter('code', $couponCode)
            ->setPageSize(1); // Limitiamo a un solo risultato

        $coupon = $couponCollection->getFirstItem();

        if ($coupon && $coupon->getId()) {
            return $coupon->getId();
        }

        return null;
    }

    /**
     * @param $order
     * @return float|int
     */
    private function getCouponDiscountAmount($order): float|int
    {
        // Restituisce l'importo totale dello sconto del coupon
        return abs($order->getDiscountAmount());
    }

}
