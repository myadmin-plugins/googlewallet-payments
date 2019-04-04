<?php

namespace Detain\MyAdminGooglewallet;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminGooglewallet
 */
class Plugin
{
	public static $name = 'Googlewallet Plugin';
	public static $description = 'Allows handling of Googlewallet based Payments through their Payment Processor/Payment System.';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event)
	{
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
			if (has_acl('client_billing')) {
			}
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Plugins\Loader $this->loader
		 */
		$loader = $event->getSubject();
		$loader->add_requirement('class.Googlewallet', '/../vendor/detain/myadmin-googlewallet-payments/src/Googlewallet.php');
		$loader->add_requirement('deactivate_kcare', '/../vendor/detain/myadmin-googlewallet-payments/src/abuse.inc.php');
		$loader->add_requirement('deactivate_abuse', '/../vendor/detain/myadmin-googlewallet-payments/src/abuse.inc.php');
		$loader->add_requirement('get_abuse_licenses', '/../vendor/detain/myadmin-googlewallet-payments/src/abuse.inc.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Settings $settings
		 **/
		$settings = $event->getSubject();
		$settings->add_radio_setting(_('Billing'), _('Google Wallet'), 'google_wallet_enabled', _('Enable Google Wallet'), _('Enable Google Wallet'), GOOGLE_WALLET_ENABLED, [true, false], ['Enabled', 'Disabled']);
		$settings->add_dropdown_setting(_('Billing'), _('Google Wallet'), 'google_wallet_sandbox', _('Use Sandbox/Test Environment'), _('Use Sandbox/Test Environment'), GOOGLE_WALLET_SANDBOX, [false, true], ['Live Environment', 'Sandbox Test Environment']);
		$settings->add_text_setting(_('Billing'), _('Google Wallet'), 'google_wallet_seller_id', _('Live Merchant ID'), _('Live Merchant ID'), (defined('GOOGLE_WALLET_SELLER_ID') ? GOOGLE_WALLET_SELLER_ID : ''));
		$settings->add_text_setting(_('Billing'), _('Google Wallet'), 'google_wallet_seller_secret', _('Live Merchant Key'), _('Live Merchant Key'), (defined('GOOGLE_WALLET_SELLER_SECRET') ? GOOGLE_WALLET_SELLER_SECRET : ''));
		$settings->add_text_setting(_('Billing'), _('Google Wallet'), 'google_wallet_sandbox_seller_id', _('Sandbox Merchant ID'), _('Sandbox Merchant ID'), (defined('GOOGLE_WALLET_SANDBOX_SELLER_ID') ? GOOGLE_WALLET_SANDBOX_SELLER_ID : ''));
		$settings->add_text_setting(_('Billing'), _('Google Wallet'), 'google_wallet_sandbox_seller_secret', _('Sandbox Merchant Key'), _('Sandbox Merchant Key'), (defined('GOOGLE_WALLET_SANDBOX_SELLER_SECRET') ? GOOGLE_WALLET_SANDBOX_SELLER_SECRET : ''));
	}
}
