<?php
session_start();
require_once 'dbconfig.php';
require_once 'secrets.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST .
    ';dbname=' . DB_NAME .
    ';charset=utf8mb4',
    DB_USERNAME,
    DB_PASSWORD
);

$totalPrice = $_POST['totalPrice'];
$paymentMethod = $_POST['paymentMethod'];
$deliveryMethod = $_POST['deliveryMethod'];
if(isset($_SESSION['user']['email'])){
    $email = $_SESSION['user']['email'];
    if(isset($_SESSION['user']['user_id'])){
        $user_id = $_SESSION['user']['user_id'];
    }else{
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['user_id'];
    }
}else{
    $email= $_POST['email'];
    $userEmail=$_POST['email'];
}
$cart = $_SESSION['cart'];

// Check if the payment method is "credit_card" (Stripe)
if ($paymentMethod === 'credit_card') {

    require_once 'vendor/autoload.php'; // Include Stripe PHP library
    // Replace with your Stripe secret key
    \Stripe\Stripe::setApiKey($stripeSecretKey);

    if (isset($_SESSION['user'])) {
    // User is logged in, fetch user information
    $userEmail = $_SESSION['user']['email'];
    $statement = $pdo->prepare("SELECT name, state, postalCode, adress, phone FROM sp_users WHERE email = :email");
    $statement->execute([':email' => $userEmail]);
    $userInfo = $statement->fetch(PDO::FETCH_ASSOC);
        $customer = \Stripe\Customer::create(array(
            'email'			=>	$_SESSION['user']['email'],
            'source'		=>	$_POST["token"],
            'name'			=>	$userInfo['name'],
            'address'		=>	array(
                'line1'			=>	$userInfo['adress'],
                'postal_code'	=>	$userInfo['postalCode'],
                'city'			=>	$userInfo['state'],
                'state'			=>	$userInfo['state'],
                'country'		=>	'CZ'
            )
        ));
    }
    else{
        $customer = \Stripe\Customer::create(array(
            'email'			=>	$_POST["email"],
            'source'		=>	$_POST["token"],
            'name'			=>	$_POST["name"],
            'address'		=>	array(
                'line1'			=>	$_POST["adress"],
                'postal_code'	=>	$_POST["postalCode"],
                'city'			=>	$_POST["state"],
                'state'			=>	$_POST["state"],
                'country'		=>	'CZ'
            )
        ));
        $userEmail=$_POST["email"];
    }
    $order_number = rand(100000,999999);

    $charge = \Stripe\Charge::create(array(
        'customer'		=>	$customer->id,
        'amount'		=>	$_POST["totalPrice"] * 100,
        'currency'		=>	$_POST["currency_code"],

        'metadata'		=> array(
            'order_id'		=>	$order_number
        )
    ));

$response = $charge->jsonSerialize();

if($response["amount_refunded"] == 0 && empty($response["failure_code"]) && $response['paid'] == 1 && $response["captured"] == 1 && $response['status'] == 'succeeded')
{
    $amount = $response["amount"]/100;
if (isset($_SESSION['user'])){
    $order_data = array(
        ':order_number'			=>	$order_number,
        ':order_total_amount'	=>	$amount,
        ':transaction_id'		=>	$response["balance_transaction"],
        ':card_cvc'				=>	$_POST["card_cvc"],
        ':card_expiry_month'	=>	$_POST["card_expiry_month"],
        ':card_expiry_year'		=>	$_POST["card_expiry_year"],
        ':order_status'			=>	$response["status"],
        ':card_holder_number'	=>	$userInfo['name'],
        ':email_address'		=>	$_SESSION['user']['email'],
        ':customer_name'		=>	$userInfo['name'],
        ':customer_address'		=>	$userInfo['adress'],
        ':customer_city'		=>	$userInfo['state'],
        ':customer_pin'			=>	$userInfo['postalCode'],
        ':customer_state'		=>	$userInfo['state'],
        ':customer_country'		=>	$userInfo['state']
    );
    }
    else{

        $order_data = array(
            ':order_number'			=>	$order_number,
            ':order_total_amount'	=>	$amount,
            ':transaction_id'		=>	$response["balance_transaction"],
            ':card_cvc'				=>	$_POST["card_cvc"],
            ':card_expiry_month'	=>	$_POST["card_expiry_month"],
            ':card_expiry_year'		=>	$_POST["card_expiry_year"],
            ':order_status'			=>	$response["status"],
            ':card_holder_number'	=>	$_POST["card_holder_number"],
            ':email_address'		=>	$_POST["email"],
            ':customer_name'		=>	$_POST["name"],
            ':customer_address'		=>	$_POST["adress"],
            ':customer_city'		=>	$_POST["state"],
            ':customer_pin'			=>	$_POST["postalCode"],
            ':customer_state'		=>	$_POST["state"],
            ':customer_country'		=>	$_POST["state"]
        );


    }
    if(!isset($user_id)){
        $user_id=null;
    }
    $date = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO sp_order (price, date, payment_method, delivery_method, email, user_id, postalCode, adress, state, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$totalPrice, $date, $paymentMethod, $deliveryMethod, $userEmail, $user_id, $_POST["postalCode"], $_POST["adress"], $_POST["state"], $_POST["phone"]]);
    $orderID = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO sp_order_table (order_id, good_id, amount, price) VALUES (?, ?, ?, ?)");
    function fetchCartItems($pdo, $cart)
    {
        $ids = implode(',', array_values($cart));
        $statement = $pdo->prepare("SELECT * FROM sp_products WHERE good_id IN ($ids)");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
    if (!empty($_SESSION['cart'])) {
        $cart = $_SESSION['cart'];
        $records = fetchCartItems($pdo, $cart);
    }

    foreach ($records as $item) {
        $quantity = isset($cart[$item['good_id']]) ? $cart[$item['good_id']] + 1 : 1;
        $stmt->execute([$orderID, $item['good_id'], $quantity, $item['price']]);
    }

    $_SESSION['cart'] = [];
    $_SESSION["success_message"] = "Payment is completed successfully. The TXN ID is " . $response["balance_transaction"] . "";
    header('location:index');
}

}
?>
