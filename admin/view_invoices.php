<?php
session_start();
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit();
}

// Mark as paid
if (isset($_POST['mark_paid'])) {
    $billingID = (int)$_POST['billingID'];
    $payment_method = mysqli_real_escape_string($con, $_POST['payment_method']);
    $reference = mysqli_real_escape_string($con, $_POST['reference']);

    mysqli_query($con, "
        UPDATE billing SET 
        paymentStatus = 'Paid', 
        paymentDate = CURDATE(),
        paymentMethod = '$payment_method',
        transactionReference = '$reference'
        WHERE billingID = $billingID
    ");

    $success = "✓ Payment recorded successfully!";
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>View Invoices</title>
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
            <h1 class="page-head-line">View Invoices</h1>
            <p><a href="billing.php" class="btn btn-primary btn-sm">← Back to Billing Dashboard</a></p>

            <?php if (isset($success)) { ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php } ?>

            <div class="panel panel-default">
                <div class="panel-heading">All Invoices</div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Nominator</th>
                                    <th>Billing Month</th>
                                    <th>Bookings</th>
                                    <th>Amount</th>
                                    <th>Billing Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $invoices = mysqli_query($con, "
                                    SELECT b.*, u.username as nominator_name,
                                           COUNT(DISTINCT p.bookingID) as booking_count
                                    FROM billing b
                                    LEFT JOIN users u ON b.nominatorID = u.id
                                    LEFT JOIN payment p ON b.billingID = p.billingID
                                    GROUP BY b.billingID
                                    ORDER BY b.billingDate DESC
                                ");

                                if (mysqli_num_rows($invoices) > 0) {
                                    while ($invoice = mysqli_fetch_array($invoices)) {
                                        $statusClass = '';
                                        switch ($invoice['paymentStatus']) {
                                            case 'Paid':
                                                $statusClass = 'success';
                                                break;
                                            case 'Pending':
                                                $statusClass = ($invoice['dueDate'] < date('Y-m-d')) ? 'danger' : 'warning';
                                                break;
                                            default:
                                                $statusClass = 'default';
                                        }
                                ?>
                                        <tr>
                                            <td><?php echo htmlentities($invoice['invoiceNumber']); ?></td>
                                            <td><?php echo htmlentities($invoice['nominator_name'] ?: 'N/A'); ?></td>
                                            <td><?php echo $invoice['billingMonth'] ?: 'N/A'; ?></td>
                                            <td><?php echo $invoice['booking_count']; ?></td>
                                            <td>RM <?php echo number_format($invoice['totalAmount'], 2); ?></td>
                                            <td><?php echo date('d M Y', strtotime($invoice['billingDate'])); ?></td>
                                            <td class="<?php echo ($invoice['dueDate'] < date('Y-m-d') && $invoice['paymentStatus'] == 'Pending') ? 'text-danger' : ''; ?>">
                                                <?php echo date('d M Y', strtotime($invoice['dueDate'])); ?>
                                            </td>
                                            <td>
                                                <span class="label label-<?php echo $statusClass; ?>">
                                                    <?php echo htmlentities($invoice['paymentStatus']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($invoice['paymentStatus'] == 'Pending') { ?>
                                                    <button class="btn btn-success btn-xs" data-toggle="modal"
                                                        data-target="#paymentModal<?php echo $invoice['billingID']; ?>">
                                                        <i class="fa fa-check"></i> Mark Paid
                                                    </button>
                                                <?php } ?>
                                            </td>
                                        </tr>

                                        <!-- Payment Modal for each invoice -->
                                        <div class="modal fade" id="paymentModal<?php echo $invoice['billingID']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <div class="modal-header">
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                            <h4 class="modal-title">Record Payment - <?php echo $invoice['invoiceNumber']; ?></h4>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="billingID" value="<?php echo $invoice['billingID']; ?>">
                                                            <div class="form-group">
                                                                <label>Payment Method:</label>
                                                                <select class="form-control" name="payment_method" required>
                                                                    <option value="">Select Method</option>
                                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                                    <option value="Cash">Cash</option>
                                                                    <option value="Credit Card">Credit Card</option>
                                                                    <option value="Other">Other</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Transaction Reference:</label>
                                                                <input type="text" class="form-control" name="reference"
                                                                    placeholder="Bank reference, receipt #, etc.">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="mark_paid" class="btn btn-success">Save Payment</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='9' class='text-center text-muted'>No invoices found. Generate bills first.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script src="../assets/js/jquery-1.11.1.js"></script>
    <script src="../assets/js/bootstrap.js"></script>
</body>

</html>