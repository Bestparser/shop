<?php
namespace Modules\Shop\Api\Methods\DeliveryMethods;

use Modules\Api\Exceptions\Exception;
use Modules\Api\Services\ApiMethod;
use Modules\Company\Models\Mappers\Territories;

class All extends ApiMethod
{
    protected $_aclResource = ['shopSettings'];

    protected $_validate = [
    ];

    public function dispatch(\Lv7CMS_Controller_Request $request)
    {
        $deliveryMethods = \Shop_Service_Scheme_Default::getInstance()->getDeliveries();

    }
}
