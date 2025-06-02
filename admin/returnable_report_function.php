// Function to get returnable items report
function getReturnableReport($conn, $date_from, $date_to) {
    $date_from = $conn->real_escape_string($date_from);
    $date_to = $conn->real_escape_string($date_to);
    
    $query = "SELECT 
                g.gatepass_number,
                g.from_location,
                g.to_location,
                gi.item_name,
                gi.quantity,
                gi.unit,
                CASE WHEN gi.returned = 1 THEN 'Returned' ELSE 'Pending Return' END as return_status,
                COALESCE(gi.return_date, '') as return_date,
                u.name as created_by,
                r.name as returned_by
              FROM gatepass_items gi
              JOIN gatepasses g ON gi.gatepass_id = g.id
              JOIN users u ON g.created_by = u.id
              LEFT JOIN users r ON gi.returned_by = r.id
              WHERE gi.is_returnable = 1
              AND DATE(g.created_at) BETWEEN '$date_from' AND '$date_to'
              ORDER BY g.created_at DESC, gi.returned ASC";
              
    $result = $conn->query($query);
    $data = array();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}
