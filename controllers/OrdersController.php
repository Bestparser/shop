<?php

use Modules\Company\Models\Mappers\Territories;
use Modules\Company\Services\Facade as CompanyFacade;

class Shop_OrdersController extends Lv7CMS_Controller_Backend
{

	protected $_filterFields = [
		'fDelivery',
		'fPaymentMethod',
		'fDate',
		'fDateAfter',
		'fDateBefore',
		'fSite',
		//'fShop',
		'fType',
		'fManager',
		'fStatus',
		//'fPosStatus',
		'fDocType',
		'fPaymentStatus',
        'fTerritory',
    ];

	protected $_filter = null;

	protected $_managerRoles = [
        'foreignCarsDirector',
        'foreignCarsManager',
        'orderManager',
        'foreignCarsManagerReq',
    ];

    public function init()
    {
    	parent::init();

    	$this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
    	if ($this->_flashMessenger->hasMessages()) {
    		$this->view->flashMessages = $this->_flashMessenger->getMessages();
    	}
    }


	public function preDispatch()
	{
		parent::preDispatch();
		if (!$this->_acl->access('shopOrders')) {
			return;
		}

		Lv7CMS_Service_PreAction::checkMemCache();

		// фильтр
		$this->_filter = new Zend_Session_Namespace(get_class($this));

        foreach ($this->_filterFields as $fFiled) {
            $this->_filter->{$fFiled} = $this->_filter->{$fFiled} ?? $this->_getFilterDefault($fFiled);
            $this->view->{$fFiled} = $this->_filter->{$fFiled};
        }

	}

	public function postDispatch()
	{
		parent::postDispatch();
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('form', 'html')->initContext();

		$this->view->headLink()->appendStylesheet($this->_dataUrl . '/css/bootstrap-multiselect.min.css?1');
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/jquery.form.js');
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/jquery.cookie.js');
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/bootstrap-multiselect.min.js');
		$this->view->headScript()->appendFile($this->_dataUrl . '/js/modules/shop/orders.js?7');

	}

	public function indexAction()
	{
		$scheme = Shop_Service_Scheme_Factory::getSchemeInstance('Default');

		$deliveries = $scheme->getDeliveries();
		$deliveryOptions = []; //array('0' => $this->view->translate('Все'));
		foreach ($deliveries as $delivery) {
			$deliveryOptions[$delivery->getId()] = strip_tags($delivery->getName());
		}
		$this->view->deliveryOptions = $deliveryOptions;

		$paymentMethods = $scheme->getPaymentMethods();
		$paymentMethodOptions = []; //array('0' => $this->view->translate('Все'));
		foreach ($paymentMethods as $paymentMethod) {
			$paymentMethodOptions[$paymentMethod->getId()] = $paymentMethod->getName();
		}
		$this->view->paymentMethodOptions = $paymentMethodOptions;

		$siteOptions = array_merge(
			//array('0' => $this->view->translate('Все')),
			Lv7_Service_Tools::createOptionsList($this->_getAccessibleSite(), 'id', 'label'));
		$this->view->sites = $siteOptions;

		$typesMapper = new Shop_Model_Mapper_OrderTypes();
		$orderTypes = $typesMapper->getList();
		$typesOptions = []; //array('0' => $this->view->translate('Все'));
		foreach ($orderTypes as $type) {
			$typesOptions[$type->id] = $type->name . ' ( ' . $type->shortName . ' )';
		}
		$this->view->typeOptions = $typesOptions;

		$shopsMapper = new CatCommon_Model_Mapper_Shops();
		$shopsList = $shopsMapper->getList();
		$shops = []; //array('0' => $this->view->translate('Все'));
		foreach ($shopsList as $shop) {
			$shops[$shop->id] = $shop->short_name;
		}
		$this->view->shopsAll = $shops;

		$managers = Users_Service_Facade::getInstance()->getList($this->_managerRoles);
		$managerOptions = array('0' => $this->view->translate('Все'));
		foreach ($managers as $manager) {
			$managerOptions[$manager->id] = $manager->getRealname();
		}
		$this->view->managerOptions = $managerOptions;


		$orderStatusMapper = new Shop_Model_Mapper_OrderStatus();
		$statusList = $orderStatusMapper->getList();
		$statusOptions = []; //array(0 => 'Все');
		foreach ($statusList as $status) {
			$statusOptions[$status->id] = $status->name;
		}
		$this->view->orderStatusOptions = $statusOptions;


		$orderDocTypesMapper = new Shop_Model_Mapper_OrderDocTypes();
		$docTypes = $orderDocTypesMapper->getList(true);
		$docTypesOptions = []; //array(0 => 'Все');
		foreach ($docTypes as $docType) {
			$docTypesOptions[$docType->id] = $docType->name;
		}
		$this->view->orderDocTypesOptions = $docTypesOptions;

		$paymentStatusMapper = new Finance_Model_PaymentInstructionStatus();
		$orderPayStatus = $paymentStatusMapper->getList();
		$statusOptions = []; //array(0 => 'Все');
		foreach ($orderPayStatus as $statusId => $statusName) {
			$statusOptions[$statusId] = $statusName;
		}
		$this->view->paymentOrderStatus = $statusOptions;

        $this->view->territoryOptions = Territories::asOptions();




	}

