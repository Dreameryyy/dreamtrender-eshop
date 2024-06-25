// Initialize Stripe with your publishable key
var stripe = Stripe('pk_test_51NmDH2GdHFRuQM4Cl9OH4vBBovvfDkFGZRi22ULaVzfsVVMHaCURSLARCDVzQBjB8Driwf6VrlpvePJwEPKiz4li00gklsLCnK');

// Create a Card Element
var elements = stripe.elements();
var card = elements.create('card');

// Mount the Card Element to the container you defined
card.mount('#card-elements');

// Handle form submission
var form = document.getElementById('payment-form');

form.addEventListener('submit', function(event) {
    event.preventDefault();

    stripe.createPaymentMethod({
        type: 'card',
        card: card,
    }).then(function(result) {
        if (result.error) {
            // Handle error
            var errorElement = document.getElementById('payment-message');
            errorElement.textContent = result.error.message;
            errorElement.classList.remove('hidden');
        } else {
            // Payment method created successfully, proceed with form submission
            var paymentMethod = result.paymentMethod;

            // Attach the paymentMethod.id to your form as a hidden input
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'paymentMethodId');
            hiddenInput.setAttribute('value', paymentMethod.id);
            form.appendChild(hiddenInput);

            // Submit the form
            form.submit();
        }
    });
});
