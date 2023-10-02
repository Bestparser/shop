<?php
namespace Modules\Shop\Api\Resources;

use Modules\Api\Services\ApiResource;

class OrderPos extends ApiResource
{

    public function mapCommon(): array
    {
        return [
            'id' => (int) $this->id,
            'orderId' => $this->order,

            'type' => $this->type,
            'typeName' => $this->type()->name,

            'code' => $this->hasWarehouseCode() ? $this->getWarehouseCode() : '',
            'articul' => $this->articul,
            'name' => $this->getClientName(),

            'manBrand' => $this->manBrand(),
            'manNumber' => $this->manNumber(),

            'mult' => $this->mult,
            'expectedMultiplicity' => $this->expectedMultiplicity(),

            'clientPrice' => $this->getClientPrice(),
            'discount' => $this->discount(),
            'sellingPrice' => $this->sellingPrice(),


            'supplierId' => $this->supplier,
            'supplierName' => $this->supplier()->name,
            'supplierCodeMB' => $this->supplier()->lf_code,
            'supplierDeliveryDays' => $this->supplier()->delivery,
            'purchasePrice' => $this->purchasePrice(),
            'finalDeliveryDays' => $this->finalDeliveryDays(),

            'status' => $this->status,
            'statusName' => $this->status()->name,

        ];
    }



}
