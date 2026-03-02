<?php
/**
 * Public Services API
 * Allows customers to view available services without admin restrictions
 */

require_once '../config.php';
require_once '../security_middleware.php';

$method = $_SERVER['REQUEST_METHOD'];

// Only allow GET requests for public access
if ($method === 'GET') {
    try {
        // Get all active services
        $stmt = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_type, service_name");
        $services = $stmt->fetchAll();
        
        // Group services by type for better organization
        $servicesByType = [];
        foreach ($services as $service) {
            $servicesByType[$service['service_type']][] = $service;
        }
        
        json_response(true, 'Services retrieved successfully', [
            'services' => $services,
            'services_by_type' => $servicesByType,
            'total_count' => count($services)
        ]);
        
    } catch (PDOException $e) {
        json_response(false, 'Database error: ' . $e->getMessage());
    }
} else {
    json_response(false, 'Method not allowed', null, 405);
}
?>
