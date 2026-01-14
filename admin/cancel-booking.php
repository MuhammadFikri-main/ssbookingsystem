<?php
session_start();
include('includes/config.php');

if (isset($_GET['bookingID'])) {
    $bookingID = (int)$_GET['bookingID'];

    // Get booking details
    $bookingQuery = mysqli_query($con, "
        SELECT b.*, cr.startDate 
        FROM booking b 
        JOIN courserun cr ON b.courseRunID = cr.courseRunID 
        WHERE b.bookingID = $bookingID
    ");
    $booking = mysqli_fetch_array($bookingQuery);

    if ($booking) {
        $daysUntilCourse = (strtotime($booking['startDate']) - time()) / (60 * 60 * 24);

        // Determine refund status
        if ($daysUntilCourse >= 14) {
            // More than 2 weeks: refund or credit
            $refundStatus = 'Refunded'; // or 'Credited'
        } else {
            // Less than 2 weeks: forfeit
            $refundStatus = 'Forfeited';
        }

        // Update booking
        mysqli_query($con, "
            UPDATE booking 
            SET status = 'Cancelled', 
                cancellationDate = NOW(),
                refundStatus = '$refundStatus'
            WHERE bookingID = $bookingID
        ");

        // Try to fill from waiting list
        $courseRunID = $booking['courseRunID'];
        $waitingQuery = mysqli_query($con, "
            SELECT w.*, cr.courseID 
            FROM waitinglist w
            JOIN courserun cr ON cr.courseID = w.courseID
            WHERE cr.courseRunID = $courseRunID
            ORDER BY w.creationDate ASC
            LIMIT 1
        ");

        if ($waiting = mysqli_fetch_array($waitingQuery)) {
            // Move from waiting list to booking
            mysqli_query($con, "
                INSERT INTO booking (courseRunID, nominatorID, delegateID, status, transferFee)
                SELECT $courseRunID, nominatorID, delegateID, 'Confirmed', 
                       (SELECT price FROM courseprice WHERE courseID = {$waiting['courseID']} 
                        AND isDeleted = 0 AND effectiveDate <= CURDATE() 
                        ORDER BY effectiveDate DESC LIMIT 1)
                FROM waitinglist 
                WHERE waitingListID = {$waiting['waitingListID']}
            ");

            // Remove from waiting list
            mysqli_query($con, "DELETE FROM waitinglist WHERE waitingListID = {$waiting['waitingListID']}");
        }

        $_SESSION['msg'] = "Booking cancelled. Transfer fee: $refundStatus";
    }
}

header('location:manage-bookings.php');
