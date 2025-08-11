<?php
/**
 * Equipment API Endpoint
 * Handles CRUD operations for equipment with security
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../models/Equipment.php';
require_once '../auth/session.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Check authentication (in production, implement proper session/token validation)
    if (!isAuthenticated()) {
        http_response_code(401);
        $response['message'] = 'Authentication required';
        echo json_encode($response);
        exit();
    }
    
    $equipment = new Equipment();
    $method = $_SERVER['REQUEST_METHOD'];
    $userId = getCurrentUserId();
    
    switch ($method) {
        case 'GET':
            handleGetRequest($equipment, $response);
            break;
            
        case 'POST':
            handlePostRequest($equipment, $response, $userId);
            break;
            
        case 'PUT':
            handlePutRequest($equipment, $response, $userId);
            break;
            
        case 'DELETE':
            handleDeleteRequest($equipment, $response, $userId);
            break;
            
        default:
            http_response_code(405);
            $response['message'] = 'Method not allowed';
            break;
    }
    
} catch (Exception $e) {
    error_log("Equipment API Error: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'Internal server error';
}

echo json_encode($response);

/**
 * Handle GET requests - retrieve equipment data
 */
function handleGetRequest($equipment, &$response) {
    try {
        // Single equipment by ID
        if (isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                http_response_code(400);
                $response['message'] = 'Invalid equipment ID';
                return;
            }
            
            $data = $equipment->getById($id);
            if (!$data) {
                http_response_code(404);
                $response['message'] = 'Equipment not found';
                return;
            }
            
            $response['success'] = true;
            $response['data'] = $data;
            return;
        }
        
        // Export to CSV
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            exportEquipmentCSV($equipment);
            return;
        }
        
        // List equipment with filters and pagination
        $filters = [];
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $limit = filter_var($_GET['limit'] ?? 10, FILTER_VALIDATE_INT) ?: 10;
        
        // Apply filters
        if (!empty($_GET['search'])) {
            $filters['search'] = sanitizeInput($_GET['search']);
        }
        
        if (!empty($_GET['status'])) {
            $filters['status'] = sanitizeInput($_GET['status']);
        }
        
        if (!empty($_GET['category'])) {
            $filters['category_id'] = filter_var($_GET['category'], FILTER_VALIDATE_INT);
        }
        
        if (!empty($_GET['room'])) {
            $filters['room_id'] = filter_var($_GET['room'], FILTER_VALIDATE_INT);
        }
        
        // Get equipment data
        $equipmentData = $equipment->getAll($filters);
        
        // Calculate pagination
        $totalItems = count($equipmentData); // In production, use separate count query
        $totalPages = ceil($totalItems / $limit);
        $offset = ($page - 1) * $limit;
        $paginatedData = array_slice($equipmentData, $offset, $limit);
        
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $limit,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages
        ];
        
        $response['success'] = true;
        $response['data'] = [
            'equipment' => $paginatedData,
            'pagination' => $pagination
        ];
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Handle POST requests - create new equipment
 */
function handlePostRequest($equipment, &$response, $userId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            $response['message'] = 'Invalid JSON data';
            return;
        }
        
        // Validate required fields
        $requiredFields = ['asset_tag', 'name', 'category_id'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                $response['message'] = "Field '$field' is required";
                return;
            }
        }
        
        // Sanitize input data
        $data = sanitizeEquipmentData($input);
        
        // Create equipment
        $equipmentId = $equipment->create($data, $userId);
        
        http_response_code(201);
        $response['success'] = true;
        $response['message'] = 'Equipment created successfully';
        $response['data'] = ['id' => $equipmentId];
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            http_response_code(409);
            $response['message'] = $e->getMessage();
        } else {
            throw $e;
        }
    }
}

/**
 * Handle PUT requests - update equipment
 */
function handlePutRequest($equipment, &$response, $userId) {
    try {
        $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            $response['message'] = 'Invalid equipment ID';
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            $response['message'] = 'Invalid JSON data';
            return;
        }
        
        // Sanitize input data
        $data = sanitizeEquipmentData($input);
        
        // Update equipment
        $updated = $equipment->update($id, $data, $userId);
        
        if ($updated > 0) {
            $response['success'] = true;
            $response['message'] = 'Equipment updated successfully';
        } else {
            http_response_code(404);
            $response['message'] = 'Equipment not found or no changes made';
        }
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'not found') !== false) {
            http_response_code(404);
            $response['message'] = $e->getMessage();
        } else if (strpos($e->getMessage(), 'already exists') !== false) {
            http_response_code(409);
            $response['message'] = $e->getMessage();
        } else {
            throw $e;
        }
    }
}