	public function formAction()
	{
		// Кирилл: определяем - нажимал ли менеджер ранее на кнопку LF
		$lfDoc = new Shop_Model_Mapper_LfDoc(); // образ класса			
		$orderAPI = $lfDoc->selectOrder($this->_getParam('id'));
		if (count($orderAPI) == 1){ // Если ранее уже нажимали на кнопку LF
            foreach ($orderAPI as $k){
                if ($k->status == 'finish'){
                    $this->view->LForderStatus = 'finish';
                } else {
                    $this->view->LForderStatus = 'nofinish';
                }
            }
			$this->view->LFcountOrder = 1;
		} else {
			$this->view->LFcountOrder = 0;
		}
		
		//$currentPage = $this->_getParam('page', 1);

		$id = $this->_getParam('id');
		$mapper = new Shop_Model_Mapper_Orders();
		$order = $mapper->find($id);

		if (class_exists('Crm_Service_Facade') && $order->client) {
			$client = Crm_Service_Facade::getInstance()->getClient($order->client);
			$this->view->client = $client;
		}
		$mapperPos = new Shop_Model_Mapper_OrderPos();
		$posList = $mapperPos->getList($id);
		$distribFacade = Distrib_Service_Facade::getInstance();
		$suppliers = $distribFacade->getSuppliers();
		$this->view->suppliers = $suppliers;

		$mapperSuppliers = new Distrib_Model_Mapper_Suppliers();
		$this->view->mapperSuppliers = $mapperSuppliers;
		$posToSendOrder = array();

		if ($posList) {
			$checkAnalogIds = array();
			$multService = new Shop_Service_Multiplicity();
			foreach ($posList as $pos) {
				if ($pos->supplier && $pos->type != Shop_Model_Mapper_OrderPosTypes::WAREHOUSE) {
					if (($pos->type == Shop_Model_Mapper_OrderPosTypes::SUPPLIERS) && $pos->analog) {
						$unit = $distribFacade->getUnitBySupplierAndAnalog($pos->supplier, $pos->analog);
						if ($unit) {
							$pos->unit = $unit;
							$pos->price_purchase = $unit->price;
							$pos->stock = $unit->stock;
							$pos->frontDelivery = $unit->frontDelivery;
						}
						$checkAnalogIds[] = $pos->analog;
					}
					if ($pos->type == Shop_Model_Mapper_OrderPosTypes::PARTNER) {
						$product = Products_Service_Facade::getInstance()->findProduct($pos->code);
						if ($product) {
    						$pricePurchase = Products_Service_Facade::getInstance()->findProductPrice($product->id, 0);
    						$pos->price_purchase = $pricePurchase->price;
    						$pos->stock = $product->qnt_gs;
    						$supplier = $suppliers[$product->supplier];
    						$pos->frontDelivery = $supplier->delivery;
						} else {
						    $pos->name .= ' <span class="text-danger">[Товар не найден в базе номенклатуры]</span>';
						}
					}

					// если товар не заказан - составляем список поставщиков
					if ($pos->status == Shop_Model_Mapper_OrderPosStatus::UNORDERED) {
						$posToSendOrder[$pos->supplier][] = $pos;
					}

					// проверка на требования к кратности товара
					if ($multInfo = $multService->check($pos->name)) {
						$pos->multInfo = $multInfo;
					}
				} elseif ($pos->code) {
					$catalogUnitIds[] = (int) $pos->code;
				}
			}

			// делаем выборку для поиска позиций с большей маржинальностью
			if ($checkAnalogIds) {
				$searchService = new Distrib_Service_Search();
				$othersUnits = $searchService->searchUnits($checkAnalogIds);
				if ($othersUnits) {
					Lv7_Service_Tools::sortBy($othersUnits, array('price'));
					$othersUnits = Lv7_Service_Tools::createIndexBy($othersUnits, 'analog', 'supplier');
					$this->view->othersUnits = $othersUnits;
				}
			}
		}

		$this->view->posToSendOrder = $posToSendOrder;

		Shop_Service_Config::setSiteId($order->site);
		$form = new Shop_Form_Order();

		/*
		if ($this->getRequest()->isPost()) {
			if ($form->isValid($this->getRequest()->getPost())) {
				$order->params->faceType = $form->getValue('faceType');
				$order->params->paymentMethod = $form->getValue('paymentMethod');
				$order->params->delivery = $form->getValue('delivery');
				$order->params->deliveryMileage = $form->getValue('distance');
				$order->params->contactFace = $form->getValue('contact_face');
				$order->params->passport = $form->getValue('passport');
				$order->params->address_register = $form->getValue('address_register');
				$order->params->phone = $form->getValue('phone');
				$order->params->email = $form->getValue('email');
				$order->params->address = $form->getValue('address');
				$order->params->discountCard = $form->getValue('discount_card');
				$order->params->vinNumber = $form->getValue('vin_number');
				$order->params->comments = $form->getValue('comments');
				$order->params->managerComments = $form->getValue('manager_comments');
				$order->shop = $form->getValue('shop');
				$order = $mapper->update($order);

				$orderManager = new Shop_Service_OrderManager();
				$orderManager->updateOrder($order->id);

				$this->_helper->redirector->gotoRouteAndExit(array('id' => $order->id), 'shop::adminOrder');
			}
		} else {
		*/
		$form->setDefault('doc_type', $order->doc_type);
		$form->setDefault('faceType', $order->params->faceType);
		$form->setDefault('paymentMethod', $order->params->paymentMethod);
		$form->setDefault('delivery', $order->params->delivery);
		$form->setDefault('distance', $order->params->deliveryMileage);
		$form->setDefault('contact_face', $order->params->contactFace);
		$form->setDefault('passport', $order->params->passport);
		$form->setDefault('address_register', $order->params->address_register);
		$form->setDefault('phone', $order->params->phone);
		$form->setDefault('email', $order->params->email);
		$form->setDefault('address', $order->params->address);
		$form->setDefault('discount_card', $order->params->discountCard);
		$form->setDefault('vin_number', $order->params->vinNumber);
		$form->setDefault('comments', $order->params->comments);
		$form->setDefault('manager_comments', $order->params->managerComments);
		$form->setDefault('shop', $order->shop);
		$form->setDefault('barcode', $order->params->barcode);
		if ($order->params->barcode) {
		    $employee = CompanyFacade::findEmployeeByBarcode($order->params->barcode);
		    if ($employee) {
		        $form->barcode->setDescription($employee->name);
            }
        }
		//}

		$this->view->posList = $posList;
		$this->view->form = $form;
		$this->view->order = $order;
		$orderTypes = new Shop_Model_Mapper_OrderTypes();
		$this->view->orderType = $orderTypes->find($order->type);

		$catalogFacade = CatCommon_Service_Facade::getInstance();
		$catalog = $catalogFacade->getCatalogBySite($order->site);
		$this->view->catalog = $catalog;

		if (is_array($catalogUnitIds)) {
			$availability = $catalogFacade->getAvailability($catalogUnitIds, $catalog->id);
			$this->view->catalogUnitsAvailability = $availability;
			$this->view->shops = $catalogFacade->getShops();
		}

		$orderStatusMapper = new Shop_Model_Mapper_OrderStatus();
		$statusList = $orderStatusMapper->getList();
		$statusOptions = array();
		foreach ($statusList as $status) {
			$statusOptions[$status->id] = $status->name;
		}
		$this->view->orderStatusOptions = $statusOptions;

		$posStatusMapper = new Shop_Model_Mapper_OrderPosStatus();
		$statusList = $posStatusMapper->getList();
		$statusOptions = array();
		foreach ($statusList as $status) {
			$statusOptions[$status->id] = $status->name;
		}
		$this->view->posStatusOptions = $statusOptions;

		$managers = Users_Service_Facade::getInstance()->getList($this->_managerRoles);
		$managerOptions = array('0' => $this->view->translate('Все'));
		foreach ($managers as $manager) {
			$managerOptions[$manager->id] = $manager->getRealname();
			if ($manager->id == $order->manager) {
				$currentManager = $manager;
			}
		}
		$this->view->managerOptions = $managerOptions;
		$this->view->currentManager = $currentManager;
		if ($currentManager) {
			$this->view->currentManagerProfile = Distrib_Service_Facade::getInstance()->getProfile($currentManager->id);
		}

        $this->view->territoryOptions = Territories::asOptions();
        $this->view->responsibleOptions = $order->territory_id
            ? [0 => '-- выберите ответственного --'] + Lv7_Service_Tools::createOptionsList($order->territory()->employees(), 'id', 'name')
            : [];

		$this->view->calculator = Shop_Service_Config::getScheme()->getCalculator();

		Shop_Service_Config::setSiteId(null);

		if (strlen($order->params->discountCard)) {
			$discountCardMapper = new Shop_Model_Mapper_DiscountCards();
			$discountCard = $discountCardMapper->findByNumber($order->params->discountCard);
			$this->view->discountCard = $discountCard;
		}

		// если оплата заказа по карте - достаем платежную инструкцию
		if (strpos($order->params->paymentMethod, 'Sberbank') !== false || strpos($order->params->paymentMethod, 'Bnpl') !== false) {
			$extObjFacade = new ExternalObjects_Service_Facade();
			$extObj = $extObjFacade->findByType('shop_order', $order->id);
			if ($extObj) {
				$pi = Finance_Service_Facade::findPaymentInstructionByExtObj($extObj->id);
				$this->view->paymentInstruction = $pi;
				if ($pi) {
					$orderId = $order->id;
					if ($order->params->paymentMethod == 'Bnpl') {
						$orderId = $order->site . '_' . $order->number;
					}
					$paymentInfo = Finance_Service_Facade::getPaymentInfo($pi->id, $orderId);
					$this->view->paymentInfo = $paymentInfo;
					if ($paymentInfo && $paymentInfo->OrderStatusName == 'Предавторизованная сумма захолдирована' && $pi->status == Finance_Model_PaymentInstructionStatus::REGISTER) {
						$piMapper = new Finance_Model_Mapper_PaymentInstructions();
						$pi->status = Finance_Model_PaymentInstructionStatus::AUTHORIZED;
						$piMapper->update($pi);
					}
				}
			}
		}

        /*
		$productsMapper = new Products_Model_Mapper_Products();
		$this->view->productsMapper = $productsMapper;
		$supplierOrderMapper = new Distrib_Model_Mapper_SupplierOrders();
		$posOrderMapper = new Distrib_Model_Mapper_SupplierOrderPos();
		$orders = $supplierOrderMapper->getList(Distrib_Model_Mapper_SupplierOrderSourceTypes::CLIENT_ORDER, $order->id);
		if ($orders) {
			foreach ($orders as $order_sup) {
				$orderItems[] = $posOrderMapper->getList($order_sup->id);
			}
		}

		if (!empty($orderItems)) {
			$findProduct = array();
			foreach ($orderItems as $item) {
					foreach ($item as $object) {
						if ($object->brand && $object->number) {
							$findProduct[] = $productsMapper->getBySupplier($object->brand, null, $object->number);
						}
					}
			}
			$this->view->findProducts = $findProduct;
		}
        */

		$orderLogMapper = new Shop_Model_Mapper_OrdersLog();
		$getLogs = $orderLogMapper->findByOrder($order->id);
		$this->view->orderLogs = $getLogs;

		// если к заказу привязан внешний объект - достаем его
		if ($order->ext_obj) {
			$extObjFacade = new ExternalObjects_Service_Facade();
			$this->view->extObj = $extObjFacade->get($order->ext_obj);
		}
	}

	public function paymentdepositAction()
	{
		$orderId = $this->_getParam('orderId');
		$mapper = new Shop_Model_Mapper_Orders();
		$order = $mapper->find($orderId);
		$currentProfile = Distrib_Service_Facade::getInstance()->getCurrentProfile();
		if ( ( strpos($order->params->paymentMethod, 'Sberbank') !== false || strpos($order->params->paymentMethod, 'Bnpl') !== false ) && $currentProfile && $currentProfile->shop == $order->shop) {
			$extObjFacade = new ExternalObjects_Service_Facade();
			$extObj = $extObjFacade->findByType('shop_order', $order->id);
			if ($extObj) {
				$pi = Finance_Service_Facade::findPaymentInstructionByExtObj($extObj->id);
				if ($pi) {
					try {
						$orderId = $order->id;
						if ($order->params->paymentMethod == 'Bnpl') {
							$orderId = $order->site . '_' . $order->number;
							$posMapper = new Shop_Model_Mapper_OrderPos();
							$calculator = Shop_Service_Config::getScheme()->getCalculator();
							$itemsList = $posMapper->getList($extObj->obj_id);
							$items = array();
							if ($itemsList) {
								$itemDiscount = 0;
								foreach ($itemsList as $item) {
									if ($order->params->discount) {
										$itemDiscount = $calculator->itemDiscount($order->params->discount, $item->name, $item->sale);
									}
									$newItem = array();
									$newItem['name'] = $item->name;
									$newItem['quantity'] = $item->quantity;
									$newItem['price'] = floor(max(1, $item->price * 1 * (100 - $itemDiscount) / 100));
									$items[] = $newItem;
								}
							}
							if ($order->params->deliveryCost) {
								$newItem = array();
								$newItem['name'] = 'Доставка';
								$newItem['price'] = $order->params->deliveryCost;
								$newItem['quantity'] = 1;
								$items[] = $newItem;
							}
							$this->_flashMessenger->addMessage('Заявка успешно подтверждена!|success');
						} else {
							$this->_flashMessenger->addMessage('Оплата прошла успешно!|success');
						}
						Finance_Service_Facade::paymentDeposit($pi->id, $orderId, $items);
						$order->status = Shop_Model_Mapper_OrderStatus::COMPLETE;

						$orderManager = new Shop_Service_OrderManager();
						$currentUser = Zend_Registry::get('user');
						$orderManager->creteOrdersLog($orderId, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::PAYMENTCOMPLATE);
						$mapper->update($order);
					} catch (Exception $e) {
						$this->_flashMessenger->addMessage($e->getMessage() . '|error');
					}
				}
			}
		} else if ( ( strpos($order->params->paymentMethod, 'Sberbank') !== false || strpos($order->params->paymentMethod, 'Bnpl') !== false ) && $currentProfile && $currentProfile->shop != $order->shop) {
			$this->_flashMessenger->addMessage('Ваш профиль не принадлежит в к данному магазину, Вы не можете принять оплату!||error');
		}
		$this->_helper->redirector->gotoRouteAndExit(array('id' => $order->id), 'shop::adminOrder');
	}

	public function paymentreverseAction()
	{
		$orderId = $this->_getParam('orderId');
		$mapper = new Shop_Model_Mapper_Orders();
		$order = $mapper->find($orderId);
		$currentProfile = Distrib_Service_Facade::getInstance()->getCurrentProfile();
		if ( ( strpos($order->params->paymentMethod, 'Sberbank') !== false || strpos($order->params->paymentMethod, 'Bnpl') !== false ) && $currentProfile && $currentProfile->shop == $order->shop) {
			$extObjFacade = new ExternalObjects_Service_Facade();
			$extObj = $extObjFacade->findByType('shop_order', $order->id);
			if ($extObj) {
				$pi = Finance_Service_Facade::findPaymentInstructionByExtObj($extObj->id);
				if ($pi) {
					try {
						$orderId = $order->id;
						if ($order->params->paymentMethod == 'Bnpl') {
							$orderId = $order->site . '_' . $order->number;
							$this->_flashMessenger->addMessage('Заявка отменена!|success');
						} else {
							$this->_flashMessenger->addMessage('Авторизация оплаты отменена!|success');
						}
						Finance_Service_Facade::paymentReverse($pi->id, $orderId);
						$order->status = Shop_Model_Mapper_OrderStatus::CANCEL;

						$orderManager = new Shop_Service_OrderManager();
						$currentUser = Zend_Registry::get('user');
						$orderManager->creteOrdersLog($orderId, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::PAYMENTREVERSE);

						$mapper->update($order);
					} catch (Exception $e) {
						$this->_flashMessenger->addMessage($e->getMessage() . '|error');
					}
				}
			}
		} else if ( ( strpos($order->params->paymentMethod, 'Sberbank') !== false || strpos($order->params->paymentMethod, 'Bnpl') !== false ) && $currentProfile && $currentProfile->shop != $order->shop) {
			$this->_flashMessenger->addMessage('Ваш профиль не принадлежит в к данному магазину, Вы не можете отменить оплату!||error');
		}
		$this->_helper->redirector->gotoRouteAndExit(array('id' => $order->id), 'shop::adminOrder');
	}

