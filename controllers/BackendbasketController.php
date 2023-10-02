<?php

class Shop_BackendbasketController extends Lv7CMS_Controller_Backend
{

	public function init()
	{
		parent::init();
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('index', 'html')->initContext();
		$ajaxContext->addActionContext('info', 'html')->initContext();

		$this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
    	if ($this->_flashMessenger->hasMessages()) {
    		$this->view->flashMessages = $this->_flashMessenger->getMessages();
    	}
	}

	public function preDispatch()
	{
		parent::preDispatch();

	}

	public function postDispatch()
	{
		parent::postDispatch();
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/modules/shop/main.js?33');
	}


	public function indexAction()
	{
		$basket = Shop_Service_Config::getBackendScheme()->getBasket();
		if ($this->getRequest()->isPost()) {
			if (is_array($items = $basket->getItems())) {
				foreach ($items as $item) {
					$uid = $item->getUid();
					//$maxQtyItem = $item->getStock() ? $item->getStock() : '';
					//$itemName = $item->getName();
					$quantity = $this->_getParam('q_' . $uid, 0);
					$toCashbox = $this->_getParam('cashbox_' . $uid, false);
					$basket->change($uid, $quantity, $toCashbox);
				}
			}
		} else if ($uid = $this->_getParam('uid', false)) {
			$basket->change($uid, intval($this->_getParam('quantity', 0)));
		}

		$calculator = Shop_Service_Config::getBackendScheme()->getCalculator();
		$this->view->quantity = $basket->getTotalQuantity();
		$this->view->cost = $calculator->getGoodsCost();
		$items = $basket->getItems();

		// определяем возможность для текущего менеджера отправить тот или иной товар на кассу
		$distribFacade = Distrib_Service_Facade::getInstance();
		$userDistribProfile = $distribFacade->getCurrentProfile();
		if ($userDistribProfile && $userDistribProfile->shop) {
			$catalogUnitIds = array();
			foreach ($items as $item) {
				if (!$item->getSupplier()) {
					$catalogUnitIds[] = $item->getId();
				}

			}
			if (count($catalogUnitIds)) {
				$catalogFacade = CatCommon_Service_Facade::getInstance();
				$catalogId = 6; // каталог ПЖ;
				$availability = $catalogFacade->getAvailability($catalogUnitIds, $catalogId);
				foreach ($items as $item) {
					if (!$item->getSupplier()) {
						$item->canBeSentToCashbox = isset($availability[$item->getId()][$userDistribProfile->shop]);
					}
				}
			}
		} else {
			foreach ($items as $item) {
				if (!$item->getSupplier()) {
					$item->canBeSentToCashbox = true;
				}
			}
		}
		$this->view->items = $items;

		$this->view->headTitle('Корзина', 'SET');
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/clipboard.min.js');
	}


	public function formAction()
	{

	}

	public function createorderAction()
	{
		$orderCreatorService = Shop_Service_Config::getBackendScheme()->getOrderCreator();
		$order = $orderCreatorService->create();
		$currentProfile = Distrib_Service_Facade::getInstance()->getCurrentProfile();
		$orderManager = new Shop_Service_OrderManager();

		$order->type = Shop_Model_Mapper_OrderTypes::INNER;
		$order->site = 'planetiron';
		$order->manager = $this->_user->id;
		$faceType = $this->_getParam('faceType') ? $this->_getParam('faceType') : 'natural';
		$paymentMethodParam = $this->_getParam('paymentMethod');

		if (empty($order->params->delivery)) {
			$delivery = Shop_Model_DeliveryFactory::create('SelfDelivery2');
			$order->params->delivery = 'SelfDelivery2';
			$order->params->deliveryName = $delivery->getName();
		}
		if ($paymentMethodParam) {
			$paymentMethod = Shop_Model_PaymentMethodFactory::create($paymentMethodParam);
			$order->params->paymentMethod =  $paymentMethod->getId();
			$order->params->paymentMethodName = $paymentMethod->getName();
		}
		$order->params->faceType = $faceType;
		$order->params->prepaid = round($order->params->totalCost / 2);
		$order->params->prepaidManualSet = false;
		$order->shop = $currentProfile ? $currentProfile->shop : 0;
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$ordersMapper->update($order);

		// Создаем файл с заказом если заказ от Юр. лица и с безналичной оплатой
        if ($order->params->faceType == 'legal' && $order->params->paymentMethod == 'NonCash') {
           	$sendOrder = $orderManager->PostProcessing($order);
     	}

		$basket = Shop_Service_Config::getBackendScheme()->getBasket();
		$basket->clear();

		$this->_helper->redirector->gotoRouteAndExit(array('id' => $order->id), 'shop::adminOrder');
	}

