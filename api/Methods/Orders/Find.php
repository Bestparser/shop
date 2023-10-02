<?php
namespace Modules\Shop\Api\Methods\Orders;


use Modules\Api\Services\ApiMethod\Find as FindApiMethod;

class Find extends FindApiMethod
{
    protected $_aclResource = ['shopOrders'];
    protected $_apiResourceClass = \Modules\Shop\Api\Resources\Order::class;
    protected $_modelClass = \Shop_Model_Mapper_Orders::class;

}
