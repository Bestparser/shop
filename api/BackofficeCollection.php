<?php
namespace Modules\Shop\Api;

use Modules\Api\Services\ApiCollection;

class BackofficeCollection extends ApiCollection
{
    public static $apiType = 'backoffice';

    public function createMethods()
    {

        //$this->get('shop-order-feed', \Modules\Shop\Api\Methods\Orders\All::class);
        $this->get('shop-order-find', \Modules\Shop\Api\Methods\Orders\Find::class);
        //$this->post('shop-order-store', \Modules\Shop\Api\Methods\Orders\Store::class);
        //$this->post('shop-order-delete', \Modules\Shop\Api\Methods\Orders\Delete::class);
        $this->get('shop-order-to-export', \Modules\Shop\Api\Methods\Orders\ToExport::class);


    }


}