	public function infoAction()
	{

		$basket = Shop_Service_Config::getBackendScheme()->getBasket();
		$calculator = Shop_Service_Config::getBackendScheme()->getCalculator();
		$this->view->quantity = $basket->getTotalQuantity();
		$this->view->cost = $calculator->getGoodsCost();
	}

	public function itemsAction()
	{

	}

	public function updateAction()
	{
		//$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout();

		if ($uid = $this->_getParam('uid')) {
			$basket = Shop_Service_Config::getBackendScheme()->getBasket();
			$this->view->mode = $basket->change($uid, intval($this->_getParam('quantity', 0)));
		} else {
			echo 'unknown uid';
		}
	}

	public function xmlAction()
	{

		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout();

		Zend_Registry::set('silentMode', true);

		$basket = Shop_Service_Config::getBackendScheme()->getBasket();
		$items = $basket->getItems();

		$xml = '';

		$suppliers = Distrib_Service_Facade::getInstance()->getSuppliers();

		$xml = "<zakaz>\r\n";
		$xml .= " <mail></mail>\r\n";
		$xml .= " <number>Заказ №</number>\r\n";
		$xml .= " <date>" . date("d-m-Y H:i") . "</date>\r\n";
		$xml .= " <faceType></faceType>\r\n";
		$xml .= " <fio></fio>\r\n";
		$xml .= " <passport></passport>\r\n";
		$xml .= " <phone></phone>\r\n";
		$xml .= " <address></address>\r\n";
		$xml .= " <paymentMethodName></paymentMethodName>\r\n";
		$xml .= " <deliveryName></deliveryName>\r\n";
		$xml .= " <deliveryCost></deliveryCost>\r\n";
		$xml .= " <comment></comment>\r\n";
		$xml .= " <discountCode></discountCode>\r\n";
		$xml .= " <year>".date("Y")."</year>\r\n";
		$xml .= " <order>\r\n";

		$posStatus = new Shop_Model_Mapper_OrderPosStatus();
		$quantity = 0;
		$total = 0;
		if (is_array($items)) {
			foreach ($items as $item) {
				if ($item->getToCashbox()) {
					continue;
				}
				$supplierId = $item->getSupplier();

				$xml .= "  <orderposition>\r\n";
				$xml .= "   <code>" . $item->getCode() . "</code>\r\n";
				$xml .= "   <name>" . $item->getName() ."</name>\r\n";
				$xml .= "   <art>" . $item->getArticul() . "</art>\r\n";
				$xml .= "   <brand>" . $item->getManBrand() . "</brand>\r\n";
				$xml .= "   <number>" . $item->getManNumber() . "</number>\r\n";
				$xml .= "   <price>" . $item->getPrice() ."</price>\r\n";
				$xml .= "   <count>" . $item->getQuantity() . "</count>\r\n";
				if ($supplier = $suppliers[$supplierId]) {
					$xml .= "   <supplier>" . $supplier->lf_code . "</supplier>\r\n";
					$xml .= "   <extra_days>" . $supplier->delivery . "</extra_days>\r\n";
				}
				$xml .= "   <status>" . Shop_Model_Mapper_OrderPosStatus::UNORDERED . "</status>\r\n";
				$xml .= "   <statusname>" . $posStatus->find(Shop_Model_Mapper_OrderPosStatus::UNORDERED)->name . "</statusname>\r\n";
				//$xml .= "   <type>".$item['type']."</type>\r\n";
				$xml .= "  </orderposition>\r\n";
				$quantity += $item->getQuantity();
				$total += $item->getPrice() * $item->getQuantity();
			}
		}
		$xml .= " </order>\r\n";
		$xml .= " <totalpos>" . $quantity . "</totalpos>\r\n";
		$xml .= " <totalprice>" . round($total) . "</totalprice>\r\n";
		$xml .= "</zakaz>";


		$this->getResponse()->setHeader('Content-type', 'application/xml');
		$this->getResponse()->setHeader('Content-Disposition', 'attachment;filename="order-' . $order->number . '.xml"');
		$this->getResponse()->setHeader('Cache-Control', 'max-age=0');

		echo $xml;


	}

	public function clearAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		$basket = Shop_Service_Config::getBackendScheme()->getBasket();
		$basket->clear();

		$this->_helper->redirector->gotoRouteAndExit(array(), 'distrib::crossIndex');
	}


}