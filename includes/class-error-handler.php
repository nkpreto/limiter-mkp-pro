<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe para tratamento centralizado de erros
 */
class Limiter_MKP_Pro_Error_Handler {
    /**
     * Registra um erro no log do WordPress
     */
    public static function log_error($message, $data = array(), $level = 'error') {
        $error_message = 'Limiter MKP Pro [' . strtoupper($level) . ']: ' . $message;
        
        if (!empty($data)) {
            // Remove dados sensíveis
            $sanitized_data = self::sanitize_sensitive_data($data);
            $error_message .= ' - Data: ' . wp_json_encode($sanitized_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        // Log no arquivo de erro do WordPress
        error_log($error_message);
        
        // Também registra no log personalizado do plugin
        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();
            $user_id = $user->ID ?? 0;
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::log(
                $user_id, 
                'system_error', 
                $message,
                [
                    'level' => $level,
                    'data' => $data,
                    'timestamp' => current_time('mysql')
                ]
            );
        }
        
        return $error_message;
    }
    
    /**
     * Verifica e loga erros de banco de dados
     */
    public static function check_db_error($result, $operation, $data = array()) {
        global $wpdb;
        
        if ($wpdb->last_error) {
            $error_data = $data ?: [];
            $error_data['db_error'] = $wpdb->last_error;
            $error_data['db_query'] = $wpdb->last_query;
            $error_data['operation'] = $operation;
            $error_data['result'] = $result;
            
            self::log_error("Database error in {$operation}", $error_data, 'critical');
            return false;
        }
        
        if ($result === false) {
            $error_data = $data ?: [];
            $error_data['operation'] = $operation;
            $error_data['result'] = $result;
            
            self::log_error("Operation failed: {$operation}", $error_data, 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Remove dados sensíveis antes de logar
     */
    private static function sanitize_sensitive_data($data) {
        if (!is_array($data)) {
            return $data;
        }
        
        $sensitive_keys = [
            'password', 'senha', 'token', 'access_token', 'secret', 'credit_card', 
            'cc_number', 'card_number', 'cvv', 'cvc', 'user_pass', 'pass'
        ];
        
        $sanitized = [];
        foreach ($data as $key => $value) {
            $key_lower = strtolower($key);
            
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize_sensitive_data($value);
            } else if (in_array($key_lower, $sensitive_keys) || strpos($key_lower, 'password') !== false || strpos($key_lower, 'pass') !== false) {
                $sanitized[$key] = '***REDACTED***';
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Registra exceções
     */
    public static function log_exception($exception, $context = []) {
        $error_data = array_merge($context, [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        self::log_error('Exception caught: ' . $exception->getMessage(), $error_data, 'exception');
    }
    
    /**
     * Registra erros de segurança
     */
    public static function log_security_violation($action, $details = []) {
        $error_data = array_merge($details, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => current_time('mysql')
        ]);
        
        self::log_error("Security violation: {$action}", $error_data, 'security');
        
        // Notifica administradores em caso de violações críticas
        if (in_array($action, ['sql_injection_attempt', 'xss_attempt', 'auth_bypass'])) {
            self::notify_admins_security_violation($action, $error_data);
        }
    }
    
    /**
     * Notifica administradores sobre violações de segurança
     */
    private static function notify_admins_security_violation($action, $data) {
        $admin_email = get_network_option(null, 'admin_email');
        
        $subject = "[ALERTA DE SEGURANÇA] Limiter MKP Pro - {$action}";
        $message = "
        Uma possível violação de segurança foi detectada no plugin Limiter MKP Pro.
        
        Detalhes:
        - Ação: {$action}
        - IP: {$data['ip']}
        - User Agent: {$data['user_agent']}
        - Timestamp: {$data['timestamp']}
        
        Por favor, verifique os logs detalhados e tome as medidas necessárias.
        
        Esta é uma notificação automática do sistema de segurança do Limiter MKP Pro.
        ";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Registra erros de API externa
     */
    public static function log_api_error($api_name, $endpoint, $error, $request_data = []) {
        $error_data = [
            'api_name' => $api_name,
            'endpoint' => $endpoint,
            'error' => $error,
            'request_data' => $request_data,
            'timestamp' => current_time('mysql')
        ];
        
        self::log_error("API error: {$api_name} - {$endpoint}", $error_data, 'api_error');
    }
}