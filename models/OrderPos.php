<?php

class Shop_Model_OrderPos extends Lv7_Entity_Model
{
    private $_notFound = -1;
    private $_purchasePrice = null;
    private $_discount = null;
    private $_sellingPrice = null;
    private $_multiplicityInfo = null;

    protected $_code = null;

    /**
     * @var AutoParts_Model_Article
     */
    protected $_article = null;
	
    /**
     * @var Catalog_Model_Unit
     */
    protected $_catalogUnit = null;

    /**
     * @var Products_Model_Product
     */
    protected $_product = null;

	public function getClientPrice()
	{
		return $this->price * ($this->mult ?: 1);
	}
	
	public function getClientQuantity()
	{
		$mult = $this->mult ? : 1;
		return $this->quantity / $mult;
	}
	
	public function getClientName()
	{
		return $this->name . (($this->mult > 1) ? ' (к-т ' . $this->mult . 'шт)' : '');
	}

    public function getArticle()
    {
        if ($this->_article == null) {
            $this->_article = $this->_notFound;
            $articleId = 0;
            if ($this->analog) {
                $articleId = $this->analog;
            } else if ($this->hasProduct()) {
                $articleId = $this->getProduct()->analog_id;
            }
            if ($articleId) {
                $this->_article = (new AutoParts_Model_Mapper_Article())->find($articleId) ?: $this->_notFound;
            }
        }
        return $this->_article;
    }

    public function hasArticle()
    {
        return $this->getArticle() != $this->_notFound;
    }

    public function getProduct()
    {
        if ($this->_product == null) {
            $product = null;
            if ($this->analog) {
                $product = (new Products_Model_Mapper_Products())
                    ->where('analog_id = ?', $this->getArticle()->id)
                    ->first();
            } else if ($this->code) {
                $product = (new Products_Model_Mapper_Products())
                    ->where('id = ?', intval($this->code))
                    ->first();
            }
            $this->_product = $product ?: $this->_notFound;
        }
        return $this->_product;
    }

    public function hasProduct()
    {
        return $this->getProduct() != $this->_notFound;
    }

    public function isItemFromSuppliers()
    {
        return $this->type == Shop_Model_Mapper_OrderPosTypes::SUPPLIERS;
    }

    public function getWarehouseCode()
    {
        if ($this->_code == null) {
            if ($this->code) {
                $this->_code = $this->code;
            } else if ($this->hasProduct()) {
                $this->_code = sprintf("%06d", $this->getProduct()->id);
            } else {
                $this->_code = 'not found';
            }
        }
        return $this->_code;
    }
    public function hasWarehouseCode()
    {
        return $this->getWarehouseCode() != 'not found';
    }

    public function getArticleNumber()
    {
        return $this->hasArticle() ? $this->getArticle()->number : 'not found';
    }
    public function getArticleName()
    {
        return $this->hasArticle() ? $this->getArticle()->name : 'not found';
    }
    public function getArticleBrandName()
    {
        return $this->hasArticle() ? $this->getArticle()->brand()->name : 'not found';
    }

    public function getProductArticul()
    {
        return $this->hasProduct() ? $this->getProduct()->artikul : 'not found';
    }

    public function order()
    {
        return $this->_belongsTo(Shop_Model_Mapper_Orders::class, 'order');
    }

    public function supplier()
    {
        if (!$this->supplier) {
            return new Lv7_Entity(['id' => 0, 'name' => '---']);
        } else if ($this->supplier == 1) {
            return new Lv7_Entity(['id' => 1, 'name' => 'LF']);
        }
        return $this->_belongsTo(Distrib_Model_Mapper_Suppliers::class, 'supplier', ['id' => 0, 'name' => '---']);
    }

    public function type()
    {
        return (new Shop_Model_Mapper_OrderPosTypes())->find($this->type);
    }

    public function status()
    {
        return (new Shop_Model_Mapper_OrderPosStatus())->find($this->status);
    }


    public function manBrand()
    {
        return $this->man_brand ?: $this->getArticleBrandName();
    }

    public function manNumber()
    {
        return $this->man_number ?: $this->getArticleNumber();
    }

    public function purchasePrice(): float
    {
        if ($this->_purchasePrice == null) {
            if ($this->price_purchase) {
                $this->_purchasePrice = $this->price_purchase;
            } else {
                $supplier = $this->supplier();
                if ($supplier->type == Distrib_Model_Mapper_SupplierTypes::SUPPLIER) {
                    $unit = (new Distrib_Service_Facade())->getUnitBySupplierAndAnalog($this->supplier, $this->analog);
                    $this->_purchasePrice = $unit->price;
                }
                if (($supplier->type == Distrib_Model_Mapper_SupplierTypes::PARTNER_WAREHOUSE) && $this->hasProduct()) {
                    $pricePurchase = Products_Service_Facade::getInstance()->findProductPrice($this->getProduct()->id, 0);
                    if ($pricePurchase) {
                        $this->_purchasePrice = $pricePurchase->price;
                    }
                }

                if ($this->_purchasePrice == null) {
                    $this->_purchasePrice = 0;
                }
            }
        }
        return $this->_purchasePrice;
    }

    public function discount()
    {
        if ($this->_discount == null) {
            $calculator = Shop_Service_Config::getScheme($this->order()->site)->getCalculator();
            $orderDiscount = $this->order()->params->discount;
            $this->_discount = $orderDiscount ? $calculator->itemDiscount($orderDiscount, $this->name, $this->sale) : 0;
        }
        return $this->_discount;
    }

    public function sellingPrice()
    {
        if ($this->_sellingPrice == null) {
            $this->_sellingPrice = floor(max(1, $this->getClientPrice() * (100 - $this->discount()) / 100));
        }
        return $this->_sellingPrice;
    }

    /**
     * @return false|Shop_Model_ExpectedMultiplicity
     */
    public function expectedMultiplicity()
    {
        if ($this->_multiplicityInfo == null) {
            if (($this->mult == 1) && $this->supplier && ($this->type != Shop_Model_Mapper_OrderPosTypes::WAREHOUSE)) {
                $multiplicityInfo = (new Shop_Service_Multiplicity())->check($this->name);
                if ($multiplicityInfo) {
                    $this->_multiplicityInfo = new Shop_Model_ExpectedMultiplicity($multiplicityInfo);
                }
            }
            if ($this->_multiplicityInfo == null) {
                $this->_multiplicityInfo = false;
            }
        }
        return $this->_multiplicityInfo;
    }

    /**
     * Если товар от поставщика, и этот поставщик НЕ работает с территорией заказа,
     * тогда добавляем ко времени доставки один день
     *
     * @return int
     */
    public function finalDeliveryDays()
    {
        $supplier = $this->supplier();
        if ($supplier->id <= 1) {
            return 0;
        }
        $deliveryDays = $supplier->delivery;
        $order = $this->order();
        $supplierTerritoryIds = array_map(function($s) { return $s->id; }, (array) $supplier->territories());
        if (!in_array($order->territory_id, $supplierTerritoryIds)) {
            $deliveryDays += 1;
        }
        return $deliveryDays;
    }



}