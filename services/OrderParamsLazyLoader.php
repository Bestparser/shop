<?php

class Shop_Service_OrderParamsLazyLoader extends Lv7_Service_LazyLoader
{
	
	public function load(Lv7_Entity_Abstract $entity)
	{
		$loader = new Lv7_Service_EntityOptions(new Shop_Model_Mapper_OrderParams());
		return $loader->getValues((int)$entity->id);
	}
	
}