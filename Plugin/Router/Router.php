<?php

namespace Zoorate\PoinZilla\Plugin\Router;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\Action\Redirect;
use Magento\Framework\UrlInterface;

class Router
{
    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @var ActionFactory
     */
    protected $actionFactory;

    /**
     * @var UrlInterface
     */
    protected $url;

    public function __construct(
        ResponseInterface $response,
        ActionFactory $actionFactory,
        UrlInterface $url
    ) {
        $this->response = $response;
        $this->actionFactory = $actionFactory;
        $this->url = $url;
    }

    public function afterMatch(
        \Magento\UrlRewrite\Controller\Router $subject,
        $result,
        RequestInterface $request
    ) {
        $temp = $request->getPathInfo();
        $success = preg_match('/referral\/([a-zA-Z0-9]+)\/referral/', $temp, $matches);
        if ($success && isset($matches[1])) {
            $url = $this->url->getUrl('', ['_query' => ['referral_code' => $matches[1], "default_view" => "referral-action"]]);
            $this->response->setRedirect($url, 302);

            $result = $this->actionFactory->create(Redirect::class);
        }
        return $result;
    }
}
