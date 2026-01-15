<?php
session_start();
include('includes/config.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit();
}

// Generate monthly bills
if (isset($_POST['generate_bills'])) {
    $month = $_POST['month'];

    // Get all unique nominators who have active bookings (not cancelled)
    $nominatorsQuery = mysqli_query($con, "
        SELECT DISTINCT b.nominatorID, u.username
        FROM booking b
        JOIN users u ON b.nominatorID = u.id
        JOIN courserun cr ON b.courseRunID = cr.courseRunID
        WHERE b.status = 'Confirmed' 
        AND b.isCanceled = 0  -- Only non-cancelled bookings
        AND cr.status != 'Cancelled'  -- Only non-cancelled course runs
        AND DATE_FORMAT(b.creationDate, '%Y-%m') = '$month'
    ");

    if (!$nominatorsQuery) {
        $error = "Error finding nominators: " . mysqli_error($con);
    } else {
        $billsGenerated = 0;
        $nominatorCount = mysqli_num_rows($nominatorsQuery);

        if ($nominatorCount == 0) {
            $error = "No active bookings found for month: $month";
        } else {
            while ($nominator = mysqli_fetch_array($nominatorsQuery)) {
                $nominatorID = $nominator['nominatorID'];

                // Check if bill already exists
                $existingBill = mysqli_query($con, "
                    SELECT billingID FROM billing 
                    WHERE nominatorID = $nominatorID 
                    AND billingMonth = '$month'
                ");

                if (mysqli_num_rows($existingBill) > 0) {
                    continue;
                }

                // Get all ACTIVE bookings for this nominator (not cancelled)
                $bookingsQuery = mysqli_query($con, "
                    SELECT b.*, cr.courseID, c.title, cr.status as courseRunStatus
                    FROM booking b
                    JOIN courserun cr ON b.courseRunID = cr.courseRunID
                    JOIN courses c ON cr.courseID = c.courseID
                    WHERE b.nominatorID = $nominatorID
                    AND b.status = 'Confirmed'
                    AND b.isCanceled = 0  -- Only non-cancelled bookings
                    AND cr.status != 'Cancelled'  -- Only non-cancelled course runs
                    AND DATE_FORMAT(b.creationDate, '%Y-%m') = '$month'
                ");

                $totalAmount = 0;
                $bookingDetails = [];

                while ($booking = mysqli_fetch_array($bookingsQuery)) {
                    $fee = $booking['transferFee'];

                    // If transferFee is 0, get course price
                    if ($fee == 0) {
                        $priceQuery = mysqli_query($con, "
                            SELECT price FROM courseprice 
                            WHERE courseID = {$booking['courseID']} 
                            AND isDeleted = 0 
                            AND effectiveDate <= CURDATE()
                            ORDER BY effectiveDate DESC LIMIT 1
                        ");
                        $priceData = mysqli_fetch_array($priceQuery);
                        $fee = $priceData ? $priceData['price'] : 0;

                        if ($fee > 0) {
                            mysqli_query($con, "UPDATE booking SET transferFee = $fee WHERE bookingID = {$booking['bookingID']}");
                            $booking['transferFee'] = $fee;
                        }
                    }

                    $totalAmount += $fee;
                    $bookingDetails[] = $booking;
                }

                if ($totalAmount > 0 && count($bookingDetails) > 0) {
                    // Generate invoice number
                    $year = date('Y');
                    $invoice_count = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) + 1 as count FROM billing WHERE YEAR(billingDate) = $year"))['count'];
                    $invoice_number = "INV-" . $year . "-" . str_pad($invoice_count, 3, '0', STR_PAD_LEFT);

                    $billing_date = date('Y-m-d');
                    $due_date = date('Y-m-d', strtotime('+30 days'));

                    $billingQuery = mysqli_query($con, "
                        INSERT INTO billing (invoiceNumber, nominatorID, totalAmount, billingDate, dueDate, billingType, billingMonth) 
                        VALUES ('$invoice_number', $nominatorID, $totalAmount, '$billing_date', '$due_date', 'Monthly', '$month')
                    ");

                    if ($billingQuery) {
                        $billingID = mysqli_insert_id($con);

                        foreach ($bookingDetails as $booking) {
                            mysqli_query($con, "
                                INSERT INTO payment (billingID, bookingID, nominatorID, totalAmount) 
                                VALUES ($billingID, {$booking['bookingID']}, $nominatorID, {$booking['transferFee']})
                            ");
                        }

                        $billsGenerated++;
                    }
                }
            }

            if ($billsGenerated > 0) {
                $success = "✓ Successfully generated $billsGenerated invoice(s) for $nominatorCount nominator(s).";
            } else {
                $error = "⚠️ Found $nominatorCount nominator(s) but no invoices generated. Transfer fees might be 0 or courses have no prices set.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Generate Monthly Bills</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet" />
    <link href="../assets/css/font-awesome.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
</head>

<body>
    <?php include('includes/header.php'); ?>
    <?php if ($_SESSION['alogin'] != "") {
        include('includes/menubar.php');
    } ?>

    <div class="content-wrapper">
        <div class="container">
            <h1 class="page-head-line">Generate Monthly Bills</h1>
            <p><a href="billing.php" class="btn btn-primary btn-sm">← Back to Billing Dashboard</a></p>

            <?php if (isset($success)) { ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php } ?>
            <?php if (isset($error)) { ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php } ?>

            <div class="panel panel-info">
                <div class="panel-heading">
                    <i class="fa fa-calendar-check"></i> Generate Monthly Invoices
                </div>
                <div class="panel-body">
                    <form method="post">
                        <div class="form-group">
                            <label>Select Month:</label>
                            <input type="month" name="month" class="form-control" value="<?php echo date('Y-m'); ?>" required>
                            <p class="help-block">This will generate invoices for each Nominating Manager based on all ACTIVE (non-cancelled) confirmed bookings in the selected month.</p>
                        </div>
                        <button type="submit" name="generate_bills" class="btn btn-primary btn-lg">
                            <i class="fa fa-file-invoice"></i> Generate All Bills for This Month
                        </button>
                    </form>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-eye"></i> Preview: Bookings for Current Month (Active Only)
                </div>
                <div class="panel-body">
                    <?php
                    $currentMonth = date('Y-m');
                    $previewQuery = mysqli_query($con, "
                        SELECT 
                            u.username, 
                            COUNT(b.bookingID) as booking_count, 
                            SUM(b.transferFee) as total_fees,
                            GROUP_CONCAT(DISTINCT CONCAT(c.title, ' (', cr.status, ')') SEPARATOR ', ') as course_details
                        FROM booking b
                        JOIN users u ON b.nominatorID = u.id
                        JOIN courserun cr ON b.courseRunID = cr.courseRunID
                        JOIN courses c ON cr.courseID = c.courseID
                        WHERE b.status = 'Confirmed' 
                        AND b.isCanceled = 0  -- Only non-cancelled bookings
                        AND cr.status != 'Cancelled'  -- Only non-cancelled course runs
                        AND DATE_FORMAT(b.creationDate, '%Y-%m') = '$currentMonth'
                        GROUP BY b.nominatorID
                    ");

                    if (mysqli_num_rows($previewQuery) > 0) {
                        echo "<div class='alert alert-info'><i class='fa fa-info-circle'></i> Showing only ACTIVE bookings (not cancelled)</div>";
                        echo "<table class='table table-striped'>";
                        echo "<tr><th>Nominator</th><th>Bookings</th><th>Total Amount</th><th>Courses</th></tr>";
                        while ($row = mysqli_fetch_array($previewQuery)) {
                            echo "<tr>";
                            echo "<td><strong>{$row['username']}</strong></td>";
                            echo "<td>{$row['booking_count']}</td>";
                            echo "<td>RM " . number_format($row['total_fees'], 2) . "</td>";
                            echo "<td><small>" . htmlentities($row['course_details']) . "</small></td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p class='text-muted'>No active bookings found for current month.</p>";
                    }
                    ?>
                </div>
            </div>

            <!-- Debug: Show ALL bookings including cancelled -->
            <!-- <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-bug"></i> Debug: All Bookings for Current Month (Including Cancelled)
                </div>
                <div class="panel-body">
                    <?php
                    $debugQuery = mysqli_query($con, "
                        SELECT 
                            b.bookingID,
                            u.username as nominator,
                            d.username as delegate,
                            c.title as course,
                            cr.status as courseRunStatus,
                            b.isCanceled as bookingCancelled,
                            b.transferFee,
                            DATE_FORMAT(b.creationDate, '%Y-%m-%d') as bookingDate
                        FROM booking b
                        JOIN users u ON b.nominatorID = u.id
                        JOIN users d ON b.delegateID = d.id
                        JOIN courserun cr ON b.courseRunID = cr.courseRunID
                        JOIN courses c ON cr.courseID = c.courseID
                        WHERE DATE_FORMAT(b.creationDate, '%Y-%m') = '$currentMonth'
                        ORDER BY b.isCanceled, u.username, b.creationDate
                    ");

                    if (mysqli_num_rows($debugQuery) > 0) {
                        echo "<table class='table table-bordered table-sm'>";
                        echo "<tr class='active'>
                                <th>Booking ID</th>
                                <th>Nominator</th>
                                <th>Delegate</th>
                                <th>Course</th>
                                <th>CourseRun Status</th>
                                <th>Booking Status</th>
                                <th>Fee</th>
                                <th>Date</th>
                              </tr>";
                        while ($row = mysqli_fetch_array($debugQuery)) {
                            $rowClass = '';
                            if ($row['isCanceled'] == 1) {
                                $rowClass = 'class="danger"';
                            } elseif ($row['courseRunStatus'] == 'Cancelled') {
                                $rowClass = 'class="warning"';
                            }

                            $bookingStatus = ($row['bookingCancelled'] == 1) ?
                                '<span class="label label-danger">Cancelled</span>' :
                                '<span class="label label-success">Active</span>';

                            $courseRunStatus = ($row['courseRunStatus'] == 'Cancelled') ?
                                '<span class="label label-warning">Cancelled</span>' :
                                '<span class="label label-info">' . $row['courseRunStatus'] . '</span>';

                            echo "<tr $rowClass>";
                            echo "<td>{$row['bookingID']}</td>";
                            echo "<td>{$row['nominator']}</td>";
                            echo "<td>{$row['delegate']}</td>";
                            echo "<td>{$row['course']}</td>";
                            echo "<td>$courseRunStatus</td>";
                            echo "<td>$bookingStatus</td>";
                            echo "<td>RM " . number_format($row['transferFee'], 2) . "</td>";
                            echo "<td>{$row['bookingDate']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                        echo "<p class='text-muted'><small>Legend: <span class='label label-danger'>Cancelled Booking</span> <span class='label label-warning'>Cancelled CourseRun</span> <span class='label label-success'>Active Booking</span></small></p>";
                    }
                    ?>
                </div>
            </div> -->
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script src="../assets/js/jquery-1.11.1.js"></script>
    <script src="../assets/js/bootstrap.js"></script>
</body>

</html>