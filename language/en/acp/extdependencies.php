<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* DO NOT CHANGE
*/
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'EXTENSION_DEPENDENCIES_EXPLAIN'		=> 'You can also view extension dependencies, if they exist.',
	'EXTENSION_DEPENDENCIES_NOT_AVAILABLE'	=> 'The selected extension cannot be enabled, the following required dependencies are not available:',
	'EXTENSION_DEPENDENCIES_NOT_CONFIRMED'	=> 'The selected extension cannot be enabled, some dependency(ies) have been disabled:',
	'EXTENSION_DEPENDANTS_NOT_CONFIRMED'	=> 'The selected extension cannot be disabled, new extension(s) depend on it:',
	'EXTENSION_CIRCULAR_DEPENDENCY'			=> 'Circular dependency detected.',
	'EXTENSION_CIRCULAR_DEPENDENCY_ENABLE'	=> 'Extension is not valid.<br />Circular dependency detected, the extension cannot be enabled.',
	'EXTENSION_CIRCULAR_DEPENDENCY_DISABLE'	=> 'Extension is not valid.<br />Circular dependency detected, the extension should be disabled.',
	'EXTENSION_REVALIDATE_NEEDED'			=> 'Extension may not be valid.<br />Dependencies not met, the extension should be revalidated.',
	'EXTENSION_ENABLE_NEEDED'				=> 'Extension is required.<br />Dependants are already enabled, the extension should be enabled.',
	'EXTENSION_ENABLED_DONE'				=> 'DONE',
	'EXTENSION_DISABLED_DONE'				=> 'DONE',

	'EXTENSION_DEPENDENCIES_INFORMATION'	=> 'Extension dependencies',
	'EXTENSION_DEPENDS_ON'					=> 'Required extensions',
	'EXTENSION_DEPENDS_ON_EXPLAIN'			=> 'Extensions required for the proper functioning of this extension.',
	'EXTENSION_DEPENDANTS'					=> 'Depending extensions',
	'EXTENSION_DEPENDANTS_EXPLAIN'			=> 'Enabled extensions that have declared that require this one for their proper functioning.',
	'EXTENSION_REQUIRED_VERSION'			=> 'Required version',
	'EXTENSION_AVAILABLE_VERSION'			=> 'Available version',
	'EXTENSION_STATUS'						=> 'Status',
	'EXTENSION_ENABLED'						=> 'Enabled',
	'EXTENSION_DISABLED'					=> 'Disabled',
	'EXTENSION_AVAILABLE'					=> 'Available',
	'EXTENSION_UNAVAILABLE'					=> 'Unavailable',

	'EXTENSION_REVALIDATE'					=> 'Revalidate',
	'EXTENSION_REVALIDATE_ACTION_EXPLAIN'	=> 'The extension needs revalidation because some of the extension dependencies are not met.',
	'EXTENSION_REVALIDATE_EXPLAIN'			=> 'Revalidating an extension disables it, together with its dependant extensions, and then reenables them, checking all dependencies.',
	'EXTENSION_REVALIDATE_ENABLE_EXPLAIN'	=> 'Second step, re-enable the extensions disabled in the previous step, with its dependencies.',
	'EXTENSION_REVALIDATE_DISABLE_EXPLAIN'	=> 'First step, disable the extension and its dependants.',

	'EXTENSION_DEP_ENABLE_CONFIRM'					=> 'Are you sure that you wish to enable the “%s” extension along with the following required dependencies?',
	'EXTENSION_DEP_DISABLE_CONFIRM'					=> 'Are you sure that you wish to disable the “%s” extension along with the following extensions that depend on it?',
	'EXTENSION_DEP_ENABLE_IN_PROGRESS'				=> 'The extension “%s” is currently being enabled. Please do not leave or refresh this page until it is completed.',
	'EXTENSION_DEP_DISABLE_IN_PROGRESS'				=> 'The extension “%s” is currently being disabled. Please do not leave or refresh this page until it is completed.',
	'EXTENSION_DEP_ENABLE_SUCCESS'					=> 'The extension “%s” was enabled successfully, together with its dependencies:',
	'EXTENSION_DEP_DISABLE_SUCCESS'					=> 'The extension “%s” was disabled successfully, together with its dependants:',

	'EXTENSION_REVALIDATE_ENABLE_CONFIRM'			=> 'Are you sure that you wish to enable, as part of the revalidation process, the “%s” extension?',
	'EXTENSION_REVALIDATE_DISABLE_CONFIRM'			=> 'Are you sure that you wish to disable, as part of the revalidation process, the “%s” extension?',
	'EXTENSION_REVALIDATE_ENABLE_IN_PROGRESS'		=> 'The extension “%s” is currently being enabled as part of the revalidation process.<br/>Please do not leave or refresh this page until it is completed.',
	'EXTENSION_REVALIDATE_DISABLE_IN_PROGRESS'		=> 'The extension “%s” is currently being disabled as part of the revalidation process.<br/>Please do not leave or refresh this page until it is completed.',
	'EXTENSION_REVALIDATE_ENABLE_SUCCESS'			=> 'The extension “%s” was revalidated successfully.',
	'EXTENSION_REVALIDATE_DISABLE_SUCCESS'			=> 'The extension “%s” was disabled successfully.',

	'EXTENSION_DEP_REVALIDATE_ENABLE_CONFIRM'		=> 'Are you sure that you wish to enable, as part of the revalidation process of the “%s” extension, the following extensions?',
	'EXTENSION_DEP_REVALIDATE_DISABLE_CONFIRM'		=> 'Are you sure that you wish to disable, as part of the revalidation process, the “%s” extension along with the following extensions that depend on it?',
	'EXTENSION_DEP_REVALIDATE_ENABLE_IN_PROGRESS'	=> 'The extension “%s” is currently being enabled as part of the revalidation process.<br/>Please do not leave or refresh this page until it is completed.',
	'EXTENSION_DEP_REVALIDATE_DISABLE_IN_PROGRESS'	=> 'The extension “%s” is currently being disabled as part of the revalidation process.<br/>Please do not leave or refresh this page until it is completed.',
	'EXTENSION_DEP_REVALIDATE_ENABLE_SUCCESS'		=> 'The extension “%s” was revalidated successfully. The following extensions have been enabled during the process:',
	'EXTENSION_DEP_REVALIDATE_DISABLE_SUCCESS'		=> 'The extension “%s” was disabled successfully, together with its dependants:',

	'PROCEED_TO_REENABLE'					=> 'Proceed to reenable.',
));
