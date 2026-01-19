<?php
session_start();
include('includes/config.php');
error_reporting(0);
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
} else {

    // Get course ID from URL
    $courseID = isset($_GET['courseID']) ? (int)$_GET['courseID'] : 0;
    $courseRunID = isset($_GET['courseRunID']) ? (int)$_GET['courseRunID'] : 0;

    if ($courseID == 0) {
        header('location:view-course-run.php');
        exit();
    }

    // Get course details
    $courseQuery = mysqli_query($con, "SELECT * FROM courses WHERE courseID = $courseID AND isDeleted = 0");
    $course = mysqli_fetch_array($courseQuery);

    if (!$course) {
        $_SESSION['msg'] = "Course not found!";
        header('location:manage-waitinglist.php');
        exit();
    }

    // Code for Deletion
    if (isset($_GET['del'])) {
        $id = (int)$_GET['id'];
        mysqli_query($con, "DELETE FROM waitingList WHERE waitingListID = $id");
        $_SESSION['msg'] = "Nomination Deleted Successfully !!";
        header("location:manage-course-nominations.php?courseID=$courseID");
        exit();
    }

    // Code for Approve/Reject
    if (isset($_GET['approve'])) {
        $id = (int)$_GET['id'];
        mysqli_query($con, "UPDATE waitingList SET status = 'Approved' WHERE waitingListID = $id");
        $_SESSION['msg'] = "Nomination Approved Successfully !!";
        header("location:manage-course-nominations.php?courseID=$courseID");
        exit();
    }

    if (isset($_GET['reject'])) {
        $id = (int)$_GET['id'];
        mysqli_query($con, "UPDATE waitingList SET status = 'Rejected' WHERE waitingListID = $id");
        $_SESSION['msg'] = "Nomination Rejected !!";
        header("location:manage-course-nominations.php?courseID=$courseID");
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
        <title>Admin | Course Nominations</title>
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
                        <h1 class="page-head-line">
                            Nominations for: <?php echo htmlentities($course['title']); ?>
                            <small>
                                <a href="manage-course-schedule.php" class="btn btn-default btn-sm">
                                    <i class="fa fa-arrow-left"></i> Back to All Courses
                                </a>
                            </small>
                        </h1>
                    </div>
                </div>

                <!-- Course Details Card -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <i class="fa fa-info-circle"></i> Course Information
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4><?php echo htmlentities($course['title']); ?></h4>
                                        <p><strong>Objectives:</strong> <?php echo htmlentities($course['objectives']); ?></p>
                                        <p><strong>Prerequisites:</strong> <?php echo htmlentities($course['prerequisites']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="well">
                                            <p><strong>Duration:</strong> <?php echo $course['durationDays']; ?> days</p>
                                            <p><strong>Maximum Students:</strong> <?php echo $course['maxStudents']; ?></p>
                                            <p><strong>Staff Level:</strong> <?php echo htmlentities($course['staffLevel']); ?></p>
                                            <?php
                                            // Get nomination statistics for this course
                                            $statsQuery = mysqli_query($con, "
                                            SELECT 
                                                COUNT(*) as total
                                            FROM booking 
                                            WHERE courseRunID = $courseRunID
                                        ");
                                            $stats = mysqli_fetch_array($statsQuery);
                                            $totalCount = $stats['total'];
                                            ?>
                                            <p><strong>Available Slots:</strong> <?php echo max(0, $course['maxStudents'] - $totalCount); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <div class="row">
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

                <!-- Nominations for this Course -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-users"></i> Delegates for this Course
                                <!-- <div class="pull-right">
                                    <a href="student-registration.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> Add New Nomination
                                    </a>
                                </div> -->
                            </div>
                            <div class="panel-body">
                                <?php if ($totalCount == 0) { ?>
                                    <div class="alert alert-info">
                                        No nominations found for this course.
                                        <a href="nomination.php">Add the first nomination</a>
                                    </div>
                                <?php } else { ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nominator</th>
                                                    <th>Delegate</th>
                                                    <th>Date Nominated</th>
                                                    <!-- <th>Status</th> -->
                                                    <!-- <th>Actions</th> -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Fetch nominations for this specific course
                                                $sql = mysqli_query($con, "
                                                SELECT 
                                                    cr.courseRunID,
                                                    cr.courseID,
                                                    n.username as nominator_name,
                                                    n.role as nominator_role,
                                                    d.username as delegate_name,
                                                    d.role as delegate_role,
                                                    b.creationDate
                                                FROM courserun cr
                                                LEFT JOIN booking b ON cr.courseRunID = b.courseRunID 
                                                LEFT JOIN users n ON b.nominatorID = n.id
                                                LEFT JOIN users d ON b.delegateID = d.id
                                                WHERE cr.courseID = $courseID
                                                AND cr.courseRunID = $courseRunID
                                                AND b.isCanceled = 0
                                                ORDER BY b.creationDate DESC
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
                                                        <td>
                                                            <?php echo htmlentities($row['nominator_name']); ?><br>
                                                            <small class="text-muted"><?php echo htmlentities($row['nominator_role']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlentities($row['delegate_name']); ?><br>
                                                            <small class="text-muted"><?php echo htmlentities($row['delegate_role']); ?></small>
                                                        </td>
                                                        <td><?php echo date('d-m-Y', strtotime($row['creationDate'])); ?></td>
                                                        <!-- <td>
                                                            <span class="label label-<?php echo $statusClass; ?>">
                                                                <?php echo htmlentities($row['status']); ?>
                                                            </span>
                                                        </td> -->
                                                        <!-- <td>
                                                            <div class="btn-group">
                                                                <?php if ($row['status'] == 'Pending') { ?>
                                                                    <?php if ($approvedCount < $course['maxStudents']) { ?>
                                                                        <a href="manage-course-nominations.php?courseID=<?php echo $courseID; ?>&id=<?php echo $row['waitingListID']; ?>&approve=yes"
                                                                            onClick="return confirm('Approve this nomination?')"
                                                                            class="btn btn-success btn-xs" title="Approve">
                                                                            <i class="fa fa-check"></i>
                                                                        </a>
                                                                    <?php } else { ?>
                                                                        <button class="btn btn-default btn-xs" title="Course Full" disabled>
                                                                            <i class="fa fa-ban"></i> Full
                                                                        </button>
                                                                    <?php } ?>
                                                                    <a href="manage-course-nominations.php?courseID=<?php echo $courseID; ?>&id=<?php echo $row['waitingListID']; ?>&reject=yes"
                                                                        onClick="return confirm('Reject this nomination?')"
                                                                        class="btn btn-warning btn-xs" title="Reject">
                                                                        <i class="fa fa-times"></i>
                                                                    </a>
                                                                <?php } ?>

                                                                <a href="edit-nomination.php?id=<?php echo $row['waitingListID']; ?>"
                                                                    class="btn btn-primary btn-xs" title="Edit">
                                                                    <i class="fa fa-edit"></i>
                                                                </a>
                                                                <a href="manage-course-nominations.php?courseID=<?php echo $courseID; ?>&id=<?php echo $row['waitingListID']; ?>&del=delete"
                                                                    onClick="return confirm('Are you sure you want to delete this nomination?')"
                                                                    class="btn btn-danger btn-xs" title="Delete">
                                                                    <i class="fa fa-trash"></i>
                                                                </a>
                                                            </div>
                                                        </td> -->
                                                    </tr>
                                                <?php
                                                    $cnt++;
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php } ?>
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
        <script>
            function approveAllPending() {
                if (confirm('This will approve all pending nominations up to the course limit (<?php echo $course['maxStudents']; ?> students). Continue?')) {
                    window.location.href = 'approve-all-pending.php?courseID=<?php echo $courseID; ?>';
                }
            }
        </script>
    </body>

    </html>
<?php } ?>