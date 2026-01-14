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
    $courseTitle = $_POST['courseTitle'];
    $objective = $_POST['objective'];
    $prerequisites = $_POST['prerequisites'];
    $durationDays = $_POST['durationDays'];
    $maxStudents = $_POST['maxStudents'];
    $staffLevel = $_POST['staffLevel'];
    $ret = mysqli_query($con, "update courses set title='$courseTitle',objectives='$objective',prerequisites='$prerequisites',durationDays='$durationDays', maxStudents='$maxStudents', staffLevel='$staffLevel' where courseID='$id'");
    if ($ret) {
      echo '<script>alert("Course Updated Successfully !!")</script>';
      echo '<script>window.location.href=course.php</script>';
    } else {
      echo '<script>alert("Error : Course not Updated!!")</script>';
      echo '<script>window.location.href=course.php</script>';
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
            <h1 class="page-head-line">Course </h1>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3"></div>
          <div class="col-md-6">
            <div class="panel panel-default">
              <div class="panel-heading">
                Course
              </div>
              <font color="green" align="center"><?php echo htmlentities($_SESSION['msg']); ?><?php echo htmlentities($_SESSION['msg'] = ""); ?></font>


              <div class="panel-body">
                <form name="dept" method="post">
                  <?php
                  $sql = mysqli_query($con, "select * from courses where courseID='$id'");
                  $cnt = 1;
                  while ($row = mysqli_fetch_array($sql)) {
                  ?>
                    <p><b>Last Updated at</b> :<?php echo htmlentities($row['updationDate']); ?></p>
                    <div class="form-group">
                      <label for="courseTitle">Course Title </label>
                      <input type="text" class="form-control" id="courseTitle" name="courseTitle" placeholder="Course Title" value="<?php echo htmlentities($row['title']); ?>" required />
                    </div>

                    <div class="form-group">
                      <label for="objective">Objective </label>
                      <input type="text" class="form-control" id="objective" name="objective" placeholder="Objective" value="<?php echo htmlentities($row['objectives']); ?>" required />
                    </div>

                    <div class="form-group">
                      <label for="prerequisites">Pre-Requisites </label>
                      <input type="text" class="form-control" id="prerequisites" name="prerequisites" placeholder="Pre-Requisites" value="<?php echo htmlentities($row['prerequisites']); ?>" required />
                    </div>

                    <div class="form-group">
                      <label for="durationDays">Duration Days </label>
                      <input type="text" class="form-control" id="durationDays" name="durationDays" placeholder="Duration Days" value="<?php echo htmlentities($row['durationDays']); ?>" required />
                    </div>

                    <div class="form-group">
                      <label for="maxStudents">Max Student </label>
                      <input type="text" class="form-control" id="maxStudents" name="maxStudents" placeholder="Max Student" value="<?php echo htmlentities($row['maxStudents']); ?>" required />
                    </div>

                    <div class="form-group">
                      <label for="staffLevel">Staff Level </label>
                      <input type="text" class="form-control" id="staffLevel" name="staffLevel" placeholder="Staff Level" value="<?php echo htmlentities($row['staffLevel']); ?>" required />
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