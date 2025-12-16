<?php
/**
 * Classe responsável por auditoria de segurança do plugin.
 *
 * @since      1.1.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */

class Limiter_MKP_Pro_Security_Audit {
    
    /**
     * Executa uma verificação de segurança.
     *
     * @since    1.1.0
     * @return   array    Array com os resultados da auditoria.
     */
    public static function run_security_scan() {
        $issues = array();
        
        // Verificar permissões de arquivos
        $issues[] = self::check_file_permissions();
        
        // Verificar se tabelas existem
        $issues[] = self::check_database_tables();
        
        // Verificar usuários suspeitos
        $issues[] = self::check_suspicious_activity();
        
        return $issues;
    }
    
    /**
     * Verifica as permissões de arquivos do plugin.
     *
     * @since    1.1.0
     * @return   array    Resultado da verificação.
     */
    public static function check_file_permissions() {
        $plugin_dir = LIMITER_MKP_PRO_PLUGIN_DIR;
        $required_permissions = 0644;
        
        // Verificar permissões de arquivos .php
        $php_files = glob($plugin_dir . '*.php');
        $issues = array();
        
        foreach ($php_files as $file) {
            $permissions = substr(sprintf('%o', fileperms($file)), -4);
            if ($permissions != '0644') {
                $issues[] = array(
                    'file' => basename($file),
                    'permission' => $permissions,
                    'expected' => '0644'
                );
            }
        }
        
        return array(
            'check' => 'file_permissions',
            'status' => empty($issues) ? 'ok' : 'warning',
            'issues' => $issues
        );
    }
    
    /**
     * Verifica se todas as tabelas do plugin existem.
     *
     * @since    1.1.0
     * @return   array    Resultado da verificação.
     */
    public static function check_database_tables() {
        global $wpdb;
        
        $tables = array(
            'planos',
            'subdominios',
            'solicitacoes',
            'logs',
            'plano_products'
        );
        
        $missing_tables = array();
        
        foreach ($tables as $table) {
            $table_name = Limiter_MKP_Pro_Database::get_table_name($table);
            $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            if ($result != $table_name) {
                $missing_tables[] = $table_name;
            }
        }
        
        return array(
            'check' => 'database_tables',
            'status' => empty($missing_tables) ? 'ok' : 'error',
            'issues' => $missing_tables
        );
    }
    
    /**
     * Verifica atividade suspeita.
     *
     * @since    1.1.0
     * @return   array    Resultado da verificação.
     */
    public static function check_suspicious_activity() {
        global $wpdb;
        
        $table_logs = Limiter_MKP_Pro_Database::get_table_name('logs');
        
        // Verificar múltiplas tentativas de criação em curto período
        $recent_attempts = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_logs 
             WHERE acao LIKE '%limite_excedido%' 
             AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        
        $issues = array();
        
        if ($recent_attempts > 10) {
            $issues[] = "Múltiplas tentativas de bypass detectadas: $recent_attempts na última hora";
        }
        
        return array(
            'check' => 'suspicious_activity',
            'status' => empty($issues) ? 'ok' : 'warning',
            'issues' => $issues
        );
    }
}