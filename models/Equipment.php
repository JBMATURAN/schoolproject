<?php
/**
 * Equipment Model Class
 * Handles all equipment-related database operations
 */

require_once 'Database.php';

class Equipment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all equipment with optional filters
     */
    public function getAll($filters = []) {
        $sql = "SELECT * FROM equipment_overview";
        $params = [];
        $conditions = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $conditions[] = "status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['category_id'])) {
            $conditions[] = "category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['room_id'])) {
            $conditions[] = "room_id = :room_id";
            $params[':room_id'] = $filters['room_id'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(name LIKE :search OR asset_tag LIKE :search OR serial_number LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get equipment by ID
     */
    public function getById($id) {
        $sql = "SELECT e.*, c.name as category_name, m.name as manufacturer_name, 
                       r.name as room_name, s.name as supplier_name
                FROM equipment e
                LEFT JOIN categories c ON e.category_id = c.id
                LEFT JOIN manufacturers m ON e.manufacturer_id = m.id
                LEFT JOIN rooms r ON e.room_id = r.id
                LEFT JOIN suppliers s ON e.supplier_id = s.id
                WHERE e.id = :id AND e.is_active = 1";
        
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    /**
     * Get equipment by asset tag
     */
    public function getByAssetTag($assetTag) {
        $sql = "SELECT * FROM equipment_overview WHERE asset_tag = :asset_tag";
        return $this->db->fetchOne($sql, [':asset_tag' => $assetTag]);
    }
    
    /**
     * Create new equipment
     */
    public function create($data, $userId = null) {
        // Validate required fields
        $requiredFields = ['asset_tag', 'name', 'category_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Check if asset tag already exists
        if ($this->getByAssetTag($data['asset_tag'])) {
            throw new Exception("Asset tag already exists");
        }
        
        // Set default values
        $data['created_by'] = $userId;
        $data['is_active'] = 1;
        
        // Generate QR code if enabled
        if ($this->isQrCodeEnabled()) {
            $data['qr_code'] = $this->generateQrCode($data['asset_tag']);
        }
        
        try {
            $this->db->beginTransaction();
            
            $equipmentId = $this->db->insert('equipment', $data);
            
            // Log audit
            $this->db->logAudit('equipment', $equipmentId, 'INSERT', null, $data, $userId);
            
            $this->db->commit();
            
            return $equipmentId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Update equipment
     */
    public function update($id, $data, $userId = null) {
        // Get original data for audit
        $originalData = $this->getById($id);
        if (!$originalData) {
            throw new Exception("Equipment not found");
        }
        
        // Check asset tag uniqueness if changed
        if (isset($data['asset_tag']) && $data['asset_tag'] !== $originalData['asset_tag']) {
            $existing = $this->getByAssetTag($data['asset_tag']);
            if ($existing && $existing['id'] != $id) {
                throw new Exception("Asset tag already exists");
            }
        }
        
        try {
            $this->db->beginTransaction();
            
            $updated = $this->db->update('equipment', $data, 'id = :id', [':id' => $id]);
            
            if ($updated > 0) {
                // Log audit
                $this->db->logAudit('equipment', $id, 'UPDATE', $originalData, $data, $userId);
            }
            
            $this->db->commit();
            
            return $updated;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Soft delete equipment
     */
    public function delete($id, $userId = null) {
        $originalData = $this->getById($id);
        if (!$originalData) {
            throw new Exception("Equipment not found");
        }
        
        $data = ['is_active' => 0];
        
        try {
            $this->db->beginTransaction();
            
            $updated = $this->db->update('equipment', $data, 'id = :id', [':id' => $id]);
            
            if ($updated > 0) {
                $this->db->logAudit('equipment', $id, 'DELETE', $originalData, $data, $userId);
            }
            
            $this->db->commit();
            
            return $updated;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Move equipment to different room
     */
    public function moveToRoom($equipmentId, $newRoomId, $reason = '', $userId = null) {
        $equipment = $this->getById($equipmentId);
        if (!$equipment) {
            throw new Exception("Equipment not found");
        }
        
        $oldRoomId = $equipment['room_id'];
        
        try {
            $this->db->beginTransaction();
            
            // Update equipment location
            $this->db->update('equipment', ['room_id' => $newRoomId], 'id = :id', [':id' => $equipmentId]);
            
            // Log movement
            $movementData = [
                'equipment_id' => $equipmentId,
                'from_room_id' => $oldRoomId,
                'to_room_id' => $newRoomId,
                'movement_type' => 'room_transfer',
                'reason' => $reason,
                'moved_by' => $userId
            ];
            
            $this->db->insert('equipment_movements', $movementData);
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Assign equipment to user
     */
    public function assignToUser($equipmentId, $userId, $reason = '', $assignedBy = null) {
        $equipment = $this->getById($equipmentId);
        if (!$equipment) {
            throw new Exception("Equipment not found");
        }
        
        if ($equipment['status'] !== 'available') {
            throw new Exception("Equipment is not available for assignment");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Update equipment
            $this->db->update('equipment', [
                'assigned_to' => $userId,
                'status' => 'in_use'
            ], 'id = :id', [':id' => $equipmentId]);
            
            // Log movement
            $movementData = [
                'equipment_id' => $equipmentId,
                'to_user_id' => $userId,
                'movement_type' => 'assignment',
                'reason' => $reason,
                'moved_by' => $assignedBy
            ];
            
            $this->db->insert('equipment_movements', $movementData);
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Return equipment from user
     */
    public function returnFromUser($equipmentId, $reason = '', $returnedBy = null) {
        $equipment = $this->getById($equipmentId);
        if (!$equipment) {
            throw new Exception("Equipment not found");
        }
        
        $fromUserId = $equipment['assigned_to'];
        
        try {
            $this->db->beginTransaction();
            
            // Update equipment
            $this->db->update('equipment', [
                'assigned_to' => null,
                'status' => 'available'
            ], 'id = :id', [':id' => $equipmentId]);
            
            // Log movement
            $movementData = [
                'equipment_id' => $equipmentId,
                'from_user_id' => $fromUserId,
                'movement_type' => 'return',
                'reason' => $reason,
                'moved_by' => $returnedBy
            ];
            
            $this->db->insert('equipment_movements', $movementData);
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get equipment statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total equipment
        $stats['total'] = $this->db->getCount('equipment', 'is_active = 1');
        
        // By status
        $statusQuery = "SELECT status, COUNT(*) as count FROM equipment WHERE is_active = 1 GROUP BY status";
        $statusResults = $this->db->fetchAll($statusQuery);
        $stats['by_status'] = [];
        foreach ($statusResults as $row) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        // By condition
        $conditionQuery = "SELECT condition_status, COUNT(*) as count FROM equipment WHERE is_active = 1 GROUP BY condition_status";
        $conditionResults = $this->db->fetchAll($conditionQuery);
        $stats['by_condition'] = [];
        foreach ($conditionResults as $row) {
            $stats['by_condition'][$row['condition_status']] = $row['count'];
        }
        
        // Total value
        $valueQuery = "SELECT SUM(current_value) as total_value FROM equipment WHERE is_active = 1";
        $valueResult = $this->db->fetchOne($valueQuery);
        $stats['total_value'] = $valueResult['total_value'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get equipment movement history
     */
    public function getMovementHistory($equipmentId) {
        $sql = "SELECT m.*, 
                       fr.name as from_room_name,
                       tr.name as to_room_name,
                       fu.full_name as from_user_name,
                       tu.full_name as to_user_name,
                       mb.full_name as moved_by_name
                FROM equipment_movements m
                LEFT JOIN rooms fr ON m.from_room_id = fr.id
                LEFT JOIN rooms tr ON m.to_room_id = tr.id
                LEFT JOIN users fu ON m.from_user_id = fu.id
                LEFT JOIN users tu ON m.to_user_id = tu.id
                LEFT JOIN users mb ON m.moved_by = mb.id
                WHERE m.equipment_id = :equipment_id
                ORDER BY m.movement_date DESC";
        
        return $this->db->fetchAll($sql, [':equipment_id' => $equipmentId]);
    }
    
    /**
     * Search equipment
     */
    public function search($searchTerm, $filters = []) {
        $searchFields = ['name', 'asset_tag', 'serial_number', 'model', 'description'];
        $additionalConditions = 'is_active = 1';
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $additionalConditions .= ' AND category_id = :category_id';
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['status'])) {
            $additionalConditions .= ' AND status = :status';
            $params[':status'] = $filters['status'];
        }
        
        return $this->db->search('equipment', $searchFields, $searchTerm, $additionalConditions, $params);
    }
    
    /**
     * Generate QR code for equipment
     */
    private function generateQrCode($assetTag) {
        // Simple QR code URL generation (you can integrate with actual QR library)
        $baseUrl = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($baseUrl . "/equipment/" . $assetTag);
    }
    
    /**
     * Check if QR codes are enabled
     */
    private function isQrCodeEnabled() {
        $setting = $this->db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = 'enable_qr_codes'");
        return $setting ? ($setting['setting_value'] === 'true') : false;
    }
    
    /**
     * Get equipment due for maintenance
     */
    public function getDueForMaintenance() {
        return $this->db->fetchAll("SELECT * FROM maintenance_due");
    }
    
    /**
     * Get warranty expiring equipment
     */
    public function getWarrantyExpiring($days = 30) {
        $sql = "SELECT * FROM equipment_overview 
                WHERE warranty_end IS NOT NULL 
                AND warranty_end <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND warranty_end >= CURDATE()
                ORDER BY warranty_end ASC";
        
        return $this->db->fetchAll($sql, [':days' => $days]);
    }
}
?>