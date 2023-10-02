<?php

use Modules\Company\Models\Mappers\Companies;
use Modules\Company\Models\Mappers\CompanyAccounts;
use Modules\Company\Models\Mappers\CompanyAddresses;
use Modules\Company\Models\Mappers\Employees;
use Modules\Company\Models\Mappers\Territories;
use Modules\Company\Services\Facade as CompanyFacade;

class Shop_Model_Order extends Lv7_Entity_Model
{
    protected $_sberbankPaymentInfo = null;
    protected $_hallManager = null;
    protected $_hallManagerTerritory = null;
    protected $_responsibleManagerTerritory = null;

    public function territory()
    {
        return $this->_belongsTo(Territories::class, 'territory_id', ['name' => '---']);
    }
	
    public function company()
    {
        return $this->_belongsTo(Companies::class, 'company_id', ['name' => '---']);
    }

    public function companyAccount()
    {
        return $this->_belongsTo(CompanyAccounts::class, 'company_account_id');
    }

    public function companyAddress()
    {
        return $this->_belongsTo(CompanyAddresses::class, 'company_address_id');
    }

    public function positions()
    {
        return $this->_hasMany(Shop_Model_Mapper_OrderPos::class, 'order');
    }

    public function faceType()
    {
        return Shop_Model_Mapper_FaceTypes::findOrDefault($this->params->faceType,
            new Lv7_Entity(['id' => 0, 'name' => '---']));
    }

    public function type()
    {
        return (new Shop_Model_Mapper_OrderTypes())->find($this->type);
    }

    public function status()
    {
        return (new Shop_Model_Mapper_OrderStatus())->find($this->status);
    }

    public function site()
    {
        return Lv7CMS::getInstance()->getSites()[$this->site] ?? null;
    }

    public function docType()
    {
        return $this->_belongsTo(Shop_Model_Mapper_OrderDocTypes::class, 'doc_type', ['id' => 0, 'name' => '---']);
    }

    public function quantity()
    {
        return array_reduce($this->positions(), function($q, $pos) {
            return $q + $pos->quantity;
        }, 0);
    }

    public function amount()
    {
        return round($this->params->totalCost);
    }

    public function paymentBySberbank()
    {
        return strpos($this->params->paymentMethod, 'Sberbank') !== false;
    }

    public function sberbankPaymentInfo()
    {
        if ($this->paymentBySberbank() && ($this->_sberbankPaymentInfo == null)) {
            $extObjFacade = new ExternalObjects_Service_Facade();
            $financeServiceFacade = new Finance_Service_Facade();
            $extObj = $extObjFacade->findByType('shop_order', $this->id);
            if ($extObj) {
                $pi = $financeServiceFacade->findPaymentInstructionByExtObj($extObj->id);
                if ($pi) {
                    $this->_sberbankPaymentInfo = Finance_Service_Facade::getPaymentInfo($pi->id, $this->id);
                }
            }
        }
        return $this->_sberbankPaymentInfo;
    }

    public function hallManager()
    {
        if ($this->_hallManager == null) {
            if ($this->param->barcode) {
                $this->_hallManager = $this->hallManagerTerritory()
                    ? $this->hallManagerTerritory()->employee
                    : null;
            }
            if ($this->_hallManager == null) {
                $this->_hallManager = new Lv7_Entity(['id' => 0, 'name' => '---']);
            }
        }
        return $this->_hallManager;
    }

    public function hallManagerTerritory()
    {
        if ($this->param->barcode && ($this->_hallManagerTerritory == null)) {
            $this->_hallManagerTerritory = CompanyFacade::findEmployeeTerritoryByBarcode($this->params->barcode);
        }
        return $this->_hallManagerTerritory;
    }

    public function responsibleManager()
    {
        return $this->_belongsTo(Employees::class, 'responsible_id', ['name' => '---']);
    }

    public function responsibleManagerTerritory()
    {
        if ($this->responsible_id && ($this->_responsibleManagerTerritory == null)) {
            $this->_responsibleManagerTerritory = CompanyFacade::findEmployeeTerritory($this->responsible_id, $this->territory_id);
        }
        return $this->_responsibleManagerTerritory;
    }
    public function exportAllowed() // Кирилл: валидатор - разрешаем ли экспорт в LF (NEW LF BUTTON)
    {
        return $this->territory_id && $this->responsible_id;
    }
    public function clientContractOfSaleAllowed() // Кирилл: валидатор - разрешаем ли экспорт в LF (NEW LF BUTTON) при наличии необходимых данных
    {
        return Shop_Service_Docs::clientContractOfSaleAllowed($this);
    }

}