	public function paymentrefundAction()
	{
		$orderId = $this->_getParam('orderId');
		$mapper = new Shop_Model_Mapper_Orders();
		$order = $mapper->find($orderId);
		if (( strpos($order->params->paymentMethod, 'Sberbank') !== false || strpos($order->params->paymentMethod, 'Bnpl') !== false )) {
			$extObjFacade = new ExternalObjects_Service_Facade();
			$extObj = $extObjFacade->findByType('shop_order', $order->id);
			if ($extObj) {
				$pi = Finance_Service_Facade::findPaymentInstructionByExtObj($extObj->id);
				if ($pi) {
					try {
						$orderId = $order->id;
						if ($order->params->paymentMethod == 'Bnpl') {
							$orderId = $order->site . '_' . $order->number;
							$currentUser = Zend_Registry::get('user');
							$posMapper = new Shop_Model_Mapper_OrderPos();
							$mapperReturnPos = new Shop_Model_Mapper_OrderPosReturn();
							$calculator = Shop_Service_Config::getScheme()->getCalculator();
							$itemsList = $posMapper->getList($extObj->obj_id);
							$items = array();
							$amount = 0;
				            if ($itemsList) {
				                $itemDiscount = 0;
				                foreach ($itemsList as $item) {
					                if ($order->params->discount) {
					                    $itemDiscount = $calculator->itemDiscount($order->params->discount, $item->name, $item->sale);
					                }
					                $newItem = array();
					                $newItem['name'] = $item->name;
					                $newItem['quantity'] = $item->quantity;
					                $newItem['price'] = floor(max(1, $item->price * 1 * (100 - $itemDiscount) / 100));
					                $amount += $newItem['price'] * $item->quantity;
					                $items[] = $newItem;

									$item->quantity = 0;
									$posMapper->update($item);

									$itemReturn = $mapperReturnPos->createObject();
									$itemReturn->order = $order->id;
									$itemReturn->user = $currentUser->id;
									$itemReturn->code = $item->code;
									$itemReturn->name = $item->name;
									$itemReturn->quantity = $item->quantity;
									$itemReturn->price = floor(max(1, $item->price * 1 * (100 - $itemDiscount) / 100));
									$mapperReturnPos->insert($itemReturn);
				                }
				             }

							if ($order->params->deliveryCost) {
								$newItem = array();
								$newItem['name'] = 'Доставка';
								$newItem['code'] = 'delivery';
								$newItem['price'] = $order->params->deliveryCost;
								$newItem['quantity'] = 1;
								$items[] = $newItem;

								$amount += $order->params->deliveryCost;

								$item = $mapperReturnPos->createObject();
								$item->order = $order->id;
								$item->user = $currentUser->id;
								$item->code = $newItem['code'];
								$item->name = $newItem['name'];
								$item->quantity = 1;
								$item->price = $newItem['price'];
								$mapperReturnPos->insert($item);
							}
						}
						Finance_Service_Facade::paymentRefund($pi->id, $orderId, $items, $amount);
						$this->_flashMessenger->addMessage('Средства за оплату заказа возвращены клиенту!|success');
						$order->status = $items ? Shop_Model_Mapper_OrderStatus::RETURNED : Shop_Model_Mapper_OrderStatus::CANCEL;

						$orderManager = new Shop_Service_OrderManager();
						$currentUser = Zend_Registry::get('user');
						$orderManager->creteOrdersLog($orderId, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::PAYMENTREFUND);

						$mapper->update($order);
					} catch (Exception $e) {
						$this->_flashMessenger->addMessage($e->getMessage() . '|error');
					}
				}
			}
		}
		$this->_helper->redirector->gotoRouteAndExit(array('id' => $order->id), 'shop::adminOrder');
	}

	public function paymentrefundpartialAction()
	{
		$orderId = $this->_getParam('orderId');
		$mapper = new Shop_Model_Mapper_Orders();
		$order = $mapper->find($orderId);
		if (strpos($order->params->paymentMethod, 'Bnpl') !== false) {
			$extObjFacade = new ExternalObjects_Service_Facade();
			$extObj = $extObjFacade->findByType('shop_order', $order->id);
			if ($extObj) {
				$pi = Finance_Service_Facade::findPaymentInstructionByExtObj($extObj->id);
				if ($pi) {
					$posMapper = new Shop_Model_Mapper_OrderPos();
					$mapperReturnPos = new Shop_Model_Mapper_OrderPosReturn();
					$posReturned = $mapperReturnPos->getList($order->id);
					if ($posReturned) {
						$mapperUser = new Users_Model_Mapper_User();
						$this->view->posReturned = Lv7_Service_Tools::createIndexBy($posReturned, 'code');
						$this->view->mapperUser = $mapperUser;
					}
					$itemsList = $posMapper->getList($extObj->obj_id);
					$calculator = Shop_Service_Config::getScheme()->getCalculator();
					$items = array();
		            if ($itemsList) {
		                $itemDiscount = 0;
		                foreach ($itemsList as $item) {
		                  if ($order->params->discount) {
		                    $itemDiscount = $calculator->itemDiscount($order->params->discount, $item->name, $item->sale);
		                  }
		                  $newItem = new stdClass();
		                  $newItem->name = $item->name;
		                  $newItem->code = $item->code;
		                  $newItem->id = $item->id;
		                  $newItem->quantity = $item->quantity;
		                  $newItem->price = floor(max(1, $item->price * 1 * (100 - $itemDiscount) / 100));
		                  $items[] = $newItem;
		                }
		             }
					if ($itemsList && $order->params->deliveryCost) {
						$newItem = new stdClass();
						$newItem->code = 'delivery';
						$newItem->id = 'delivery';
						$newItem->name = 'Доставка';
						$newItem->price = $order->params->deliveryCost;
						$newItem->quantity = 1;
						$items[] = $newItem;
					}
					$this->view->itemsList = $items;
					$this->view->order = $order;
				}
			}
		}
	}

	public function orderreturnAction()
	{
		$orderId = $this->_getParam('orderId');
		$mapper = new Shop_Model_Mapper_Orders();
		$order = $mapper->find($orderId);
		$currentUser = Zend_Registry::get('user');
		if ($this->getRequest()->isPost()) {
			$mapperPos = new Shop_Model_Mapper_OrderPos();
			$mapperReturnPos = new Shop_Model_Mapper_OrderPosReturn();
			$posList = $mapperPos->getList($orderId);
			$extObjFacade = new ExternalObjects_Service_Facade();
			$extObj = $extObjFacade->findByType('shop_order', $order->id);
			$orderIdBnpl = $order->site . '_' . $order->number;
			if ($extObj) {
				$pi = Finance_Service_Facade::findPaymentInstructionByExtObj($extObj->id);
				if ($pi) {
					if ($posList) {
						$items = array();
						$amount = 0;
						$calculator = Shop_Service_Config::getScheme()->getCalculator();
						foreach ($posList as $pos) {
							$itemId = $this->_getParam('item_' . $pos->id);
							$returnQty = $this->_getParam('returnQty_' . $pos->id);
							$quantity = $pos->quantity;
							$returnValue = $quantity - $returnQty;
							$itemDiscount = 0;
			                if ($order->params->discount) {
			                	$itemDiscount = $calculator->itemDiscount($order->params->discount, $pos->name, $pos->sale);
			                }
							if ($returnQty) {
								$newItem = array();
								$newItem['name'] = $pos->name;
								$newItem['quantity'] = $returnQty;
								$newItem['price'] = floor(max(1, $pos->price * 1 * (100 - $itemDiscount) / 100));
								$amount += $newItem['price'] * $returnQty;
								$items[] = $newItem;
							}
							if ($returnQty) {
								$pos->quantity = $returnValue;
								$mapperPos->update($pos);
							}
							if ($returnQty) {
								$item = $mapperReturnPos->createObject();
								$item->order = $orderId;
								$item->user = $currentUser->id;
								$item->code = $pos->code;
								$item->name = $pos->name;
								$item->quantity = $returnQty;
								$item->price = floor(max(1, $pos->price * 1 * (100 - $itemDiscount) / 100));
								$mapperReturnPos->insert($item);
							}
						}

						if ($this->_getParam('returnQty_delivery') && $order->params->deliveryCost) {
							$newItem = array();
							$newItem['name'] = 'Доставка';
							$newItem['code'] = 'delivery';
							$newItem['price'] = $order->params->deliveryCost;
							$newItem['quantity'] = 1;
							$items[] = $newItem;

							$amount += $order->params->deliveryCost;

							$item = $mapperReturnPos->createObject();
							$item->order = $orderId;
							$item->user = $currentUser->id;
							$item->code = $newItem['code'];
							$item->name = $newItem['name'];
							$item->quantity = 1;
							$item->price = $newItem['price'];
							$mapperReturnPos->insert($item);
						}

						if ($items) {
							Finance_Service_Facade::paymentRefund($pi->id, $orderIdBnpl, $items, $amount);
							$this->_flashMessenger->addMessage('Возврат осуществлен!|success');
							$order->status = Shop_Model_Mapper_OrderStatus::RETURNED;
							$mapper->update($order);
							$orderManager = new Shop_Service_OrderManager();
							$orderManager->creteOrdersLog($orderId, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::PAYMENTREFUND);
						}
					}
				}
			}
		}
		$this->_helper->redirector->gotoRouteAndExit(array('id' => $orderId), 'shop::adminOrder');
	}

