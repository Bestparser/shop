<?php

class Shop_AcceptController extends Lv7CMS_Controller_Frontend
{


	public function indexAction()
	{
		$scheme = Shop_Service_Config::getScheme();
		$basket = $scheme->getBasket();
		
		
		if ($basket->isEmpty()) {
			$this->_helper->redirector->gotoUrl($scheme->getBasketUrl());
		}
		
		$orderCreator = $scheme->getOrderCreator();


		try {
			$order = $orderCreator->create();

			if (!$order) {
				throw new Lv7CMS_Exception('Order create error!');
			}

			if (class_exists('Finance_Service_Facade') && ((strpos($order->params->paymentMethod, 'Sberbank') !== false) || (strpos($order->params->paymentMethod, 'Bnpl') !== false)) ) {
				$this->_jumpToPaymentProcess($order);
			}

			$orderManager = new Shop_Service_OrderManager();

			if ($order->type == Shop_Model_Mapper_OrderTypes::ORDERHALL && ($order->params->delivery == 'MkadOut' || $order->params->delivery == 'MkadIn')) {
				$orderManager->sendOrderMailToManager($order);
			} else if ($order->type != Shop_Model_Mapper_OrderTypes::ORDERHALL) { // если заказ НЕ из зала - отправляем письмо-уведомление менеджерам
				$orderManager->sendOrderMailToManager($order);
			}
			// отправляем письмо-уведомление клиенту
			$orderManager->sendOrderMailToClient($order);

			// Кирилл: очищаем sql-корзину после заказа
			$BasketSave = new Shop_Model_Mapper_BasketSave(); // Кирилл: образ класса sql-корзины
			$data = array(
				'user_uid = ?' => $BasketSave->getUserUid()
			);
			$BasketSave->basketDelete($data); // Кирилл: после оплаты наличкой - удаляем sql-корзину
			
			Shop_Service_Config::getScheme()->getBasket()->clear();			
			$this->view->successText = Lv7CMS_Resources::getTextTemplates()
				->shopOrderAccept->getText(array('ORDER_NUMBER' => $order->number));

		} catch (Exception $e) {

			$this->view->errorText = Lv7CMS_Resources::getTextTemplates()
				->shopOrderError->getText();

			echo  $e->getMessage() . '<br>' . $e->getTraceAsString();
		}


	}

	public function invoiceAction()
	{
		$orderId = $this->_getParam('orderId');
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$order = $ordersMapper->find($orderId);

		if (!$order || stripos($order->params->paymentMethod, 'Sberbank') === false) {
			throw new Zend_Controller_Dispatcher_Exception('Not found', 404);
		}


		if ($this->getRequest()->isPost() && ($this->_getParam('mode') == 'goto-bank')) {

			$extObjFacade = new ExternalObjects_Service_Facade();
			$extObj = $extObjFacade->findByType('shop_order', $order->id);
			if ($extObj && ($pi = Finance_Service_Facade::findPaymentInstructionByExtObj($extObj->id))) {
				// если по этому заказу уже есть платежная инструкция - увеличиваем кол-во попыток и переходим к оплате
				$pi->attempts = $pi->attempts + 1;
				Finance_Service_Facade::updatePaymentInstruction($pi);
				Finance_Service_Facade::payByPaymentInstruction($pi->id);
			} else {
				//иначе создаем новую инструцию и переходим к оплате
				$this->_jumpToPaymentProcess($order);
			}

		} else {
			$params = array(
				'ORDER_NUMBER' => $order->number,
				'ORDER_COST' => Lv7_Service_Text::asPrice($order->params->totalCost, 0) . 'р.'
			);
			$this->view->intro = $this->_textTemplates->shopInvoiceIntro->getText($params);
			//$this->view->text = $this->_textTemplates->shopInvoiceText->getText();
		}
	}

	protected function _jumpToPaymentProcess($order)
	{
		$extObjId = ExternalObjects_Service_Facade::add('shop_order', $order->id);
		$defaultCurrency = Shop_Service_Config::getDefaultCurrencyId();
		$paymentMethodId = $order->params->paymentMethod;

		$scheme = Shop_Service_Config::getScheme();

		$checkoutData = $scheme->getCheckoutData();
		$backUrl = $checkoutData->backUrl;
		$categoryId = 0; // оплата заказов
		if ($this->_user->isGuest()) {
			Finance_Service_Facade::unauthorizedPay($order->params->contactFace, $order->params->email,
				$paymentMethodId, $order->params->totalCost, $defaultCurrency, $extObjId, $backUrl, $categoryId, $order->params->phone);
		} else {
			Finance_Service_Facade::authorizedPay($this->_user->id,
				$paymentMethodId, $order->params->totalCost, $defaultCurrency, $extObjId, $backUrl, $categoryId, $order->params->phone);
		}

	}

}