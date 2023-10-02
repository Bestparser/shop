<?php
namespace Modules\Shop\Api\Methods\Orders;


use Modules\Api\Services\ApiMethod;

class ToExport extends ApiMethod
{
    protected $_aclResource = ['shopOrders'];
    protected $_apiResourceClass = \Modules\Shop\Api\Resources\Order::class;
    protected $_modelClass = \Shop_Model_Mapper_Orders::class;

    protected $_validate = [
        'id' => 'required|int',
    ];

    public function dispatch(\Lv7CMS_Controller_Request $request)
    {
        $order = \Shop_Service_OrderManager::markForExport($request->get('id'));
        $res = $this->getApiResourceClass();
        return new $res($order);
    }

}
