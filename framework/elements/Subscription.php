<?php
/**
 * Email For Download element
 *
 * @package downloadcodes.org.cashmusic
 * @author CASH Music
 * @link http://cashmusic.org/
 *
 * Copyright (c) 2013, CASH Music
 * Licensed under the Affero General Public License version 3.
 * See http://www.gnu.org/licenses/agpl-3.0.html
 *
 *
 * This file is generously sponsored by Anant Narayanan [anant@kix.in]
 * define FALSE TRUE — Just kidding.
 *
 **/
class Subscription extends ElementBase {
	public $type = 'subscription';
	public $name = 'Subscription';

	public function getData() {
		$this->element_data['public_url'] = CASH_PUBLIC_URL;

		// payment connection settings
		$this->element_data['paypal_connection'] = false;
		$this->element_data['stripe_public_key'] = false;
        $this->element_data['verification'] = false;

		$settings_request = new CASHRequest(
			array(
				'cash_request_type' => 'system',
				'cash_action' => 'getsettings',
				'type' => 'payment_defaults',
				'user_id' => $this->element_data['user_id']
			)
		);
		if (is_array($settings_request->response['payload'])) {

			if (isset($settings_request->response['payload']['stripe_default'])) {
				if ($settings_request->response['payload']['stripe_default']) {
					$payment_seed = new StripeSeed($this->element_data['user_id'],$settings_request->response['payload']['stripe_default']);
					if (!empty($payment_seed->publishable_key)) {
						$this->element_data['stripe_public_key'] = $payment_seed->publishable_key;
					}
				}
			}
		} else {
			if (isset($this->element_data['connection_id'])) {
				$connection_settings = CASHSystem::getConnectionTypeSettings($this->element_data['connection_type']);
				$seed_class = $connection_settings['seed'];
				if ($seed_class == 'StripeSeed') {
					$payment_seed = new StripeSeed($this->element_data['user_id'],$this->element_data['connection_id']);
					if (!empty($payment_seed->publishable_key)) {
						$this->element_data['stripe_public_key'] = $payment_seed->publishable_key;
					}
				}
			}
		}


		if (!$this->element_data['paypal_connection'] && !$this->element_data['stripe_public_key']) {
			$this->setError("No valid payment connection found.");
		}

		// get plan data
		$plan_request = new CASHRequest(
			array(
				'cash_request_type' => 'commerce',
				'cash_action' => 'getsubscriptionplan',
				'user_id' => $this->element_data['user_id'],
				'id' => $this->element_data['plan_id']
			)
		);

		$currency_request = new CASHRequest(
			array(
				'cash_request_type' => 'system',
				'cash_action' => 'getsettings',
				'type' => 'use_currency',
				'user_id' => $this->element_data['user_id']
			)
		);
		if ($currency_request->response['payload']) {
			$this->element_data['currency'] = CASHSystem::getCurrencySymbol($currency_request->response['payload']);
		} else {
			$this->element_data['currency'] = CASHSystem::getCurrencySymbol('USD');
		}

		if ($plan_request->response['payload'] && !empty($plan_request->response['payload'][0])) {
			$this->element_data['plan_name'] = $plan_request->response['payload'][0]['name'];

			$this->element_data['plan_description'] = $plan_request->response['payload'][0]['description'];
			$this->element_data['flexible_price'] = $plan_request->response['payload'][0]['flexible_price'];

			$this->element_data['plan_price'] = $plan_request->response['payload'][0]['price'];

			// if flexible pricing is set let's set the default to suggested price
			if (!empty($this->element_data['flexible_price'])) {
				$this->element_data['plan_price'] = $plan_request->response['payload'][0]['suggested_price'];
				$this->element_data['minimum_price'] = $plan_request->response['payload'][0]['price'];
			} else {
				$this->element_data['minimum_price'] = $this->element_data['plan_price'];
			}

			$this->element_data['plan_interval'] = $plan_request->response['payload'][0]['interval'];

			$this->element_data['plan_id'] = $plan_request->response['payload'][0]['sku'];

			$this->element_data['plan_flexible_price'] =
				($plan_request->response['payload'][0]['flexible_price'] == 1) ? true: false;

			$this->element_data['shipping'] = ($plan_request->response['payload'][0]['physical'] == 0) ? "false": "true";

		} else {
			//error
		}
		error_log("verify ".$_REQUEST['verification']);
		if (!empty($_REQUEST['verification'])) {
			$this->element_data['verification'] = true;
			//https://s3-us-west-2.amazonaws.com/cashmusic.tests.for.tom/element.html?verification=b8e468db848d808791c5a0abc4187354&address=tom%40jsdfjdf.com
		}

		if (isset($_REQUEST['state'])) {
			if ($_REQUEST['state'] == "success") {
				$this->setTemplate('success');
			}
		}



		return $this->element_data;
	}
} // END class
?>
