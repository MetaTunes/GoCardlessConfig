<?php namespace ProcessWire;

class GoCardlessConfig extends WireData implements Module, ConfigurableModule {

	/**
	 * GoCardlessConfig Module for ProcessWire
	 * @version 0.0.4
	 * @summary ProcessWire module that holds keys for GoCardless API.
	 * @icon key
	 * @author Mark Evens
	 *
	 * This module integrates GoCardless payment services with ProcessWire, providing a configurable
	 * interface for managing GoCardless API keys and webhook secrets. It is designed for superusers (or those with gocardless_config permission)
	 * and offers a secure way to store and access sensitive GoCardless configuration details directly
	 * within the ProcessWire admin interface.
	 *
	 * Features:
	 * - Secure storage of GoCardless LIVE and SANDBOX API keys and webhook secrets.
	 * - Password protection to prevent unauthorized access to GoCardless settings.
	 * - Easy toggling between LIVE and SANDBOX environments for testing and production use.
	 *
	 * Requirements:
	 * - ProcessWire 3.x or newer
	 *
	 * Configuration:
	 * The module requires entry of GoCardless API keys and webhook secrets for both LIVE and SANDBOX
	 * environments. Access to these settings is password protected for security. Only superusers or those with gocardless_config permission can
	 * view and modify the GoCardless configuration.
	 *
	 * Usage:
	 * After configuration, the module can be used by other components of your site to initiate
	 * GoCardless payments and handle GoCardless webhooks securely.
	 *
	 * Note:
	 * Always ensure that your GoCardless API keys and webhook secrets are kept confidential and are
	 * only accessible to authorized personnel. The data in this module are only accessible in the module by superusers or those with special permission,
	 * but anyone with access to the ProcessWire admin interface and developer tools such as Tracy Debugger can access the API keys
	 * if they have sufficient knowledge, so bear that in mind in granting other access rights.
	 */

	public static function getModuleInfo() {
		return [
			'title' => 'GoCardlessConfig',
			'version' => '0.0.5',
			'summary' => 'ProcessWire module that holds keys for GoCardless API.',
			'icon' => 'key',
			'permission' => 'gocardless_config',
			'permissions' => ['gocardless_config' => 'Access to the GoCardless keys'],
		];
	}

	public function init() {
	}

	public function unsetPassword() {
		$modules = $this->wire('modules');
		$data = $modules->getConfig('GoCardlessConfig');
		$data['password'] = '';
		$modules->saveConfig('GoCardlessConfig', $data);
	}

	/**
	 * Config inputfields
	 *
	 * @param InputfieldWrapper $inputfields
	 */
	public function getModuleConfigInputfields($inputfields) {
		$modules = $this->wire('modules');
		$user = $this->wire('user');
		$session = $this->wire('session');
//		bd([$this->password, $session->hidePassword], 'password, hidePassword');

		/* @var InputfieldMarkup $f */
		$f = $modules->InputfieldMarkup;
		$f->label = 'GoCardless site links';
		$f->value = "<a href='https://manage.gocardless.com/sign-in' target='_blank'>GoCardless dashboard</a>
			<br/>
			<a href='https://manage-sandbox.gocardless.com/sign-in' target='_blank'>GoCardless sandbox dashboard</a>
			<br/>
			<a href='https://manage-sandbox.gocardless.com/developers' target='_blank'>GoCardless sandbox developers dashboard</a>";

		$inputfields->add($f);

		$allowedUser = ($user->isSuperuser() || $user->hasPermission('gocardless_config'));
		if(!$allowedUser) {
			$this->error('You must be a superuser or have special permission to access this module');
			return;
		}

		if($allowedUser && (!$session->get('hidePassword') || !$this->password || !$session->authenticate($user, $this->password))) {
			$this->unsetPassword();
			/* @var InputfieldText $f */
			$f = $modules->InputfieldText;
			$f->attr('name', 'password');
			$f->attr('type', 'password');
			$f->label = 'Password';
			$f->description = 'Enter the PW password for the user that is currently signed in (must be a superuser or have special permission).';
			$f->notes = 'This is required to access the GoCardless keys in order to prevent inadvertent disclosure or amendment.';
			$f->value = '';
			$inputfields->add($f);
			$session->set('showSettings', false);
			$session->set('hidePassword', true);

		} else {
			$session->set('showSettings', true);
		}


		if($allowedUser && $session->get('showSettings') && $session->authenticate($user, $this->password)) {
			$hide = !$session->showSettings;
			$session->set('hidePassword', false);
			$this->unsetPassword();
		} else {
			$hide = true;
		}

//		bd([$hide, $session->get('hidePassword')], 'hide, hide password');

		/* @var InputfieldWrapper $form */
		$form = $modules->InputfieldWrapper;
		$form->attr('id+name', 'gocardless_config');

		/* @var InputfieldFieldset $fs */
		$fs = $modules->InputfieldFieldset;
		$fs_name = 'gocardless_settings_live';
		$fs->name = $fs_name;
		$fs->attr('hidden', $hide);
		$fs->wrapAttr('hidden', $hide);
		$fs->label = 'GoCardless settings - LIVE';
		$fs->notes = 'On submitting this form, the settings will be hidden and you will need to re-enter the password to view them';

		/* @var InputfieldText $f */
		$f = $modules->InputfieldText;
		$f->attr('hidden', $hide);
		$f->attr('name', 'gocardless_access_token_LIVE');
		$f->label = 'GoCardless access token - LIVE';
		$f->description = 'Enter the GoCardless LIVE access token';
		$f->value = $this->gocardless_access_token_LIVE;
		$fs->add($f);

		/* @var InputfieldText $f */
		$f = $modules->InputfieldText;
		$f->attr('name', 'gocardless_webhook_endpoint_secret_LIVE');
		$f->attr('hidden', $hide);
		$f->label = 'GoCardless webhook endpoint secret - LIVE';
		$f->description = 'Enter the GoCardless LIVE webhook endpoint secret';
		$f->value = $this->gocardless_webhook_endpoint_secret_LIVE;
		$fs->add($f);

		$form->add($fs);

		/* @var InputfieldFieldset $fs */
		$fs = $modules->InputfieldFieldset;
		$fs_name = 'gocardless_settings_sandbox';
		$fs->name = $fs_name;
		$fs->attr('hidden', $hide);
		$fs->wrapAttr('hidden', $hide);
		$fs->label = 'GoCardless settings - SANDBOX';
		$fs->notes = 'On submitting this form, the settings will be hidden and you will need to re-enter the password to view them';

		/* @var InputfieldText $f */
		$f = $modules->InputfieldText;
		$f->attr('hidden', $hide);
		$f->attr('name', 'gocardless_access_token_SANDBOX');
		$f->label = 'GoCardless access token - SANDBOX';
		$f->description = 'Enter the GoCardless SANDBOX access token';
		$f->value = $this->gocardless_access_token_SANDBOX;
		$fs->add($f);

		/* @var InputfieldText $f */
		$f = $modules->InputfieldText;
		$f->attr('name', 'gocardless_webhook_endpoint_secret_SANDBOX');
		$f->attr('hidden', $hide);
		$f->label = 'GoCardless webhook endpoint secret - SANDBOX';
		$f->description = 'Enter the GoCardless SANDBOX webhook endpoint secret';
		$f->value = $this->gocardless_webhook_endpoint_secret_SANDBOX;
		$fs->add($f);

		$form->add($fs);

		$inputfields->add($form);

	}


}