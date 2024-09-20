<?php

namespace Zoorate\PoinZilla\Model\Api\PoinZilla;

use Magento\SalesRule\Api\CouponRepositoryInterface;
use Zoorate\PoinZilla\Model\Api\PoinZilla;

class External extends PoinZilla
{
    protected CouponRepositoryInterface $couponRepository;
    private $couponCollectionFactory;
    private $categoryRepository;

    public function __construct(
        \Zoorate\PoinZilla\Helper\Data $helper,
        \Magento\Framework\HTTP\Client\Curl $client,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        CouponRepositoryInterface $couponRepository,
        \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory $couponCollectionFactory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository
    ) {
        $this->couponRepository = $couponRepository;
        $this->couponCollectionFactory = $couponCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        parent::__construct($helper, $client, $logger, $productRepository);
    }


    public function createConsumer($customer)
    {
        $postData = json_encode([
            "email"        => $customer->getEmail(),
            "firstName"    => $customer->getFirstName(),
            "lastName"     => $customer->getLastName(),
            "merchantCode" => $this->helper->getMerchantCode(),
            "externalId"   => $customer->getId(),
            "birthDate"    => null
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
