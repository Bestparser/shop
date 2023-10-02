<?php

class Shop_Model_BasketItem
{
	protected $_type = 0;
	protected $_id;
	protected $_code;
	protected $_articul;
	protected $_name;
	protected $_price;
	protected $_imageUrl;
	protected $_previewUrl;

	protected $_itemUrl;

	protected $_quantity;

	protected $_analog;
	protected $_supplier;

	protected $_sale = false;

	protected $_pricePurchase;
	protected $_manBrand;
	protected $_manNumber;
	protected $_minOrder;

	protected $_toCashbox;

	protected $_deliveryTerms;

	protected $_stock;
	protected $_stocks;
	protected $_stockday;

	public function getType()
	{
		return $this->_type;
	}

	public function setType($type)
	{
		$this->_type = $type;
		return $this;
	}

	public function getId()
	{
		return $this->_id;
	}

	public function setId($id)
	{
		$this->_id = $id;
		return $this;
	}

	public function getCode()
	{
		return $this->_code;
	}

	public function setCode($code)
	{
		$this->_code = $code;
		return $this;
	}

	public function getArticul()
	{
		return $this->_articul;
	}

	public function setArticul($articul)
	{
		$this->_articul = $articul;
		return $this;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function setName($name)
	{
		$this->_name = $name;
		return $this;
	}

	public function getPrice()
	{
		return ceil($this->_price);
	}

	public function setPrice($price)
	{
		$this->_price = $price;
		return $this;
	}

	public function getImageUrl()
	{
		return $this->_imageUrl;
	}

	public function setImageUrl($imageUrl)
	{
		$this->_imageUrl = $imageUrl;
		return $this;
	}

	public function getPreviewUrl()
	{
		return $this->_previewUrl;
	}

	public function setPreviewUrl($previewUrl)
	{
		$this->_previewUrl = $previewUrl;
		return $this;
	}

	public function getItemUrl()
	{
		return $this->_itemUrl;
	}

	public function setItemUrl($itemUrl)
	{
		$this->_itemUrl = $itemUrl;
		return $this;
	}

	public function getQuantity()
	{
		return $this->_quantity;
	}

	public function setQuantity($quantity)
	{
		$this->_quantity = $quantity;
		return $this;
	}


	public function getAnalog()
	{
		return $this->_analog;
	}

	public function setAnalog($analog)
	{
		$this->_analog = $analog;
		return $this;
	}


	public function getSupplier()
	{
		return $this->_supplier;
	}

	public function setSupplier($supplier)
	{
		$this->_supplier = $supplier;
		return $this;
	}

	public function getSale()
	{
		return $this->_sale;
	}

	public function setSale($sale)
	{
		$this->_sale = $sale;
		return $this;
	}

	public function getPricePurchase()
	{
		return $this->_pricePurchase;
	}

	public function setPricePurchase($pricePurchase)
	{
		$this->_pricePurchase = $pricePurchase;
		return $this;
	}

	public function getManBrand()
	{
		return $this->_manBrand;
	}

	public function setManBrand($manBrand)
	{
		$this->_manBrand = $manBrand;
		return $this;
	}

	public function getManNumber()
	{
		return $this->_manNumber;
	}

	public function setManNumber($manNumber)
	{
		$this->_manNumber = $manNumber;
		return $this;
	}


	public function getDeliveryTerms()
	{
		return $this->_deliveryTerms;
	}

	public function setDeliveryTerms($deliveryTerms)
	{
		$this->_deliveryTerms = $deliveryTerms;
		return $this;
	}



	public function getToCashbox()
	{
		return $this->_toCashbox;
	}
	public function setToCashbox($value)
	{
		$this->_toCashbox = $value;
		return $this;
	}

	public function getStock()
	{
		return $this->_stock;
	}

	public function setStock($stock)
	{
		$this->_stock = $stock;
		return $this;
	}

	public function getStockday()
	{
		return $this->_stockday;
	}

	public function setStockday($day)
	{
		$this->_stockday = $day;
		return $this;
	}

	public function getStocks()
	{
		return $this->_stocks;
	}

	public function setStocks($quantityStock, $shopId)
	{
		if (empty($quantityStock)) {
			$quantityStock = 0;
		}
		$this->_stocks[$shopId] = $quantityStock;
		return $this;
	}

	public function getMinOrder()
	{
		return $this->_minOrder;
	}

	public function setMinOrder($min_order)
	{
		$this->_minOrder = $min_order;
		return $this;
	}


	public function getUid()
	{
		return md5($this->_type . $this->_id . $this->_articul . $this->_manBrand . $this->_manNumber . $this->_name . $this->_price);
	}


	public function getTotalPrice()
	{
		return $this->getPrice() * $this->getQuantity();
	}

}