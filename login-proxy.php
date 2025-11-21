<?php

// Magento login page URL
$loginPageUrl = "https://m2-prod-chelmsfordsafety-co-uk.cfstack.com/customer/account/login/";
$loginPostUrl = "https://m2-prod-chelmsfordsafety-co-uk.cfstack.com/customer/account/loginPost/";

// Start CURL session
$ch = curl_init();

// 1) First request: get login page to capture form_key + cookies
curl_setopt($ch, CURLOPT_URL, $loginPageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);

// Extract cookies
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
$cookies = implode('; ', $matches[1]);

// Extract form_key
preg_match('/name="form_key" value="([^"]+)"/', $response, $keyMatch);
$form_key = $keyMatch[1] ?? "";

// If we didn’t get a form_key, fail early
if (!$form_key) {
    die("Error: Could not retrieve form_key from Magento.");
}

// 2) Second request: submit login
$postFields = [
    'form_key' => $form_key,
    'login[username]' => $_POST['username'],
    'login[password]' => $_POST['password']
];

curl_setopt($ch, CURLOPT_URL, $loginPostUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: $cookies"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponse = curl_exec($ch);

// Capture redirected location
preg_match('/Location:\s*(.*)\s/i', $loginResponse, $locationMatch);

curl_close($ch);

// Redirect user to Magento after successful login
if (!empty($locationMatch[1])) {
    header("Location: " . trim($locationMatch[1]));
    exit;
}

// If nothing was returned, show error
echo "Magento Login Failed.";
?>
