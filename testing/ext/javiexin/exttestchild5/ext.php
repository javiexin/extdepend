<?php
/**
 *
 * Test extention - Child 5. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2016, javiexin
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace javiexin\exttestchild5;

/**
 * Test extention - Child 5 Extension base
 *
 * It is recommended to remove this file from
 * an extension if it is not going to be used.
 */
class ext extends \phpbb\extension\base
{
	public function depends_on()
	{
		return array(
			'javiexin/exttestchild4'	=> '>=1.0.0@dev',
			'javiexin/exttestchild2'	=> '>=1.0.0@dev',
		);
	}

	public function is_enableable()
	{
		return $this->container->get('ext.manager')->is_enabled('javiexin/extdependencies');
	}
}
