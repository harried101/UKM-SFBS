<?php
// ==========================================
// FETCH CANCELLATION RATE (WEEKLY) API - MODIFIED FOR INTERNAL USE
// ==========================================

/**
 * Calculates the student's weekly cancellation statistics and returns them as a PHP array.
 * This function is used when the file is included by other PHP scripts (like book.php).
 * It DOES NOT output JSON or call exit().
 *
 * @param mysqli $conn The database connection object.
 * @param string $studentIdentifier The unique student identifier from the session.
 * @return array An array containing 'rate_value', 'is_blocked', 'message', etc.
 */
function get_cancellation_stats_internal($conn, $studentIdentifier) {
    try {
        // ---------- Get User ID ----------
        $uStmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ? LIMIT 1");
        $uStmt->bind_param("s", $studentIdentifier);
        $uStmt->execute();
        $userRow = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();

        if (!$userRow) {
            return ['error' => 'User not found'];
        }

        $uid = (int)$userRow['UserID'];

        // ---------- Monthly Booking Stats (Modified to Monthly) ----------
        // $weekMode = 1; // Removed

        $sql = "
            SELECT 
                COUNT(*) AS total_bookings,
                SUM(CASE WHEN Status = 'Canceled' THEN 1 ELSE 0 END) AS total_canceled
            FROM bookings
            WHERE UserID = ?
            AND MONTH(StartTime) = MONTH(NOW()) 
            AND YEAR(StartTime) = YEAR(NOW())
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $totalBookings = (int)($stats['total_bookings'] ?? 0);
        $totalCanceled = (int)($stats['total_canceled'] ?? 0);

        // ---------- Calculation ----------
        $cancelRate = ($totalBookings > 0)
            ? round(($totalCanceled / $totalBookings) * 100)
            : 0;

        // ---------- Status Logic (Same as original) ----------
        $status = 'Low'; 
        $color = 'green';
        $message = 'Your cancellation rate is excellent (Low).';
        $is_blocked = false;

        if ($totalBookings >= 3) {
            // Check against the 33% threshold (High Risk)
            if ($cancelRate >= 33) { 
                $status = 'High (Blocked)';
                $color = 'red';
                $message = 'Booking restricted. Monthly cancellation rate is too high (>33%).';
                $is_blocked = true;
            } elseif ($cancelRate >= 15) { 
                // Moderate Risk
                $status = 'Moderate';
                $color = 'amber';
                $message = 'Your cancellation rate is moderate (≥15%). Avoid frequent cancellations.';
            }
        } else {
            $message = 'Not enough data yet. Your account is in good standing.';
        }

        // ---------- Quota Calculation (User Friendly) ----------
        // Max cancellations allowed before hitting > 33% (given CURRENT total bookings)
        // Formula: allowed < Total * 0.33
        // If they book more, they get more allowance.
        
        $max_allowed = floor($totalBookings * 0.33); 
        
        // Safety Buffer: If total bookings < 3, we allow them to cancel until they hit the limit of 3 bookings.
        // But practically, if Total < 3, they are SAFE regardless of cancellations (as per our logic).
        // So we can display a "Safe Mode" message or a virtual quota.
        // Let's set a virtual quota of 2 for new users (since 3rd booking activates check).
        
        if ($totalBookings < 3) {
            $cancellations_remaining = "Safe (Low Activity)";
            $quota_message = "You are in the safety period. You can make up to 3 bookings before cancellation rate applies.";
        } else {
            $remaining = $max_allowed - $totalCanceled;
            $cancellations_remaining = max(0, $remaining);
            $quota_message = "You have $cancellations_remaining free cancellation(s) remaining for this month.";
            
            if ($is_blocked) {
                $cancellations_remaining = 0;
                $quota_message = "You have exceeded your cancellation limit.";
            }
        }

        // ---------- Return Array ----------
        return [
            'total_monthly'     => $totalBookings, 
            'canceled_monthly'  => $totalCanceled,
            'rate_value'        => $cancelRate,
            'rate_status'       => $status,
            'status_color'      => $color,
            'message'           => $message,
            'is_blocked'        => $is_blocked,
            'cancellations_remaining' => $cancellations_remaining, // NEW
            'quota_message'     => $quota_message // NEW
        ];

    } catch (Exception $e) {
        return ['error' => 'Server error: ' . $e->getMessage()];
    }
}

// ----------------------------------------------------
// MAIN API ENDPOINT LOGIC
// ----------------------------------------------------

// This check ensures the code below only runs if the file is called directly (as an API)
// and is skipped when the file is included (required_once) by book.php.
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    
    ob_start();
    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    session_start();
    // Assuming this path is correct for the API to connect to the database
    require_once '../../includes/db_connect.php'; 
    date_default_timezone_set('Asia/Kuala_Lumpur');

    header('Content-Type: application/json');
    
    // Helper function for API response
    function jsonResponse($success, $data) {
        ob_clean();
        echo json_encode([
            'success' => $success,
            'data' => $data
        ]);
        exit; // IMPORTANT: This terminates the script for API calls
    }

    // Auth check
    if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Student') {
        jsonResponse(false, ['message' => 'Access denied']);
    }

    // Get student identifier
    $studentIdentifier = $_SESSION['user_id'];
    
    // Call the internal function
    $healthData = get_cancellation_stats_internal($conn, $studentIdentifier);

    if (isset($healthData['error'])) {
         jsonResponse(false, ['message' => $healthData['error']]);
    }

    jsonResponse(true, $healthData);
}
?>