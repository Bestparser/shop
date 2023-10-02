<?php
namespace Modules\Shop\Api\Methods\Orders;

use Modules\Api\Exceptions\Exception as ApiException;
use Modules\Api\Services\ApiMethod;
use Modules\Company\Models\Mappers\CompanyLf;

class Update extends ApiMethod
{
    protected $_aclResource = ['shopOrders'];
    protected $_apiResourceClass = \Modules\Shop\Api\Resources\Order::class;
    protected $_modelClass = \Shop_Model_Mapper_Orders::class;

    protected $_validate = [
        '*' => [
            'id' => 'required|int',
            'status' => 'nullable|int',
            'docId' => 'nullable|max:32',
            'docNumber' => 'nullable|max:32',
            'docBarcode' => 'nullable|max:32',
            'docDate' => 'nullable|date:Y-m-d',
            'companyCodeMB' => 'nullable|max:10',
        ]

    ];

    public function dispatch(\Lv7CMS_Controller_Request $request)
    {
        $data = $request->all();
        $result = [];

        $model = $this->getModelQuery();
        foreach ($data as $row) {

            $modified = false;
            $order = $model->findOrFail($row['id']);

            $fillable = [
                'status' => 'status',
                'doc_id' => 'docId',
                'doc_number' => 'docNumber',
                'doc_barcode' => 'docBarcode',
                'doc_date' => 'docDate',
            ];

            foreach ($fillable as $dbField => $dataField) {
                if (isset($row[$dataField]) && $order->{$dbField} != $row[$dataField]) {
                    $order->{$dbField} = $row[$dataField];
                    $modified = true;
                }
            }

            if ($row['companyCodeMB']) {
                $companyLf = CompanyLf
                    ::where('territory_id = ?', $order->territory_id)
                    ->where('code_mb LIKE ?', $row['companyCodeMB'])
                    ->first();
                if (!$companyLf) {
                    throw new ApiException(sprintf(
                        'Unknown company! (code_mb=%s, territory_id=%s)',
                        $row['companyCodeMB'],
                        $order->territory_id
                    ));
                }
                $order->company_id = $companyLf->company_id;
                $order->company_account_id = $companyLf->company_account_id;
                $order->company_address_id = $companyLf->company_address_id;
                $order->company_phone = $companyLf->phone;
            }

            if ($modified) {
                $order = $model->update($order);
            }

            $result[] = [
                'id' => $order->id,
                'docId' => $order->doc_id,
                'modified' => $modified,
            ];
        }

        return $result;
    }
}
