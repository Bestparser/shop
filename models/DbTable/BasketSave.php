<?php

class Shop_Model_DbTable_BasketSave extends Zend_Db_Table_Abstract
{
	protected $_name = 'shop_basket';
	protected $_primary = 'id';

	public function basketDelete($data) // Удалить из sql-корзины: 1) по собственному желанию пользователя на странице корзины; 2) после заказа
	{
		return $this->delete($data);
	}

	public function basketAdd($data, $user_uid) // Добавить товар в sql-корзину
	{
		$d = 0;
		foreach ($this->basketSelect($user_uid) as $k){
			if ($data['product_id'] == $k->product_id) $d++;
		}
		if ($d == 0) $this->insert($data);
	}

	public function basketUpdate($data, $where) // Поменять количество товара в sql-корзине
	{
		if (count($where) == 1){
			$_where = $this->_db->quoteInto($where);
		} else {			
			$key = array_keys($where);
			$_where = $this->_db->quoteInto($key[0], $where[''.$key[0].'']);
			$_where .= ' AND ';
			$_where .= $this->_db->quoteInto($key[1], $where[''.$key[1].'']);
		}
		$this->update($data, $_where);
	}

	public function basketSelect($user_uid) // Выгрузить товары из sql-корзины
	{	
		$select = $this->select();
		$select->where('user_uid = ?', $user_uid);	
		return $this->fetchAll($select);			
	}	
	
	public function basketGetTecdoc($product_id) // Получение информации о товаре с tecdoc
	{	
		$dbConfig = $this->_db->getConfig();		
		$link = mysqli_connect($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['dbname']) or die ('Error connect db');
		mysqli_query($link, 'SET NAMES utf8');
		mysqli_query($link, 'SET CHARACTER SET utf8');
	
		$query = "SELECT 
			`distrib_unit`.`id`,
			`distrib_unit`.`name`,
			`distrib_unit`.`analog`,
			`distrib_unit`.`price`,
			`distrib_unit`.`supplier`,
			`distrib_unit`.`sale`,
			`distrib_unit`.`min_order`,
			`distrib_unit`.`stock`,
			`autoparts_article`.`id`,
			`autoparts_article`.`number`,
			`autoparts_article`.`brand`,
			`autoparts_brand`.`id`,
			`autoparts_brand`.`name`
			FROM `distrib_unit` 
			INNER JOIN `autoparts_article` ON `distrib_unit`.`analog` = `autoparts_article`.`id` 
			INNER JOIN `autoparts_brand` ON `autoparts_brand`.`id` = `autoparts_article`.`brand` 
			WHERE `distrib_unit`.`id` = '".$product_id."' ";	
		$result = mysqli_query($link, $query);
		return mysqli_fetch_array($result);		
	}	
	
	public function basketGetSupplierPrice($supplier, $price) // Получение цен товаров от поставщиков ставится процентное соотношение
	{	
		$dbConfig = $this->_db->getConfig();		
		$link = mysqli_connect($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['dbname']) or die ('Error connect db');
		mysqli_query($link, 'SET NAMES utf8');
		mysqli_query($link, 'SET CHARACTER SET utf8');
	
		
		if ($price < 100) $percent = 1;
		if (($price >= 100) and ($price < 1000)) $percent = 2;
		if (($price >= 1000) and ($price < 5000)) $percent = 3;
		if (($price >= 5000) and ($price < 10000)) $percent = 4;
		if (($price >= 10000) and ($price <= 30000)) $percent = 5;
		if ($price > 30000) $percent = 20;

		$query = "SELECT `id`, `markup_".$percent."` FROM `distrib_supplier` WHERE `id` = '".$supplier."' ";	
		$result = mysqli_query($link, $query);
		
		$price = round($price + $price / 100 * mysqli_fetch_assoc(mysqli_query($link, $query))['markup_'.$percent.'']);

		return $price;
	}
	
	
	
}
