<?php
session_start();
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit();
}

// Add price
if (isset($_POST['add_price'])) {
    $courseID = (int)$_POST['courseID'];
    $price = (float)$_POST['price'];
    $effective_date = $_POST['effective_date'];

    $query = mysqli_query($con, "
        INSERT INTO courseprice (courseID, price, effectiveDate) 
        VALUES ($courseID, $price, '$effective_date')
    ");

    if ($query) {
        $success = "✓ Price added successfully!";
    } else {
        $error = "✗ Error: " . mysqli_error($con);
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Add Course Price</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet" />
    <link href="../assets/css/font-awesome.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        .price-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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
                    <h1 class="page-head-line">Add Course Prices</h1>
                    <p><a href="billing.php" class="btn btn-primary btn-sm">← Back to Billing</a></p>
                </div>
            </div>

            <?php if (isset($success)) { ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo $success; ?>
                </div>
            <?php } ?>

            <?php if (isset($error)) { ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo $error; ?>
                </div>
            <?php } ?>

            <!-- Add Price Form -->
            <div class="row">
                <div class="col-md-6">
                    <div class="price-card">
                        <h3><i class="fa fa-plus-circle"></i> Add New Price</h3>
                        <form method="post">
                            <div class="form-group">
                                <label>Course:</label>
                                <select class="form-control" name="courseID" required>
                                    <option value="">Select Course</option>
                                    <?php
                                    $courses = mysqli_query($con, "SELECT * FROM courses WHERE isDeleted = 0 ORDER BY title");
                                    while ($course = mysqli_fetch_array($courses)) {
                                        // Get current price
                                        $currentPriceQuery = mysqli_query($con, "
                                            SELECT price FROM courseprice 
                                            WHERE courseID = {$course['courseID']} 
                                            AND isDeleted = 0
                                            AND effectiveDate <= CURDATE()
                                            ORDER BY effectiveDate DESC LIMIT 1
                                        ");
                                        $currentPrice = mysqli_fetch_array($currentPriceQuery);
                                        $priceInfo = $currentPrice ? " (Current: RM " . number_format($currentPrice['price'], 2) . ")" : " (No price set)";

                                        echo "<option value='{$course['courseID']}'>{$course['title']}$priceInfo</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Price (RM):</label>
                                <input type="number" class="form-control" name="price"
                                    step="0.01" min="0" placeholder="500.00" required>
                            </div>
                            <div class="form-group">
                                <label>Effective Date:</label>
                                <input type="date" class="form-control" name="effective_date"
                                    value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <button type="submit" name="add_price" class="btn btn-success btn-lg">
                                <i class="fa fa-save"></i> Add Price
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Current Prices -->
                <div class="col-md-6">
                    <div class="price-card">
                        <h3><i class="fa fa-list"></i> Current Course Prices</h3>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Price</th>
                                    <th>Effective Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $prices = mysqli_query($con, "
                                    SELECT c.title, cp.price, cp.effectiveDate
                                    FROM courseprice cp
                                    JOIN courses c ON cp.courseID = c.courseID
                                    WHERE cp.isDeleted = 0
                                    AND cp.effectiveDate <= CURDATE()
                                    ORDER BY c.title
                                ");

                                if (mysqli_num_rows($prices) > 0) {
                                    while ($price = mysqli_fetch_array($prices)) {
                                        echo "<tr>";
                                        echo "<td>{$price['title']}</td>";
                                        echo "<td><strong>RM " . number_format($price['price'], 2) . "</strong></td>";
                                        echo "<td>" . date('d M Y', strtotime($price['effectiveDate'])) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center text-muted'>No prices set yet</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- All Prices History -->
            <div class="row">
                <div class="col-md-12">
                    <div class="price-card">
                        <h3><i class="fa fa-history"></i> All Price Records</h3>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Price (RM)</th>
                                    <th>Effective Date</th>
                                    <th>Created Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $allPrices = mysqli_query($con, "
                                    SELECT cp.*, c.title 
                                    FROM courseprice cp
                                    JOIN courses c ON cp.courseID = c.courseID
                                    WHERE cp.isDeleted = 0
                                    ORDER BY cp.effectiveDate DESC, cp.creationDate DESC
                                ");

                                while ($price = mysqli_fetch_array($allPrices)) {
                                    $isCurrent = ($price['effectiveDate'] <= date('Y-m-d'));
                                    $statusLabel = $isCurrent ? '<span class="label label-success">Active</span>' : '<span class="label label-warning">Future</span>';

                                    echo "<tr>";
                                    echo "<td>{$price['title']}</td>";
                                    echo "<td>RM " . number_format($price['price'], 2) . "</td>";
                                    echo "<td>" . date('d M Y', strtotime($price['effectiveDate'])) . "</td>";
                                    echo "<td>" . date('d M Y', strtotime($price['creationDate'])) . "</td>";
                                    echo "<td>$statusLabel</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <p class="text-center">
                        <a href="set_transfer_fees.php" class="btn btn-warning btn-lg">
                            <i class="fa fa-sync"></i> Update Transfer Fees for Existing Bookings
                        </a>
                        <a href="billing.php" class="btn btn-primary btn-lg">
                            <i class="fa fa-arrow-right"></i> Go to Billing Page
                        </a>
                    </p>
                </div>
            </div>

        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script src="../assets/js/jquery-1.11.1.js"></script>
    <script src="../assets/js/bootstrap.js"></script>
</body>

</html>