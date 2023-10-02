<?php

	/*-------------------------------------------------------
	|
	|	ФУНКЦИОНАЛ ПОСТОЯННОГО ХРАНЕНИЯ ТОВАРОВ В КОРЗИНЕ, 12.04.2023
	|	Создал sql таблицу `shop_basket` и храню в ней информацию о товарах в коризне.
	|	При операциях: "добавить", "изменить кол-во", "удалить", "удалить после заказа" - синхронизировал действия между кеш-корзиной и sql-корзиной.		
	|	Кеш-корзина проверяется от sql-корзины в трех местах:
	|		- главная траница (счтечик корзины в верхнем правом углу)
	|		- каталог товаров + карточка товара
	|		- страница корзины
	|	Таким образом товары из кеш-корзины никогда сами не исчзают	
	|
	*/

class Shop_Model_Mapper_BasketSave extends Lv7CMS_Mapper_Abstract
{
	protected $_domainName = 'Shop_Model_BasketSave';
	protected $_tableName = 'Shop_Model_DbTable_BasketSave';


	public function basketDelete($data) // Удалить из sql-корзины: 1) по собственному желанию пользователя на странице корзины; 2) после заказа
	{
		$result = $this->_table->basketDelete($data);
		return $result;
	}

	public function getUserUid() // Достать user_uid из куки, к которому в sql привязываются товары в sql-корзине
	{
		return $_COOKIE['user_uid'];
	}

	public function basketAdd($data, $user_uid) // Добавить товар в sql-корзину
	{
		$result = $this->_table->basketAdd($data, $user_uid);
		return $result;
	}

	public function basketUpdate($data, $where) // Поменять количество товара в sql-корзине
	{
		$result = $this->_table->basketUpdate($data, $where);
		return $result;
	}

	public function basketSelect($user_uid) // Выгрузить товары из sql-корзины
	{
		$result = $this->_table->basketSelect($user_uid);
		return $result;
	}
	
	public function basketGetTecdoc($product_id) // Получение информации о товаре с tecdoc
	{
		$result = $this->_table->basketGetTecdoc($product_id);
		return $result;
	}
	
	public function basketGetSupplierPrice($supplier, $price) // Получение цен товаров от поставщиков ставится процентное соотношение
	{
		$result = $this->_table->basketGetSupplierPrice($supplier, $price);
		return $result;
	}	
	
	public function basketRecovery() // САМОЕ ГЛАВНОЕ: восстановление кеш-корзины в случае исчезновения из кеша
	{
		if (stripos($_SERVER['REQUEST_URI'], 'pay/final') === false) {
			$basket = Shop_Service_Config::getScheme()->getBasket(); // Образ класса кеш-корзины
			if ($this->getUserUid() !== null){ // Если первый раз в жизни зашел на сайт, то блокируем восстановление кеш-корзины вообще		
				$catalogService = new CatCommon_Service_Catalog(); // Образ класса для вычисления товара в каталоге
				$siteId = Lv7CMS::getInstance()->getSiteId();
				$urlService = Catalog_Service_UrlFactory::getInstance()->getService($siteId); // Объект для получения url товара
				$catalog = $catalogService->getCatalogBySite($siteId);
				$unitsMapper = Catalog_Model_Mapper_Manager::getInstance()->units($catalog->id, true);					
				$myBasketSQL = $this->basketSelect($this->getUserUid()); // Получаем данные из sql-корзины			
				$items = $basket->getItems(); // Получаем хоть какие-нибудь имеющиеся товары в кеш-корзине (при исчезновении конечо там никаких товаров нет, но эта переменная используется для того когда пользователь кладет товар в корзину после исчезновения кеша, но до обновления страницы (вчера оставил каталог с товарами в браузере, а сегодня решил продолжить класть в корзину без перезагрузки) )
				if (count($items) != count($myBasketSQL)){ // Только лишь в том случае если количество sql-корзины и кеш-корзины не совпадают (количество sql всегда актуально, а кеш - может исчезнуть). Значит запускаем восстановление в случае исчезновения кеша							
					foreach ($myBasketSQL as $k){										
						// Если в кеш-корзине уже есть такой product_id, то блокируем восстановление для этого товара в кеш-корзине
						$d = 0;
						foreach ($items as $item){
							if ($k->product_id == $item->getId()) $d++;
						}
						if ($d == 0){
							$success = 0; // Датчик - 1 если товар с таким id реально существует	
							$unit = $unitsMapper->find($k->product_id); // Достаем товар из каталога по product_id
							$basketItem = Shop_Service_Facade::getInstance()->createBasketItem(); // Инициируем создание товара в кеш-корзине
							if ($unit-id > 0){ // Для нормальных товаров с нормальным LF кодом и для товаров под заказ (короче все нормальные товары, которые находятся в поиске на сайте по коду)							
								$success = 1;
								$basketItem->setId(intval($unit->id));
								$basketItem->setCode($unit->code);
								$basketItem->setArticul($unit->articul);
								$basketItem->setName($unit->name);
								$basketItem->setPrice(round($unit->price, 1));
								$basketItem->setItemUrl($urlService->getUnitUrl($unit));
								
								$basketItem->setSupplier($unit->supplier);								
								$basketItem->setStock($unit->getStock());								
							} else { // Для товаров по предзаказу (553180678 / 1879001109)
								$unit = $this->basketGetTecdoc($k->product_id); // Получение информации о товаре от поставщиков
								if ($unit['number'] > 0){
									$price = $this->basketGetSupplierPrice($unit['supplier'], $unit['price']); // Для цен товаров от поставщиков ставится процентное соотношение
									$success = 1;
									$basketItem->setType(Shop_Model_Mapper_OrderPosTypes::SUPPLIERS); // Меняем тип инициируемого создания товара в кеш-корзине под тип "от поставщиков" 
									$basketItem->setId($k->product_id);
									$basketItem->setArticul(''.$unit['name'] . ' ' . $unit['number'].'');
									$basketItem->setName(''.$unit[1].'');
									$basketItem->setPrice($price); // У товаров от поставщиков есть накрутка цен в два раза больше
									$basketItem->setManBrand(''.$unit['name'].'');
									$basketItem->setManNumber($unit['number']);			

									$basketItem->setAnalog($unit['analog']);	
									$basketItem->setSupplier($unit['supplier']);	
									$basketItem->setSale($unit['sale']);	
									$basketItem->setPricePurchase($unit['price']);	
									$basketItem->setMinOrder($unit['min_order']);	
									$basketItem->setStock($unit['stock']);									
								}
							}
							if ($success == 1){ // Если такой товар действительно существует
								Shop_Service_Facade::getInstance()->getBasketForm_BasketSave($basketItem, !$inStock); // Инициируем карточку товара
								$basket->change($basketItem->getUid(), $k->product_count); // Непосредственно кладем в кеш-корзину
								
								// Если товар в корзине восстанавливается на странице оформления заказа - то перезагрузить страницу после восстановления (чтобы поставились нужные radio)
								if ($_SERVER['REQUEST_URI'] == '/shop/checkout') header("Location: ".((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']."");
								
							} else { // Если не нашел такой товар, то удаляем из sql-корзины
								$data = array(				
									'product_id = ?' => $k->product_id,
									'user_uid = ?' => $this->getUserUid()
								);
								$this->basketDelete($data);
							}
						}
					}
				}
			}
		} else {
			$data = array(
				'user_uid = ?' => $this->getUserUid()
			);
			$this->basketDelete($data);	// После оплаты заказа по карте - удаляем sql-корзину		
		}
	}




}