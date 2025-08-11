<?php
/**
 * Dashboard Statistics API Endpoint
 * Provides real-time statistics for dashboard widgets and charts
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../models/Equipment.php';
require_once '../models/Database.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Check authentication (simplified for demo)
    if (!isAuthenticated()) {
        http_response_code(401);
        $response['message'] = 'Authentication required';
        echo json_encode($response);
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        $response['message'] = 'Method not allowed';
        echo json_encode($response);
        exit();
    }
    
    $equipment = new Equipment();
    $db = Database::getInstance();
    
    // Get equipment statistics
    $stats = $equipment->getStatistics();
    
    // Get additional dashboard metrics
    $dashboardData = [
        'total' => $stats['total'],
        'by_status' => $stats['by_status'],
        'by_condition' => $stats['by_condition'],
        'total_value' => $stats['total_value'],
        'recent_activities' => getRecentActivities($db),
        'maintenance_due' => getMaintenanceDue($equipment),
        'warranty_expiring' => getWarrantyExpiring($equipment),
        'alerts' => getSystemAlerts($db),
        'room_utilization' => getRoomUtilization($db),
        'monthly_trends' => getMonthlyTrends($db)
    ];
    
    $response['success'] = true;
    $response['data'] = $dashboardData;
    
} catch (Exception $e) {
    error_log("Dashboard Stats API Error: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'Internal server error';
}

echo json_encode($response);

/**
 * Get recent activities from audit log and equipment movements
 */
function getRecentActivities($db) {
    try {
        $sql = "
            (SELECT 
                'equipment_add' as type,
                CONCAT('New equipment added: ', e.name, ' (', e.asset_tag, ')') as description,
                a.created_at as activity_date,
                u.full_name as user_name
            FROM audit_log a
            JOIN equipment e ON a.record_id = e.id
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.table_name = 'equipment' AND a.action = 'INSERT'
            ORDER BY a.created_at DESC
            LIMIT 5)
            
            UNION ALL
            
            (SELECT 
                'equipment_move' as type,
                CONCAT('Equipment moved: ', e.asset_tag, ' to ', tr.name) as description,
                m.movement_date as activity_date,
                u.full_name as user_name
            FROM equipment_movements m
            JOIN equipment e ON m.equipment_id = e.id
            LEFT JOIN rooms tr ON m.to_room_id = tr.id
            LEFT JOIN users u ON m.moved_by = u.id
            WHERE m.movement_type = 'room_transfer'
            ORDER BY m.movement_date DESC
            LIMIT 5)
            
            UNION ALL
            
            (SELECT 
                'maintenance_complete' as type,
                CONCAT('Maintenance completed: ', e.asset_tag, ' - ', mr.description) as description,
                mr.updated_at as activity_date,
                u.full_name as user_name
            FROM maintenance_records mr
            JOIN equipment e ON mr.equipment_id = e.id
            LEFT JOIN users u ON mr.technician_id = u.id
            WHERE mr.status = 'completed'
            ORDER BY mr.updated_at DESC
            LIMIT 5)
            
            ORDER BY activity_date DESC
            LIMIT 10
        ";
        
        return $db->fetchAll($sql);
        
    } catch (Exception $e) {
        error_log("Error fetching recent activities: " . $e->getMessage());
        return [];
    }
}

/**
 * Get equipment due for maintenance
 */
function getMaintenanceDue($equipment) {
    try {
        $maintenanceDue = $equipment->getDueForMaintenance();
        return [
            'count' => count($maintenanceDue),
            'items' => array_slice($maintenanceDue, 0, 5) // Top 5 most urgent
        ];
    } catch (Exception $e) {
        error_log("Error fetching maintenance due: " . $e->getMessage());
        return ['count' => 0, 'items' => []];
    }
}

/**
 * Get equipment with expiring warranties
 */
