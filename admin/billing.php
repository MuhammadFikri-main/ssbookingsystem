<?php
session_start();
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Billing Dashboard</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet" />
    <link href="../assets/css/font-awesome.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        .dashboard-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            text-align: center;
            transition: transform 0.2s;
            /* Add flexbox for equal height */
            display: flex;
            flex-direction: column;
            height: 100%;
            /* Ensure equal width within flex container */
            flex: 1 0 0;
            min-width: 0;
            /* Important for equal width in flex */
        }

        .dashboard-card h3 {
            margin-bottom: 10px;
        }

        .dashboard-card p {
            color: #666;
            margin-bottom: 20px;
            /* Allow paragraph to grow and push button down */
            flex: 1;
        }

        /* Make the row a flex container */
        .row.equal-height {
            display: flex;
            flex-wrap: wrap;
        }

        /* Make columns flex items */
        .row.equal-height>[class*="col-"] {
            display: flex;
        }

        /* Ensure button stays at bottom */
        .dashboard-card .btn-block {
            margin-top: auto;
            /* This pushes button to bottom */
            width: 100%;
        }
    </style>
</head>

<body>
    <?php include('includes/header.php'); ?>
    <?php if ($_SESSION['alogin'] != "") {
        include('includes/menubar.php');
    } ?>

    <div class="content-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line">Billing Management (BI)</h1>
                    <p class="text-muted">Manage course payments, generate invoices, and maintain price book</p>
                </div>
            </div>

            <?php if (isset($_SESSION['msg']) && $_SESSION['msg'] != '') { ?>
                <div class="alert alert-info alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo htmlentities($_SESSION['msg']);
                    $_SESSION['msg'] = ''; ?>
                </div>
            <?php } ?>

            <!-- Statistics -->
            <div class="row">
                <div class="col-md-3">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-file-invoice-dollar fa-3x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <?php
                                    $total = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as total FROM billing WHERE MONTH(billingDate) = MONTH(CURDATE())"));
                                    ?>
                                    <div class="huge"><?php echo $total['total']; ?></div>
                                    <div>This Month's Invoices</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="panel panel-success">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-check-circle fa-3x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <?php
                                    $paid = mysqli_fetch_array(mysqli_query($con, "SELECT SUM(totalAmount) as total FROM billing WHERE paymentStatus = 'Paid'"));
                                    ?>
                                    <div class="huge">RM <?php echo number_format($paid['total'] ?: 0, 2); ?></div>
                                    <div>Total Paid</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="panel panel-warning">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-clock fa-3x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <?php
                                    $pending = mysqli_fetch_array(mysqli_query($con, "SELECT SUM(totalAmount) as total FROM billing WHERE paymentStatus = 'Pending'"));
                                    ?>
                                    <div class="huge">RM <?php echo number_format($pending['total'] ?: 0, 2); ?></div>
                                    <div>Pending Payment</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="panel panel-danger">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-exclamation-triangle fa-3x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <?php
                                    $overdue = mysqli_fetch_array(mysqli_query($con, "SELECT SUM(totalAmount) as total FROM billing WHERE paymentStatus = 'Pending' AND dueDate < CURDATE()"));
                                    ?>
                                    <div class="huge">RM <?php echo number_format($overdue['total'] ?: 0, 2); ?></div>
                                    <div>Overdue</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="row equal-height">
                <div class="col-md-4 equal-height-col">
                    <div class="dashboard-card" style="border-top: 4px solid #5bc0de;">
                        <h3>Generate Monthly Bills</h3>
                        <p>Create invoices for all nominators based on bookings</p>
                        <a href="generate_monthly_bills.php" class="btn btn-info btn-block">
                            <i class="fa fa-calendar-check"></i> Generate Bills
                        </a>
                    </div>
                </div>

                <div class="col-md-4 equal-height-col">
                    <div class="dashboard-card" style="border-top: 4px solid #5cb85c;">
                        <h3>View Invoices</h3>
                        <p>View all invoices and mark payments</p>
                        <a href="view_invoices.php" class="btn btn-success btn-block">
                            <i class="fa fa-list"></i> View Invoices
                        </a>
                    </div>
                </div>

                <div class="col-md-4 equal-height-col">
                    <div class="dashboard-card" style="border-top: 4px solid #f0ad4e;">
                        <h3>Price Book</h3>
                        <p>Manage course prices and rates</p>
                        <a href="add_course_price.php" class="btn btn-warning btn-block">
                            <i class="fa fa-tags"></i> Manage Prices
                        </a>
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