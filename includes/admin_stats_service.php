<?php
// ==========================================
// ADMIN STATS SERVICE (OVERALL CANCELLATION RATE)
// ==========================================

/**
 * Calculates the overall aggregate weekly cancellation statistics for all students.
 * NOTE: Assumes $conn is available from db_connect.php.
 *
 * @param mysqli $conn The database connection object.
 * @return array An array containing the overall rate, status, and message.
 */
function get_overall_cancellation_stats($conn) {
    // ISO week (Monday start)
    $weekMode = 1;

    // Fetch aggregate statistics for the current week across all students
    $sql = "
        SELECT 
            COUNT(*) AS total_bookings,
            SUM(CASE WHEN Status = 'Canceled' THEN 1 ELSE 0 END) AS total_canceled
        FROM bookings
        WHERE YEARWEEK(StartTime, ?) = YEARWEEK(NOW(), ?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $weekMode, $weekMode);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalBookings = (int)($stats['total_bookings'] ?? 0);
    $totalCanceled = (int)($stats['total_canceled'] ?? 0);

    // ---------- Calculation ----------
    $overallRate = ($totalBookings > 0)
        ? round(($totalCanceled / $totalBookings) * 100)
        : 0;

    // ---------- Status Logic for Admin View ----------
    $status = 'Low';
    $color = 'green';
    $message = 'Overall weekly cancellation rate is healthy.';

    if ($totalBookings >= 10) {
        if ($overallRate >= 25) { 
            $status = 'HIGH';
            $color = 'red';
            $message = 'The overall rate is ' . $overallRate . '%, which may indicate a systemic issue or high demand stress.';
        } elseif ($overallRate >= 10) { 
            $status = 'MODERATE';
            $color = 'amber';
            $message = 'The overall rate is ' . $overallRate . '%. Monitor closely.';
        }
    } else {
        $message = 'Not enough data this week to establish a trend.';
    }

    return [
        'total_weekly'      => $totalBookings,
        'canceled_weekly'   => $totalCanceled,
        'rate_value'        => $overallRate,
        'rate_status'       => $status,
        'status_color'      => $color,
        'message'           => $message
    ];
}
?>