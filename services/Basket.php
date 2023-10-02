<?php

class Shop_Service_Basket
{

	protected $_ver = '1.0';
	protected $_namespace;


	/**
	 * Singleton instance
	 *
	 * @var Shop_Service_Basket
	 */
	protected static $_instance = null;

	/**
	 * Singleton instance
	 *
	 * @return Shop_Service_Basket
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function createItem()
	{
		return new Shop_Model_BasketItem();
	}

	public function isEmpty()
	{
		return count($this->getItems()) == 0;
	}

	public function getTotalQuantity()
	{
		$result = 0;
		$items = $this->getItems();
		foreach ($items as $item) {
			$result += $item->getQuantity();
		}
		return $result;
	}

	public function change($uid, $quantity, $toCashbox = null)
	{
		$result = 'unknown';

		if ($quantity) {
			if ($item = $this->_getFromStorage($uid)) {
				$result = 'update';
			} else if ($item = $this->_getFromTempStorage($uid)) {
				$result = 'add';
			}
			if ($item) {
				$item->setQuantity($quantity);
				if (!is_null($toCashbox)) {
					$item->setToCashbox($toCashbox);
				}
				$this->_addToStorage($item);
			}
		} else {
			$this->delete($uid);
			$result = 'delete';
		}
		return $result;
	}

	public function delete($uid)
	{
		if ($item = $this->getItem($uid)) {
			$this->_addToTempStorage($item);
		}
		$this->_deleteFromStorage($uid);
	}

	public function getItems()
	{
		return $this->_namespace->items;
	}

	public function getItem($uid)
	{
		return $this->_namespace->items[$uid];
	}

	public function getItemFromTempStorage($uid)
	{
		return $this->_namespace->tempItems[$uid];
	}


	public function getItemById($type, $id)
	{
		$item = $this->createItem();
		$uid = $item->setType($type)->setId($id)->getUid();
		return $this->getItem($uid);
	}

	public function clear()
	{
		$this->_clearStorage();
		$this->_clearTempStorage();
	}

	public function debug()
	{
		foreach ($this->_namespace as $name => $value) {
			echo $name . '<br>';
			echo '<pre>';
			print_r($value);
			echo '</pre>';
		}
	}

	public function clearTempStorage()
	{
		$this->_clearTempStorage();
	}

	public function addToTempStorage(Shop_Model_BasketItem $item)
	{
		$this->_addToTempStorage($item);
		return $item->getUID();
	}

	public function add(Shop_Model_BasketItem $item)
	{
		$this->_addToStorage($item);
	}


	protected function _addToStorage(Shop_Model_BasketItem $item)
	{
		$this->_namespace->items[$item->getUid()] = $item;
	}

	protected function _getFromStorage($uid)
	{
		return $this->_namespace->items[$uid];
	}

	protected function _deleteFromStorage($uid)
	{
		if (isset($this->_namespace->items[$uid])) {
			unset($this->_namespace->items[$uid]);
		}
	}

	protected function _clearStorage()
	{
		$this->_namespace->items = array();
	}


	protected function _addToTempStorage(Shop_Model_BasketItem $item)
	{
		$this->_namespace->tempItems[$item->getUid()] = $item;
	}

	protected function _getFromTempStorage($uid)
	{
		return $this->_namespace->tempItems[$uid];
	}

	protected function _clearTempStorage()
	{
		$this->_namespace->tempItems = array();
	}


	protected function _getLastUid()
	{
		return $this->_namespace->lastUid;
	}

	protected function _setLastUid($uid)
	{
		$this->_namespace->lastUid = $uid;
	}

	protected function __construct()
	{
		$this->_namespace = new Zend_Session_Namespace('shop_basket_' . $this->_ver);
		$this->_namespace->setExpirationSeconds(600 * 300 * 100);
		if ($_SESSION['__ZF']['shop_basket_' . $this->_ver]['ENT'] - time() < 3000) {
             $this->_namespace->setExpirationSeconds(600 * 300 * 100);
         }
		if (!is_array($this->_namespace->items)) {
			$this->_namespace->items = array();
		}

		if (!is_array($this->_namespace->tempItems)) {
			$this->_namespace->tempItems = array();
		}

		//unset($_SESSION['SHOP_BASKET_V2']);
	}


}