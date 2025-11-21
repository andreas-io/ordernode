<?php

// ------------------------------------------------------
// CONFIG: Magento URLs
// ------------------------------------------------------

$magentoLoginPage = "https://m2-prod-chelmsfordsafety-co-uk.cfstack.com/customer/account/login/";
$magentoLoginPost = "https://m2-prod-chelmsfordsafety-co-uk.cfstack.com/customer/account/loginPost/";


// ------------------------------------------------------
// GET DATA FROM THE HTML FORM
// ------------------------------------------------------

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    die("Missing username or password.");
}


// ------------------------------------------------------
// STEP 1: Request Magento login page to obtain form_key + cookies
// ------------------------------------------------------

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $magentoLoginPage,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,            // include headers (for cookies)
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$loginPageResponse = curl_exec($ch);

if (!$loginPageResponse) {
    die("Unable to reach Magento login page.");
}


// ------------------------------------------------------
// EXTRACT COOKIE HEADER
// ------------------------------------------------------

preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $loginPageResponse, $cookieMatches);
$cookies = implode('; ', $cookieMatches[1]);


// ------------------------------------------------------
// EXTRACT THE form_key VALUE
// ------------------------------------------------------

preg_match('/name="form_key" value="([^"]+)"/', $loginPageResponse, $formMatches);
$form_key = $formMatches[1] ?? '';

if (!$form_key) {
    die("Unable to extract form_key from Magento.");
}


// ------------------------------------------------------
// STEP 2: POST LOGIN REQUEST with form_key + cookies
// ------------------------------------------------------

curl_setopt_array($ch, [
    CURLOPT_URL => $magentoLoginPost,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        "form_key" => $form_key,
        "login[username]" => $username,
        "login[password]" => $password,
    ]),
    CURLOPT_HTTPHEADER => [
        "Cookie: $cookies",
        "Content-Type: application/x-www-form-urlencoded"
    ],
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false
]);

$loginResponse = curl_exec($ch);

if (!$loginResponse) {
    die("Error submitting login to Magento.");
}


// ------------------------------------------------------
// EXTRACT REDIRECT (Magento redirects to customer account)
// ------------------------------------------------------

preg_match('/Location:\s*(.*)\s/i', $loginResponse, $redirectMatch);
$redirectUrl = trim($redirectMatch[1] ?? "");

curl_close($ch);


// ------------------------------------------------------
// REDIRECT USER TO MAGENTO ACCOUNT
// ------------------------------------------------------

if ($redirectUrl) {
    header("Location: $redirectUrl");
    exit;
}

// If no redirect, login failed
echo "Magento login failed. Check credentials.";
?>
