<?php
/**
 *
 * Extension Dependency Management. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2016, javiexin
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace javiexin\extdependencies;

/**
 * Extension Dependency Management Extension base
 *
 * It is recommended to remove this file from
 * an extension if it is not going to be used.
 */
class ext extends \phpbb\extension\base
{
	/**
	* Enable step that initializes the stored config_var
	*
	* @param mixed $old_state State returned by previous call of this method
	* @return mixed Returns false after last step, otherwise temporary state
	*/
	public function enable_step($old_state)
	{
		$this->container->get('config_text')->set('javiexin.extdependencies.saved', json_encode(array()));
		return parent::enable_step($old_state);
	}

	/**
	* Disable step that removes the stored config_var
	*
	* @param mixed $old_state State returned by previous call of this method
	* @return mixed Returns false after last step, otherwise temporary state
	*/
	public function disable_step($old_state)
	{
		$this->container->get('config_text')->delete('javiexin.extdependencies.saved');
		return parent::disable_step($old_state);
	}
}
