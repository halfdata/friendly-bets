<?php
include_once(dirname(__FILE__).'/inc/functions.php');
include_once(dirname(__FILE__).'/inc/icdb.php');
include_once(dirname(__FILE__).'/inc/common.php');

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (array_key_exists('payment', $_GET) && $_GET['payment'] == 'stripe') {
	if (!class_exists("\Stripe\Stripe")) require_once(dirname(__FILE__).'/inc/stripe/init.php');
	$payload = @file_get_contents('php://input');
	$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
	$event = null;
	try {
		$event = \Stripe\Webhook::constructEvent($payload, $sig_header, $options['stripe-webhook-secret']);
	} catch(\UnexpectedValueException $e) {
		// Invalid payload
		http_response_code(400);
		exit;
	} catch(\Stripe\Exception\SignatureVerificationException $e) {
		// Invalid signature
		http_response_code(400);
		exit;
	}
	$post_data = json_decode($payload, true);

	if (!in_array($post_data['type'], array('checkout.session.completed', 'invoice.paid', 'invoice.payment_failed'))) exit;
	$stripe_customer = $wpdb->get_row("SELECT t1.*, t2.membership_id AS m_id, t2.membership_txn_id AS m_txn_id FROM ".$wpdb->prefix."user_customers t1 
			INNER JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
		WHERE t1.customer_id = '".esc_sql($post_data['data']['object']['customer'])."' AND t1.deleted != '1' AND t1.gateway = 'stripe'", ARRAY_A);
	if (empty($stripe_customer)) exit;
	switch($post_data['type']) {
		case 'checkout.session.completed':
			$client_reference_id = explode('-', $post_data['data']['object']['client_reference_id']);
			if (sizeof($client_reference_id) && $client_reference_id[0] == 'membership') {
				$membership_expires = 0;
				$membership_price_id = intval($client_reference_id[1]);
				$membership_price = $wpdb->get_row("SELECT t1.*, t2.uuid AS membership_uuid, t2.title AS membership_title, t2.options AS membership_options FROM ".$wpdb->prefix."membership_prices t1 
						INNER JOIN ".$wpdb->prefix."memberships t2 ON t2.id = t1.membership_id
					WHERE t1.id = '".esc_sql($membership_price_id)."' AND t1.deleted != '1' AND t2.deleted != '1'", ARRAY_A);
				if ($post_data['data']['object']['mode'] == 'subscription') {
					$subscription_id = $post_data['data']['object']['subscription'];
					try {
						\Stripe\Stripe::setApiKey($options['stripe-secret-key']);
						$subscription = \Stripe\Subscription::retrieve($subscription_id, []);
						$membership_expires = $subscription->current_period_end + $options['membership-grace-period']*3600*24;
					} catch(Exception $e) {
						// TODO: Notify admin about API problem.
						echo esc_html(rtrim($body['error']['message'], '.').'.');
						exit;
					}
				} else $subscription_id = '';

				if (array_key_exists('currency', $post_data['data']['object'])) $currency = strtoupper($post_data['data']['object']['currency']);
				else $currency = strtoupper($post_data['data']['object']['display_items'][0]['currency']);
				if (in_array($currency, $stripe_no_100)) $multiplier = 1;
				else $multiplier = 100;
				if (array_key_exists('amount_total', $post_data['data']['object'])) $price = number_format($post_data['data']['object']['amount_total']/$multiplier, 2, '.', '');
				else $price = number_format($post_data['data']['object']['display_items'][0]['amount']/$multiplier, 2, '.', '');
	
				$wpdb->query("INSERT INTO ".$wpdb->prefix."transactions (
					gateway, 
					customer_id, 
					subscription_id, 
					details,
					type,
					price,
					currency,
					txn_id,
					deleted, 
					created
				) VALUES (
					'stripe',
					'".esc_sql($post_data['data']['object']['customer'])."',
					'".esc_sql($subscription_id)."',
					'".esc_sql(json_encode($post_data))."',
					'checkout.session.completed',
					'".esc_sql($price)."',
					'".esc_sql($currency)."',
					'".esc_sql($post_data['data']['object']['id'])."',
					'0',
					'".time()."'
				)");
				$txn_id = $wpdb->insert_id;
				if (!empty($membership_price)) {
					if ($stripe_customer['m_id'] > 0) {
						// TODO: Notify admin about new subscription of subscribed user.
					} else {
						$wpdb->query("UPDATE ".$wpdb->prefix."users SET membership_id = '".esc_sql($membership_price['membership_id'])."', membership_expires = '".esc_sql($membership_expires)."', membership_txn_id = '".esc_sql($txn_id)."' WHERE id = '".esc_sql($stripe_customer['user_id'])."'");
					}
				} else {
					// TODO: Notify admin about data inconsistency.
				}
			}
			break;
		case 'invoice.paid':
			if (!empty($post_data['data']['object']['subscription'])) {
				if (array_key_exists('currency', $post_data['data']['object'])) $currency = strtoupper($post_data['data']['object']['currency']);
				else $currency = strtoupper($post_data['data']['object']['lines']['data'][0]['currency']);
				if (in_array($currency, $stripe_no_100)) $multiplier = 1;
				else $multiplier = 100;
				if (array_key_exists('amount_paid', $post_data['data']['object'])) $price = number_format($post_data['data']['object']['amount_paid']/$multiplier, 2, '.', '');
				else $price = number_format($post_data['data']['object']['lines']['data'][0]['amount']/$multiplier, 2, '.', '');
				$wpdb->query("INSERT INTO ".$wpdb->prefix."transactions (
					gateway, 
					customer_id, 
					subscription_id, 
					details,
					type,
					price,
					currency,
					txn_id,
					deleted, 
					created
				) VALUES (
					'stripe',
					'".esc_sql($post_data['data']['object']['customer'])."',
					'".esc_sql($post_data['data']['object']['subscription'])."',
					'".esc_sql(json_encode($post_data))."',
					'invoice.paid',
					'".esc_sql($price)."',
					'".esc_sql($currency)."',
					'".esc_sql($post_data['data']['object']['id'])."',
					'0',
					'".time()."'
				)");
				$checkout_transaction = $wpdb->get_row("SELECT t1.*, t3.id AS user_id FROM ".$wpdb->prefix."transactions t1 
						INNER JOIN ".$wpdb->prefix."user_customers t2 ON t2.customer_id = t1.customer_id
						INNER JOIN ".$wpdb->prefix."users t3 ON t3.id = t2.user_id AND t3.membership_txn_id = t1.id
					WHERE 
						t1.gateway = 'stripe' AND 
						t1.type = 'checkout.session.completed' AND 
						t1.customer_id = '".esc_sql($post_data['data']['object']['customer'])."' AND
						t1.subscription_id = '".esc_sql($post_data['data']['object']['subscription'])."' AND
						t2.gateway = 'stripe'
					", ARRAY_A);
				if (!empty($checkout_transaction) && !empty($checkout_transaction['user_id'])) {
					try {
						\Stripe\Stripe::setApiKey($options['stripe-secret-key']);
						$subscription = \Stripe\Subscription::retrieve($post_data['data']['object']['subscription'], []);
						$membership_expires = $subscription->current_period_end + $options['membership-grace-period']*3600*24;
						$wpdb->query("UPDATE ".$wpdb->prefix."users SET membership_expires = '".esc_sql($membership_expires)."' WHERE id = '".esc_sql($checkout_transaction['user_id'])."'");
					} catch(Exception $e) {
						// TODO: Notify admin about API problem.
						echo esc_html(rtrim($body['error']['message'], '.').'.');
						exit;
					}
				}
			}
			break;
		default:
			break;
	}
}
?>