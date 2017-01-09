<?php
/**
 *
 * Test extention - Child 1. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2016, javiexin
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace javiexin\exttestchild1;

/**
 * Test extention - Child 1 Extension base
 *
 * It is recommended to remove this file from
 * an extension if it is not going to be used.
 */
class ext extends \phpbb\extension\base
{
	public function depends_on()
	{
		return array(
			'javiexin/exttestmgr'	=> '>=1.0.0@dev',
		);
	}

/*
	public function is_enableable()
	{
		$required = $this->depends_on();

		$ext_dependency_not_met = array();
		$ext_mgr = $this->container->get('ext.manager');
		
		foreach ($required as $ext_name => $ext_verstion)
		{
			if (!$ext_mgr->is_enabled($ext_name))
			{
				$ext_dependency_not_met[] = $ext_name;
			}				
		}
		return empty($ext_dependency_not_met);
	}
*/
}
