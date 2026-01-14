<?php
session_start();
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
} else {

    //Code for Insertion
    if (isset($_POST['submit'])) {
        $courseTitle = $_POST['courseTitle'];
        $objective = $_POST['objective'];
        $prerequisites = $_POST['prerequisites'];
        $durationDays = $_POST['durationDays'];
        $maxStudents = $_POST['maxStudents'];
        $staffLevel = $_POST['staffLevel'];
        $ret = mysqli_query($con, "insert into courses(title,objectives,prerequisites,durationDays,maxStudents,staffLevel) values('$courseTitle','$objective','$prerequisites','$durationDays','$maxStudents','$staffLevel')");
        if ($ret) {
            echo '<script>alert("Course Created Successfully !!")</script>';
            echo '<script>window.location.href=course.php</script>';
        } else {
            echo '<script>alert("Error : Course not created!!")</script>';
            echo '<script>window.location.href=course.php</script>';
        }
    }

    //Code for Insertion
    if (isset($_GET['del'])) {
        mysqli_query($con, "update courses set isDeleted = 1 where courseID = '" . $_GET['id'] . "'");
        echo '<script>alert("Course deleted!!")</script>';
        echo '<script>window.location.href=course.php</script>';
    }
?>

    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml">

    <head>
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
                                    <div class="form-group">
                                        <label for="courseTitle">Course Title </label>
                                        <input type="text" class="form-control" id="courseTitle" name="courseTitle" placeholder="Course Title" required />
                                    </div>

                                    <div class="form-group">
                                        <label for="objective">Objective </label>
                                        <input type="text" class="form-control" id="objective" name="objective" placeholder="Objective" required />
                                    </div>

                                    <div class="form-group">
                                        <label for="prerequisites">Pre-Requisites </label>
                                        <input type="text" class="form-control" id="prerequisites" name="prerequisites" placeholder="Pre-Requisites" required />
                                    </div>

                                    <div class="form-group">
                                        <label for="durationDays">Duration Days </label>
                                        <input type="text" class="form-control" id="durationDays" name="durationDays" placeholder="Duration Days" required />
                                    </div>

                                    <div class="form-group">
                                        <label for="maxStudents">Max Student </label>
                                        <input type="text" class="form-control" id="maxStudents" name="maxStudents" placeholder="Max Student" required />
                                    </div>

                                    <div class="form-group">
                                        <label for="staffLevel">Staff Level </label>
                                        <input type="text" class="form-control" id="staffLevel" name="staffLevel" placeholder="Staff Level" required />
                                    </div>

                                    <button type="submit" name="submit" class="btn btn-default">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
                <font color="red" align="center"><?php echo htmlentities($_SESSION['delmsg']); ?><?php echo htmlentities($_SESSION['delmsg'] = ""); ?></font>
                <div class="col-md-12">
                    <!--    Bordered Table  -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Manage Course
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="table-responsive table-bordered">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Course Title</th>
                                            <th>Objective</th>
                                            <th>Pre-Requisites</th>
                                            <th>Duration Days</th>
                                            <th>Max Students</th>
                                            <th>Staff Level</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = mysqli_query($con, "select * from courses where isDeleted != 1");
                                        $cnt = 1;
                                        while ($row = mysqli_fetch_array($sql)) {
                                        ?>


                                            <tr>
                                                <td><?php echo $cnt; ?></td>
                                                <td><?php echo htmlentities($row['title']); ?></td>
                                                <td><?php echo htmlentities($row['objectives']); ?></td>
                                                <td><?php echo htmlentities($row['prerequisites']); ?></td>
                                                <td><?php echo htmlentities($row['durationDays']); ?></td>
                                                <td><?php echo htmlentities($row['maxStudents']); ?></td>
                                                <td><?php echo htmlentities($row['staffLevel']); ?></td>
                                                <td>
                                                    <a href="edit-course.php?id=<?php echo $row['courseID'] ?>">
                                                        <button class="btn btn-primary"><i class="fa fa-edit "></i> Edit</button> </a>
                                                    <a href="course.php?id=<?php echo $row['courseID'] ?>&del=delete" onClick="return confirm('Are you sure you want to delete?')">
                                                        <button class="btn btn-danger">Delete</button>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php
                                            $cnt++;
                                        } ?>


                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!--  End  Bordered Table  -->
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