<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
} else {
    // WEEKLY SCHEDULING REVIEW FUNCTION
    function performWeeklySchedulingReview($con)
    {
        // Find courses with waiting list >= minimum size (5)
        $coursesToSchedule = mysqli_query($con, "
        SELECT w.courseID, 
               c.title,
               c.maxStudents,
               c.durationDays,
               COUNT(w.waitingListID) as waiting_count,
               GROUP_CONCAT(DISTINCT w.delegateID) as delegate_ids
        FROM waitinglist w
        JOIN courses c ON w.courseID = c.courseID
        WHERE NOT EXISTS (
            SELECT 1 FROM courserun cr 
            WHERE cr.courseID = w.courseID 
            AND cr.status = 'Scheduled'
            AND cr.startDate >= CURDATE()
        )
        GROUP BY w.courseID
        HAVING COUNT(w.waitingListID) > 1
    ");

        $scheduledCount = 0;

        while ($course = mysqli_fetch_array($coursesToSchedule)) {
            if ($course['waiting_count'] > 1) {
                // Schedule new Course Run (4 weeks from now)
                $startDate = date('Y-m-d', strtotime('+4 weeks'));
                $endDate = date('Y-m-d', strtotime('+' . $course['durationDays'] . ' days', strtotime($startDate)));
                $location = 'Training Room A'; // Default location

                // Insert into courserun
                $scheduleQuery = mysqli_query($con, "
                INSERT INTO courserun (courseID, startDate, endDate, location, status) 
                VALUES ('{$course['courseID']}', '$startDate', '$endDate', '$location', 'Scheduled')
            ");

                if ($scheduleQuery) {
                    $courseRunID = mysqli_insert_id($con);

                    // Move delegates from Waiting List to Booking
                    $delegateIDs = explode(',', $course['delegate_ids']);
                    $maxStudents = min($course['maxStudents'], count($delegateIDs));

                    // Move first N delegates (up to maxStudents) to bookings
                    $movedCount = 0;
                    for ($i = 0; $i < $maxStudents && $i < count($delegateIDs); $i++) {
                        $delegateID = $delegateIDs[$i];

                        // Get nominator ID for this delegate
                        $nominatorQuery = mysqli_query($con, "
                        SELECT nominatorID FROM waitinglist 
                        WHERE courseID = {$course['courseID']} 
                        AND delegateID = $delegateID 
                        LIMIT 1
                    ");

                        if ($nominator = mysqli_fetch_array($nominatorQuery)) {

                            // Get course price as transfer fee
                            $priceQuery = mysqli_query($con, "
                                SELECT price FROM courseprice 
                                WHERE courseID = {$course['courseID']} 
                                AND isDeleted = 0 
                                AND effectiveDate <= CURDATE()
                                ORDER BY effectiveDate DESC LIMIT 1
                            ");
                            $priceData = mysqli_fetch_array($priceQuery);
                            $transferFee = $priceData ? $priceData['price'] : 0;

                            // Create booking with transfer fee
                            mysqli_query($con, "
                                INSERT INTO booking (courseRunID, nominatorID, delegateID, status, transferFee) 
                                VALUES ('$courseRunID', '{$nominator['nominatorID']}', '$delegateID', 'Confirmed', '$transferFee')
                            ");

                            // Remove from waiting list
                            mysqli_query($con, "
                            UPDATE waitinglist
                            SET isBooked = 1 
                            WHERE courseID = {$course['courseID']} 
                            AND delegateID = $delegateID
                        ");

                            $movedCount++;
                        }
                    }

                    $scheduledCount++;

                    // Log this action
                    error_log("Auto-scheduled course: {$course['title']} (ID: {$course['courseID']}) - Moved $movedCount delegates to bookings");
                }
            }
        }

        return $scheduledCount;
    }

    // Manual trigger for weekly review (could be cron job)
    if (isset($_GET['weekly_review'])) {
        $scheduled = performWeeklySchedulingReview($con);
        $_SESSION['msg'] = "Weekly review completed. Scheduled $scheduled new course runs.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Code for Scheduling a Course Run (MANUAL - Single Course)
    if (isset($_POST['schedule'])) {
        $courseID = (int)$_POST['courseID'];
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];
        $location = mysqli_real_escape_string($con, $_POST['location']);

        // Get course details and waiting delegates
        $courseQuery = mysqli_query($con, "
        SELECT 
            c.title,
            c.maxStudents,
            c.durationDays,
            COUNT(w.waitingListID) as waiting_count,
            GROUP_CONCAT(DISTINCT w.delegateID) as delegate_ids
        FROM courses c
        LEFT JOIN waitinglist w ON c.courseID = w.courseID
        WHERE c.courseID = '$courseID'
        GROUP BY c.courseID
    ");

        $courseData = mysqli_fetch_array($courseQuery);
        $waitingCount = $courseData['waiting_count'];
        $maxStudents = $courseData['maxStudents'];

        if ($waitingCount < 1) {
            $_SESSION['msg'] = "Error: No waiting delegates found for this course.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        // Insert into courserun table
        $scheduleQuery = mysqli_query($con, "
        INSERT INTO courserun (courseID, startDate, endDate, location, status) 
        VALUES ('$courseID', '$startDate', '$endDate', '$location', 'Scheduled')
    ");

        if ($scheduleQuery) {
            $courseRunID = mysqli_insert_id($con);
            $movedCount = 0;

            // Get delegate IDs
            $delegateIDs = explode(',', $courseData['delegate_ids']);
            $maxToMove = min($maxStudents, count($delegateIDs), $waitingCount);

            // Move delegates from Waiting List to Booking (with transfer fees)
            for ($i = 0; $i < $maxToMove; $i++) {
                $delegateID = $delegateIDs[$i];

                // Get nominator ID for this delegate
                $nominatorQuery = mysqli_query($con, "
                SELECT nominatorID FROM waitinglist 
                WHERE courseID = '$courseID' 
                AND delegateID = '$delegateID' 
                LIMIT 1
            ");

                if ($nominatorData = mysqli_fetch_array($nominatorQuery)) {
                    $nominatorID = $nominatorData['nominatorID'];

                    // Get course price as transfer fee
                    $priceQuery = mysqli_query($con, "
                    SELECT price FROM courseprice 
                    WHERE courseID = '$courseID' 
                    AND isDeleted = 0 
                    AND effectiveDate <= CURDATE()
                    ORDER BY effectiveDate DESC LIMIT 1
                ");

                    $priceData = mysqli_fetch_array($priceQuery);
                    $transferFee = $priceData ? $priceData['price'] : 0;

                    // Create booking with transfer fee
                    mysqli_query($con, "
                    INSERT INTO booking (courseRunID, nominatorID, delegateID, status, transferFee) 
                    VALUES ('$courseRunID', '$nominatorID', '$delegateID', 'Confirmed', '$transferFee')
                ");

                    // Remove from waiting list
                    mysqli_query($con, "
                    UPDATE waitinglist
                    SET isBooked = 1 
                    WHERE courseID = '$courseID' 
                    AND delegateID = '$delegateID'
                ");

                    $movedCount++;
                }
            }

            // Update any remaining waiting list entries to different status
            // if ($waitingCount > $maxToMove) {
            //     // You might want to keep them in waiting list or change status
            //     mysqli_query($con, "
            //     UPDATE waitinglist 
            //     SET status = 'Waiting_Next_Run' 
            //     WHERE courseID = '$courseID'
            // ");
            // }

            $_SESSION['msg'] = "Course Run Scheduled Successfully!<br>
                           Course Run ID: $courseRunID<br>
                           $movedCount delegate(s) moved from waiting list to bookings.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['msg'] = "Error scheduling course: " . mysqli_error($con);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Code for Approve/Reject
    if (isset($_GET['approve'])) {
        $id = (int)$_GET['id'];
        mysqli_query($con, "UPDATE waitingList SET status = 'Approved' WHERE waitingListID = $id");
        $_SESSION['msg'] = "Nomination Approved Successfully !!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_GET['reject'])) {
        $id = (int)$_GET['id'];
        mysqli_query($con, "UPDATE waitingList SET status = 'Rejected' WHERE waitingListID = $id");
        $_SESSION['msg'] = "Nomination Rejected !!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
?>

    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Schedule Management</title>
        <link href="../assets/css/bootstrap.css" rel="stylesheet" />
        <link href="../assets/css/font-awesome.css" rel="stylesheet" />
        <link href="../assets/css/style.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css" rel="stylesheet" />
        <style>
            .course-card {
                border-left: 4px solid #337ab7;
                margin-bottom: 15px;
            }

            .ready-to-schedule {
                border-left-color: #5cb85c !important;
                background-color: #f9fff9;
            }

            .minimum-not-met {
                border-left-color: #f0ad4e !important;
                background-color: #fff9f0;
            }

            .no-nominations {
                border-left-color: #d9534f !important;
                background-color: #fff6f6;
            }

            .schedule-form {
                display: none;
                margin-top: 10px;
                padding: 15px;
                background-color: #f8f9fa;
                border-radius: 4px;
            }
        </style>
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
                        <h1 class="page-head-line">Course Scheduling (CS)</h1>
                        <p class="text-muted">Review nominations by course and schedule training sessions</p>
                    </div>
                </div>

                <div class="row">
                    <!-- Success/Error Messages -->
                    <div class="col-md-12">
                        <?php if (isset($_SESSION['msg']) && $_SESSION['msg'] != '') { ?>
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php echo htmlentities($_SESSION['msg']); ?>
                                <?php $_SESSION['msg'] = ''; ?>
                            </div>
                        <?php } ?>

                        <?php if (isset($_SESSION['delmsg']) && $_SESSION['delmsg'] != '') { ?>
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php echo htmlentities($_SESSION['delmsg']); ?>
                                <?php $_SESSION['delmsg'] = ''; ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Courses Ready for Scheduling -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">

                                <i class="fa fa-calendar-check-o"></i> Courses Ready for Scheduling
                                <span class="pull-right">
                                    <!-- <a href="?weekly_review=1" class="btn btn-warning btn-xs"
                                        onclick="return confirm('Run weekly scheduling review? This will auto-schedule courses with 5+ waiting delegates.')">
                                        <i class="fa fa-refresh"></i> Run Weekly Review
                                    </a> -->
                                    <small>Minimum: 3 delegates | Maximum: Based on course limit</small>
                                </span>
                            </div>
                            <div class="panel-body">
                                <?php
                                // Get courses with approved nominations count
                                $coursesQuery = mysqli_query($con, "
                                SELECT 
                                    c.courseID,
                                    c.title,
                                    c.maxStudents,
                                    c.durationDays,
                                    c.staffLevel,
                                    COUNT(w.waitingListID) as total_nominations,
                                    GROUP_CONCAT(DISTINCT d.username SEPARATOR ', ') as delegate_names
                                FROM courses c
                                LEFT JOIN waitinglist w ON c.courseID = w.courseID
                                LEFT JOIN users d ON w.delegateID = d.id
                                WHERE c.isDeleted = 0
                                AND w.isBooked = 0
                                AND w.isDeleted = 0
                                GROUP BY c.courseID
                                HAVING COUNT(w.waitingListID) > 0
                                ORDER BY total_nominations DESC
                            ");

                                $courseCount = 0;
                                while ($course = mysqli_fetch_array($coursesQuery)) {
                                    $totalNominations = $course['total_nominations'];
                                    $maxStudents = $course['maxStudents'];

                                    // Determine if course is ready to schedule
                                    $isReady = ($totalNominations >= 3);
                                    $hasNominations = ($totalNominations > 0);

                                    // Card class based on status
                                    $cardClass = 'course-card';
                                    if (!$hasNominations) {
                                        $cardClass .= ' no-nominations';
                                    } elseif ($isReady) {
                                        $cardClass .= ' ready-to-schedule';
                                    } else {
                                        $cardClass .= ' minimum-not-met';
                                    }

                                    $courseCount++;
                                ?>
                                    <div class="<?php echo $cardClass; ?>">
                                        <div class="panel panel-default" style="margin-bottom: 0; border: 1px solid #ddd;">
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-md-9">
                                                        <h4 style="margin-top: 0;">
                                                            <?php echo htmlentities($course['title']); ?>
                                                            <small class="text-muted">(ID: <?php echo $course['courseID']; ?>)</small>
                                                        </h4>
                                                        <p>
                                                            <span class="label label-info"><?php echo htmlentities($course['staffLevel']); ?></span>
                                                            <span class="label label-default"><?php echo $course['durationDays']; ?> days</span>
                                                            <span class="label label-default">Max: <?php echo $maxStudents; ?> students</span>
                                                        </p>

                                                        <?php if ($hasNominations) { ?>
                                                            <p><strong>Delegates:</strong> <?php echo htmlentities($course['delegate_names'] ?: 'None'); ?></p>
                                                        <?php } ?>
                                                    </div>

                                                    <div class="col-md-3 text-right">
                                                        <div class="btn-group-vertical">
                                                            <?php if ($isReady) { ?>
                                                                <button class="btn btn-success btn-sm schedule-btn" data-courseid="<?php echo $course['courseID']; ?>">
                                                                    <i class="fa fa-calendar-plus-o"></i> Schedule Now
                                                                </button>
                                                            <?php } elseif ($hasNominations) { ?>
                                                                <span class="label label-warning">
                                                                    <?php echo max(0, $maxStudents - $totalNominations); ?> more slot left
                                                                </span>
                                                            <?php } else { ?>
                                                                <span class="label label-danger">No nominations yet</span>
                                                            <?php } ?>

                                                            <a href="manage-students.php?courseID=<?php echo $course['courseID']; ?>"
                                                                class="btn btn-info btn-sm">
                                                                <i class="fa fa-list"></i> View Nominations
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Schedule Form (Hidden by default) -->
                                                <div class="schedule-form" id="schedule-form-<?php echo $course['courseID']; ?>">
                                                    <h5>Schedule Course Run</h5>
                                                    <form method="post" class="form-horizontal" name="schedule">
                                                        <input type="hidden" name="courseID" value="<?php echo $course['courseID']; ?>">

                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label">Start Date:</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" class="form-control datepicker" name="startDate" required
                                                                    placeholder="YYYY-MM-DD">
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label">End Date:</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" class="form-control datepicker" name="endDate" required
                                                                    placeholder="YYYY-MM-DD">
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label">Location:</label>
                                                            <div class="col-sm-9">
                                                                <select class="form-control" name="location" required>
                                                                    <option value="">Select Location</option>
                                                                    <option value="Training Room A">Training Room A</option>
                                                                    <option value="Training Room B">Training Room B</option>
                                                                    <option value="Conference Room 1">Conference Room 1</option>
                                                                    <option value="Computer Lab 1">Computer Lab 1</option>
                                                                    <option value="Online - Zoom">Online - Zoom</option>
                                                                    <option value="Online - Teams">Online - Teams</option>
                                                                    <option value="Executive Boardroom">Executive Boardroom</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <div class="col-sm-offset-3 col-sm-9">
                                                                <button type="submit" name="schedule" class="btn btn-primary">
                                                                    <i class="fa fa-calendar-check-o"></i> Confirm Schedule
                                                                </button>
                                                                <button type="button" class="btn btn-default cancel-schedule">Cancel</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php }

                                if ($courseCount == 0) {
                                    echo '<div class="alert alert-info">No courses found. Please add courses first.</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Nominations Table -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-list"></i> All Nominations
                                <div class="pull-right">
                                    <!-- <a href="student-registration.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> Add New Nomination
                                    </a> -->
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Course</th>
                                                <th>Nominator</th>
                                                <th>Delegate</th>
                                                <th>Date Nominated</th>
                                                <!-- <th>Status</th>
                                                <th>Actions</th> -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = mysqli_query($con, "
                                            SELECT 
                                                w.waitingListID,
                                                w.creationDate,
                                                c.title as course_title,
                                                n.username as nominator_name,
                                                d.username as delegate_name
                                            FROM waitinglist w
                                            LEFT JOIN courses c ON w.courseID = c.courseID
                                            LEFT JOIN users n ON w.nominatorID = n.id
                                            LEFT JOIN users d ON w.delegateID = d.id
                                            WHERE c.isDeleted = 0
                                            AND w.isBooked = 0
                                            AND w.isDeleted = 0
                                            ORDER BY w.creationDate DESC
                                        ");

                                            $cnt = 1;
                                            while ($row = mysqli_fetch_array($sql)) {
                                                $statusClass = 'default';
                                                switch ($row['status']) {
                                                    case 'Pending':
                                                        $statusClass = 'warning';
                                                        break;
                                                    case 'Approved':
                                                        $statusClass = 'success';
                                                        break;
                                                    case 'Rejected':
                                                        $statusClass = 'danger';
                                                        break;
                                                    case 'Enrolled':
                                                        $statusClass = 'info';
                                                        break;
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo $cnt; ?></td>
                                                    <td><?php echo htmlentities($row['course_title']); ?></td>
                                                    <td><?php echo htmlentities($row['nominator_name']); ?></td>
                                                    <td><?php echo htmlentities($row['delegate_name']); ?></td>
                                                    <td><?php echo date('d-m-Y', strtotime($row['creationDate'])); ?></td>
                                                    <!-- <td>
                                                        <span class="label label-<?php echo $statusClass; ?>">
                                                            <?php echo htmlentities($row['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <?php if ($row['status'] == 'Pending') { ?>
                                                                <a href="manage-waitinglist.php?id=<?php echo $row['waitingListID']; ?>&approve=yes"
                                                                    onClick="return confirm('Approve this nomination?')"
                                                                    class="btn btn-success btn-xs" title="Approve">
                                                                    <i class="fa fa-check"></i>
                                                                </a>
                                                                <a href="manage-waitinglist.php?id=<?php echo $row['waitingListID']; ?>&reject=yes"
                                                                    onClick="return confirm('Reject this nomination?')"
                                                                    class="btn btn-warning btn-xs" title="Reject">
                                                                    <i class="fa fa-times"></i>
                                                                </a>
                                                            <?php } ?>
                                                            <a href="edit-nomination.php?id=<?php echo $row['waitingListID']; ?>"
                                                                class="btn btn-primary btn-xs" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                        </div>
                                                    </td> -->
                                                </tr>
                                            <?php
                                                $cnt++;
                                            }

                                            if ($cnt == 1) {
                                                echo '<tr><td colspan="7" class="text-center">No nominations found.</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
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
        <script src="../assets/js/jquery-1.11.1.js"></script>
        <script src="../assets/js/bootstrap.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>
        <script>
            $(document).ready(function() {
                // Initialize datepicker
                $('.datepicker').datepicker({
                    format: 'yyyy-mm-dd',
                    autoclose: true,
                    todayHighlight: true,
                    startDate: new Date() // Prevent past dates
                });

                // Show schedule form when "Schedule Now" is clicked
                $('.schedule-btn').click(function() {
                    var courseID = $(this).data('courseid');
                    var form = $('#schedule-form-' + courseID);

                    // Get course duration
                    var courseCard = $(this).closest('.course-card');
                    var durationText = courseCard.find('.label-default:contains("days")').text();
                    var duration = parseInt(durationText.match(/\d+/)[0]) || 1;

                    // Set default start date (4 weeks from now like weekly review)
                    var defaultStartDate = new Date();
                    defaultStartDate.setDate(defaultStartDate.getDate() + 28); // 4 weeks
                    var startDateStr = defaultStartDate.toISOString().split('T')[0];

                    // Set default end date (start date + duration)
                    var defaultEndDate = new Date(defaultStartDate);
                    defaultEndDate.setDate(defaultEndDate.getDate() + duration);
                    var endDateStr = defaultEndDate.toISOString().split('T')[0];

                    // Set default location
                    form.find('input[name="startDate"]').val(startDateStr);
                    form.find('input[name="endDate"]').val(endDateStr);
                    form.find('select[name="location"]').val('Training Room A');

                    // Show the form
                    form.slideDown();
                    $(this).hide();
                });

                // Hide schedule form when cancel is clicked
                $('.cancel-schedule').click(function() {
                    $(this).closest('.schedule-form').slideUp();
                    $(this).closest('.course-card').find('.schedule-btn').show();
                });

                // Calculate end date based on course duration when start date changes
                $('input[name="startDate"]').change(function() {
                    var startDate = $(this).val();
                    var courseCard = $(this).closest('.course-card');
                    var durationText = courseCard.find('.label-default:contains("days")').text();
                    var duration = parseInt(durationText.match(/\d+/)[0]) || 1;

                    if (startDate) {
                        var start = new Date(startDate);
                        start.setDate(start.getDate() + duration);
                        var endDate = start.toISOString().split('T')[0];
                        $(this).closest('.schedule-form').find('input[name="endDate"]').val(endDate);
                    }
                });

                // Add confirmation for scheduling
                // $('form[name="schedule"]').submit(function(e) {
                //     var courseTitle = $(this).closest('.course-card').find('h4').text().trim();
                //     var startDate = $(this).find('input[name="startDate"]').val();
                //     var location = $(this).find('select[name="location"]').val();

                //     return confirm(
                //         "Schedule Course Run?\n\n" +
                //         "Course: " + courseTitle + "\n" +
                //         "Start: " + startDate + "\n" +
                //         "Location: " + location + "\n\n" +
                //         "This will:\n" +
                //         "1. Create a new Course Run\n" +
                //         "2. Move delegates from waiting list to bookings\n" +
                //         "3. Apply transfer fees\n" +
                //         "4. Update waiting list status"
                //     );
                // });
            });
        </script>
    </body>

    </html>
<?php } ?>