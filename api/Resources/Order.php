<?php
namespace Modules\Shop\Api\Resources;

use Modules\Api\Services\ApiResource;
use Modules\Company\Api\Resources\Employee;
use Modules\Company\Api\Resources\EmployeeTerritory;

class Order extends ApiResource
{
    protected $scopes = ['positions'];
    protected $hiddenScopes = ['xml'];

    public function mapCommon(): array
    {
        return [
            'id' => (int) $this->id,
            'number' => $this->number,

            'site' => $this->site,
            'siteName' => $this->site()->name,

            'type' => $this->type,
            'typeName' => $this->type()->name,

            'status' => $this->status,
            'statusName' => $this->status()->name,

            'clientFaceTypeId' => $this->faceType()->id,
            'clientFaceTypeName' => $this->faceType()->name,

            'clientEmail' => $this->params->email,
            'clientName' => $this->params->contactFace,
            'clientPassport' => $this->params->passport,
            'clientRegistrationAddress' => $this->params->address_register,
            'clientPhone' => $this->params->phone,

            'paymentMethod' => $this->params->paymentMethod,
            'paymentMethodName' => $this->params->paymentMethodName,

            'delivery' => $this->params->delivery,
            'deliveryName' => $this->params->deliveryName,
            'deliveryAddress' => $this->params->address,
            'deliveryCost' => $this->params->deliveryCost,

            'comment' => $this->params->comment,
            'discountCard' => $this->params->discountCard,

            'docTypeId' => $this->docType()->id,
            'docTypeName' => $this->docType()->name,
            'docId' => $this->doc_id,
            'docNumber' => $this->doc_number,
            'docBarcode' => $this->doc_barcode,
            'docDate' => $this->doc_date,

            'territoryId' => $this->territory_id,
            'territoryCode' => $this->territory()->code,
            'territoryName' => $this->territory()->name,

            'companyId' => $this->company_id,
            'companyName' => $this->company()->name,

            'quantity' => $this->quantity(),
            'amount' => $this->amount(),

            'prepaidAmount' => $this->params->prepaid,

            'vinNumber' => $this->params->vinNumber,

            'sberbankStatus' => $this->sberbankPaymentInfo() ? $this->sberbankPaymentInfo()->OrderStatusName : '',
            'sberbankDepositAmount' => $this->sberbankPaymentInfo() ? $this->sberbankPaymentInfo()->depositAmount / 100 : '',

            'hallManager' => $this->hallManager()
                ? new Employee($this->hallManager(), ['common'])
                : null,
            'hallManagerTerritory' => $this->hallManagerTerritory()
                ? new EmployeeTerritory($this->hallManagerTerritory(), ['common'])
                : null,

            'responsibleManager' => $this->responsibleManager()
                ? new Employee($this->responsibleManager(), ['common'])
                : null,
            'responsibleManagerTerritory' => $this->responsibleManagerTerritory()
                ? new EmployeeTerritory($this->responsibleManagerTerritory(), ['common'])
                : null,
            'exportAllowed' => $this->exportAllowed(),
            'clientContractOfSaleAllowed' => $this->clientContractOfSaleAllowed(),

        ];
    }

    public function mapPositions(): array
    {
        return [
            'positions' => OrderPos::collection($this->positions()),
        ];
    }

    public function mapXml(): array
    {
        $manager = new \Shop_Service_OrderManager();
        return [
            'xml' => $manager->createXML($this),
        ];
    }



}
