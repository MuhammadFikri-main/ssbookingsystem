<?php
session_start();
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
  header('location:index.php');
} else {

  if (isset($_POST['submit'])) {
    $courseID = (int)$_POST['courseID'];
    $nominatorID = (int)$_POST['nominatorID'];
    $delegateID = (int)$_POST['delegateID'];

    // Check if delegate is already booked or waiting for this course
    $check = mysqli_query($con, "
        SELECT EXISTS(
            SELECT 1 FROM waitinglist 
            WHERE courseID = $courseID AND delegateID = $delegateID
            UNION ALL
            SELECT 1 FROM booking b
            JOIN courserun cr ON b.courseRunID = cr.courseRunID
            WHERE cr.courseID = $courseID AND b.delegateID = $delegateID
        ) as already_exists
    ");

    $result = mysqli_fetch_array($check);
    if ($result['already_exists'] == 1) {
      echo '<script>alert("This delegate is already nominated/booked for this course.")</script>';
    } else {
      // Step 1: Check for upcoming Course Run (within next 60 days)
      $upcomingRunQuery = mysqli_query($con, "
                SELECT cr.*, 
                       COUNT(b.bookingID) as booked_count,
                       c.maxStudents
                FROM courserun cr
                JOIN courses c ON cr.courseID = c.courseID
                LEFT JOIN booking b ON cr.courseRunID = b.courseRunID 
                    AND b.status IN ('Confirmed', 'Pending')
                WHERE cr.courseID = $courseID 
                AND cr.status = 'Scheduled'
                AND cr.startDate >= CURDATE()
                AND cr.startDate <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                GROUP BY cr.courseRunID
                HAVING booked_count < c.maxStudents
                ORDER BY cr.startDate ASC
                LIMIT 1
            ");

      if (mysqli_num_rows($upcomingRunQuery) > 0) {
        // Course Run exists and has available seats
        $courseRun = mysqli_fetch_array($upcomingRunQuery);
        $courseRunID = $courseRun['courseRunID'];
        $availableSeats = $courseRun['maxStudents'] - $courseRun['booked_count'];

        if ($availableSeats > 0) {
          // Create Booking (CONFIRMED)
          $bookingQuery = mysqli_query($con, "
                        INSERT INTO booking (courseRunID, nominatorID, delegateID, status) 
                        VALUES ('$courseRunID', '$nominatorID', '$delegateID', 'Confirmed')
                    ");

          if ($bookingQuery) {
            $bookingID = mysqli_insert_id($con);

            // Send notification (simulated - in real system you'd send email)
            $notifyQuery = mysqli_query($con, "
                            SELECT title, startDate, location 
                            FROM courses c 
                            JOIN courserun cr ON c.courseID = cr.courseID 
                            WHERE cr.courseRunID = $courseRunID
                        ");
            $courseInfo = mysqli_fetch_array($notifyQuery);

            $_SESSION['msg'] = "Booking Confirmed Successfully!<br>
                                            Course: " . $courseInfo['title'] . "<br>
                                            Start Date: " . date('d-m-Y', strtotime($courseInfo['startDate'])) . "<br>
                                            Location: " . $courseInfo['location'] . "<br>
                                            Joining instructions will be sent 10 days before the course.";

            echo '<script>alert("Booking confirmed! Joining instructions will be sent 10 days before the course.")</script>';
          } else {
            $_SESSION['msg'] = "Error creating booking: " . mysqli_error($con);
          }
        } else {
          // No seats available - Add to Waiting List
          $waitingQuery = mysqli_query($con, "
                        INSERT INTO waitinglist (courseID, nominatorID, delegateID) 
                        VALUES ('$courseID', '$nominatorID', '$delegateID')
                    ");

          if ($waitingQuery) {
            $_SESSION['msg'] = "Added to Waiting List. All upcoming sessions are full.";
          } else {
            $_SESSION['msg'] = "Error adding to waiting list: " . mysqli_error($con);
          }
        }
      } else {
        // No upcoming Course Run - Add to Waiting List
        $waitingQuery = mysqli_query($con, "
                    INSERT INTO waitinglist (courseID, nominatorID, delegateID) 
                    VALUES ('$courseID', '$nominatorID', '$delegateID')
                ");

        if ($waitingQuery) {
          $_SESSION['msg'] = "Added to Waiting List. No upcoming sessions scheduled.";
        } else {
          $_SESSION['msg'] = "Error adding to waiting list: " . mysqli_error($con);
        }
      }

      header('location:student-registration.php');
      exit();
    }
  }

  // Code for delete nomination
  if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    mysqli_query($con, "DELETE FROM waitinglist WHERE waitingListID = $id");
    $_SESSION['msg'] = "Nomination deleted successfully!";
    header('location:student-registration.php');
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
    <title>Admin | Course Nomination & Booking</title>
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
            <h1 class="page-head-line">Course Nomination & Booking System</h1>
            <p class="text-muted">Nominate delegates for courses. System will automatically book if seats available.</p>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <div class="row">
          <div class="col-md-12">
            <?php if (isset($_SESSION['msg']) && $_SESSION['msg'] != '') { ?>
              <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?php echo htmlentities($_SESSION['msg']); ?>
                <?php $_SESSION['msg'] = ''; ?>
              </div>
            <?php } ?>
          </div>
        </div>

        <!-- Nomination Form -->
        <div class="row">
          <div class="col-md-8">
            <div class="panel panel-default">
              <div class="panel-heading">
                <i class="fa fa-user-plus"></i> Nominate Delegate for Course
              </div>
              <div class="panel-body">
                <form name="nominationForm" method="post">
                  <div class="form-group">
                    <label for="courseID">Select Course *</label>
                    <select class="form-control" id="courseID" name="courseID" required>
                      <option value="">-- Select Course --</option>
                      <?php
                      // Fetch active courses with upcoming run info
                      $courseQuery = mysqli_query($con, "
                                            SELECT c.*, 
                                                   COUNT(cr.courseRunID) as upcoming_runs,
                                                   MIN(cr.startDate) as next_start
                                            FROM courses c
                                            LEFT JOIN courserun cr ON c.courseID = cr.courseID 
                                                AND cr.status = 'Scheduled'
                                                AND cr.startDate >= CURDATE()
                                            WHERE c.isDeleted = 0
                                            GROUP BY c.courseID
                                            ORDER BY c.title
                                        ");

                      while ($course = mysqli_fetch_array($courseQuery)) {
                        $info = "";
                        if ($course['upcoming_runs'] > 0) {
                          $nextStart = date('d M Y', strtotime($course['next_start']));
                          $info = " (Next run: $nextStart)";
                        } else {
                          $info = " (No scheduled runs)";
                        }
                        echo "<option value='" . $course['courseID'] . "'>" .
                          htmlentities($course['title']) . $info . "</option>";
                      }
                      ?>
                    </select>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="nominatorID">Nominator (Manager) *</label>
                        <select class="form-control" id="nominatorID" name="nominatorID" required>
                          <option value="">-- Select Nominator --</option>
                          <?php
                          $nominatorQuery = mysqli_query($con, "
                                                    SELECT id, username, role 
                                                    FROM users 
                                                    WHERE role = 'manager' 
                                                    ORDER BY username
                                                ");

                          while ($nominator = mysqli_fetch_array($nominatorQuery)) {
                            $selected = ($_SESSION['alogin'] == $nominator['username']) ? "selected" : "";
                            echo "<option value='" . $nominator['id'] . "' $selected>" .
                              htmlentities($nominator['username']) . " (" .
                              htmlentities($nominator['role']) . ")</option>";
                          }
                          ?>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="delegateID">Delegate (Staff) *</label>
                        <select class="form-control" id="delegateID" name="delegateID" required>
                          <option value="">-- Select Delegate --</option>
                          <?php
                          $delegateQuery = mysqli_query($con, "
                                                    SELECT id, username, role 
                                                    FROM users 
                                                    WHERE role IN ('staff', 'clerk', 'operator') 
                                                    ORDER BY username
                                                ");

                          while ($delegate = mysqli_fetch_array($delegateQuery)) {
                            echo "<option value='" . $delegate['id'] . "'>" .
                              htmlentities($delegate['username']) . " (" .
                              htmlentities($delegate['role']) . ")</option>";
                          }
                          ?>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="form-group">
                    <div class="alert alert-warning">
                      <strong>System Logic:</strong><br>
                      1. Checks for upcoming course runs (next 60 days)<br>
                      2. If seats available → Creates Confirmed Booking<br>
                      3. If no seats/no runs → Adds to Waiting List<br>
                      4. Auto-schedule when waiting list reaches minimum size
                    </div>
                  </div>

                  <button type="submit" name="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Submit Nomination
                  </button>
                  <button type="reset" class="btn btn-default">
                    <i class="fa fa-refresh"></i> Reset
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Quick Stats -->
          <div class="col-md-4">
            <div class="panel panel-info">
              <div class="panel-heading">
                <i class="fa fa-info-circle"></i> Quick Stats
              </div>
              <div class="panel-body">
                <?php
                // Get stats
                $stats = array(
                  'waiting' => mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM waitinglist"))['cnt'],
                  'bookings' => mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM booking WHERE status = 'Confirmed'"))['cnt'],
                  'upcoming' => mysqli_fetch_array(mysqli_query($con, "
                                    SELECT COUNT(DISTINCT cr.courseRunID) as cnt 
                                    FROM courserun cr 
                                    WHERE cr.status = 'Scheduled' 
                                    AND cr.startDate >= CURDATE()
                                "))['cnt']
                );
                ?>
                <p><strong>Waiting List:</strong> <?php echo $stats['waiting']; ?> delegates</p>
                <p><strong>Confirmed Bookings:</strong> <?php echo $stats['bookings']; ?> delegates</p>
                <p><strong>Upcoming Runs:</strong> <?php echo $stats['upcoming']; ?> courses</p>
                <hr>
                <a href="manage-course-schedule.php" class="btn btn-success btn-block">
                  <i class="fa fa-calendar"></i> View Scheduling Dashboard
                </a>
                <a href="course-run.php" class="btn btn-info btn-block">
                  <i class="fa fa-list"></i> View All Course Runs
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Active Nominations & Bookings -->
        <div class="row">
          <div class="col-md-12">
            <div class="panel panel-default">
              <div class="panel-heading">
                <i class="fa fa-list"></i> Active Nominations & Bookings
              </div>
              <div class="panel-body">
                <ul class="nav nav-tabs">
                  <li class="active"><a href="#waiting" data-toggle="tab">Waiting List</a></li>
                  <li><a href="#bookings" data-toggle="tab">Confirmed Bookings</a></li>
                </ul>

                <div class="tab-content">
                  <!-- Waiting List Tab -->
                  <div class="tab-pane fade in active" id="waiting">
                    <div class="table-responsive" style="margin-top: 15px;">
                      <table class="table table-striped table-bordered">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Course</th>
                            <th>Nominator</th>
                            <th>Delegate</th>
                            <th>Date Added</th>
                            <th>Reason</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $waitingQuery = mysqli_query($con, "
                                                    SELECT w.*, 
                                                           c.title as course_title,
                                                           n.username as nominator_name,
                                                           d.username as delegate_name,
                                                           (SELECT COUNT(cr.courseRunID) 
                                                            FROM courserun cr 
                                                            WHERE cr.courseID = c.courseID 
                                                            AND cr.status = 'Scheduled'
                                                            AND cr.startDate >= CURDATE()) as upcoming_runs
                                                    FROM waitinglist w
                                                    JOIN courses c ON w.courseID = c.courseID
                                                    JOIN users n ON w.nominatorID = n.id
                                                    JOIN users d ON w.delegateID = d.id
                                                    ORDER BY w.creationDate DESC
                                                ");

                          $cnt = 1;
                          while ($row = mysqli_fetch_array($waitingQuery)) {
                            $reason = ($row['upcoming_runs'] > 0) ?
                              "Upcoming runs full" :
                              "No scheduled runs";
                          ?>
                            <tr>
                              <td><?php echo $cnt; ?></td>
                              <td><?php echo htmlentities($row['course_title']); ?></td>
                              <td><?php echo htmlentities($row['nominator_name']); ?></td>
                              <td><?php echo htmlentities($row['delegate_name']); ?></td>
                              <td><?php echo date('d-m-Y', strtotime($row['creationDate'])); ?></td>
                              <td><?php echo $reason; ?></td>
                              <td>
                                <a href="?del=<?php echo $row['waitingListID']; ?>"
                                  onclick="return confirm('Remove from waiting list?')"
                                  class="btn btn-danger btn-xs" title="Remove">
                                  <i class="fa fa-trash"></i>
                                </a>
                              </td>
                            </tr>
                          <?php
                            $cnt++;
                          }

                          if ($cnt == 1) {
                            echo '<tr><td colspan="7" class="text-center">No waiting list entries.</td></tr>';
                          }
                          ?>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Bookings Tab -->
                  <div class="tab-pane fade" id="bookings">
                    <div class="table-responsive" style="margin-top: 15px;">
                      <table class="table table-striped table-bordered">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Course</th>
                            <th>Delegate</th>
                            <th>Start Date</th>
                            <th>Location</th>
                            <th>Booking Date</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $bookingQuery = mysqli_query($con, "
                                                    SELECT b.*,
                                                           c.title as course_title,
                                                           d.username as delegate_name,
                                                           cr.startDate,
                                                           cr.location,
                                                           cr.status as run_status
                                                    FROM booking b
                                                    JOIN courserun cr ON b.courseRunID = cr.courseRunID
                                                    JOIN courses c ON cr.courseID = c.courseID
                                                    JOIN users d ON b.delegateID = d.id
                                                    WHERE b.status = 'Confirmed'
                                                    ORDER BY cr.startDate DESC
                                                ");

                          $cnt = 1;
                          while ($row = mysqli_fetch_array($bookingQuery)) {
                            $statusClass = ($row['run_status'] == 'Scheduled') ? 'info' : (($row['run_status'] == 'Ongoing') ? 'warning' : 'success');
                          ?>
                            <tr>
                              <td><?php echo $cnt; ?></td>
                              <td><?php echo htmlentities($row['course_title']); ?></td>
                              <td><?php echo htmlentities($row['delegate_name']); ?></td>
                              <td><?php echo date('d M Y', strtotime($row['startDate'])); ?></td>
                              <td><?php echo htmlentities($row['location']); ?></td>
                              <td><?php echo date('d-m-Y', strtotime($row['bookingDate'])); ?></td>
                              <td>
                                <span class="label label-<?php echo $statusClass; ?>">
                                  <?php echo htmlentities($row['run_status']); ?>
                                </span>
                              </td>
                            </tr>
                          <?php
                            $cnt++;
                          }

                          if ($cnt == 1) {
                            echo '<tr><td colspan="7" class="text-center">No confirmed bookings.</td></tr>';
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

      </div>
    </div>
    <!-- CONTENT-WRAPPER SECTION END-->
    <?php include('includes/footer.php'); ?>
    <!-- FOOTER SECTION END-->

    <script src="../assets/js/jquery-1.11.1.js"></script>
    <script src="../assets/js/bootstrap.js"></script>
    <script>
      $(document).ready(function() {
        // Prevent self-nomination
        $('#nominatorID, #delegateID').change(function() {
          var nominator = $('#nominatorID').val();
          var delegate = $('#delegateID').val();

          if (nominator === delegate && nominator !== '') {
            alert('Nominator cannot nominate themselves!');
            $('#delegateID').val('');
          }
        });

        // Show course info when selected
        $('#courseID').change(function() {
          if ($(this).val()) {
            // You could add AJAX here to show course details
          }
        });
      });
    </script>
  </body>

  </html>
<?php } ?>