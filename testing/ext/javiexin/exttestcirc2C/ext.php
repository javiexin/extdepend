<?php
/**
 *
 * Test extention - Circular dependency 2C
 *
 * @copyright (c) 2016, javiexin
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace javiexin\exttestcirc2C;

/**
 * Test extention - Circular dependency 2C
 *
 * It is recommended to remove this file from
 * an extension if it is not going to be used.
 */
class ext extends \phpbb\extension\base
{
	public function depends_on()
	{
		return array(
			'javiexin/exttestcirc2A'	=> '>=1.0.0@dev',
		);
	}
}
