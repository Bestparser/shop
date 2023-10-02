<?php

class Shop_BasketController extends Lv7CMS_Controller_Frontend
{

	public function init()
	{
		parent::init();
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('index', 'html')->initContext();
	}

	public function preDispatch()
	{
		parent::preDispatch();
	}

	public function postDispatch()
	{
		parent::postDispatch();
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/modules/shop/main.js?v=2');
	}


	public function indexAction()
	{	
		$catalogService = new CatCommon_Service_Catalog(); // Кирилл: Образ класса для вычисления товара в каталоге
		$siteId = Lv7CMS::getInstance()->getSiteId();				
		$catalog = $catalogService->getCatalogBySite($siteId);
		$unitsMapper = Catalog_Model_Mapper_Manager::getInstance()->units($catalog->id, true);
	
		$basket = Shop_Service_Config::getScheme()->getBasket();		
		$BasketSave = new Shop_Model_Mapper_BasketSave();
		$BasketSave->basketRecovery(); // Кирилл: восстановление кеш-корзины из sql-корзины на странице корзины
		
		if ($this->getRequest()->isPost()) {
			if (is_array($items = $basket->getItems())) {
				foreach ($items as $item) {
					$uid = $item->getUid();					
					$value = $this->_getParam('q_' . $uid, 0);
					$basket->change($uid, $value);			
					
					$data = array(
						'product_count' => $value,
						'updated' => date('Y-m-d H:i:s')
					);
					$where = array(
						'product_id = ?' => $item->getId(),
						'user_uid = ?' => $BasketSave->getUserUid()
					);
					$BasketSave->basketUpdate($data, $where); // Кирилл: Поменять количество товара на странице корзины пользователем в sql-корзине
				}
			}
		} else if ($uid = $this->_getParam('uid', false)) {
			$basket = Shop_Service_Config::getScheme()->getBasket(); // Кирилл: по uid вычисляем id товара, чтобы по нему удалить из sql-корзины
			$item = $basket->getItem($uid);
			
			$data = array(				
				'product_id = ?' => $item->getId(),
				'user_uid = ?' => $BasketSave->getUserUid()
			);
			$BasketSave->basketDelete($data); // Кирилл: удаление пользователем товара sql-корзины вручную по собственному желанию
			$basket->change($uid, intval($this->_getParam('quantity', 0)));
		}
	
		$this->view->tecdoc = 0; 
		foreach ($basket->getItems() as $k){
			$unit = $BasketSave->basketGetTecdoc($k->getId());
			if ($unit['id']) $this->view->tecdoc = 1; // Кирилл: ставим флажок на наличие в корзине хотябы одного товара tecdoc (для того чтобы убрать из синей надписи предупреждения "Яндекс доставка")
		}
		

		$settings = Shop_Service_Config::getScheme()->getSettings();
		$minOrderAmount = $settings['min_order_amount'];
		$this->view->minOrderAmount = $settings['min_order_amount'];

		$calculator = Shop_Service_Config::getScheme()->getCalculator();
		$cost = $calculator->getGoodsCost();
		$this->view->cost = $cost;
		$this->view->quantity = $basket->getTotalQuantity();		
		$this->view->items = $basket->getItems();
		if ($minOrderAmount && ($cost < $minOrderAmount)) {
			$this->view->smallOrderAmount = true;
		}

		$this->view->headTitle('Корзина', 'SET');		
	}


	public function formAction()
	{	
		$disabled = $this->_getParam('disabled', false);
		$this->view->disabled = $disabled;
	}

	public function gridFormAction()
	{
		$disabled = $this->_getParam('disabled', false);
		$this->view->disabled = $disabled;
	}

	public function infoAction()
	{	
		$this->_helper->layout->disableLayout();		
		$basket = Shop_Service_Config::getScheme()->getBasket(); 		
		
		$BasketSave = new Shop_Model_Mapper_BasketSave();
		$BasketSave->basketRecovery(); // Кирилл: восстановление кеш-корзины из sql-корзины в счетчике корзины в правом верхнем углу
		
		$calculator = Shop_Service_Config::getScheme()->getCalculator();		
		$this->view->quantity = $basket->getTotalQuantity();
		$this->view->cost = $calculator->getGoodsCost();
		$this->view->items = $basket->getItems();
		$this->view->user_uid = $BasketSave->getUserUid(); // Кирилл: передаем в счетчик кеш-корзины user_uid		
	}

	public function updateAction()
	{
		//$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout();

		if ($uid = $this->_getParam('uid')) {
			$basket = Shop_Service_Config::getScheme()->getBasket();
			$this->view->mode = $basket->change($uid, intval($this->_getParam('quantity', 0)));
			
			// Кирилл: Когда пользователь кладет товар в корзину, чтобы в sql-корзину тоже сразу клался						
				$BasketSave = new Shop_Model_Mapper_BasketSave(); // образ класса sql-корзины				
				$item = $basket->getItem($uid);
				$data = array(
					'user_uid' => $BasketSave->getUserUid(),
					'site' => Lv7CMS::getInstance()->getSiteId(),
					'product_id' => $item->getId(),
					'product_count' => intval($this->_getParam('quantity', 0))
				);				
				$BasketSave->basketAdd($data, $BasketSave->getUserUid()); // инсерт товар в sql-корзину
			// end Кирилл
		} else {
			echo 'unknown uid';
		}
	}

	public function tableAction()
	{
		$orderId = Zend_Registry::get('tableBasketOrderId');
		if (!$orderId) {
			throw new Lv7CMS_Exception('OrderId not found!');
		}
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$order = $ordersMapper->find($orderId);

		if (!$order) {
			throw new Lv7CMS_Exception('Unknown order! ID=' . $orderId);
		}
		$this->view->order = $order;

		$orderPosMapper = new Shop_Model_Mapper_OrderPos();
		$posList = $orderPosMapper->getList($order->id);
		if ($posList) {
			$distribFacade = Distrib_Service_Facade::getInstance();

			foreach ($posList as $pos) {
				if ($pos->supplier && ($pos->supplier > 1)) {
					$supplier = Distrib_Service_Facade::getInstance()->getSupplier($pos->supplier);
					if ($supplier && $supplier->type) {
						if ($supplier->type == Distrib_Model_Mapper_SupplierTypes::PARTNER_WAREHOUSE) {
							$pos->deliveryDays = $supplier->delivery;
						}
						if (($supplier->type == Distrib_Model_Mapper_SupplierTypes::SUPPLIER) && $pos->analog) {
							$unit = $distribFacade->getUnitBySupplierAndAnalog($pos->supplier, $pos->analog);
							if ($unit) {
								$pos->deliveryDays = $unit->frontDelivery;
							}
						}
					}
				}
			}
		}

		$this->view->posList = $posList;

		$totalQuantity = 0;
		if (is_array($posList)) {
			foreach ($posList as $pos) {
				$totalQuantity += $pos->getClientQuantity();
			}
		}
		$this->view->quantity = $totalQuantity;
		$this->view->discount = $order->params->discount;
		$this->view->cost = $order->params->goodsCost;

		Shop_Service_Config::setSiteId($order->site);
		$scheme = Shop_Service_Config::getScheme();
		$calculator = $scheme->getCalculator();
		$this->view->calculator = $calculator;

		Shop_Service_Config::setSiteId(null);
	}

	public function resetAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		$basket = Shop_Service_Config::getScheme()->getBasket();
		$basket->clear();

		$this->_helper->redirector->gotoUrl('/?downthrow=yes');
	}


}