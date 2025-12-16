<?php
/**
 * Classe para verificação de saúde do sistema.
 *
 * @since      1.2.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */
if (!defined('ABSPATH')) {
    exit;
}
class Limiter_MKP_Pro_Health_Check {
    
    /**
     * Executa todas as verificações de saúde.
     *
     * @since    1.2.0
     * @return   array    Resultados das verificações.
     */
    public static function run_checks() {
        return [
            'database_tables' => self::check_database_tables(),
            'woocommerce_integration' => self::check_woocommerce_status(),
            'cron_jobs' => self::check_cron_jobs(),
            'file_permissions' => self::check_file_permissions(),
            'cache_status' => self::check_cache_status()
        ];
    }
    
    /**
     * Verifica se todas as tabelas do banco de dados existem.
     *
     * @since    1.2.0
     * @return   array    Resultado da verificação.
     */
    public static function check_database_tables() {
        global $wpdb;
        
        $tables = [
            'planos',
            'subdominios',
            'solicitacoes',
            'logs',
            'plano_products',
            'tokens'
        ];
        
        $missing_tables = [];
        
        foreach ($tables as $table) {
            $table_name = Limiter_MKP_Pro_Database::get_table_name($table);
            $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            if ($result != $table_name) {
                $missing_tables[] = $table_name;
            }
        }
        
        return [
            'status' => empty($missing_tables) ? 'ok' : 'error',
            'message' => empty($missing_tables) ? 'Todas as tabelas do banco de dados existem.' : 'Tabelas faltando: ' . implode(', ', $missing_tables),
            'details' => $missing_tables
        ];
    }
    
    /**
     * Verifica o status da integração com WooCommerce.
     *
     * @since    1.2.0
     * @return   array    Resultado da verificação.
     */
    public static function check_woocommerce_status() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
        
        $wc_active = Limiter_MKP_Pro_WooCommerce_Integration::is_woocommerce_active();
        $subs_active = Limiter_MKP_Pro_WooCommerce_Integration::is_subscriptions_active();
        
        $status = 'warning';
        $message = 'WooCommerce não está ativo.';
        
        if ($wc_active && $subs_active) {
            $status = 'ok';
            $message = 'WooCommerce e Subscriptions estão ativos e funcionando.';
        } elseif ($wc_active && !$subs_active) {
            $status = 'warning';
            $message = 'WooCommerce está ativo mas Subscriptions não está.';
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'details' => [
                'woocommerce_active' => $wc_active,
                'subscriptions_active' => $subs_active
            ]
        ];
    }
    
    /**
     * Verifica se os cron jobs estão agendados.
     *
     * @since    1.2.0
     * @return   array    Resultado da verificação.
     */
    public static function check_cron_jobs() {
        $crons = _get_cron_array();
        $our_crons = [];
        
        foreach ($crons as $timestamp => $cronhooks) {
            foreach ($cronhooks as $hook => $keys) {
                if (strpos($hook, 'limiter_mkp_pro') !== false) {
                    $our_crons[] = $hook;
                }
            }
        }
        
        $expected_crons = ['limiter_mkp_pro_cron_daily'];
        $missing_crons = array_diff($expected_crons, $our_crons);
        
        return [
            'status' => empty($missing_crons) ? 'ok' : 'warning',
            'message' => empty($missing_crons) ? 'Todos os cron jobs estão agendados.' : 'Cron jobs faltando: ' . implode(', ', $missing_crons),
            'details' => [
                'found' => $our_crons,
                'missing' => $missing_crons
            ]
        ];
    }
    
    /**
     * Verifica permissões de arquivos importantes.
     *
     * @since    1.2.0
     * @return   array    Resultado da verificação.
     */
    public static function check_file_permissions() {
        $plugin_dir = LIMITER_MKP_PRO_PLUGIN_DIR;
        $important_files = [
            $plugin_dir . 'limiter-mkp-pro.php',
            $plugin_dir . 'includes/class-database.php',
            $plugin_dir . 'includes/class-woocommerce-integration.php'
        ];
        
        $incorrect_permissions = [];
        
        foreach ($important_files as $file) {
            if (file_exists($file)) {
                $permissions = substr(sprintf('%o', fileperms($file)), -4);
                if ($permissions !== '0644') {
                    $incorrect_permissions[] = [
                        'file' => basename($file),
                        'current' => $permissions,
                        'expected' => '0644'
                    ];
                }
            }
        }
        
        return [
            'status' => empty($incorrect_permissions) ? 'ok' : 'warning',
            'message' => empty($incorrect_permissions) ? 'Permissões de arquivo estão corretas.' : 'Alguns arquivos têm permissões incorretas.',
            'details' => $incorrect_permissions
        ];
    }
    
    /**
     * Verifica o status do cache.
     *
     * @since    1.2.0
     * @return   array    Resultado da verificação.
     */
    public static function check_cache_status() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-cache-manager.php';
        
        // Testa o cache
        $test_key = 'health_check_test';
        $test_value = 'test_value';
        
        $set_result = Limiter_MKP_Pro_Cache_Manager::set($test_key, $test_value, 60);
        $get_result = Limiter_MKP_Pro_Cache_Manager::get($test_key);
        $delete_result = Limiter_MKP_Pro_Cache_Manager::delete($test_key);
        
        $cache_working = ($set_result && $get_result === $test_value && $delete_result);
        
        return [
            'status' => $cache_working ? 'ok' : 'error',
            'message' => $cache_working ? 'Sistema de cache está funcionando.' : 'Sistema de cache com problemas.',
            'details' => [
                'set_works' => $set_result,
                'get_works' => ($get_result === $test_value),
                'delete_works' => $delete_result
            ]
        ];
    }
    
    /**
     * Gera um relatório completo de saúde.
     *
     * @since    1.2.0
     * @return   string    Relatório em formato de texto.
     */
    public static function generate_report() {
        $checks = self::run_checks();
        $report = "=== RELATÓRIO DE SAÚDE - LIMITER MKP PRO ===\n";
        $report .= "Data: " . current_time('mysql') . "\n\n";
        
        foreach ($checks as $check_name => $result) {
            $status_icon = $result['status'] === 'ok' ? '✅' : ($result['status'] === 'warning' ? '⚠️' : '❌');
            $report .= "{$status_icon} " . strtoupper(str_replace('_', ' ', $check_name)) . "\n";
            $report .= "   Status: {$result['message']}\n";
            
            if (!empty($result['details'])) {
                $report .= "   Detalhes: " . json_encode($result['details']) . "\n";
            }
            $report .= "\n";
        }
        
        return $report;
    }
}