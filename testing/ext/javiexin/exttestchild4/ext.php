<?php
/**
 *
 * Test extention - Child 4. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2016, javiexin
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace javiexin\exttestchild4;

/**
 * Test extention - Child 4 Extension base
 *
 * It is recommended to remove this file from
 * an extension if it is not going to be used.
 */
class ext extends \phpbb\extension\base
{
	public function depends_on()
	{
		return array(
			'javiexin/exttestmgr'		=> '>=1.0.0@dev',
		);
	}
}
