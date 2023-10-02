<?php

use Modules\Api\Services\CollectionBroker;

class Shop_Module extends Lv7CMS_Module_Abstract
{

    public function init()
    {
		if (class_exists('ExternalObjects_Service_Broker')) {
			ExternalObjects_Service_Broker::register('shop_order', 'Shop_Service_ExternalObject_Order');
		}

        CollectionBroker::register(Modules\Shop\Api\ExternalCollection::class);
        CollectionBroker::register(Modules\Shop\Api\BackofficeCollection::class);
    }

	public function getRoutes()
	{
        $routes = array();

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::basket',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'shop/basket',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'basket',
				'action' => 'index',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::checkoutDefault',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'shop/checkout',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'checkout',
				'action' => 'index',
			)
		));

        $routes[] = $this->_resourceItemFactory->createRoute(array( // Кирилл YandexDelivery: подключаем url, по которому через ajax подрубаем яндекс такси
            'id' => 'shop::yandexdelivery',
            'type' => 'Zend_Controller_Router_Route',
            'route' => 'shop/checkout/yandexdelivery/:process',
            'defaults' => array(
                'module' => 'shop',
                'controller' => 'checkout',
                'action' => 'yandex',
            )
        ));
		
        $routes[] = $this->_resourceItemFactory->createRoute(array( // Кирилл кнопка LF (new) - вывод на печать договора по иномаркам
            'id' => 'shop::adminOrderPrintDoc',
            'type' => 'Zend_Controller_Router_Route',
            'route' => 'admin/shop/orders/printdoc/:orderId',
            'defaults' => array(
                'module' => 'shop',
                'controller' => 'orders',
                'action' => 'printdoc',
            )
        ));				

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::acceptDefault',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'shop/orderaccept',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'accept',
				'action' => 'index',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::orderInvoice',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'shop/orderInvoice/:orderId',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'accept',
				'action' => 'invoice',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::settings',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'admin/shop/settings',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'settings',
				'action' => 'index',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::settingsForm',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/settings/form/:scheme',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'settings',
				'action' => 'form',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrders',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/orders',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'index',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrder',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/order/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'form',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderLoad',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/order/load/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'form',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderDelete',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/delete/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'delete',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderAsExcel',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/orderAsExcel/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'excel',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderAsXml',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/orderAsXml/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'xml',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderPosUpdate',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/order/pos/update/:orderId',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'posupdate',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderPosDelete',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/order/pos/delete/:orderId/:posId',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'posdelete',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderUpdate',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/order/update/:orderId',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'orderupdate',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderSave',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/order/save/:orderId',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'ordersave',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminReturnSave',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/order/return/:orderId',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'orderreturn',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderPosBySupplier',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/orderpos/bySupplier/:supplierId',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'bysupplier',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderSendToClient',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/order/:orderId/sendToClient',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'sendtoclient',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderEmailView',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/order/:orderId/viewEmail',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'view-order-email',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderPaymentSendToClient',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/orderPayment/:orderId/sendToClient',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'send-order-payment-to-client',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::adminOrderPaymentEmailView',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/orderPayment/:orderId/viewEmail',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'view-order-payment-email',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::userOrders',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'personal/orders/:page',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'user',
				'action' => 'orders',
				'page' => '1',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::userOrder',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'personal/order/:page/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'user',
				'action' => 'order',
				'page' => '1',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::personalOrders',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'personal/myOrders/:page',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'personal',
				'action' => 'orders',
				'page' => '1',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::personalOrder',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'personal/orderDetails/:page/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'personal',
				'action' => 'order',
				'page' => '1',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::personalPresets',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'personal/presets',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'personal',
				'action' => 'presets',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::personalPresetDelete',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'personal/presets/delete/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'personal',
				'action' => 'presetdelete',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::backendBasket',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/basket',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'backendbasket',
				'action' => 'index',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::backendCreateInnerOrder',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/basket/createOrder',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'backendbasket',
				'action' => 'createorder',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::orderDocTypes',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'admin/shop/orderDocTypes',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'order-doc-types',
				'action' => 'index',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::orderDocTypesForm',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/orderDocTypes/form/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'order-doc-types',
				'action' => 'form',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::orderDocTypesDelete',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/orderDocTypes/delete/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'order-doc-types',
				'action' => 'delete',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::orderDocTypesData',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'admin/shop/orderDocTypes/data',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'order-doc-types',
				'action' => 'data',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::multKeys',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'admin/shop/multKeys',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'mult-keys',
				'action' => 'index',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::multKeysForm',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/multKeys/form/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'mult-keys',
				'action' => 'form',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::multKeysDelete',
			'type' => 'Zend_Controller_Router_Route',
			'route' => 'admin/shop/multKeys/delete/:id',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'mult-keys',
				'action' => 'delete',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::multKeysData',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'admin/shop/multKeys/data',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'mult-keys',
				'action' => 'data',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::unitsInWay',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'admin/shop/unitsInWay',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'in-way',
				'action' => 'index',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::unitsInWayData',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'admin/shop/unitsInWay/data',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'in-way',
				'action' => 'data',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::exportCards',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'shop/exportcards',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders',
				'action' => 'exportcards',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::ordersLog',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'admin/shop/orderslog',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders-log',
				'action' => 'index',
			)
		));

		$routes[] = $this->_resourceItemFactory->createRoute(array(
			'id' => 'shop::ordersLogData',
			'type' => 'Zend_Controller_Router_Route_Static',
			'route' => 'admin/shop/orderslog/data',
			'defaults' => array(
				'module' => 'shop',
				'controller' => 'orders-log',
				'action' => 'data',
			)
		));

		return $routes;
	}

    public function getEntryPoints()
    {
        $points = array();

        $points[] = $this->_resourceItemFactory->createEntryPoint(array(
        	'id' => 'shop::settings',
        	'routeId' => 'shop::settings',
        	'label' => 'Настройки магазина',
        ));

        $points[] = $this->_resourceItemFactory->createEntryPoint(array(
        	'id' => 'shop::adminOrders',
        	'routeId' => 'shop::adminOrders',
        	'label' => 'Заказы',
        ));

        $points[] = $this->_resourceItemFactory->createEntryPoint(array(
        	'id' => 'shop::personalOrders',
        	'routeId' => 'shop::personalOrders',
        	'label' => 'personal: Заказы',
        ));

        $points[] = $this->_resourceItemFactory->createEntryPoint(array(
        	'id' => 'shop::personalPresets',
        	'routeId' => 'shop::personalPresets',
        	'label' => 'personal: Адреса доставки',
        ));

        $points[] = $this->_resourceItemFactory->createEntryPoint(array(
        	'id' => 'shop::orderDocTypes',
        	'routeId' => 'shop::orderDocTypes',
        	'label' => 'Настройка типов документов',
        ));

        $points[] = $this->_resourceItemFactory->createEntryPoint(array(
        	'id' => 'shop::multKeys',
        	'routeId' => 'shop::multKeys',
        	'label' => 'Настройка ключевых слов для кратности',
        ));

        $points[] = $this->_resourceItemFactory->createEntryPoint(array(
        	'id' => 'shop::unitsInWay',
        	'routeId' => 'shop::unitsInWay',
        	'label' => 'Список товаров В пути',
        ));

        return $points;
    }

    public function getTextTemplates()
    {
		$templates = array();


		$templates[] = $this->_resourceItemFactory->createTextTemplate(array(
			'id' => 'shopMailClient',
			'template' => '',
			'name' => 'Письмо для Клиента',
			'context' => 'html',
			'category' => 'Письма заказов'
		));

        $templates[] = $this->_resourceItemFactory->createTextTemplate(array(
            'id' => 'shopSmsClient',
            'template' => '',
            'name' => 'Смс для Клиента',
            'context' => 'text',
            'category' => 'Сообщения'
        ));

		$templates[] = $this->_resourceItemFactory->createTextTemplate(array(
			'id' => 'shopMailClientToPayment',
			'template' => '',
			'name' => 'Письмо Клиенту на оплату',
			'context' => 'html',
			'category' => 'Письма заказов'
		));

		$templates[] = $this->_resourceItemFactory->createTextTemplate(array(
			'id' => 'shopMailManager',
			'template' => '',
			'name' => 'Письмо для Менеджера',
			'context' => 'html',
			'category' => 'Письма заказов'
		));

		$templates[] = $this->_resourceItemFactory->createTextTemplate(array(
			'id' => 'shopOrderAccept',
			'template' => '<h3>Спасибо за Ваш заказ!</h3><p>Вашему заказу присвоен номер %ORDER_NUMBER%, в ближайшее время наш менеджер свяжется с Вами!</p>',
			'name' => 'Заказ оформлен',
			'context' => 'html',
			'category' => 'Сообщения'
		));

		$templates[] = $this->_resourceItemFactory->createTextTemplate(array(
			'id' => 'shopOrderError',
			'template' => 'При оформлении заказа произошла ошибка! Пожалуйста, обратитесь к администрации, координаты указаны на странице "Контакты"',
			'name' => 'Ошибка при оформлении заказ',
			'context' => 'html',
			'category' => 'Сообщения'
		));

		$templates[] = $this->_resourceItemFactory->createTextTemplate(array(
			'id' => 'shopAuthorize',
			'template' => '<p>Если вы уже зарегистрированны на нашем сайте, <a class="js-action" href="/login" id="shop_auth_link">авторизуйтесь</a>, чтобы не заполнять форму заказа повторно. Если нет - вы можете <a href="/register">зарегистрироваться</a> на сайте, чтобы при следующих заказах сэкономить время на оформлении заказа.</p>',
			'name' => 'Предложение авторизоваться',
			'context' => 'html',
			'category' => 'Сообщения'
		));

		$templates[] = $this->_resourceItemFactory->createTextTemplate(array(
			'id' => 'shopInvoiceIntro',
			'template' => '',
			'name' => 'Приветствие на странице инвойса',
			'context' => 'html',
			'category' => 'Сообщения'
		));

		$templates[] = $this->_resourceItemFactory->createTextTemplate(array(
			'id' => 'shopCheckoutPaymentText',
			'template' => '',
			'name' => 'Текст про оплату картой на странице оформления заказа',
			'context' => 'html',
			'category' => 'Сообщения'
		));

		$templates[] = $this->_resourceItemFactory->createTextTemplate(array(
			'id' => 'shopCheckoutBankPaymentOnly',
			'template' => '',
			'name' => 'Текст про необходимость оплаты банковской картой',
			'context' => 'text',
			'category' => 'Сообщения'
		));

		$templates[] = $this->_resourceItemFactory->createTextTemplate(array(
			'id' => 'shopPaymentBnplInfo',
			'template' => 'Оплата товара «долями». Услугу предоставляет банк «Тинькофф». Максимальная сумма — 10 000 рублей.<br>
Оплата покупки делится на четыре части: 25% надо оплатить сразу, оставшиеся три части спишутся с карты автоматически с интервалом в две недели. Это не кредит: процентная ставка и комиссия не взимаются. Сервис не влияет на вашу кредитную историю.<br>
Доступно для всех граждан РФ старше 18 лет. Операции проводятся с соблюдением всех требований безопасности платежных систем. Платежные данные защищены по стандарту PCI DSS.',
			'name' => 'Информация по оплате «Долями»',
			'context' => 'html',
			'category' => 'Сообщения'
		));




		return $templates;
    }

    public function getAclResources()
    {
        $resources = array();

        $resources[] = $this->_resourceItemFactory->createAclResource(array(
        	'id' => 'shopSettings',
        	'label' => 'Управление настройками магазина',
        	'bySite' => true
        ));

        $resources[] = $this->_resourceItemFactory->createAclResource(array(
        	'id' => 'shopOrders',
        	'label' => 'Доступ к заказам',
        ));

        $resources[] = $this->_resourceItemFactory->createAclResource(array(
        	'id' => 'shopOrderView',
        	'label' => 'Просмотр заказов магазина',
        	'bySite' => true
        ));

        $resources[] = $this->_resourceItemFactory->createAclResource(array(
        	'id' => 'shopOrderViewNormal',
        	'label' => 'Доступ к обычным заказам (со склада)',
        ));

        $resources[] = $this->_resourceItemFactory->createAclResource(array(
        	'id' => 'shopOrderViewDistrib',
        	'label' => 'Доступ к заказам с товарами от поставщиков',
        ));

        $resources[] = $this->_resourceItemFactory->createAclResource(array(
        	'id' => 'shopOrderViewInner',
        	'label' => 'Доступ к внутренним заказам',
        ));

        $resources[] = $this->_resourceItemFactory->createAclResource(array(
        	'id' => 'shopOrderViewVin',
        	'label' => 'Доступ к заказам на основе заявок по VIN',
        ));

        $resources[] = $this->_resourceItemFactory->createAclResource(array(
        	'id' => 'shopOrderHall',
        	'label' => 'Доступ к заказам их зала',
        ));

        $resources[] = $this->_resourceItemFactory->createAclResource(array(
        	'id' => 'shopMultKeys',
        	'label' => 'Доступ к справочнику кратности',
        ));

        return $resources;
    }


}





