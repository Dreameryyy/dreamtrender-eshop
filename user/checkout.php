<?php
session_start();

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart");
    exit();
}

require_once '../dbconfig.php';
$pdo = new PDO(
    'mysql:host=' . DB_HOST .
    ';dbname=' . DB_NAME .
    ';charset=utf8mb4',
    DB_USERNAME,
    DB_PASSWORD
);

$cart = $_SESSION['cart'];

function fetchCartItems($pdo, $cart)
{
    $ids = implode(',', array_values($cart));
    $statement = $pdo->prepare("SELECT * FROM sp_products WHERE good_id IN ($ids)");
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);

    return $result;
}

$records = fetchCartItems($pdo, $cart);
$totalPrice = $_POST['totalPrice'];

$userInfo = null; // Initialize user information as null

if (isset($_SESSION['user'])) {
    // User is logged in, fetch user information
    $userEmail = $_SESSION['user']['email'];
    $statement = $pdo->prepare("SELECT name, state, postalCode, adress, phone FROM sp_users WHERE email = :email");
    $statement->execute([':email' => $userEmail]);
    $userInfo = $statement->fetch(PDO::FETCH_ASSOC);
}
?>

<?php require '../header.php'; ?>
<script src="jquery.creditCardValidator.js"></script>
<script src="https://js.stripe.com/v2/"></script>

<body class="container">
<?php require '../navbar.php'; ?>

<h3 class="mb-4">Checkout</h3>

<!-- Add a form to collect payment information -->
<form  method="post" id="order_process_form" action="../order.php">
    <?php if ($userInfo) : ?>
        <!-- User is logged in, display user's address information -->
        <div class="alert alert-info" role="alert">
            Your current delivery information:
            <ul>
                <li>Name: <?php echo $userInfo['name']; ?></li>
                <li>State: <?php echo $userInfo['state']; ?></li>
                <li>Postal Code: <?php echo $userInfo['postalCode']; ?></li>
                <li>Address: <?php echo $userInfo['adress']; ?></li>
                <li>Phone: <?php echo $userInfo['phone']; ?></li>
                <input type="hidden" class="form-control" id="name" name="name" value="<?php echo $userInfo['name']; ?>">
                <input type="hidden" class="form-control" id="state" name="state" value="<?php echo $userInfo['state']; ?>">
                <input type="hidden" class="form-control" id="postalCode" name="postalCode" value="<?php echo $userInfo['postalCode']; ?>">
                <input type="hidden" class="form-control" id="adress" name="adress" value="<?php echo $userInfo['adress']; ?>">
                <input type="hidden" class="form-control" id="phone" name="phone" value="<?php echo $userInfo['phone']; ?>">

            </ul>
            <a href="profile" class="btn btn-primary">Edit</a>
        </div>
    <?php else : ?>
        <div class="alert alert-warning" role="alert">
            Please provide your delivery information:
        </div>
        <div class="form-group">
            <label for="email">Email<span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="name">Name<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="state">State<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="state" name="state" required>
        </div>
        <div class="form-group">
            <label for="postalCode">Postal Code<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="postalCode" name="postalCode" required>
        </div>
        <div class="form-group">
            <label for="adress">Address<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="adress" name="adress" required>
        </div>
        <div class="form-group">
            <label for="phone">Phone<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="phone" name="phone" required>
        </div>
    <?php endif; ?>
    <table class="table">
        <thead>
        <tr>
            <th>Item</th>
            <th>Quantity</th>
            <th>Price</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($records as $item) : ?>
            <tr>
                <td><?php echo $item['name']; ?></td>
                <td><?php echo isset($cart[$item['good_id']]) ? $cart[$item['good_id']] + 1 : 1; ?></td>
                <td><?php echo $item['price']; ?> €</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="2" class="text-right"><strong>Total:</strong></td>
            <td><?php echo $totalPrice; ?> €</td>
        </tr>
        </tfoot>
    </table>
