<?php
namespace Modules\Shop\Api\Methods\Orders;

use Modules\Api\Exceptions\Exception;
use Modules\Api\Services\ApiMethod;
use Modules\Company\Models\Mappers\Territories;

class Selection extends ApiMethod
{
    protected $_aclResource = ['shopOrders'];
    protected $_apiResourceClass = \Modules\Shop\Api\Resources\Order::class;
    protected $_modelClass = \Shop_Model_Mapper_Orders::class;
    protected $_apiResourceScopes = ['common', 'positions', 'sysDates'];

    protected $_validate = [
        'page' => 'nullable',
        'perPage' => 'nullable',
        'createdAfter' => 'nullable|date:Y-m-d',
        'territoryCode' => 'nullable',
        'docAvailability' => 'nullable|boolean',
    ];

    public function dispatch(\Lv7CMS_Controller_Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', 50);
        $createdAfter = $request->get('createdAfter');
        $territoryCode = $request->get('territoryCode');
        $docAvailability = $request->get('docAvailability');

        $resourceClass = $this->getApiResourceClassOrFail();
        $model = $this->getModelQuery();

        $orders = $model
            ->where('to_export = 1')
            ->when($createdAfter, function($select, $createdAfter) {
                $date = \Lv7_Service_Datetime::AtomToZendDate($createdAfter);
                $select->where('created > ?', \Lv7_Service_Datetime::ZendDateToClearAtom($date));
            })
            ->when($territoryCode, function($select, $territoryCode) {
                $territory = Territories::where('code = ?', $territoryCode)->first();
                if (!$territory) {
                    throw new Exception('Unknown territory code [' . $territoryCode . ']');
                }
                $select->where('territory_id = ?', $territory->id);
            })
            ->when($docAvailability != null, function($select, $res) use ($docAvailability) {
                $docAvailability
                    ? $select->where('doc_id IS NOT NULL')
                    : $select->where('doc_id IS NULL');
            })
            ->limitPage($page, $perPage)
            ->order('created DESC')
            ->get();

        return $orders ? $resourceClass::collection($orders, $this->_apiResourceScopes) : [];
    }
}
