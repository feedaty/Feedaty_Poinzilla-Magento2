<?php

namespace Zoorate\PoinZilla\Model\Api\PoinZilla;

use Magento\SalesRule\Api\CouponRepositoryInterface;
use Zoorate\PoinZilla\Model\Api\PoinZilla;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;


class External extends PoinZilla
{
    protected CouponRepositoryInterface $couponRepository;
    private $couponCollectionFactory;
    private $categoryRepository;
    protected $storeManager;
    protected $scopeConfig;

    public function __construct(
        \Zoorate\PoinZilla\Helper\Data $helper,
        \Magento\Framework\HTTP\Client\Curl $client,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        CouponRepositoryInterface $couponRepository,
        \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory $couponCollectionFactory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->couponRepository = $couponRepository;
        $this->couponCollectionFactory = $couponCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($helper, $client, $logger, $productRepository);
    }


    /**
     * @param $customer
     * @return mixed
     */
    public function createConsumer($customer)
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
            "email"        => $customer->getEmail(),
            "firstName"    => $customer->getFirstName(),
            "lastName"     => $customer->getLastName(),
            "merchantCode" => $this->helper->getMerchantCode(),
            "externalId"   => $customer->getId(),
            "birthDate"    => null,
            "cultureId"      => $culture, // Lingua validata
            "group" => [$customer->getGroupId()]
        ]);

        return $this->postRequest('externalConsumer', $postData);
    }


    /**
     * @param $order
     * @return mixed
     */
    public function createOrder($order)
    {
        $postData = json_encode([
            "id"          => $order->getId(),
            "status"      => $order->getStatus(),
            "customer_id" => $order->getCustomerId(),
            "line_items"  => $this->getOrderItems($order),
            "coupon_lines" => $this->getCouponLines($order)
        ]);
        return $this->postRequest('externalOrder', $postData);
    }

    private function getOrderItems($order)
    {
        $items = $order->getAllVisibleItems();
        $products = [];

        foreach ($items as $item) {

            $product = $this->productRepository->getById($item->getProductId());
            $categoryIds = $product->getCategoryIds();

            // Recupera l'alberatura completa delle categorie, incluse le categorie padre
            $allCategoryIds = $this->getAllCategoryTree($categoryIds);

            if($item->getRowTotalInclTax()) {
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

    private function getAllCategoryTree($categoryIds)
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

        // Riorganizza l'array per rimuovere le chiavi numeriche
        return array_values($filteredCategoryIds);
    }



    private function getCategoryPath($categoryId)
    {
        $categoryPath = [];

        try {
            // Recupera la categoria tramite l'ID
            $category = $this->categoryRepository->get($categoryId);

            $this->logger->info("Category with ID $categoryId found: " . $category->getName());

            // Ottieni il percorso completo della categoria (array di ID)
            $pathIds = $category->getPathIds();

            // Aggiungi ogni ID di categoria al percorso
            foreach ($pathIds as $id) {
                $categoryPath[] = $id;
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Gestione dell'errore se la categoria non viene trovata
            $this->logger->error("Category with ID $categoryId not found: " . $e->getMessage());
        }

        return $categoryPath;
    }


    private function getCouponLines($order)
    {
        $couponLines = [];

        // Recupera il codice coupon dall'ordine
        $couponCode = $order->getCouponCode();
        // Se esiste un coupon applicato
        if ($couponCode) {
            $couponLines[] = [
                'id'       => $this->getCouponIdByCode($couponCode), // Metodo per recuperare l'ID del coupon
                'code'     => $couponCode,
                'discount' => $this->getCouponDiscountAmount($order) // Metodo per recuperare lo sconto del coupon
            ];
        }

        return $couponLines;
    }


    private function getCouponDiscountAmount($order): float|int
    {
        // Restituisce l'importo totale dello sconto del coupon
        return abs($order->getDiscountAmount());
    }

    private function getCouponIdByCode($couponCode)
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


}
