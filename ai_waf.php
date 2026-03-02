<?php
/**
 * AI-WAF: Web Application Firewall with Machine Learning Heuristics
 * Implements pattern-based detection and simple Bayesian classification
 */

class AI_WAF {
    private $db;
    private $security;
    private $thresholds = [
        'sql_injection' => 80,
        'xss' => 75,
        'lfi' => 85,
        'rce' => 90,
        'command_injection' => 85,
        'suspicious_patterns' => 60
    ];
    
    // Attack signatures
    private $signatures = [
        'sql_injection' => [
            '/union\s+select/i',
            '/select\s+.*\s+from/i',
            '/insert\s+into/i',
            '/update\s+.*\s+set/i',
            '/delete\s+from/i',
            '/drop\s+table/i',
            '/create\s+table/i',
            '/alter\s+table/i',
            '/exec\s*\(/i',
            '/execute\s*\(/i',
            '/sp_executesql/i',
            '/xp_cmdshell/i',
            '/--/',
            '/\/\*.*\*\//',
            '/\'.*or.*\'.*=.*\'/i',
            '/".*or.*".*=."/i',
            '/1\s*=\s*1/i',
            '/true\s*=\s*true/i'
        ],
        'xss' => [
            '/<script/i',
            '/<\/script>/i',
            '/<iframe/i',
            '/<\/iframe>/i',
            '/<object/i',
            '/<embed/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/alert\s*\(/i',
            '/confirm\s*\(/i',
            '/prompt\s*\(/i',
            '/document\./i',
            '/window\./i',
            '/eval\s*\(/i',
            '/<.*>.*<.*>/i'
        ],
        'lfi' => [
            '/\.\.\/\.\.\//i',
            '/\.\.\\\\\.\.\\\\/i',
            '/etc\/passwd/i',
            '/etc\/shadow/i',
            '/proc\/self/i',
            '/windows\/system32/i',
            '/boot\.ini/i',
            '/\/etc\/hosts/i',
            '/\/proc\/version/i',
            '/\/proc\/cpuinfo/i'
        ],
        'rce' => [
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/passthru\s*\(/i',
            '/shell_exec\s*\(/i',
            '/backticks/i',
            '/\$\(/i',
            '/\`.*\`/i',
            '/curl\s+.*\|/i',
            '/wget\s+.*\|/i',
            '/nc\s+.*-l/i',
            '/netcat\s+.*-l/i'
        ],
        'command_injection' => [
            '/;\s*cat\s+/i',
            '/;\s*ls\s+/i',
            '/;\s*dir\s+/i',
            '/;\s*whoami/i',
            '/;\s*id/i',
            '/;\s*pwd/i',
            '/&&\s*cat\s+/i',
            '/&&\s*ls\s+/i',
            '/&&\s*dir\s+/i',
            '/\|\s*cat\s+/i',
            '/\|\s*ls\s+/i',
            '/\|\s*dir\s+/i'
        ]
    ];
    
    public function __construct($db, $security) {
        $this->db = $db;
        $this->security = $security;
    }
    
    /**
     * Analyze request for malicious patterns
     */
    public function analyzeRequest($request) {
        $score = 0;
        $threats = [];
        
        // Analyze different parts of the request
        $targets = [
            'uri' => $request['uri'] ?? '',
            'get' => http_build_query($request['get'] ?? []),
            'post' => http_build_query($request['post'] ?? []),
            'headers' => json_encode($request['headers'] ?? []),
            'cookies' => http_build_query($request['cookies'] ?? [])
        ];
        
        foreach ($targets as $target => $data) {
            if (empty($data)) continue;
            
            $analysis = $this->analyzeData($data, $target);
            $score += $analysis['score'];
            $threats = array_merge($threats, $analysis['threats']);
        }
        
        // Apply Bayesian classification
        $bayesianScore = $this->bayesianClassify($request);
        $score = ($score + $bayesianScore) / 2;
        
        // Log if suspicious
        if ($score > 30) {
            $this->logThreat($score, $threats, $request);
        }
        
        return [
            'score' => $score,
            'threats' => $threats,
            'blocked' => $score > 70,
            'bayesian_score' => $bayesianScore
        ];
    }
    
    /**
     * Analyze data for attack signatures
     */
    private function analyzeData($data, $target) {
        $score = 0;
        $threats = [];
        
        foreach ($this->signatures as $type => $patterns) {
            $typeScore = 0;
            $matches = [];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $data)) {
                    $matches[] = $pattern;
                    $typeScore += 20;
                }
            }
            
