<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function gatewaymodule_MetaData()
{
    return array(
        'DisplayName' => 'Spektra',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * @return array
 */
function spektrapay_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Spektra',
        ),
        'enabled' => array(
            'FriendlyName' => 'Enabled',
            'Type' => 'yesno',
            'Description' => 'Enable Spektra',
        ),
        'title' => array(
            'FriendlyName' => 'Title',
            'Type' => 'text',
            'Size' => '50',
            'Default' => 'Spektra',
            'Description' => 'This controls the title which the user sees during checkout.',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '2',
            'Cols' => '60',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'publicKey' => array(
            'FriendlyName' => 'Live Public Key',
            'Type' => 'text',
            'Size' => '100',
            'Default' => '',
            'Description' => 'This is your live public key generated from your Spektra dashboard',
        ),
        'privateKey' => array(
            'FriendlyName' => 'Live Private Key',
            'Type' => 'password',
            'Size' => '100',
            'Default' => '',
            'Description' => 'This is your live private key generated from your Spektra dashboard',
        ),
        'testPublicKey' => array(
            'FriendlyName' => 'Test Public Key',
            'Type' => 'text',
            'Size' => '100',
            'Default' => '',
            'Description' => 'This is your test public key generated from your Spektra sandbox',
        ),
        'testPrivateKey' => array(
            'FriendlyName' => 'Test Private Key',
            'Type' => 'password',
            'Size' => '100',
            'Default' => '',
            'Description' => 'This is your test private key generated from your Spektra sandbox',
        ),
        'testMode' => array(
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to enable test mode',
        ),
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function spektrapay_link($params)
{
    // Gateway Configuration Parameters
    $title = $params['title'];
    $description = $params['description'];
    $testMode = $params['testMode'];
    $publicKey = $params['publicKey'];
    $privateKey = $params['privateKey'];
    $testPublicKey = $params['testPublicKey'];
    $testPrivateKey = $params['testPrivateKey'];
    $enabled = $params['enabled'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];


    $urlprefix = $testMode ? 'https://api-test.spektra.co/' : 'https://api.spektra.co/';
    $token = spektrapay_get_token($urlprefix, $testMode ? $testPublicKey : $publicKey, $testMode ? $testPrivateKey : $privateKey);

    $postdata ="{
        \"amount\":\"{$amount}\",
        \"currency\":\"{$currencyCode}\",
        \"description\":\"{$description}\",
        \"successURL\":\"{$returnUrl}\",
        \"cancelURL\":\"{$returnUrl}\"
    }";
    $checkoutId = spektrapay_get_checkout_id($token, $postdata, $urlprefix);
    if(!$checkoutId){
        return;
    }

    $checkoutUrlPrefix = $testMode ? 'https://demo-checkout.spektra.co/' : 'https://checkout.spektra.co/';
    $checkoutUrl = $checkoutUrlPrefix . $checkoutId;

    $postfields = array();
    $postfields['username'] = $username;
    $postfields['invoice_id'] = $invoiceId;
    $postfields['description'] = $description;
    $postfields['amount'] = $amount;
    $postfields['currency'] = $currencyCode;
    $postfields['first_name'] = $firstname;
    $postfields['last_name'] = $lastname;
    $postfields['email'] = $email;
    $postfields['address1'] = $address1;
    $postfields['address2'] = $address2;
    $postfields['city'] = $city;
    $postfields['state'] = $state;
    $postfields['postcode'] = $postcode;
    $postfields['country'] = $country;
    $postfields['phone'] = $phone;
    $postfields['callback_url'] = $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php';
    $postfields['return_url'] = $returnUrl;

    $htmlOutput = '<form method="GET" action="' . $checkoutUrl . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . urlencode($v) . '" />';
    }
    $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}

function spektrapay_auth_key($publicKey, $privateKey){
	$key = $publicKey.':'.$privateKey;
	return base64_encode($key);
}

function spektrapay_get_token($urlprefix, $publicKey, $privateKey){
    $url = $urlprefix . "oauth/token?grant_type=client_credentials";
    
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_HTTPHEADER => array(
        "Authorization: Basic " . spektrapay_auth_key($publicKey, $privateKey),
        "Content-Type: application/json"
    ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    return $err ? "Authentication Error #:" . $err : json_decode($response)->access_token;
}

function spektrapay_get_checkout_id($token, $postdata, $urlprefix){
    $url = $urlprefix . "api/v1/checkout/initiate";

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $postdata,
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer ". $token,
        "Content-Type: application/json"
    ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    return $err ? null : json_decode($response)->checkoutID;
}