function getWarrantyExpiring($equipment) {
    try {
        $warrantyExpiring = $equipment->getWarrantyExpiring(30); // 30 days
        return [
            'count' => count($warrantyExpiring),
            'items' => array_slice($warrantyExpiring, 0, 5) // Top 5 most urgent
        ];
    } catch (Exception $e) {
        error_log("Error fetching warranty expiring: " . $e->getMessage());
        return ['count' => 0, 'items' => []];
    }
}

/**
 * Get system alerts and notifications
 */
function getSystemAlerts($db) {
    try {
        $alerts = [];
        
        // Critical incidents
        $criticalIncidents = $db->fetchAll("
            SELECT COUNT(*) as count 
            FROM incidents 
            WHERE priority = 'critical' AND status IN ('open', 'in_progress')
        ");
        
        if ($criticalIncidents[0]['count'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'exclamation-triangle',
                'title' => 'Critical Incidents',
                'message' => $criticalIncidents[0]['count'] . ' critical incidents require immediate attention',
                'link' => '#incidents'
            ];
        }
        
        // Equipment needing repair
        $brokenEquipment = $db->fetchAll("
            SELECT COUNT(*) as count 
            FROM equipment 
            WHERE condition_status = 'broken' AND is_active = 1
        ");
        
        if ($brokenEquipment[0]['count'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'tools',
                'title' => 'Equipment Needs Repair',
                'message' => $brokenEquipment[0]['count'] . ' pieces of equipment need repair',
                'link' => '#equipment?status=repair'
            ];
        }
        
        // Low inventory alerts (if applicable)
        $lowStock = $db->fetchAll("
            SELECT c.name, COUNT(e.id) as count
            FROM categories c
            LEFT JOIN equipment e ON c.id = e.category_id AND e.is_active = 1
            GROUP BY c.id, c.name
            HAVING count < 5 AND c.name IN ('Desktop Computers', 'Laptops')
        ");
        
        foreach ($lowStock as $item) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'info-circle',
                'title' => 'Low Inventory',
                'message' => "Only {$item['count']} {$item['name']} available",
                'link' => '#equipment?category=' . $item['name']
            ];
        }
        
        return $alerts;
        
    } catch (Exception $e) {
        error_log("Error fetching system alerts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get room utilization statistics
 */
function getRoomUtilization($db) {
    try {
        $sql = "
            SELECT 
                r.name as room_name,
                r.capacity,
                COUNT(e.id) as equipment_count,
                ROUND((COUNT(e.id) / r.capacity) * 100, 1) as utilization_percent
            FROM rooms r
            LEFT JOIN equipment e ON r.id = e.room_id AND e.is_active = 1
            WHERE r.room_type = 'computer_lab' AND r.is_active = 1
            GROUP BY r.id, r.name, r.capacity
            ORDER BY utilization_percent DESC
        ";
        
        return $db->fetchAll($sql);
        
    } catch (Exception $e) {
        error_log("Error fetching room utilization: " . $e->getMessage());
        return [];
    }
}

/**
 * Get monthly trends data for charts
 */
function getMonthlyTrends($db) {
    try {
        // Equipment added per month (last 12 months)
        $equipmentTrends = $db->fetchAll("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM equipment 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        
        // Maintenance activities per month
        $maintenanceTrends = $db->fetchAll("
            SELECT 
                DATE_FORMAT(start_date, '%Y-%m') as month,
                COUNT(*) as count
            FROM maintenance_records 
            WHERE start_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(start_date, '%Y-%m')
            ORDER BY month ASC
        ");
        
        // Incidents per month
        $incidentTrends = $db->fetchAll("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM incidents 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        
        return [
            'equipment_added' => $equipmentTrends,
            'maintenance_activities' => $maintenanceTrends,
            'incidents_reported' => $incidentTrends
        ];
        
    } catch (Exception $e) {
        error_log("Error fetching monthly trends: " . $e->getMessage());
        return [
            'equipment_added' => [],
            'maintenance_activities' => [],
            'incidents_reported' => []
        ];
    }
}

/**
 * Check if user is authenticated (simplified for demo)
 */
function isAuthenticated() {
    // In production, implement proper session/token validation
    return true; // For demo purposes
}
?>