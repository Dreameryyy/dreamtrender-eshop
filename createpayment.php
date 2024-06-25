<?php

require_once '../vendor/autoload.php';
require_once 'secrets.php';
$stripe = new \Stripe\StripeClient("$stripeSecretKey");
$totalPrice = $_POST['totalPrice'];

header('Content-Type: application/json');

try {
    // Create a PaymentIntent with amount and currency
    $paymentIntent = $stripe->paymentIntents->create([
        'amount' => $totalPrice * 100, // Amount should be in cents
        'currency' => 'czk',
        'payment_method_types' => ['card'],
    ]);

    $output = [
        'clientSecret' => $paymentIntent->client_secret,
    ];

    echo json_encode($output);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
