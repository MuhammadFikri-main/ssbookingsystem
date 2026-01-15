<section class="menu-section">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="navbar-collapse collapse ">
                    <ul id="menu-top" class="nav navbar-nav navbar-right">
                        <li style="color: white;">
                            <a href="#"><?php echo htmlentities($_SESSION['alogin']); ?>
                                <!-- <small>(<?php echo htmlentities($_SESSION['role']); ?>)</small> --></a>
                        </li>
                        <li><a href="course.php">Course</a></li>
                        <?php if ($_SESSION['role'] == "manager") {
                        ?>
                            <li><a href="student-registration.php">Nomination</a></li>
                        <?php }
                        ?>
                        <?php if ($_SESSION['role'] == "admin") {
                        ?>
                            <li><a href="manage-course-schedule.php">Schedule</a></li>
                            <li><a href="enroll-history.php">Booking</a></li>
                        <?php } ?>
                        <li><a href="billing.php">Billing</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>