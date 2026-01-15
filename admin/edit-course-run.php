<?php
session_start();
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
  header('location:index.php');
} else {
  $id = intval($_GET['id']);
  // date_default_timezone_set('Asia/Kolkata'); // change according timezone
  // $currentTime = date('d-m-Y h:i:s A', time());
  if (isset($_POST['submit'])) {
    $startDate = $_POST['startDate'];
    $endDate   = $_POST['endDate'];
    $location  = $_POST['location'];
    $status    = $_POST['status'];

    $ret = mysqli_query($con, "
        UPDATE courserun 
        SET startDate='$startDate',
            endDate='$endDate',
            location='$location',
            status='$status'
        WHERE courseRunID='$id'
    ");

    if ($ret) {
      echo '<script>alert("Course Run Updated Successfully !!")</script>';
      echo '<script>window.location.href=manage-course-schedule.php</script>';
    } else {
      echo '<script>alert("Error : Course Run not Updated!!")</script>';
    }
  }

?>

  <!DOCTYPE html>
  <html xmlns="http://www.w3.org/1999/xhtml">

  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Admin | Course</title>
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
            <h1 class="page-head-line">Edit course Schedule </h1>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3"></div>
          <div class="col-md-6">
            <div class="panel panel-default">
              <div class="panel-heading">
                Edit Course Schedule
              </div>
              <font color="green" align="center"><?php echo htmlentities($_SESSION['msg']); ?><?php echo htmlentities($_SESSION['msg'] = ""); ?></font>


              <div class="panel-body">
                <form name="dept" method="post">
                  <?php
                  $sql = mysqli_query($con, "select * from courserun where courseRunID='$id'");
                  $cnt = 1;
                  while ($row = mysqli_fetch_array($sql)) {
                  ?>
                    <p><b>Last Updated at</b> : <?php echo htmlentities($row['updationDate']); ?></p>

                    <div class="form-group">
                      <label for="startDate">Start Date</label>
                      <input type="date" class="form-control" id="startDate" name="startDate"
                        value="<?php echo htmlentities($row['startDate']); ?>" required />
                    </div>

                    <div class="form-group">
                      <label for="endDate">End Date</label>
                      <input type="date" class="form-control" id="endDate" name="endDate"
                        value="<?php echo htmlentities($row['endDate']); ?>" required />
                    </div>

                    <div class="form-group">
                      <label for="location">Location</label>
                      <input type="text" class="form-control" id="location" name="location"
                        value="<?php echo htmlentities($row['location']); ?>" required />
                    </div>

                    <div class="form-group">
                      <label for="status">Status</label>
                      <select class="form-control" id="status" name="status" required>
                        <option value="Scheduled" <?php if ($row['status'] == 'Scheduled') echo 'selected'; ?>>Scheduled</option>
                        <option value="Ongoing" <?php if ($row['status'] == 'Ongoing') echo 'selected'; ?>>Ongoing</option>
                        <option value="Completed" <?php if ($row['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                        <option value="Cancelled" <?php if ($row['status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                      </select>
                    </div>

                  <?php } ?>
                  <button type="submit" name="submit" class="btn btn-default"><i class=" fa fa-refresh "></i> Update</button>
                </form>
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