<input type="hidden" name="deliveryMethod" value="express"/>
    <input type="hidden" name="totalPrice" value="<?php echo $totalPrice;?>" />
    <input type="hidden" name="currency_code" value="EUR" />
    <!-- Payment method selection -->
    <div class="form-group">
        <label for="paymentMethod">Payment Method<span class="text-danger">*</span></label>
        <select class="form-control" id="paymentMethod" name="paymentMethod">
            <option value="cash">Cash</option>
            <option value="credit_card">Credit Card</option>
            <!-- Add other payment options here -->
        </select>
    </div>

    <div id="cardDetails" style="display: none;"> <!-- Add this div -->
        <h4 align="center">Payment Details</h4>
        <div class="form-group">
            <label>Card Number <span class="text-danger">*</span></label>
            <input type="text" name="card_holder_number" id="card_holder_number" class="form-control" placeholder="1234 5678 9012 3456" maxlength="20" onkeypress="" />
            <span id="error_card_number" class="text-danger"></span>
        </div>
        <div class="form-group">
            <div class="row">
                <div class="col-md-4">
                    <label>Expiry Month<span class="text-danger">*</span></label>
                    <input type="text" name="card_expiry_month" id="card_expiry_month" class="form-control" placeholder="MM" maxlength="2" onkeypress="return only_number(event);" />
                    <span id="error_card_expiry_month" class="text-danger"></span>
                </div>
                <div class="col-md-4">
                    <label>Expiry Year<span class="text-danger">*</span></label>
                    <input type="text" name="card_expiry_year" id="card_expiry_year" class="form-control" placeholder="YYYY" maxlength="4" onkeypress="return only_number(event);" />
                    <span id="error_card_expiry_year" class="text-danger"></span>
                </div>
                <div class="col-md-4">
                    <label>CVC<span class="text-danger">*</span></label>
                    <input type="text" name="card_cvc" id="card_cvc" class="form-control" placeholder="123" maxlength="4" onkeypress="return only_number(event);" />
                    <span id="error_card_cvc" class="text-danger"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Add a submit button -->
    <input type="button" name="button_action" id="button_action" class="btn btn-success btn-sm" onclick="stripePay(event)" value="Pay Now" />
    <!-- Display payment result -->
    <div id="payment-message" class="hidden"></div>


</form>

