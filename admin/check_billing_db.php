<?php
include('includes/config.php');

echo "<h2>Billing Table Structure</h2>";
$result = mysqli_query($con, 'SHOW COLUMNS FROM billing');
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Default']}</td></tr>";
}
echo "</table>";

echo "<h2>Payment Table Structure</h2>";
$result = mysqli_query($con, 'SHOW COLUMNS FROM payment');
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Default']}</td></tr>";
}
echo "</table>";

echo "<h2>Booking Table Structure</h2>";
$result = mysqli_query($con, 'SHOW COLUMNS FROM booking');
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Default']}</td></tr>";
}
echo "</table>";

echo "<h2>Current Billing Records</h2>";
$result = mysqli_query($con, 'SELECT * FROM billing LIMIT 5');
echo "<pre>";
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";

echo "<h2>Current Bookings with Transfer Fee</h2>";
$result = mysqli_query($con, 'SELECT bookingID, courseRunID, nominatorID, delegateID, status, transferFee, creationDate FROM booking LIMIT 5');
echo "<pre>";
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";

echo "<h2>Test Query - Nominators with bookings this month</h2>";
$month = date('Y-m');
$query = "
    SELECT DISTINCT b.nominatorID, u.username
    FROM booking b
    JOIN users u ON b.nominatorID = u.id
    WHERE b.status = 'Confirmed'
    AND DATE_FORMAT(b.creationDate, '%Y-%m') = '$month'
";
echo "<p>Query: <code>$query</code></p>";
$result = mysqli_query($con, $query);
if (!$result) {
    echo "<p style='color:red'>Error: " . mysqli_error($con) . "</p>";
} else {
    echo "<p>Found " . mysqli_num_rows($result) . " nominators</p>";
    echo "<pre>";
    while($row = mysqli_fetch_assoc($result)) {
        print_r($row);
    }
    echo "</pre>";
}
?>
