<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Money - CrossPay</title>
    <!-- إضافة خطوط من Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom right, #e0f7fa, #ffffff);
            color: #333;
        }

        .container {
            max-width: 500px;
            margin: 60px auto;
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: left;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            color: #1a73e8;
            font-size: 2rem;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 700;
        }

        .recipient {
            color: #0c47a1;
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
            animation: slideIn 1s ease-in-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        label {
            display: block;
            margin: 15px 0 8px;
            font-weight: 500;
            font-size: 0.95rem;
            color: #555;
        }

        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus {
            border-color: #1a73e8;
            outline: none;
        }

        .amount-input {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a73e8;
        }

        button {
            width: 100%;
            padding: 14px;
            font-size: 1.1rem;
            background-color: #1a73e8;
            color: #fff;
            border: none;
            border-radius: 6px;
            margin-top: 25px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-weight: 500;
        }

        button:hover {
            background-color: #1669c1;
            transform: translateY(-2px);
        }

        .agreement {
            margin-top: 20px;
            padding: 15px;
            background: #f1f3f4;
            border: 1px solid #dcdcdc;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #555;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }

        .checkbox-container input {
            margin-right: 10px;
            width: 18px;
            height: 18px;
        }

        .checkbox-container label {
            margin: 0;
            font-size: 0.95rem;
            color: #333;
            cursor: pointer;
            user-select: none;
        }

        .error {
            color: #d93025;
            margin-top: 10px;
            font-size: 0.9rem;
            display: none;
        }

        @media (max-width: 600px) {
            .container {
                margin: 30px 20px;
                padding: 30px 20px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .recipient {
                font-size: 1.4rem;
            }

            button {
                font-size: 1rem;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Send Money</h1>
        <div class="recipient" id="recipientName">To: Mohammed Alnwajha</div>
        <form id="paymentForm" action="process_payment.php" method="post">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required placeholder="Enter your full name">

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="example@example.com">

            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" required placeholder="+970599110011">

            <label for="currency">Currency</label>
            <select id="currency" name="currency" required>
                <option value="USD">US Dollar (USD)</option>
                <option value="ILS">Shekel (ILS)</option>
            </select>

            <label for="amount">Payment Amount</label>
            <input type="number" id="amount" name="amount" class="amount-input" required placeholder="0.00" min="1" step="0.01">
            <span id="currencySymbol" style="font-size: 0.9rem; color: #555;">USD</span>

            <div class="agreement">
                <p>We do not store any sensitive data, and all transactions are securely processed using 3D Secure technology.</p>
            </div>

            <div class="checkbox-container">
                <input type="checkbox" id="agree" name="agree">
                <label for="agree">I agree to the Terms of Use and Privacy Policy</label>
            </div>

            <div id="error-message" class="error">You must agree to the Terms of Use and Privacy Policy to proceed.</div>

            <button type="button" id="payButton">Pay Now</button>
        </form>
    </div>

    <script>
        const currencySelect = document.getElementById('currency');
        const currencySymbol = document.getElementById('currencySymbol');
        const payButton = document.getElementById('payButton');
        const amountInput = document.getElementById('amount');
        const agreeCheckbox = document.getElementById('agree');
        const errorMessage = document.getElementById('error-message');
        const paymentForm = document.getElementById('paymentForm');
        const recipientName = document.getElementById('recipientName').textContent;

        function updateCurrencySymbol() {
            const selectedCurrency = currencySelect.value;
            currencySymbol.textContent = selectedCurrency;
            if (selectedCurrency === 'USD') {
                amountInput.max = 2000;
            } else if (selectedCurrency === 'ILS') {
                amountInput.max = 7000;
            }
        }

        payButton.addEventListener('click', function () {
            const amount = parseFloat(amountInput.value);
            const maxAmount = parseFloat(amountInput.max);

            if (isNaN(amount) || amount < 1 || amount > maxAmount) {
                alert(`Please enter an amount between 1 and ${maxAmount} ${currencySelect.value}`);
                return;
            }

            if (!agreeCheckbox.checked) {
                errorMessage.style.display = 'block';
                return;
            } else {
                errorMessage.style.display = 'none';
            }

            if (confirm(`You are about to send ${amount} ${currencySelect.value} to ${recipientName}. Do you want to proceed?`)) {
                paymentForm.submit();
            }
        });

        currencySelect.addEventListener('change', updateCurrencySymbol);
        updateCurrencySymbol();
    </script>
</body>
</html>