<script>

    // Add event listener to toggle card details based on payment method
    const paymentMethodSelect = document.getElementById('paymentMethod');
    const cardDetailsDiv = document.getElementById('cardDetails');

    paymentMethodSelect.addEventListener('change', function() {
        if (paymentMethodSelect.value === 'credit_card') {
            cardDetailsDiv.style.display = 'block';
        } else {
            cardDetailsDiv.style.display = 'none';
        }
    });

    function validate_form()
    {
        var valid_card = 0;
        var valid = false;
        var card_cvc = $('#card_cvc').val();
        var card_expiry_month = $('#card_expiry_month').val();
        var card_expiry_year = $('#card_expiry_year').val();
        var card_holder_number = $('#card_holder_number').val();
        var email_address = $('#email').val();
        var customer_name = $('#name').val();
        var customer_address = $('#adress').val();
        var customer_city = $('#state').val();
        var customer_pin = $('#postalCode').val();
        var customer_country = $('#state').val();
        var name_expression = /^[a-z ,.'-]+$/i;
        var email_expression = /^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/;
        var month_expression = /^01|02|03|04|05|06|07|08|09|10|11|12$/;
        var year_expression = /^2017|2018|2019|2020|2021|2022|2023|2024|2025|2026|2027|2028|2029|2030|2031$/;
        var cvv_expression = /^[0-9]{3,3}$/;

        $('#card_holder_number').validateCreditCard(function(result){
            if(result.valid)
            {
                $('#card_holder_number').removeClass('require');
                $('#error_card_number').text('');
                valid_card = 1;
            }
            else
            {
                $('#card_holder_number').addClass('require');
                $('#error_card_number').text('Invalid Card Number');
                valid_card = 0;
            }
        });

        if(valid_card == 1)
        {
            if(!month_expression.test(card_expiry_month))
            {
                $('#card_expiry_month').addClass('require');
                $('#error_card_expiry_month').text('Invalid Data');
                valid = false;
            }
            else
            {
                $('#card_expiry_month').removeClass('require');
                $('#error_card_expiry_month').text('');
                valid = true;
            }

            if(!year_expression.test(card_expiry_year))
            {
                $('#card_expiry_year').addClass('require');
                $('#error_card_expiry_year').error('Invalid Data');
                valid = false;
            }
            else
            {
                $('#card_expiry_year').removeClass('require');
                $('#error_card_expiry_year').error('');
                valid = true;
            }

            if(!cvv_expression.test(card_cvc))
            {
                $('#card_cvc').addClass('require');
                $('#error_card_cvc').text('Invalid Data');
                valid = false;
            }
            else
            {
                $('#card_cvc').removeClass('require');
                $('#error_card_cvc').text('');
                valid = true;
            }
            if(!name_expression.test(customer_name))
            {
                $('#name').addClass('require');
                $('#error_customer_name').text('Invalid Name');
                valid = false;
            }
            else
            {
                $('#name').removeClass('require');
                $('#error_customer_name').text('');
                valid = true;
            }

            if(!email_expression.test(email_address))
            {
                $('#email').addClass('require');
                $('#error_email_address').text('Invalid Email Address');
                valid = false;
            }
            else
            {
                $('#email').removeClass('require');
                $('#error_email_address').text('');
                valid = true;
            }

            if(customer_address == '')
            {
                $('#adress').addClass('require');
                $('#error_customer_address').text('Enter Address Detail');
                valid = false;
            }
            else
            {
                $('#adress').removeClass('require');
                $('#error_customer_address').text('');
                valid = true;
            }

            if(customer_city == '')
            {
                $('#state').addClass('require');
                $('#error_customer_city').text('Enter City');
                valid = false;
            }
            else
            {
                $('#state').removeClass('require');
                $('#error_customer_city').text('');
                valid = true;
            }

            if(customer_pin == '')
            {
                $('#postalCode').addClass('require');
                $('#error_customer_pin').text('Enter Zip code');
                valid = false;
            }
            else
            {
                $('#postalCode').removeClass('require');
                $('#error_customer_pin').text('');
                valid = true;
            }

            if(customer_country == '')
            {
                $('#state').addClass('require');
                $('#error_customer_country').text('Enter Country Detail');
                valid = false;
            }
            else
            {
                $('#state').removeClass('require');
                $('#error_customer_country').text('');
                valid = true;
            }


        }
        return valid;
    }

    Stripe.setPublishableKey('pk_test_51NmDH2GdHFRuQM4Cl9OH4vBBovvfDkFGZRi22ULaVzfsVVMHaCURSLARCDVzQBjB8Driwf6VrlpvePJwEPKiz4li00gklsLCnK');

    function stripeResponseHandler(status, response)
    {
        if(response.error)
        {
            console.log("Zkouška");
            $('#button_action').attr('disabled', false);
            $('#message').html(response.error.message).show();
        }
        else
        {

            var token = response['id'];
            $('#order_process_form').append("<input type='hidden' name='token' value='" + token + "' />");

            $('#order_process_form').submit();
        }
    }

    function stripePay(event)
    {
        event.preventDefault();
        $('#button_action').attr('disabled', 'disabled');
        $('#button_action').val('Payment Processing....');
console.log("test");
        Stripe.createToken({
            number:$('#card_holder_number').val(),
            cvc:$('#card_cvc').val(),
            exp_month : $('#card_expiry_month').val(),
            exp_year : $('#card_expiry_year').val()
        }, stripeResponseHandler);
        return false;

    }



    function only_number(event)
    {
        var charCode = (event.which) ? event.which : event.keyCode;
        if (charCode != 32 && charCode > 31 && (charCode < 48 || charCode > 57))
        {
            return false;
        }
        return true;
    }

    document.getElementById('button_action').addEventListener('click', function(event) {
        event.preventDefault();
        stripePay(event);
    });

</script>


<!-- Include JavaScript for Stripe integration -->
<script src="https://js.stripe.com/v3/"></script>


<?php require '../footer.php'; ?>
</body>
</html>
			