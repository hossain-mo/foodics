<!-- resources/views/emails/low_stock_alert.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <title>Low Stock Alert</title>
</head>
<body>
    <p>Dear Merchant,</p>
    <p>The stock for {{ $ingredientName }} is low. Remaining stock: {{ $remainingStock }}g.</p>
    <p>Please replenish the stock as soon as possible.</p>
</body>
</html>
