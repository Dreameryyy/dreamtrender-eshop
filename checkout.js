const stripe = Stripe("");

// Elements to capture card details
let elements;
let card;

initialize();
checkStatus();

document
    .querySelector("#payment-form")
    .addEventListener("submit", handleSubmit);

// Fetches a payment intent and captures the client secret
async function initialize() {
    const { clientSecret } = await fetch("/createpayment.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ totalPrice }),
    }).then((r) => r.json());

    elements = stripe.elements();

    // Create a Stripe Element for the card
    card = elements.create("card");

    // Mount the Stripe card element to the DOM
    card.mount("#card-elements");

    // Handle card element changes and display errors
    card.addEventListener("change", function (event) {
        const displayError = document.getElementById("card-errors");
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = "";
        }
    });
}

// Handle form submission
async function handleSubmit(e) {
    e.preventDefault();

    const { token, error } = await stripe.createToken(card);

    if (error) {
        // Display error to the user
        const errorElement = document.getElementById("card-errors");
        errorElement.textContent = error.message;
    } else {
        // Token was created successfully, proceed with form submission
        const form = document.getElementById("payment-form");
        const hiddenInput = document.createElement("input");
        hiddenInput.setAttribute("type", "hidden");
        hiddenInput.setAttribute("name", "stripeToken");
        hiddenInput.setAttribute("value", token.id);
        form.appendChild(hiddenInput);
        form.submit();
    }
}

