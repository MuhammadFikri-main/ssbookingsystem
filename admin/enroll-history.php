<?php
session_start();
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
} else {
    // Code to delete a course run
    if (isset($_GET['del'])) {
        $id = (int)$_GET['id'];
        mysqli_query($con, "DELETE FROM courserun WHERE courseRunID = $id");
        echo '<script>alert("Course Run Deleted Successfully !!")</script>';
        echo '<script>window.location.href="course-run.php"</script>';
    }

    // Code to change status
    if (isset($_GET['status'])) {
        $id = (int)$_GET['id'];
        $status = mysqli_real_escape_string($con, $_GET['status']);
        mysqli_query($con, "UPDATE courserun SET status = '$status' WHERE courseRunID = $id");
        echo '<script>alert("Course Run Status Updated Successfully !!")</script>';
        echo '<script>window.location.href="course-run.php"</script>';
    }
?>

    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Course Run Management</title>
        <link href="../assets/css/bootstrap.css" rel="stylesheet" />
        <link href="../assets/css/font-awesome.css" rel="stylesheet" />
        <link href="../assets/css/style.css" rel="stylesheet" />
    </head>

    <body>
        <?php include('includes/header.php'); ?>
        <!-- LOGO HEADER END-->
        <?php if ($_SESSION['alogin'] != "") {
            include('includes/menubar.php');
        }
        ?>
        <!-- MENU SECTION END-->
        <div class="content-wrapper">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="page-head-line">Course Run Management</h1>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-3">
                                        <i class="fa fa-calendar fa-3x"></i>
                                    </div>
                                    <div class="col-xs-9 text-right">
                                        <?php
                                        $total = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as total FROM courserun"));
                                        ?>
                                        <div class="huge"><?php echo $total['total']; ?></div>
                                        <div>Total Course Runs</div>
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
                                        $completed = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as completed FROM courserun WHERE status = 'Completed'"));
                                        ?>
                                        <div class="huge"><?php echo $completed['completed']; ?></div>
                                        <div>Completed</div>
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
                                        <i class="fa fa-spinner fa-3x"></i>
                                    </div>
                                    <div class="col-xs-9 text-right">
                                        <?php
                                        $ongoing = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as ongoing FROM courserun WHERE status = 'Ongoing'"));
                                        ?>
                                        <div class="huge"><?php echo $ongoing['ongoing']; ?></div>
                                        <div>Ongoing</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-3">
                                        <i class="fa fa-clock-o fa-3x"></i>
                                    </div>
                                    <div class="col-xs-9 text-right">
                                        <?php
                                        $scheduled = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as scheduled FROM courserun WHERE status = 'Scheduled'"));
                                        ?>
                                        <div class="huge"><?php echo $scheduled['scheduled']; ?></div>
                                        <div>Scheduled</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <!-- Course Run Table -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Course Run Schedule
                                <div class="pull-right">
                                    <a href="schedule-course.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> Schedule New Course Run
                                    </a>
                                </div>
                            </div>
                            <!-- /.panel-heading -->
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Course</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Duration</th>
                                                <th>Location</th>
                                                <th>Status</th>
                                                <th>Created Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Fetch all course runs with course details
                                            $sql = mysqli_query($con, "
                                            SELECT 
                                                cr.courseRunID,
                                                cr.courseID,
                                                cr.startDate,
                                                cr.endDate,
                                                cr.location,
                                                cr.status,
                                                cr.creationDate,
                                                c.title as course_title,
                                                c.durationDays,
                                                c.maxStudents,
                                                c.staffLevel,
                                                DATEDIFF(cr.endDate, cr.startDate) + 1 as run_duration,
                                                COUNT(b.bookingID) as enrolled_count
                                            FROM courserun cr
                                            LEFT JOIN courses c ON cr.courseID = c.courseID
                                            LEFT JOIN booking b ON cr.courseRunID = b.courseRunID
                                            GROUP BY cr.courseRunID
                                            ORDER BY cr.startDate DESC, cr.status
                                        ");

                                            $cnt = 1;
                                            while ($row = mysqli_fetch_array($sql)) {
                                                // Determine status badge color
                                                $statusClass = 'default';
                                                switch ($row['status']) {
                                                    case 'Scheduled':
                                                        $statusClass = 'info';
                                                        break;
                                                    case 'Ongoing':
                                                        $statusClass = 'warning';
                                                        break;
                                                    case 'Completed':
                                                        $statusClass = 'success';
                                                        break;
                                                    case 'Cancelled':
                                                        $statusClass = 'danger';
                                                        break;
                                                }

                                                // Check if dates are in past/present/future
                                                $today = date('Y-m-d');
                                                $startDate = $row['startDate'];
                                                $endDate = $row['endDate'];

                                                // Auto-update status based on dates (optional)
                                                if ($row['status'] == 'Scheduled' && $startDate <= $today) {
                                                    $statusClass = 'warning';
                                                }
                                                if ($row['status'] == 'Ongoing' && $endDate < $today) {
                                                    $statusClass = 'success';
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo $cnt; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlentities($row['course_title']); ?></strong><br>
                                                        <small class="text-muted">
                                                            Max: <?php echo $row['maxStudents']; ?> students |
                                                            Staff: <?php echo htmlentities($row['staffLevel']); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <?php echo date('d M Y', strtotime($row['startDate'])); ?>
                                                        <?php if ($row['startDate'] < $today): ?>
                                                            <span class="label label-success">Started</span>
                                                        <?php elseif ($row['startDate'] == $today): ?>
                                                            <span class="label label-warning">Today</span>
                                                        <?php else: ?>
                                                            <span class="label label-info">Upcoming</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo date('d M Y', strtotime($row['endDate'])); ?>
                                                        <?php if ($row['endDate'] < $today): ?>
                                                            <span class="label label-default">Ended</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $row['run_duration']; ?> days<br>
                                                        <small><?php echo htmlentities($row['durationDays']); ?> days planned</small>
                                                    </td>
                                                    <td><?php echo htmlentities($row['location']); ?></td>
                                                    <td>
                                                        <span class="label label-<?php echo $statusClass; ?>">
                                                            <?php echo htmlentities($row['status']); ?>
                                                        </span><br>
                                                        <small>Enrolled: <?php echo $row['enrolled_count']; ?>/<?php echo $row['maxStudents']; ?></small>
                                                    </td>
                                                    <td><?php echo date('d-m-Y', strtotime($row['creationDate'])); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <!-- Status Change Dropdown -->
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                                                    Status <span class="caret"></span>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <li><a href="course-run.php?id=<?php echo $row['courseRunID']; ?>&status=Scheduled" onclick="return confirm('Change status to Scheduled?')">Scheduled</a></li>
                                                                    <li><a href="course-run.php?id=<?php echo $row['courseRunID']; ?>&status=Ongoing" onclick="return confirm('Change status to Ongoing?')">Ongoing</a></li>
                                                                    <li><a href="course-run.php?id=<?php echo $row['courseRunID']; ?>&status=Completed" onclick="return confirm('Change status to Completed?')">Completed</a></li>
                                                                    <li><a href="course-run.php?id=<?php echo $row['courseRunID']; ?>&status=Cancelled" onclick="return confirm('Change status to Cancelled?')">Cancelled</a></li>
                                                                </ul>
                                                            </div>

                                                            <a href="edit-course-run.php?id=<?php echo $row['courseRunID']; ?>" class="btn btn-primary btn-xs" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                            <a href="view-course-run.php?courseID=<?php echo $row['courseID']; ?>&courseRunID=<?php echo $row['courseRunID']; ?>" class="btn btn-info btn-xs" title="View Attendees">
                                                                <i class="fa fa-users"></i>
                                                            </a>
                                                            <a href="course-run.php?id=<?php echo $row['courseRunID']; ?>&del=delete"
                                                                onClick="return confirm('Are you sure you want to delete this course run?')"
                                                                class="btn btn-danger btn-xs" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                                $cnt++;
                                            }

                                            if ($cnt == 1): ?>
                                                <tr>
                                                    <td colspan="9" class="text-center">
                                                        No course runs found.
                                                        <a href="schedule-course.php">Schedule your first course run</a>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Course Runs -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <i class="fa fa-calendar"></i> Upcoming Course Runs (Next 30 Days)
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <?php
                                    $upcomingQuery = mysqli_query($con, "
                                    SELECT 
                                        cr.courseRunID,
                                        cr.courseID,
                                        cr.startDate,
                                        cr.endDate,
                                        cr.location,
                                        c.title as course_title,
                                        DATEDIFF(cr.startDate, CURDATE()) as days_until_start
                                    FROM courserun cr
                                    LEFT JOIN courses c ON cr.courseID = c.courseID
                                    WHERE cr.startDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                                    AND cr.status = 'Scheduled'
                                    ORDER BY cr.startDate ASC
                                    LIMIT 6
                                ");

                                    while ($upcoming = mysqli_fetch_array($upcomingQuery)) {
                                        $daysUntil = $upcoming['days_until_start'];
                                        $panelClass = ($daysUntil <= 3) ? 'panel-warning' : 'panel-info';
                                    ?>
                                        <div class="col-md-4">
                                            <div class="panel <?php echo $panelClass; ?>">
                                                <div class="panel-heading">
                                                    <h3 class="panel-title"><?php echo htmlentities($upcoming['course_title']); ?></h3>
                                                </div>
                                                <div class="panel-body">
                                                    <p><strong>Start:</strong> <?php echo date('d M Y', strtotime($upcoming['startDate'])); ?></p>
                                                    <p><strong>End:</strong> <?php echo date('d M Y', strtotime($upcoming['endDate'])); ?></p>
                                                    <p><strong>Location:</strong> <?php echo htmlentities($upcoming['location']); ?></p>
                                                    <p>
                                                        <span class="label label-<?php echo ($daysUntil <= 3) ? 'warning' : 'info'; ?>">
                                                            <?php echo ($daysUntil == 0) ? 'Starts Today' : "Starts in $daysUntil days"; ?>
                                                        </span>
                                                    </p>
                                                    <a href="view-course-run.php?courseID=<?php echo $upcoming['courseID']; ?>&courseRunID=<?php echo $upcoming['courseRunID']; ?>"
                                                        class="btn btn-default btn-sm btn-block">
                                                        <i class="fa fa-eye"></i> View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- CONTENT-WRAPPER SECTION END-->
        <?php include('includes/footer.php'); ?>
        <!-- FOOTER SECTION END-->
        <!-- JAVASCRIPT AT THE BOTTOM TO REDUCE THE LOADING TIME  -->
        <!-- CORE JQUERY SCRIPTS -->
        <script src="../assets/js/jquery-1.11.1.js"></script>
        <!-- BOOTSTRAP SCRIPTS  -->
        <script src="../assets/js/bootstrap.js"></script>
    </body>

    </html>
<?php } ?>