	public function sendOrderPaymentToClientAction()
	{
		$orderId = $this->_getParam('orderId');
		$mapper = new Shop_Model_Mapper_Orders();
		$order = $mapper->find($orderId);

		if (strpos($order->params->paymentMethod, 'Sberbank') === false) {
			throw new Lv7CMS_Exception('Метод оплаты НЕ банковской картой!');
		}

		$repository = new Lv7CMS_Resource_Repository($order->site);

		$orderManager = new Shop_Service_OrderManager();
		$params = $orderManager->getOrderTemplateParams($order);

		$form = new Shop_Form_Mail();

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($this->getRequest()->getPost())) {

				$mailText = $this->_getParam('mailText');
				$orderManager->sendPaymentOrderMailToClient($order, $mailText);

				$this->_flashMessenger->addMessage('Письмо об оплате отправлено клиенту!|success');

				$currentUser = Zend_Registry::get('user');
				$orderManager->creteOrdersLog($order->id, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::EMAILPAYMENT);

				$this->_helper->redirector->gotoRouteAndExit(array('id' => $orderId), 'shop::adminOrder');

			}
		} else {
			$template = $repository->find(Lv7CMS_Resource_Types::TEXT_TEMPLATES, 'shopMailClientToPayment');
			$mailText = $template->getText($params);
			$form->setDefault('mailText', $mailText);
		}
		$this->view->form = $form;
		$this->view->clientEmail = $order->params->email;
		$this->view->managerEmail = $params['MANAGER_MAIL'];
		$this->view->orderNumber = $order->number;

		$cmsSettings = $repository->findAll(Lv7CMS_Resource_Types::SETTINGS);
		$this->view->subject = 'Оплата заказа #' . $order->number . ' на сайте ' . $cmsSettings['defaultSiteName']->getValue();

	}

	public function viewOrderPaymentEmailAction()
	{
		$orderId = $this->_getParam('orderId');
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$this->view->order = $ordersMapper->find($orderId);
	}


	public function posupdateAction()
	{
		$orderId = $this->_getParam('orderId');

		if ($this->getRequest()->isPost()) {

			$ordersMapper = new Shop_Model_Mapper_Orders();
			$order = $ordersMapper->find($orderId);
			$order->params->deliveryCostManualSet = $this->_getParam('delivery_set_manual');
			if ($order->params->deliveryCostManualSet) {
				$order->params->deliveryCost = $this->_getParam('delivery_cost');
			}
			$order->params->prepaidManualSet = $this->_getParam('prepaid_set_manual');
			if ($order->params->prepaidManualSet) {
				$order->params->prepaid = $this->_getParam('prepaid');
			}
			$ordersMapper->update($order);

			$mapperPos = new Shop_Model_Mapper_OrderPos();
			$posList = $mapperPos->getList($orderId);
			$addPositions = array();
			if ($posList) {
				foreach ($posList as $pos) {
					$quantity = $this->_getParam('pos_quantity_' . $pos->id);
					$toDelete = $this->_getParam('pos_delete_' . $pos->id);
					if (!$toDelete && $quantity) {
						$pos->quantity = $quantity;
						$pos->price = $this->_getParam('pos_price_' . $pos->id);
						$pos->status = $this->_getParam('status_' . $pos->id);
						$addPositions[] = $pos;
						$mapperPos->update($pos);
					} else {
						$mapperPos->delete($pos);
					}
				}
			}
			$orderManager = new Shop_Service_OrderManager();
			$currentUser = Zend_Registry::get('user');
			$orderManager->creteOrdersLog($orderId, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::CHANGEPOS);
			$orderManager->updateOrder($orderId, true);

	        // Создаем файл с заказом если заказ от Юр. лица и с безналичной оплатой
	        if ($order->params->paymentMethod == 'NonCash' || $order->params->faceType == 'legal') {
	            $sendOrder = $orderManager->PostProcessing($order, $addPositions);
	        }

		}
		$this->_helper->redirector->gotoRouteAndExit(array('id' => $orderId), 'shop::adminOrder');
	}

	/*
	public function posdeleteAction()
	{
		$orderId = $this->_getParam('orderId');
		$posId = $this->_getParam('posId');

		$orderPosMapper = new Shop_Model_Mapper_OrderPos();
		$pos = $orderPosMapper->find($posId);
		if ($pos) {
			$orderPosMapper->delete($pos);

			$orderManager = new Shop_Service_OrderManager();
			$orderManager->updateOrder($orderId);
		}

		$this->_helper->redirector->gotoRouteAndExit(array('id' => $orderId), 'shop::adminOrder');
	}
	*/

	public function posaddAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		$orderId = $this->_getParam('orderId');
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$order = $ordersMapper->find($orderId);

		$catalogUnits = $this->_getParam('catalogUnits');
		$distribUnits = $this->_getParam('distribUnits');
		$productsStockMapper = new Products_Model_Mapper_Stock();
        $orderPosMapper = new Shop_Model_Mapper_OrderPos();

		$catalogId = 6; // ПЖ

		$addPositions = array();
		if ($catalogUnits) {
			if (!is_array($catalogUnits)) {
				$catalogUnits = array($catalogUnits);
			}
			foreach ($catalogUnits as $unitId) {
				$unit = CatCommon_Service_Facade::getInstance()->getUnit($unitId, $catalogId);
				if ($unit) {

                    $stock = $productsStockMapper->findByShop(intval($unit->code), 'ГС'); // Основной склад

					$pos = $orderPosMapper->createObject();
					$pos->order = $orderId;
					if ($unit->supplier > 1 && $stock->quantity < 1) {
						$pos->type = Shop_Model_Mapper_OrderPosTypes::SUPPLIERS;
                        $pos->status = Shop_Model_Mapper_OrderPosStatus::UNORDERED;
					} else {
						$pos->type = Shop_Model_Mapper_OrderPosTypes::WAREHOUSE;
                        $pos->status = Shop_Model_Mapper_OrderPosStatus::ON_WAREHOUSE;
					}
					$pos->code = $unit->code;
					$pos->name = $unit->name;
					$pos->articul = $unit->articul;
					$pos->supplier = $unit->supplier;
					$pos->price = $unit->price;
					$pos->sale = $unit->sale;
					$pos->quantity = 1;
                    $pos->analog = $unit->analog;
                    $pos->man_brand = $unit->brand;
					$pos->man_number = $unit->articul2;

                    $addPositions[] = $pos;
					$orderPosMapper->insert($pos);
				}
			}
		}

		if ($distribUnits) {
			if (!is_array($distribUnits)) {
				$distribUnits = array($distribUnits);
			}
			foreach ($distribUnits as $unitId) {
				$unit = Distrib_Service_Facade::getInstance()->getUnit($unitId);
				if ($unit) {
					$orderPosMapper = new Shop_Model_Mapper_OrderPos();
					$pos = $orderPosMapper->createObject();
					$pos->order = $orderId;
					$pos->type = Shop_Model_Mapper_OrderPosTypes::SUPPLIERS;
					$pos->code = '';
					$pos->name = $unit->name;
					$analog = Distrib_Service_Facade::getInstance()->getAnalogById($unit->analog);
					$brand = Distrib_Service_Facade::getInstance()->getBrand($analog->brand);
					$pos->articul = $brand->name . ' ' . $analog->number;
					$pos->price = $unit->frontPrice;
					$pos->price_purchase = $unit->price;
					$pos->quantity = 1;
					$pos->analog = $unit->analog;
					$pos->supplier = $unit->supplier;
					$pos->man_brand = $brand->name;
					$pos->man_number = $analog->number;
					$pos->status = Shop_Model_Mapper_OrderPosStatus::UNORDERED;
					$addPositions[] = $pos;
					$orderPosMapper->insert($pos);
				}
			}
		}

		$orderManager = new Shop_Service_OrderManager();
        // Создаем файл с заказом если заказ от Юр. лица и с безналичной оплатой
        if ($order->params->paymentMethod == 'NonCash') {
            $sendOrder = $orderManager->PostProcessing($order, $addPositions);
        }

		$currentUser = Zend_Registry::get('user');
		$orderManager->creteOrdersLog($orderId, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::CHANGEPOS);
		$orderManager->updateOrder($orderId, true);
	}

	public function orderupdateAction()
	{
		$orderId = $this->_getParam('orderId');
		$statusId = $this->_getParam('statusId');
		$managerId = $this->_getParam('managerId');
		$mapper = new Shop_Model_Mapper_Orders();
		if ($order = $mapper->find($orderId)) {
			$order->manager = $managerId;
			$order->status = $statusId;
			$order->params->managerComments = $this->_getParam('managerComments');
			$mapper->update($order);

			$orderManager = new Shop_Service_OrderManager();
			$currentUser = Zend_Registry::get('user');
			$orderManager->creteOrdersLog($orderId, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::CHANGESTATUS);
			$orderManager->updateOrderPosStatus($orderId);
		}

		$this->_helper->redirector->gotoRouteAndExit(array('id' => $orderId), 'shop::adminOrder');
	}

	public function ordersaveAction()
	{
		$orderId = $this->_getParam('orderId');
		$mapper = new Shop_Model_Mapper_Orders();
		$orderManager = new Shop_Service_OrderManager();
		$currentUser = Zend_Registry::get('user');
		$accessibleRoles = array('developer', 'administrator');
		$canChangeManager = ($currentUser && in_array($currentUser->getRole(), $accessibleRoles));
		$currentProfile = Distrib_Service_Facade::getInstance()->getCurrentProfile();
		$order = $mapper->find($orderId);

		$catalogFacade = CatCommon_Service_Facade::getInstance();
		$catalog = $catalogFacade->getCatalogBySite($order->site);

		if ($order && $currentProfile && $currentProfile->shop == $order->shop) {

			$orderOldManager = $order->manager;
			$orderOldDocType = $order->doc_type;
			$order->manager = $this->_getParam('managerId');
			$order->territory_id = $this->_getParam('territoryId');
			$order->responsible_id = $this->_getParam('responsibleId');
			//$order->to_export = $this->_getParam('toExport');
			$order->status = $this->_getParam('statusId');
			$order->doc_type = (int)$this->_getParam('doc_type');

			$order->params->managerComments = $this->_getParam('managerComments');

			$delivery = Shop_Model_DeliveryFactory::create($this->_getParam('delivery'));
			$paymentMethod = Shop_Model_PaymentMethodFactory::create($this->_getParam('paymentMethod'));

			$order->params->faceType = $this->_getParam('faceType');
			$order->params->paymentMethod = $paymentMethod->getId();
			$order->params->paymentMethodName = $paymentMethod->getName();
			$order->params->delivery = $delivery->getId();
			$order->params->deliveryName = $delivery->getName();
			$order->params->deliveryMileage = $this->_getParam('distance');
			$order->params->contactFace = $this->_getParam('contact_face');
			$order->params->passport = $this->_getParam('passport');
			$order->params->address_register = $this->_getParam('address_register');
			$order->params->phone = Lv7_Service_Text::normalizePhoneNumber($this->_getParam('phone'));
			$order->params->email = $this->_getParam('email');
			$order->params->address = $this->_getParam('address');
			$order->params->discountCard = $this->_getParam('discount_card');
			$order->params->vinNumber = $this->_getParam('vin_number');
			$order->params->comments = $this->_getParam('comments');
			$order->shop = $this->_getParam('shop');

			$order->params->deliveryCostManualSet = $this->_getParam('delivery_set_manual');
				if ($order->params->deliveryCostManualSet) {
				$order->params->deliveryCost = $this->_getParam('delivery_cost');
			}
			$order->params->prepaidManualSet = $this->_getParam('prepaid_set_manual');
			if ($order->params->prepaidManualSet) {
				$order->params->prepaid = $this->_getParam('prepaid');
			}

			$orderManagerProfile = Distrib_Service_Facade::getInstance()->getProfile($order->manager);


			if (!$order->responsible_id) {
                $this->_flashMessenger->addMessage('Ошибка! Не указан ответственный менеджер, данные по заказу не сохранены!|error');
            } else if (!$order->manager) {
				$this->_flashMessenger->addMessage('У заказа должен быть ответственный менеджер, действия не сохранены!|error');
			} else if(!empty($orderManagerProfile) && $orderManagerProfile->shop != $order->shop) {
				$this->_flashMessenger->addMessage('Менеджер не подключен к данному магазину, данные не сохранены!|error');
			//} else if (!empty($order->manager) && !$canChangeManager && !$orderOldManager &&  $orderOldManager != $order->manager) {
			//	$this->_flashMessenger->addMessage('Изменить ответственного менеджера может только администратор!|error');
			} else {
				$order = $mapper->update($order);

				$mapperPos = new Shop_Model_Mapper_OrderPos();
				$posList = $mapperPos->getList($orderId);
				if ($posList) {
					foreach ($posList as $pos) {
						$quantity = $this->_getParam('pos_quantity_' . $pos->id);
						$price = $this->_getParam('pos_price_' . $pos->id);
						$toDelete = $this->_getParam('pos_delete_' . $pos->id);

						if ($pos->code) {
							$catalogUnitIds[] = (int) $pos->code;
						}
						if (is_array($catalogUnitIds)) {
							$availability = $catalogFacade->getAvailability($catalogUnitIds, $catalog->id);
						}

						if ($order->type == Shop_Model_Mapper_OrderTypes::ORDERHALL) {
							if ($pos->stock) {
								$posStock = $pos->stock;
							} else if (is_array($a = $availability[(int)$pos->code])) {
								foreach ($a as $availability) {
									if ($availability->shop == 2) {
										$posStock = $availability->quantity;
									}
								}
							}

							if ($quantity > $posStock) {
								$this->_flashMessenger->addMessage('Введено неверное кол-во товара <b>' . $pos->name . '</b>. Вы не можете указать больше чем есть в наличии!|error');
							} else {
								if (!$toDelete && $quantity) {
									$pos->quantity = $quantity * $pos->mult;
									//$pos->mult_quantity = $quantity;
									$pos->price = $price / $pos->mult;
									//$pos->mult_price = $price;
									$pos->status = $this->_getParam('status_' . $pos->id);
									$mapperPos->update($pos);
								} else {
									$mapperPos->delete($pos);
								}

							}

						}

						if ($order->type != Shop_Model_Mapper_OrderTypes::ORDERHALL) {
							if (!$toDelete && $quantity) {
								$pos->quantity = $quantity * $pos->mult;
								//$pos->mult_quantity = $quantity;
								$pos->price = $price / $pos->mult;
								//$pos->mult_price = $price;
								$pos->status = $this->_getParam('status_' . $pos->id);
								$mapperPos->update($pos);
							} else {
								$mapperPos->delete($pos);
							}



						}
					}
				}

				$sendXml = true;
				if ($orderOldDocType == 13 && $order->params->faceType == 'legal' && $order->doc_type == 1) {
					$sendXml = false;
				}
				$orderManager->creteOrdersLog($orderId, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::SAVE);
				$orderManager->updateOrder($orderId, $sendXml);
				$orderManager->updateOrderPosStatus($orderId);
				$this->_flashMessenger->addMessage('Данные успешно сохранены!|success');
			}
		} else if ($order && $currentProfile && $currentProfile->shop != $order->shop) {
			$this->_flashMessenger->addMessage('Вы не можете изменить заказ, так как не принадлежите к данному магазину!|error');
		}

		$this->_helper->redirector->gotoRouteAndExit(array('id' => $orderId), 'shop::adminOrder');
	}


	public function testorderAction() {
	$orderManager = new Shop_Service_OrderManager();
		$orderManager->sendOrderMailToManager(260723);
	}

	public function setPosMultiplicityAction()
	{
		$posId = $this->_getParam('posId');
		$mult = $this->_getParam('mult');
		$mapperPos = new Shop_Model_Mapper_OrderPos();
		$pos = $mapperPos->find($posId);
		$pos->mult = $mult;
		//$pos->mult_quantity = $pos->quantity / $mult;
		//$pos->mult_price = $pos->price * $mult;
		$mapperPos->update($pos);

		$orderManager = new Shop_Service_OrderManager();
		$currentUser = Zend_Registry::get('user');
		$orderManager->creteOrdersLog($pos->order, $currentUser->id, 'planetspares', Shop_Model_Mapper_OrdersLogTypes::POSMULTI);

		$this->_flashMessenger->addMessage('Кратность товара изменена!|success');

		$this->_helper->redirector->gotoRouteAndExit(array('id' => $pos->order), 'shop::adminOrder');
	}


	public function deleteAction()
	{
        $id = $this->_getParam('id');
		if ($id) {
			$mapper = new Shop_Model_Mapper_Orders();
			$obj = $mapper->find($id);
			if (!empty($obj)) {
				$mapper->delete($obj);
				$orderManager = new Shop_Service_OrderManager();
				$currentUser = Zend_Registry::get('user');
				$orderManager->creteOrdersLog($obj->id, $currentUser->id, $obj->site, Shop_Model_Mapper_OrdersLogTypes::DELETE);
			}
		}
		if ($this->_getParam('from')) {
			$this->_helper->redirector->gotoUrlAndExit($this->_getParam('from'));
		} else {
			$this->_helper->redirector->gotoRouteAndExit(array(), 'shop::adminOrders');
		}
	}

	public function dataAction()
	{

		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		$mapper = new Shop_Model_Mapper_Orders();
		$sites = Lv7CMS::getInstance()->getSites();

		$conditions = $this->_getConditions();

		if ($this->_filter->fPaymentMethod) {
			$additions = array('contactFace', 'phone', 'email', 'totalCost', 'deliveryName', 'paymentMethod', 'paymentMethodName');
		} else {
			$additions = array('contactFace', 'phone', 'email', 'totalCost');
		}
		$select = $mapper->getDbSelect($conditions, $additions);

		$select->joinLeft(array('op' => 'shop_order_pos'), '`o`.`id` = `op`.`order`', array('code', 'name', 'articul'));
		$select->group('o.id');

		/*
		echo '<code>';
	  	echo $select->assemble();
	  	echo '</code><br><br>';
		*/
		$searchColumns = array(
			'o.number',
			't_contactFace.value',
			't_phone.value',
			't_email.value',
			'op.code',
			'op.name',
			'op.articul'
		);
		$orderColumns = array(
			2 => 'number',
			3 => 'contactFace',
			6 => 'totalCost:i',
			7 => 'created'
		);

		$dtService = new Lv7CMS_Service_DataTables();
		$data = $dtService
			->setMapper($mapper)
			->setSearchColumns($searchColumns)
			->setOrderColumns($orderColumns)
			->getData($select);


		$canDelete = ($this->_user->getRole() == 'administrator');

		$crmFacade = Crm_Service_Facade::getInstance();
		$extObjFacade = new ExternalObjects_Service_Facade();
		$typesMapper = new Shop_Model_Mapper_OrderTypes();
		$userMapper = new Users_Model_Mapper_User();
		$orderParamsMapper = new Shop_Model_Mapper_OrderParams();
		if (is_array($data)) {
			$dataOutput = array();
			$orderStatus = new Shop_Model_Mapper_OrderStatus();
			foreach ($data as $item) {
				$params['id'] = $item->id;

				if (!$this->_filter->fPaymentMethod) {
					$findOrderParams = $orderParamsMapper->findByEntity($item->id);
					$findOrderParamsIndex = Lv7_Service_Tools::createIndexBy($findOrderParams, 'order','key');
				}
				$item->paymentMethod = $item->paymentMethod ?: $findOrderParamsIndex[$item->id]['paymentMethod']->value;
				$row = array();
				$row[] = ($item->type ? $typesMapper->find($item->type)->shortName : '---');
				$row[] = isset($sites[$item->site]) ? $sites[$item->site]->label : '---';
				$row[] = $item->number;

				$findClient = false;
				if (in_array($item->type, array(Shop_Model_Mapper_OrderTypes::ORDERHALL, Shop_Model_Mapper_OrderTypes::INNER)) || empty($item->client)) {
					$client = $item->contactFace;
				} else {
					$findClient = true;
					$client = $crmFacade->getClient($item->client);
				}
				$row[] = $findClient ? $this->view->crmClientName($client) : $client;
				$row[] = Lv7_Service_Text::humanreadablePhoneNumber($item->phone) . ($item->email ? '<br><a href="mailto:'.$item->email.'">' . $item->email . '</a>' : '');
				$row[] = '<small>' . ($item->deliveryName ?: $findOrderParamsIndex[$item->id]['deliveryName']->value) . '<br>'
                    . ($item->paymentMethodName ?: $findOrderParamsIndex[$item->id]['paymentMethodName']->value) . '<br>'
                    . '</small>';
				$row[] = Lv7_Service_Text::asPrice($item->totalCost, 0) . 'р.';
				$row[] = '<small>' . $this->view->showDate($item->created, 'KKK') . '</small>';

                $row[] = '<i class="fa fa-map-marker fa-fw"></i> ' . $item->territory()->name . '<br>'
                        . ($item->responsibleManager()->id ? ('<i class="fa fa-user fa-fw"></i> <span title="' . $item->responsibleManager()->name . '">' . $item->responsibleManager()->shortName() . '</span>') : '');

                $row[] = ($item->company_id ? '<i class="fa fa-building-o fa-fw"></i> ' . $item->company()->name . '<br>' : '') .
                         ($item->doc_number ? '<i class="fa fa-file-text-o fa-fw"></i> ' . $item->doc_number . '<br>' : '');

                /*
				$managerName = '';
				if ($item->manager) {
					if (isset($userCache[$item->manager])) {
						$managerName = $userCache[$item->manager];
					} else {
						$m = $userService->find($item->manager);
						if ($m) {
							$userCache[$m->id] = $m->getRealname();
							$managerName = $userCache[$m->id];
						}
					}
				}
                */
                if ($item->manager && ($user = $userMapper->find($item->manager))) {
                    $managerName = $user->getRealname();
                    //if (!empty($user->email)) {
                    //    $managerEmail = 'Email: ' . $user->email;
                    //}
                    $managerPhone = '';
                    if (!empty($user->internal_phone)) {
                        $managerPhone = '<br><span class="text-muted">' . $user->internal_phone . '</span>';
                    }
                    $row[] = '<a href="' . $this->view->url(array('id' => $user->id), 'users::userForm') .'">' . $managerName . '</a>' . $managerPhone;
                } else {
                    $row[] = '---';
                }

				$statusText = ($status = $orderStatus->find($item->status)) ? $status->name : '---';
				$paymentStatus = 0;
				if (strpos($item->paymentMethod, 'Sberbank') !== false || strpos($item->paymentMethod, 'Bnpl') !== false) {
					$extObj = $extObjFacade->findByType('shop_order', $item->id);
					if ($extObj) {
						$pi = Finance_Service_Facade::findPaymentInstructionByExtObj($extObj->id);
						if ($pi) {
							$paymentStatus = $pi->status;
							$statusText .= '<br><small>' . $pi->getStatusName() . '</small>';
						}
					}
				}
				$row[] = $statusText;
				$row[] = '<a href="' . $this->view->url($params, 'shop::adminOrder') .'"
								class="button-edit">' . $this->view->translate('изменить') . '</a>'
							. ($canDelete ? '<a href="'. $this->view->url($params, 'shop::adminOrderDelete') . '"
								class="button-delete" query="' . $this->view->translate("Удалить заказ?") .'"
							>'. $this->view->translate('удалить') . '</a>' : '');

				if (in_array($paymentStatus, array(0, Finance_Model_PaymentInstructionStatus::AUTHORIZED, Finance_Model_PaymentInstructionStatus::PAID, Finance_Model_PaymentInstructionStatus::ERROR))) {
					switch ($item->status) {
						case Shop_Model_Mapper_OrderStatus::NEW_ORDER:
							if (in_array($paymentStatus, array(Finance_Model_PaymentInstructionStatus::AUTHORIZED)) || in_array($item->type, array(Shop_Model_Mapper_OrderTypes::DISTRIB, Shop_Model_Mapper_OrderTypes::VIN))) {
								$row["DT_RowClass"] = "text-danger";
							} else if (in_array($paymentStatus, array(Finance_Model_PaymentInstructionStatus::ERROR))) {
								$row["DT_RowClass"] = "text-muted";
							} else {
								$row["DT_RowClass"] = "";
							}
							//$row["DT_RowClass"] = in_array($item->type, array(Shop_Model_Mapper_OrderTypes::DISTRIB, Shop_Model_Mapper_OrderTypes::VIN)) ? "text-danger" : "";
							break;
						case Shop_Model_Mapper_OrderStatus::IN_WORK:
							$row["DT_RowClass"] = "text-primary";
							break;
						case Shop_Model_Mapper_OrderStatus::WAITING:
							$row["DT_RowClass"] = "text-system";
							break;
						case Shop_Model_Mapper_OrderStatus::ON_WAREHOUSE:
							$row["DT_RowClass"] = "text-alert";
							break;
						case Shop_Model_Mapper_OrderStatus::COMPLETE:
							$row["DT_RowClass"] = "text-success";
							break;
						case Shop_Model_Mapper_OrderStatus::CANCEL:
							$row["DT_RowClass"] = "text-muted";
							break;
					}
				} else {
					$row["DT_RowClass"] = "text-muted";
				}
				/*
				if (!$item->activity || !count($ids)) {
					$row["DT_RowClass"] = "disabled";
				}
				*/
				$dataOutput[] = $row;
			}

		}

		echo $dtService->getOutput($dataOutput);
	}

	public function filterAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
        foreach ($this->_filterFields as $fFiled) {
            if ($this->hasParam($fFiled)) {
                $this->_filter->{$fFiled} = $this->_getParam($fFiled);
            } else {
                $this->_filter->{$fFiled} = $this->_getFilterDefault($fFiled);
            }
        }
	}

	public function summaryAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		$mapper = new Shop_Model_Mapper_Orders();
		$conditions = $this->_getConditions();
		$select = $mapper->getDbSelect($conditions); // $additions
		$adapter = $select->getAdapter();
		$result = $adapter->fetchAll($select);
		$ids = array();
		if (is_array($result)) {
			foreach ($result as $row) {
				$ids[] = $row['id'];
			}
		}
		$result = new stdClass();
		$result->total = '---';
		$result->goods = '---';
		$result->delivery = '---';
		if (count($ids)) {
			$paramsMapper = new Shop_Model_Mapper_OrderParams();
			$result->total = Lv7_Service_Text::asPrice($paramsMapper->getSum('totalCost', $ids), 2);
			$result->goods = Lv7_Service_Text::asPrice($paramsMapper->getSum('goodsCost', $ids), 2);
			$result->delivery = Lv7_Service_Text::asPrice($paramsMapper->getSum('deliveryCost', $ids), 2);
		}

		echo json_encode($result);
	}

	public function bysupplierAction()
	{
		$supplierId = $this->_getParam('supplierId');
		if (!$supplierId) {
			return;
		}

		$orderMapper = new Shop_Model_Mapper_Orders();
		$orderManager = new Shop_Service_OrderManager();
		$orderPosMapper = new Shop_Model_Mapper_OrderPos();
		$distribFacade = Distrib_Service_Facade::getInstance();

		$supplier = $distribFacade->getSupplier($supplierId);
		$this->view->supplier = $supplier;

		$posStatusMapper = new Shop_Model_Mapper_OrderPosStatus();
		$posStatus = $posStatusMapper->getList();

		$s = (count($posStatus) == count($this->_filter->fPosStatus)) ? null : $this->_filter->fPosStatus;
		$posList = $orderPosMapper->getList(null, $supplierId, $s);

		$updateStatus = false;
		if ($this->getRequest()->isPost() && $this->_getParam('updatePosStatus')) {
			$updateStatus = true;
			foreach ($posList as $pos) {
				$status = $this->_getParam('status_' . $pos->id);
				$pos->status = $status;
				$orderPosMapper->update($pos);
			}
		}

		if ($posList) {
			foreach ($posList as $pos) {
				$orderIds[$pos->order] = 1;
				$unit = $distribFacade->getUnitBySupplierAndAnalog($pos->supplier, $pos->analog);
				if ($unit) {
					$pos->unit = $unit;
				}
			}

			$orderIds = array_keys($orderIds);
			if ($orderIds) {
				$orders = Lv7_Service_Tools::createIndexBy($orderMapper->findByIds($orderIds), 'id');
				if ($updateStatus) {
					foreach ($orders as $order) {
						$orderManager->updateOrderStatus($order->id);
					}
				}
				$this->view->orders = $orders;
			}
		}

		$this->view->posList = $posList;

		$this->view->posStatusOptions = Lv7_Service_Tools::createOptionsList($posStatus, 'id', 'name');

	}

	public function byclientAction()
	{
		$clientId = $this->_getParam('clientId');
		if (!$clientId) {
			return;
		}

		$mapper = new Shop_Model_Mapper_Orders();
		$this->view->orders = $mapper->getList($clientId);

	}

	public function excelAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout();

		Zend_Registry::set('silentMode', true);

		$id = $this->_getParam('id');
		$mapper = new Shop_Model_Mapper_Orders();
		$order = $mapper->find($id);

		$mapperPos = new Shop_Model_Mapper_OrderPos();
		$posList = $mapperPos->getList($id);
		$distribFacade = Distrib_Service_Facade::getInstance();
		$suppliers = $distribFacade->getSuppliers();

		if ($posList) {
			foreach ($posList as $pos) {
				if ($pos->supplier && $pos->analog) {
					$unit = $distribFacade->getUnitBySupplierAndAnalog($pos->supplier, $pos->analog);
					if ($unit) {
						$pos->unit = $unit;
					}
				} elseif ($pos->code) {
					$catalogUnitIds[] = (int) $pos->code;
				}
			}
		}

		$catalogFacade = CatCommon_Service_Facade::getInstance();
		$catalog = $catalogFacade->getCatalogBySite($order->site);

		if (is_array($catalogUnitIds)) {
			$availability = $catalogFacade->getAvailability($catalogUnitIds, $catalog->id);
			$shops = $catalogFacade->getShops();
		}


		include(LIBRARY_PATH . DIRECTORY_SEPARATOR . 'PHPExcel.php');
		$pExcel = new PHPExcel();
		$pExcel->setActiveSheetIndex(0);
		$aSheet = $pExcel->getActiveSheet();
		$aSheet->setTitle('Заказ №' . $order->number);

		$aSheet->getColumnDimension('A')->setWidth(17);
		$aSheet->getColumnDimension('B')->setWidth(60);
		$aSheet->getColumnDimension('C')->setWidth(23);
		$aSheet->getColumnDimension('D')->setWidth(17);
		$aSheet->getColumnDimension('E')->setWidth(12);
		$aSheet->getColumnDimension('F')->setWidth(18);
		$aSheet->getColumnDimension('G')->setWidth(12);
		$aSheet->getColumnDimension('H')->setWidth(9);
		$aSheet->getColumnDimension('I')->setWidth(12);

		$row = 1;
		// заголовок
		$style = Lv7CMS_Service_ExcelStyles::title1();
		$style = array_merge($style, Lv7CMS_Service_ExcelStyles::borderBottom(PHPExcel_Style_Border::BORDER_THICK, '4f81bd'));

		$aSheet->mergeCells('A' . $row . ':I' . $row);
		$aSheet->setCellValue('A' . $row, 'Заказ №' . $order->number);
		$aSheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($style);
		$aSheet->getRowDimension($row)->setRowHeight(35);


		$row++;
		// заголовок таблицы
		$style = Lv7CMS_Service_ExcelStyles::title1();
		$style = array_merge($style, Lv7CMS_Service_ExcelStyles::borderBottom());
		$aSheet->setCellValue('A' . $row, 'Код');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->setCellValue('B' . $row, 'Название');
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->setCellValue('C' . $row, 'Артикул');
		$aSheet->getStyle('C' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->setCellValue('D' . $row, 'Поставщик');
		$aSheet->getStyle('D' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->setCellValue('E' . $row, 'Цена поставщика');
		$aSheet->getStyle('E' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->setCellValue('F' . $row, 'Наличие');
		$aSheet->getStyle('F' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->setCellValue('G' . $row, 'Цена для клиента');
		$aSheet->getStyle('G' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->setCellValue('H' . $row, 'Кол-во');
		$aSheet->getStyle('H' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->setCellValue('I' . $row, 'Сумма');
		$aSheet->getStyle('I' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->getRowDimension($row)->setRowHeight(48);

		if ($posList) {
			foreach ($posList as $pos) {
				$row++;
				$aSheet->setCellValue('A' . $row, $pos->code ? $pos->code : '---');
				$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());
				$aSheet->setCellValue('B' . $row, $pos->name);
				$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());
				$aSheet->setCellValue('C' . $row, $pos->articul);
				$aSheet->getStyle('C' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());
				$aSheet->setCellValue('D' . $row, isset($suppliers[$pos->supplier]) ? $suppliers[$pos->supplier]->name : '');
				$aSheet->getStyle('D' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());
				$aSheet->setCellValue('E' . $row, $pos->unit ? $pos->unit->price : '');
				$aSheet->getStyle('E' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::price(0));
				if ($pos->unit->stock) {
					$aText = $pos->unit->stock . 'шт.';
				} elseif (is_array($a = $availability[(int)$pos->code])) {
					foreach ($a as $aData) {
						$shop = $shops[$aData->shop];
						$aText = $shop->short_name . ': ' . $aData->quantity . 'шт. ';
					}
				} else {
					$aText = '---';
				}
				$aSheet->setCellValue('F' . $row, $aText);
				$aSheet->getStyle('F' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::textCenter());
				$aSheet->setCellValue('G' . $row, $pos->price);
				$aSheet->getStyle('G' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::price(0));
				$aSheet->setCellValue('H' . $row, $pos->quantity);
				$aSheet->getStyle('H' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::number());
				$aSheet->setCellValue('I' . $row, $pos->price * $pos->quantity);
				$aSheet->getStyle('I' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::price(0));

				$aSheet->getRowDimension($row)->setRowHeight(20);

			}
		}

		$row++;
		$aSheet->mergeCells('A' . $row . ':H' . $row);
		$aSheet->setCellValue('A' . $row, 'Итого по товарам');
		$aSheet->getStyle('A' . $row . ':H' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());
		$aSheet->setCellValue('I' . $row, $order->params->goodsCost);
		$aSheet->getStyle('I' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::price(0));
		$aSheet->getRowDimension($row)->setRowHeight(24);

		$row++;
		$aSheet->mergeCells('A' . $row . ':H' . $row);
		$aSheet->setCellValue('A' . $row, 'Стоимость доставки');
		$aSheet->getStyle('A' . $row . ':H' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());
		$aSheet->setCellValue('I' . $row, $order->params->deliveryCost);
		$aSheet->getStyle('I' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::price(0));
		$aSheet->getRowDimension($row)->setRowHeight(24);

		$row++;
		$boldStyle['font']['bold'] = true;
		$aSheet->mergeCells('A' . $row . ':H' . $row);
		$aSheet->setCellValue('A' . $row, 'Итого');
		$aSheet->getStyle('A' . $row . ':H' . $row)->applyFromArray(array_merge_recursive(Lv7CMS_Service_ExcelStyles::text(), $boldStyle));
		$aSheet->setCellValue('I' . $row, $order->params->totalCost);
		$aSheet->getStyle('I' . $row)->applyFromArray(array_merge_recursive(Lv7CMS_Service_ExcelStyles::price(0), $boldStyle));
		$aSheet->getRowDimension($row)->setRowHeight(24);

		if ($order->type == Shop_Model_Mapper_OrderTypes::INNER) {
			$row++;
			$aSheet->mergeCells('A' . $row . ':H' . $row);
			$aSheet->setCellValue('A' . $row, 'Предоплата');
			$aSheet->getStyle('A' . $row . ':H' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());
			$aSheet->setCellValue('I' . $row, $order->params->prepaid);
			$aSheet->getStyle('I' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::price(0));
			$aSheet->getRowDimension($row)->setRowHeight(24);
		}

		$row += 2;
		// заголовок таблицы
		$style = Lv7CMS_Service_ExcelStyles::title1();
		$style = array_merge($style, Lv7CMS_Service_ExcelStyles::borderBottom());
		$aSheet->setCellValue('A' . $row, 'Параметр');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->setCellValue('B' . $row, 'Значение');
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::tableHeader1());
		$aSheet->getRowDimension($row)->setRowHeight(24);

		// данные
		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Дата заказа');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$date = new Zend_Date($order->created);
		$aSheet->setCellValue('B' . $row, $date->toString('KKKK'));
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());


		if (strlen($order->params->faceType)) {
			$row += 1;
			$aSheet->setCellValue('A' . $row, 'Способ оформления');
			$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
			$aSheet->setCellValue('B' . $row, ($order->params->faceType == 'natural') ? 'Физическое лицо' : 'Юридическое лицо');
			$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());
		}

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Контактное лицо');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $order->params->contactFace);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Паспортные данные');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $order->params->passport);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Адрес регистрации');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $order->params->address_register);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Телефон');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $order->params->phone);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'E-mail');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $order->params->email);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Адрес');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $order->params->address);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Способ доставки');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $order->params->deliveryName);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Способ оплаты');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $order->params->paymentMethodName);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Дисконтная карта');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $order->params->discountCard);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Дополнительные пожелания');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $order->params->comments);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$userMapper = new Users_Model_Mapper_User();
		$user = $userMapper->find($order->user);
		$userRealname = $user ? $user->getRealname() : '---';

		$row += 1;
		$aSheet->setCellValue('A' . $row, 'Пользователь');
		$aSheet->getStyle('A' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::descr());
		$aSheet->setCellValue('B' . $row, $userRealname);
		$aSheet->getStyle('B' . $row)->applyFromArray(Lv7CMS_Service_ExcelStyles::text());

		$objWriter = new PHPExcel_Writer_Excel5($pExcel);

		$this->getResponse()->setHeader('Content-type', 'application/vnd.ms-excel');
		$this->getResponse()->setHeader('Content-Disposition', 'attachment;filename="order-' . $order->number . '.xls"');
		$this->getResponse()->setHeader('Cache-Control', 'max-age=0');
		$objWriter->save('php://output');

		$orderManager = new Shop_Service_OrderManager();
		$currentUser = Zend_Registry::get('user');
		$orderManager->creteOrdersLog($order->id, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::DOWNLOAD_EXCEL);
	}

	public function xmlAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout();

		Zend_Registry::set('silentMode', true);

		$id = $this->_getParam('id');
		$mapper = new Shop_Model_Mapper_Orders();
		$order = $mapper->find($id);
		if (!$order) {
			throw new Lv7CMS_Exception('Заказ не найден!');
		}
		$orderManager = new Shop_Service_OrderManager();
		$xml = $orderManager->createXML($order);

		if ($_GET['kComand'] == 'add_to_lf'){ // Кирилл: менеджер нажимает в админке на кнопку LF: кидаем xml заказа в sql
			$db = $mapper->getDbAdapter();
			$dbConfig = $db->getConfig();
			$lfDoc = new Shop_Model_Mapper_LfDoc(); // образ класса			
			$lfDoc->insertXML($dbConfig, $id, $xml); // запись xml в sql

            // помечаем заказ как готовым для экспорта в LF
            Shop_Service_OrderManager::markForExport($id);

		} else { // end Кирилл			
			$this->getResponse()->setHeader('Content-type', 'application/xml');
			$this->getResponse()->setHeader('Content-Disposition', 'attachment;filename="order-' . $order->number . '.xml"');
			$this->getResponse()->setHeader('Cache-Control', 'max-age=0');
			echo $xml;
		
			$currentUser = Zend_Registry::get('user');
			$orderManager->creteOrdersLog($order->id, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::DOWNLOAD_XML);
		}
	}
	


	public function linkusersAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout();

		$ordersMapper = new Shop_Model_Mapper_Orders();
		$orders = $ordersMapper->getList();
		if (!$orders) {
			return;
		}
		$count = 0;
		$userFacade = Users_Service_Facade::getInstance();
		foreach ($orders as $order) {
			if ($order->user) {
				continue;
			}
			$email = $order->params->email;
			if (!$email) {
				continue;
			}
			$user = $userFacade->findByEmail($email);
			if (!$user) {
				continue;
			}
			$order->user = $user->id;
			$ordersMapper->update($order);
			$count++;
		}

		echo 'Связано заказов с пользователями: ' . $count;
	}

	public function sendtoclientAction()
	{
		$orderId = $this->_getParam('orderId');

		$ordersMapper = new Shop_Model_Mapper_Orders();
		$order = $ordersMapper->find($orderId);

		$orderManager = new Shop_Service_OrderManager();
		$params = $orderManager->getOrderTemplateParams($order);

		$form = new Shop_Form_Mail();

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($this->getRequest()->getPost())) {
				$mailText = $this->_getParam('mailText');

				$orderManager->sendOrderMailToClient($order, $mailText);

				$this->_flashMessenger->addMessage('Письмо отправлено клиенту!|success');

				$currentUser = Zend_Registry::get('user');
				$orderManager->creteOrdersLog($order->id, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::SEND_EMAIL);

				$this->_helper->redirector->gotoRouteAndExit(array('id' => $orderId), 'shop::adminOrder');

			}
		} else {
			$mailText = $orderManager->getOrderInfoForClient($order, $params);
			$form->setDefault('mailText', $mailText);
		}
		$this->view->form = $form;
		$this->view->clientEmail = $order->params->email;
		$this->view->managerEmail = $params['MANAGER_MAIL'];
		$this->view->orderNumber = $order->number;

		$repository = new Lv7CMS_Resource_Repository($order->site);
		$cmsSettings = $repository->findAll(Lv7CMS_Resource_Types::SETTINGS);
		$this->view->subject = 'Заказ #' . $order->number . ' на сайте ' . $cmsSettings['defaultSiteName']->getValue();
	}

	public function viewOrderEmailAction()
	{
		$orderId = $this->_getParam('orderId');
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$this->view->order = $ordersMapper->find($orderId);
	}

    public function printdocAction() // Кирилл: вывод на печать договора по иномаркам (NEW LF BUTTON)
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo Shop_Service_Docs::clientContractOfSale($this->_getParam('orderId'));
    }

	public function printAction()
	{
		$this->_helper->layout->disableLayout();



		$orderId = $this->_getParam('id');
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$order = $ordersMapper->find($orderId);
		$orderManager = new Shop_Service_OrderManager();
		
		if ($_GET['kComand'] == 'add_to_lf'){ // Кирилл: генерация word договора
			$params = $orderManager->getOrderInfoForManager($orderId);
			$db = $ordersMapper->getDbAdapter();
			$dbConfig = $db->getConfig();
			
			$get = array(
				'orderId' => $orderId,
				'prepaid' => $_GET['prepaid'],
				'manager' => $_GET['manager'],
				'address_register' => $_GET['address_register']
			);
			
			$lfDoc = new Shop_Model_Mapper_LfDoc(); // образ класса
			$wordDocData = $lfDoc->dataDoc($dbConfig, $params, $get); // собираем в массив динамические данные для договора							
			$lfDoc->showWord($wordDocData); // вывод word документа		end Кирилл									
		} else {
			$this->view->orderInfo = $orderManager->getOrderInfoForManager($orderId);
			$currentUser = Zend_Registry::get('user');
			$orderManager->creteOrdersLog($order->id, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::PRINTS);				
		}
	}

	public function sendtosuppliersAction()
	{
		$orderId = $this->_getParam('id');
		$supplierIds = $this->_getParam('suppliers');

        $orderManager = new Shop_Service_OrderManager();
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$order = $ordersMapper->find($orderId);

        $orderManager->sendToSuppliersByIds($orderId, $supplierIds);

        $currentUser = Zend_Registry::get('user');
		$orderManager->creteOrdersLog($order->id, $currentUser->id, $order->site, Shop_Model_Mapper_OrdersLogTypes::SEND_SUPPLIERS);
		$orderManager->updateOrder($orderId);

		$this->_flashMessenger->addMessage((count($supplierIds) > 1) ? 'Письма отправлены поставщикам!|success' : 'Письмо отправлено поставщику!|success');

		$this->_helper->redirector->gotoRouteAndExit(array('id' => $orderId), 'shop::adminOrder');
	}

	public function sendtosuppliersgoodstableAction()
	{
		$this->view->posList = $this->_getParam('supplierOrderPos');

		//$orderPosMapper = new Shop_Model_Mapper_OrderPos();
		//$this->view->posList = $orderPosMapper->findByIds($posIds);
	}

	public function setOrderPosSupplierAction()
	{
		$orderId = $this->_getParam('order');
		$posId = $this->_getParam('pos');
		$supplierId = $this->_getParam('supplier');

		//$ordersMapper = new Shop_Model_Mapper_Orders();
		$orderPosMapper = new Shop_Model_Mapper_OrderPos();

		$pos = $orderPosMapper->find($posId);
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$order = $ordersMapper->find($orderId);

		$distribFacade = Distrib_Service_Facade::getInstance();
		$unit = $distribFacade->getUnitBySupplierAndAnalog($supplierId, $pos->analog);
		if ($unit) {
			$pos->supplier = $supplierId;
			$pos->price_purchase = $unit->price;
			$pos->status = Shop_Model_Mapper_OrderPosStatus::UNORDERED;
			$orderPosMapper->update($pos);
			$addPositions[] = $pos;
			$this->_flashMessenger->addMessage('Поставщик успешно изменен!|success');
		} else {
			$this->_flashMessenger->addMessage('Позиция не найдена!|error');
		}
        if ($order->params->paymentMethod == 'NonCash' && $addPositions) {
        	$orderManager = new Shop_Service_OrderManager();
        	$sendOrder = $orderManager->PostProcessing($order, $addPositions);
        }
		$this->_helper->redirector->gotoRouteAndExit(array('id' => $orderId), 'shop::adminOrder');
	}

	protected function _getConditions()
	{
		$conditions = array();

		$scheme = Shop_Service_Scheme_Factory::getSchemeInstance('Default');

		$deliveries = $scheme->getDeliveries();
		if ($deliveries) {
			foreach ($deliveries as $delivery) {
				$deliveryAll[$delivery->getId()] = $delivery->getId();
			}
		}


		$paymentMethods = $scheme->getPaymentMethods();
		if ($paymentMethods) {
			foreach ($paymentMethods as $paymentMethod) {
				$paymentMethodAll[$paymentMethod->getId()] = $paymentMethod->getId();
			}
		}

		if (!empty($this->_filter->fDelivery[0])) {
			$conditions[] = array(
					'key' => 'delivery',
					'value' => $this->_filter->fDelivery,
					'op' => 'IN'
			);
		} else {
			$conditions[] = array(
				'key' => 'delivery',
				'value' => $deliveryAll,
				'op' => 'IN'
			);
		}

		if (!empty($this->_filter->fPaymentMethod[0])) {
			$conditions[] = array(
					'key' => 'paymentMethod',
					'value' => $this->_filter->fPaymentMethod,
					'op' => 'IN'
			);
		} else {
			$conditions[] = array(
				'key' => 'paymentMethod',
				'value' => $paymentMethodAll,
				'op' => 'IN'
			);
		}

		/*if ($this->_filter->fDelivery) {
			$conditions[] = array(
				'key' => 'delivery',
				'value' => $this->_filter->fDelivery
			);
		}
		if ($this->_filter->fPaymentMethod) {
			$conditions[] = array(
				'key' => 'paymentMethod',
				'value' => $this->_filter->fPaymentMethod
			);
		}*/
		if ($this->_filter->fDateAfter) {
			$date = new Zend_Date($this->_filter->fDateAfter);
			$date->setTime('00:00:00');
			$conditions[] = array(
				'key' => 'created',
				'value' => Lv7_Service_Datetime::ZendDateToClearAtom($date),
				'op' => '>='
			);
		}
		if ($this->_filter->fDateBefore) {
			$date = new Zend_Date($this->_filter->fDateBefore);
			$date->setTime('23:59:59');
			$conditions[] = array(
				'key' => 'created',
				'value' => Lv7_Service_Datetime::ZendDateToClearAtom($date),
				'op' => '<='
			);
		}
		$accessibleSites = $this->_getAccessibleSite();
		$selectedSite = array();
		if ($accessibleSites) {
			foreach ($accessibleSites as $site) {
				$selectedSite[$site->id] = $site->id;
			}
		}

		if (!empty($this->_filter->fSite[0])) {
			$conditions[] = array(
					'key' => 'site',
					'value' => $this->_filter->fSite,
					'op' => 'IN'
			);
		} else {
			$conditions[] = array(
				'key' => 'site',
				'value' => $selectedSite,
				'op' => 'IN'
			);
		}

		$orderTypesAll = new Shop_Model_Mapper_OrderTypes();
		$orderType = $orderTypesAll->getList();
		$selectedType = array();
		if ($orderType) {
			foreach ($orderType as $key) {
				$selectedType[$key->id] = $key->id;
			}
		}

		$orderPayStatusAll = new Finance_Model_PaymentInstructionStatus();
		$orderPayStatus = $orderPayStatusAll->getList();
		$selectedStatus = array();

		if ($orderPayStatus) {
			foreach ($orderPayStatus as $key => $name) {
				$selectedStatus[$key] = $key;
			}
		}

		$shopsAll = new CatCommon_Model_Mapper_Shops();
		$shops = $shopsAll->getList();
		$selectedShop = array();
		if ($shops) {
			foreach ($shops as $key) {
				$selectedShop[$key->id] = $key->id;
			}
		}

		if (!empty($this->_filter->fShop[0])) {
			$conditions[] = array(
					'key' => 'shop',
					'value' => $this->_filter->fShop,
					'op' => 'IN'
			);
		} else {
			$conditions[] = array(
				'key' => 'shop',
				'value' => $selectedShop,
				'op' => 'IN'
			);
		}

        if (!empty($this->_filter->fTerritory[0])) {
            $conditions[] = array(
                'key' => 'territory_id',
                'value' => $this->_filter->fTerritory,
                'op' => 'IN'
            );
        } else {
            /*
            $conditions[] = array(
                'key' => 'territory',
                'value' => array_keys(Territories::asOptions()),
                'op' => 'IN'
            );
            */
        }

		if (!empty($this->_filter->fType[0])) {
			$conditions[] = array(
					'key' => 'type',
					'value' => $this->_filter->fType,
					'op' => 'IN'
			);
		} else {
			$conditions[] = array(
				'key' => 'type',
				'value' => $selectedType,
				'op' => 'IN'
			);
		}

		if (!empty($this->_filter->fPaymentStatus[0])) {
			$conditions[] = array(
					'key' => 'status',
					'value' => $this->_filter->fPaymentStatus,
					'of' => 'IN'
			);
		} else {
			/*$conditions[] = array(
				'key' => 'status',
				'value' => $selectedStatus,
				'of' => 'IN'
			);*/
		}

		if ($this->_filter->fManager) {
			$conditions[] = array(
				'key' => 'manager',
				'value' => $this->_filter->fManager
			);
		}

		if ($this->_filter->fStatus) {
			$conditions[] = array(
				'key' => 'status',
				'value' => $this->_filter->fStatus,
				'op' => 'IN'
			);
		}

		if ($this->_filter->fDocType) {
			$conditions[] = array(
				'key' => 'doc_type',
				'value' => $this->_filter->fDocType,
				'op' => 'IN'
			);
		}

		/*$currentProfile = Distrib_Service_Facade::getInstance()->getCurrentProfile();
		if ($currentProfile && $currentProfile->shop) {
			$conditions[] = array(
				'key' => 'shop',
				'value' => $currentProfile->shop
			);
		}*/

		return $conditions;
	}

	protected function _getAccessibleSite()
	{
		return Lv7CMS_Acl_Service::getAccessibleSites('shopOrderView', null, 'Shop');
	}

    protected function _getFilterDefault($filterField)
    {
        $arrayFilters = [
            'fDelivery',
            'fPaymentMethod',
            'fSite',
            'fType',
            'fStatus',
            'fDocType',
            'fPaymentStatus',
            'fTerritory',
        ];
        if ($filterField == 'fDateAfter') {
            $day = new Zend_Date();
            $day->addMonth(-1);
            return Lv7_Service_Datetime::ZendDateToDDMMYYYY($day);
        } else if ($filterField == 'fDateBefore') {
            return '';
        }
        return in_array($filterField, $arrayFilters) ? [] : 0;
    }

}