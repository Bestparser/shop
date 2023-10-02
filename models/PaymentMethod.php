<?php

abstract class Shop_Model_PaymentMethod
{
	
	public function getId()
	{
		return substr(get_class($this), 25);
	}
	
	public function getName()
	{
		return '';
	}
	
	public function getDescr()
	{
		return '';
	}
	
}