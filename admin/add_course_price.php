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

        .course-group {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            margin: 0;
        }

        .price-history-table {
            margin-bottom: 0;
        }

        .price-history-table tbody tr:last-child td {
            border-bottom: none;
        }

        .current-price {
            background-color: #f0fff0 !important;
            font-weight: bold;
        }

        .future-price {
            background-color: #fff8e1 !important;
        }

        .past-price {
            background-color: #f5f5f5 !important;
            color: #666;
        }

        .price-change {
            font-size: 12px;
            color: #666;
            margin-left: 5px;
        }

        .price-increase {
            color: #d9534f;
        }

        .price-decrease {
            color: #5cb85c;
        }

        .no-price-change {
            color: #999;
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
                    <h1 class="page-head-line">Course Price Management</h1>
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
                                <select class="form-control" name="courseID" required id="courseSelect">
                                    <option value="">Select Course</option>
                                    <?php
                                    $courses = mysqli_query($con, "
                                        SELECT c.*, 
                                            (SELECT price FROM courseprice 
                                             WHERE courseID = c.courseID 
                                             AND isDeleted = 0
                                             AND effectiveDate <= CURDATE()
                                             ORDER BY effectiveDate DESC LIMIT 1) as current_price
                                        FROM courses c 
                                        WHERE c.isDeleted = 0 
                                        ORDER BY c.title
                                    ");

                                    while ($course = mysqli_fetch_array($courses)) {
                                        $priceInfo = "";
                                        if ($course['current_price']) {
                                            $priceInfo = " (Current: RM " . number_format($course['current_price'], 2) . ")";
                                        } else {
                                            $priceInfo = " (No price set)";
                                        }

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
                                <small class="text-muted">Prices take effect from this date</small>
                            </div>
                            <button type="submit" name="add_price" class="btn btn-success btn-lg">
                                <i class="fa fa-save"></i> Add Price
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Stats -->
                <!-- <div class="col-md-6">
                    <div class="price-card">
                        <h3><i class="fa fa-chart-bar"></i> Price Overview</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="well text-center">
                                    <h4><i class="fa fa-book text-primary"></i></h4>
                                    <h3>
                                        <?php
                                        $courseCount = mysqli_fetch_array(mysqli_query($con, "
                                            SELECT COUNT(*) as cnt FROM courses WHERE isDeleted = 0
                                        "));
                                        echo $courseCount['cnt'];
                                        ?>
                                    </h3>
                                    <p>Total Courses</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="well text-center">
                                    <h4><i class="fa fa-money-bill-wave text-success"></i></h4>
                                    <h3>
                                        <?php
                                        $pricedCount = mysqli_fetch_array(mysqli_query($con, "
                                            SELECT COUNT(DISTINCT cp.courseID) as cnt 
                                            FROM courseprice cp
                                            JOIN courses c ON cp.courseID = c.courseID
                                            WHERE cp.isDeleted = 0 
                                            AND c.isDeleted = 0
                                            AND cp.effectiveDate <= CURDATE()
                                        "));
                                        echo $pricedCount['cnt'];
                                        ?>
                                    </h3>
                                    <p>Courses with Prices</p>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>Note:</strong> New prices will apply to all future bookings.
                        </div>
                    </div>
                </div> -->
            </div>

            <!-- Course Price History (Grouped by Course) -->
            <div class="row">
                <div class="col-md-12">
                    <div class="price-card">
                        <h3><i class="fa fa-history"></i> Course Price History</h3>
                        <p class="text-muted">Prices grouped by course with latest price highlighted</p>

                        <?php
                        // Get all courses with their price history
                        $courses = mysqli_query($con, "
                            SELECT c.courseID, c.title, c.staffLevel,
                                (SELECT price FROM courseprice 
                                 WHERE courseID = c.courseID 
                                 AND isDeleted = 0
                                 AND effectiveDate <= CURDATE()
                                 ORDER BY effectiveDate DESC LIMIT 1) as current_price
                            FROM courses c 
                            WHERE c.isDeleted = 0 
                            ORDER BY c.title
                        ");

                        $courseCounter = 0;
                        while ($course = mysqli_fetch_array($courses)) {
                            $courseCounter++;

                            // Get all prices for this course
                            $prices = mysqli_query($con, "
                                SELECT cp.*,
                                    (SELECT price FROM courseprice cp2 
                                     WHERE cp2.courseID = cp.courseID 
                                     AND cp2.isDeleted = 0
                                     AND cp2.effectiveDate < cp.effectiveDate
                                     ORDER BY cp2.effectiveDate DESC LIMIT 1) as previous_price
                                FROM courseprice cp
                                WHERE cp.courseID = {$course['courseID']}
                                AND cp.isDeleted = 0
                                ORDER BY cp.effectiveDate DESC, cp.creationDate DESC
                            ");

                            if (mysqli_num_rows($prices) > 0) {
                        ?>
                                <div class="course-group">
                                    <h4 class="course-header">
                                        <i class="fa fa-book"></i>
                                        <?php echo htmlentities($course['title']); ?>
                                        <small style="opacity: 0.8;">
                                            (ID: <?php echo $course['courseID']; ?> |
                                            <?php echo htmlentities($course['staffLevel']); ?>)
                                        </small>
                                        <span class="pull-right">
                                            <?php if ($course['current_price']) { ?>
                                                <span class="badge" style="background: rgba(255,255,255,0.2);">
                                                    Current: RM <?php echo number_format($course['current_price'], 2); ?>
                                                </span>
                                            <?php } ?>
                                        </span>
                                    </h4>

                                    <table class="table price-history-table">
                                        <thead>
                                            <tr>
                                                <th width="20%">Effective Date</th>
                                                <th width="25%">Price (RM)</th>
                                                <th width="20%">Change</th>
                                                <th width="20%">Status</th>
                                                <th width="15%">Created</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $rowCounter = 0;
                                            while ($price = mysqli_fetch_array($prices)) {
                                                $rowCounter++;

                                                // Determine status
                                                $today = date('Y-m-d');
                                                $effectiveDate = $price['effectiveDate'];

                                                if ($effectiveDate > $today) {
                                                    $status = 'Future';
                                                    $statusClass = 'future-price';
                                                    $statusBadge = 'warning';
                                                } elseif ($rowCounter === 1) {
                                                    $status = 'Current';
                                                    $statusClass = 'current-price';
                                                    $statusBadge = 'success';
                                                } else {
                                                    $status = 'Past';
                                                    $statusClass = 'past-price';
                                                    $statusBadge = 'default';
                                                }

                                                // Calculate price change
                                                $priceChange = '';
                                                $changeClass = '';

                                                if ($price['previous_price'] && $price['previous_price'] > 0) {
                                                    $changeAmount = $price['price'] - $price['previous_price'];
                                                    $changePercent = ($changeAmount / $price['previous_price']) * 100;

                                                    if ($changeAmount > 0) {
                                                        $priceChange = '+RM ' . number_format($changeAmount, 2) . ' (+' . number_format($changePercent, 1) . '%)';
                                                        $changeClass = 'price-increase';
                                                    } elseif ($changeAmount < 0) {
                                                        $priceChange = '-RM ' . number_format(abs($changeAmount), 2) . ' (' . number_format($changePercent, 1) . '%)';
                                                        $changeClass = 'price-decrease';
                                                    } else {
                                                        $priceChange = 'No change';
                                                        $changeClass = 'no-price-change';
                                                    }
                                                } else {
                                                    $priceChange = 'First price';
                                                    $changeClass = 'no-price-change';
                                                }
                                            ?>
                                                <tr class="<?php echo $statusClass; ?>">
                                                    <td>
                                                        <i class="fa fa-calendar"></i>
                                                        <?php echo date('d M Y', strtotime($effectiveDate)); ?>
                                                    </td>
                                                    <td>
                                                        <strong>RM <?php echo number_format($price['price'], 2); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="price-change <?php echo $changeClass; ?>">
                                                            <?php echo $priceChange; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="label label-<?php echo $statusBadge; ?>">
                                                            <?php echo $status; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo date('d M Y', strtotime($price['creationDate'])); ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php
                            } else {
                                // Course with no prices
                            ?>
                                <div class="course-group">
                                    <h4 class="course-header">
                                        <i class="fa fa-book"></i>
                                        <?php echo htmlentities($course['title']); ?>
                                        <small style="opacity: 0.8;">
                                            (ID: <?php echo $course['courseID']; ?> |
                                            <?php echo htmlentities($course['staffLevel']); ?>)
                                        </small>
                                    </h4>
                                    <div class="alert alert-warning" style="margin: 15px;">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        No prices set for this course.
                                        <a href="#addPriceForm" onclick="document.getElementById('courseSelect').value='<?php echo $course['courseID']; ?>';">
                                            Add a price now
                                        </a>
                                    </div>
                                </div>
                        <?php
                            }
                        }

                        if ($courseCounter == 0) {
                            echo '<div class="alert alert-info">No courses found.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Price Timeline Visualization -->
            <div class="row">
                <div class="col-md-12">
                    <div class="price-card">
                        <h3><i class="fa fa-chart-line"></i> Recent Price Changes</h3>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Course</th>
                                        <th>Old Price</th>
                                        <th>New Price</th>
                                        <th>Change</th>
                                        <th>Effective Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get recent price changes
                                    $recentChanges = mysqli_query($con, "
                                        SELECT 
                                            cp1.courseID,
                                            c.title,
                                            cp1.price as new_price,
                                            cp1.effectiveDate,
                                            cp1.creationDate,
                                            (SELECT price FROM courseprice cp2 
                                             WHERE cp2.courseID = cp1.courseID 
                                             AND cp2.isDeleted = 0
                                             AND cp2.effectiveDate < cp1.effectiveDate
                                             ORDER BY cp2.effectiveDate DESC LIMIT 1) as old_price
                                        FROM courseprice cp1
                                        JOIN courses c ON cp1.courseID = c.courseID
                                        WHERE cp1.isDeleted = 0
                                        AND c.isDeleted = 0
                                        HAVING old_price IS NOT NULL
                                        ORDER BY cp1.creationDate DESC
                                        LIMIT 10
                                    ");

                                    if (mysqli_num_rows($recentChanges) > 0) {
                                        while ($change = mysqli_fetch_array($recentChanges)) {
                                            $changeAmount = $change['new_price'] - $change['old_price'];
                                            $changePercent = ($changeAmount / $change['old_price']) * 100;

                                            if ($changeAmount > 0) {
                                                $changeClass = 'danger';
                                                $changeIcon = 'fa-arrow-up';
                                                $changeText = '+' . number_format($changePercent, 1) . '%';
                                            } elseif ($changeAmount < 0) {
                                                $changeClass = 'success';
                                                $changeIcon = 'fa-arrow-down';
                                                $changeText = number_format($changePercent, 1) . '%';
                                            } else {
                                                $changeClass = 'info';
                                                $changeIcon = 'fa-equals';
                                                $changeText = 'No change';
                                            }
                                    ?>
                                            <tr>
                                                <td><?php echo date('d M Y', strtotime($change['creationDate'])); ?></td>
                                                <td><?php echo htmlentities($change['title']); ?></td>
                                                <td>RM <?php echo number_format($change['old_price'], 2); ?></td>
                                                <td><strong>RM <?php echo number_format($change['new_price'], 2); ?></strong></td>
                                                <td>
                                                    <span class="label label-<?php echo $changeClass; ?>">
                                                        <i class="fa <?php echo $changeIcon; ?>"></i>
                                                        <?php echo $changeText; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($change['effectiveDate'])); ?></td>
                                            </tr>
                                    <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center text-muted">No price changes recorded yet</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- <div class="row">
                <div class="col-md-12">
                    <div class="text-center" style="margin-top: 30px;">
                        <a href="billing.php" class="btn btn-primary btn-lg">
                            <i class="fa fa-arrow-right"></i> Go to Billing Page
                        </a>
                    </div>
                </div>
            </div> -->

        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script src="../assets/js/jquery-1.11.1.js"></script>
    <script src="../assets/js/bootstrap.js"></script>
    <script>
        $(document).ready(function() {
            // Scroll to add price form when clicking "Add a price now"
            $('a[href="#addPriceForm"]').click(function(e) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('.price-card:first').offset().top - 20
                }, 500);
            });

            // Highlight current course when selected from history
            $('#courseSelect').change(function() {
                var courseID = $(this).val();
                $('.course-group').css('border-color', '#ddd');
                if (courseID) {
                    $('.course-group').each(function() {
                        var header = $(this).find('.course-header').text();
                        if (header.indexOf('ID: ' + courseID) > -1) {
                            $(this).css('border-color', '#5cb85c').css('border-width', '2px');
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>