/**
 * Handle DELETE requests - delete equipment
 */
function handleDeleteRequest($equipment, &$response, $userId) {
    try {
        $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            $response['message'] = 'Invalid equipment ID';
            return;
        }
        
        $deleted = $equipment->delete($id, $userId);
        
        if ($deleted > 0) {
            $response['success'] = true;
            $response['message'] = 'Equipment deleted successfully';
        } else {
            http_response_code(404);
            $response['message'] = 'Equipment not found';
        }
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Export equipment data to CSV
 */
function exportEquipmentCSV($equipment) {
    $filters = [];
    
    // Apply same filters as GET request
    if (!empty($_GET['search'])) {
        $filters['search'] = sanitizeInput($_GET['search']);
    }
    
    if (!empty($_GET['status'])) {
        $filters['status'] = sanitizeInput($_GET['status']);
    }
    
    if (!empty($_GET['category'])) {
        $filters['category_id'] = filter_var($_GET['category'], FILTER_VALIDATE_INT);
    }
    
    if (!empty($_GET['room'])) {
        $filters['room_id'] = filter_var($_GET['room'], FILTER_VALIDATE_INT);
    }
    
    $equipmentData = $equipment->getAll($filters);
    
    // Set CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="equipment_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    $headers = [
        'Asset Tag', 'Name', 'Category', 'Manufacturer', 'Model', 
        'Serial Number', 'Status', 'Condition', 'Location', 
        'Assigned To', 'Purchase Date', 'Purchase Price', 'Current Value'
    ];
    
    fputcsv($output, $headers);
    
    // CSV data
    foreach ($equipmentData as $item) {
        $row = [
            $item['asset_tag'],
            $item['name'],
            $item['category_name'] ?? '',
            $item['manufacturer_name'] ?? '',
            $item['model'] ?? '',
            $item['serial_number'] ?? '',
            $item['status'],
            $item['condition_status'],
            $item['room_name'] ?? '',
            $item['assigned_to_name'] ?? '',
            $item['purchase_date'] ?? '',
            $item['purchase_price'] ?? '',
            $item['current_value'] ?? ''
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Sanitize equipment data input
 */
function sanitizeEquipmentData($input) {
    $data = [];
    
    // String fields
    $stringFields = [
        'asset_tag', 'name', 'model', 'serial_number', 'description',
        'status', 'condition_status', 'notes', 'qr_code', 'barcode'
    ];
    
    foreach ($stringFields as $field) {
        if (isset($input[$field])) {
            $data[$field] = sanitizeInput($input[$field]);
        }
    }
    
    // Integer fields
    $intFields = [
        'category_id', 'manufacturer_id', 'supplier_id', 'room_id', 
        'assigned_to', 'depreciation_years'
    ];
    
    foreach ($intFields as $field) {
        if (isset($input[$field]) && $input[$field] !== '') {
            $data[$field] = filter_var($input[$field], FILTER_VALIDATE_INT);
        }
    }
    
    // Decimal fields
    $decimalFields = ['purchase_price', 'current_value'];
    
    foreach ($decimalFields as $field) {
        if (isset($input[$field]) && $input[$field] !== '') {
            $data[$field] = filter_var($input[$field], FILTER_VALIDATE_FLOAT);
        }
    }
    
    // Date fields
    $dateFields = ['purchase_date', 'warranty_start', 'warranty_end'];
    
    foreach ($dateFields as $field) {
        if (isset($input[$field]) && $input[$field] !== '') {
            if (validateDate($input[$field])) {
                $data[$field] = $input[$field];
            }
        }
    }
    
    // JSON field for specifications
    if (isset($input['specifications']) && is_array($input['specifications'])) {
        $data['specifications'] = json_encode($input['specifications']);
    }
    
    return $data;
}

/**
 * Sanitize string input
 */
function sanitizeInput($input) {
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Check if user is authenticated (simplified for demo)
 */
function isAuthenticated() {
    // In production, implement proper session/token validation
    return true; // For demo purposes
}

/**
 * Get current user ID (simplified for demo)
 */
function getCurrentUserId() {
    // In production, get from session or JWT token
    return 1; // Admin user ID for demo
}
?>