            if ($typeScore > 0) {
                $score += $typeScore;
                $threats[] = [
                    'type' => $type,
                    'target' => $target,
                    'score' => $typeScore,
                    'matches' => $matches
                ];
            }
        }
        
        // Check for encoding anomalies
        $encodingScore = $this->checkEncodingAnomalies($data);
        if ($encodingScore > 0) {
            $score += $encodingScore;
            $threats[] = [
                'type' => 'encoding_anomaly',
                'target' => $target,
                'score' => $encodingScore
            ];
        }
        
        return ['score' => $score, 'threats' => $threats];
    }
    
    /**
     * Simple Bayesian classification
     */
    private function bayesianClassify($request) {
        // Extract features
        $features = $this->extractFeatures($request);
        
        // Get training data (simplified - in production, use proper ML model)
        $trainingData = $this->getTrainingData();
        
        // Calculate probabilities
        $probMalicious = $this->calculateProbability($features, $trainingData['malicious']);
        $probBenign = $this->calculateProbability($features, $trainingData['benign']);
        
        // Apply Bayes' theorem
        $totalProb = $probMalicious + $probBenign;
        if ($totalProb > 0) {
            return ($probMalicious / $totalProb) * 100;
        }
        
        return 0;
    }
    
    /**
     * Extract features from request
     */
    private function extractFeatures($request) {
        $features = [];
        
        // Length features
        $features['uri_length'] = strlen($request['uri'] ?? '');
        $features['param_count'] = count($request['get'] ?? []) + count($request['post'] ?? []);
        
        // Character distribution
        $allData = ($request['uri'] ?? '') . 
                   http_build_query($request['get'] ?? []) . 
                   http_build_query($request['post'] ?? []);
        
        $features['special_chars'] = preg_match_all('/[<>"\'&;|`$(){}[\]]/', $allData);
        $features['sql_chars'] = preg_match_all('/[\'";,\\/*=<>]/', $allData);
        $features['xss_chars'] = preg_match_all('/[<>"\'&]/', $allData);
        
        // Pattern features
        $features['has_script'] = preg_match('/<script/i', $allData) ? 1 : 0;
        $features['has_union'] = preg_match('/union\s+select/i', $allData) ? 1 : 0;
        $features['has_eval'] = preg_match('/eval\s*\(/i', $allData) ? 1 : 0;
        
        return $features;
    }
    
    /**
     * Get training data (simplified)
     */
    private function getTrainingData() {
        // In production, load from database or ML model
        return [
            'malicious' => [
                'uri_length' => [50, 120, 200],
                'param_count' => [3, 8, 15],
                'special_chars' => [5, 15, 30],
                'sql_chars' => [3, 10, 20],
                'xss_chars' => [2, 8, 15],
                'has_script' => [0, 0, 1],
                'has_union' => [0, 0, 1],
                'has_eval' => [0, 0, 1]
            ],
            'benign' => [
                'uri_length' => [20, 40, 80],
                'param_count' => [1, 3, 6],
                'special_chars' => [0, 2, 5],
                'sql_chars' => [0, 1, 3],
                'xss_chars' => [0, 1, 2],
                'has_script' => [0, 0, 0],
                'has_union' => [0, 0, 0],
                'has_eval' => [0, 0, 0]
            ]
        ];
    }
    
    /**
     * Calculate probability based on features
     */
    private function calculateProbability($features, $trainingData) {
        $probability = 1.0;
        
        foreach ($features as $feature => $value) {
            if (!isset($trainingData[$feature])) continue;
            
            $data = $trainingData[$feature];
            $mean = array_sum($data) / count($data);
            
            // Simple Gaussian probability
            $stdDev = sqrt(array_sum(array_map(function($x) use ($mean) {
                return pow($x - $mean, 2);
            }, $data)) / count($data));
            
            if ($stdDev > 0) {
                $prob = exp(-0.5 * pow(($value - $mean) / $stdDev, 2)) / ($stdDev * sqrt(2 * M_PI));
                $probability *= $prob;
            }
        }
        
        return $probability;
    }
    
    /**
     * Check for encoding anomalies
     */
    private function checkEncodingAnomalies($data) {
        $score = 0;
        
        // Check for multiple encoding layers
        if (preg_match('/%25[0-9A-Fa-f]{2}/', $data)) {
            $score += 15; // Double URL encoding
        }
        
        // Check for unusual Unicode
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $data)) {
            $score += 10; // Control characters
        }
        
        // Check for mixed encoding
        if (preg_match('/%[0-9A-Fa-f]{2}.*&#\d+/', $data)) {
            $score += 10; // Mixed URL and HTML encoding
        }
        
        return $score;
    }
    
    /**
     * Log threat for analysis
     */
    private function logThreat($score, $threats, $request) {
        $logData = [
            'score' => $score,
            'threats' => $threats,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'uri' => $request['uri'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        try {
            $this->security->secureQuery(
                "INSERT INTO waf_logs (threat_score, threat_data, ip_address, user_agent, uri, method, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $score,
                    json_encode($threats),
                    $logData['ip'],
                    $logData['user_agent'],
                    $logData['uri'],
                    $logData['method'],
                    $logData['timestamp']
                ]
            );
        } catch (Exception $e) {
            error_log('WAF logging failed: ' . $e->getMessage());
        }
        
        $this->security->logSecurityEvent('WAF_THREAT_DETECTED', $logData);
    }
    
    /**
     * Block request if score exceeds threshold
     */
    public function shouldBlock($analysis) {
        return $analysis['score'] > 70 || $analysis['blocked'];
    }
    
    /**
     * Get WAF statistics
     */
    public function getStats($timeRange = 24) {
        try {
            $stmt = $this->security->secureQuery(
                "SELECT 
                    COUNT(*) as total_requests,
                    AVG(threat_score) as avg_score,
                    MAX(threat_score) as max_score,
                    COUNT(CASE WHEN threat_score > 70 THEN 1 END) as blocked_requests
                FROM waf_logs 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)",
                [$timeRange]
            );
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Update threat model with feedback
     */
    public function updateModel($request, $isMalicious) {
        $features = $this->extractFeatures($request);
        
        try {
            $this->security->secureQuery(
                "INSERT INTO waf_training (request_data, features, is_malicious, created_at) VALUES (?, ?, ?, ?)",
                [
                    json_encode($request),
                    json_encode($features),
                    $isMalicious ? 1 : 0,
                    date('Y-m-d H:i:s')
                ]
            );
        } catch (Exception $e) {
            error_log('WAF training update failed: ' . $e->getMessage());
        }
    }
}

// Create WAF tables
$createWAFLogsTable = "
CREATE TABLE IF NOT EXISTS waf_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    threat_score DECIMAL(5,2) NOT NULL,
    threat_data JSON NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    uri VARCHAR(2048) NOT NULL,
    method VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_threat_score (threat_score),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";

$createWAFTrainingTable = "
CREATE TABLE IF NOT EXISTS waf_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_data JSON NOT NULL,
    features JSON NOT NULL,
    is_malicious TINYINT(1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_malicious (is_malicious),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";
?>
