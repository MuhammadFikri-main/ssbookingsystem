<?php
include('includes/config.php');

echo "<h1>Database Schema Fixes for Billing</h1>";

$errors = [];
$success = [];

// Check if columns exist before altering
function columnExists($con, $table, $column) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return mysqli_num_rows($result) > 0;
}

// 1. Fix booking table - add transferFee, cancellationDate, refundStatus
echo "<h2>1. Fixing Booking Table</h2>";

if (!columnExists($con, 'booking', 'transferFee')) {
    if (mysqli_query($con, "ALTER TABLE booking ADD COLUMN transferFee DECIMAL(10,2) DEFAULT 0.00 AFTER status")) {
        $success[] = "Added transferFee column to booking table";
    } else {
        $errors[] = "Failed to add transferFee: " . mysqli_error($con);
    }
} else {
    $success[] = "transferFee column already exists";
}

if (!columnExists($con, 'booking', 'cancellationDate')) {
    if (mysqli_query($con, "ALTER TABLE booking ADD COLUMN cancellationDate DATETIME NULL AFTER transferFee")) {
        $success[] = "Added cancellationDate column to booking table";
    } else {
        $errors[] = "Failed to add cancellationDate: " . mysqli_error($con);
    }
} else {
    $success[] = "cancellationDate column already exists";
}

if (!columnExists($con, 'booking', 'refundStatus')) {
    if (mysqli_query($con, "ALTER TABLE booking ADD COLUMN refundStatus ENUM('None', 'Refunded', 'Credited', 'Forfeited') DEFAULT 'None' AFTER cancellationDate")) {
        $success[] = "Added refundStatus column to booking table";
    } else {
        $errors[] = "Failed to add refundStatus: " . mysqli_error($con);
    }
} else {
    $success[] = "refundStatus column already exists";
}

// 2. Fix billing table - add nominatorID, billingMonth
echo "<h2>2. Fixing Billing Table</h2>";

if (!columnExists($con, 'billing', 'nominatorID')) {
    if (mysqli_query($con, "ALTER TABLE billing ADD COLUMN nominatorID INT NULL AFTER invoiceNumber")) {
        $success[] = "Added nominatorID column to billing table";
    } else {
        $errors[] = "Failed to add nominatorID to billing: " . mysqli_error($con);
    }
} else {
    $success[] = "nominatorID column already exists in billing";
}

if (!columnExists($con, 'billing', 'billingMonth')) {
    if (mysqli_query($con, "ALTER TABLE billing ADD COLUMN billingMonth VARCHAR(7) NULL AFTER billingType")) {
        $success[] = "Added billingMonth column to billing table";
    } else {
        $errors[] = "Failed to add billingMonth: " . mysqli_error($con);
    }
} else {
    $success[] = "billingMonth column already exists";
}

// Make courseRunID nullable
$courseRunInfo = mysqli_query($con, "SHOW COLUMNS FROM billing WHERE Field='courseRunID'");
if ($courseRunInfo) {
    $col = mysqli_fetch_assoc($courseRunInfo);
    if ($col['Null'] == 'NO') {
        if (mysqli_query($con, "ALTER TABLE billing MODIFY courseRunID INT NULL")) {
            $success[] = "Made courseRunID nullable in billing table";
        } else {
            $errors[] = "Failed to make courseRunID nullable: " . mysqli_error($con);
        }
    } else {
        $success[] = "courseRunID is already nullable";
    }
}

// 3. Fix payment table - check nominatorID type
echo "<h2>3. Fixing Payment Table</h2>";

$nominatorInfo = mysqli_query($con, "SHOW COLUMNS FROM payment WHERE Field='nominatorID'");
if ($nominatorInfo) {
    $col = mysqli_fetch_assoc($nominatorInfo);
    echo "<p>Current nominatorID type: {$col['Type']}</p>";
    
    // If it's VARCHAR, we need to drop and recreate
    if (strpos($col['Type'], 'varchar') !== false || strpos($col['Type'], 'char') !== false) {
        // Drop the column
        if (mysqli_query($con, "ALTER TABLE payment DROP COLUMN nominatorID")) {
            $success[] = "Dropped old nominatorID column (was VARCHAR)";
            
            // Add it back as INT
            if (mysqli_query($con, "ALTER TABLE payment ADD COLUMN nominatorID INT NOT NULL AFTER billingID")) {
                $success[] = "Re-added nominatorID as INT";
            } else {
                $errors[] = "Failed to re-add nominatorID as INT: " . mysqli_error($con);
            }
        } else {
            $errors[] = "Failed to drop nominatorID: " . mysqli_error($con);
        }
    } else {
        $success[] = "nominatorID is already INT type";
    }
} else {
    // Column doesn't exist, add it
    if (mysqli_query($con, "ALTER TABLE payment ADD COLUMN nominatorID INT NOT NULL AFTER billingID")) {
        $success[] = "Added nominatorID column to payment table";
    } else {
        $errors[] = "Failed to add nominatorID: " . mysqli_error($con);
    }
}

// 4. Set transfer fees for existing bookings (based on course prices)
echo "<h2>4. Setting Transfer Fees for Existing Bookings</h2>";

$updateQuery = "
UPDATE booking b
JOIN courserun cr ON b.courseRunID = cr.courseRunID
JOIN courses c ON cr.courseID = c.courseID
LEFT JOIN (
    SELECT courseID, price 
    FROM courseprice 
    WHERE isDeleted = 0 
    AND effectiveDate <= CURDATE()
    ORDER BY effectiveDate DESC
) cp ON c.courseID = cp.courseID
SET b.transferFee = COALESCE(cp.price, 0)
WHERE b.transferFee = 0 OR b.transferFee IS NULL
";

if (mysqli_query($con, $updateQuery)) {
    $affected = mysqli_affected_rows($con);
    $success[] = "Updated transfer fees for $affected bookings";
} else {
    $errors[] = "Failed to update transfer fees: " . mysqli_error($con);
}

// Display results
echo "<h2>Results</h2>";

if (count($success) > 0) {
    echo "<div style='background-color: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h3 style='color: #155724;'>Success:</h3><ul>";
    foreach ($success as $msg) {
        echo "<li style='color: #155724;'>✓ $msg</li>";
    }
    echo "</ul></div>";
}

if (count($errors) > 0) {
    echo "<div style='background-color: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3 style='color: #721c24;'>Errors:</h3><ul>";
    foreach ($errors as $msg) {
        echo "<li style='color: #721c24;'>✗ $msg</li>";
    }
    echo "</ul></div>";
}

echo "<hr>";
echo "<p><a href='check_billing_db.php' class='btn btn-info'>Check Database Structure</a></p>";
echo "<p><a href='billing.php' class='btn btn-primary'>Go to Billing Page</a></p>";
?>
