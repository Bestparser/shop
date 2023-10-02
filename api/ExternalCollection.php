<?php
namespace Modules\Shop\Api;

use Modules\Api\Services\ApiCollection;

class ExternalCollection extends ApiCollection
{
    public static $apiType = 'external';


    public function createMethods()
    {
        //$this->post('shop-delivery-method-all', \Modules\Shop\Api\Methods\DeliveryMethods\All::class);

        $this->post('shop-order-selection', \Modules\Shop\Api\Methods\DeliveryMethods\All::class);

        $this->get('shop-order', \Modules\Shop\Api\Methods\Orders\Selection::class);
        $this->post('shop-order', \Modules\Shop\Api\Methods\Orders\Update::class);

    }


}