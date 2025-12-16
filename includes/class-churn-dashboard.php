<?php
/**
 * Classe para gerenciar o dashboard de churn
 * 
 * @since 2.0.0
 * @package Limiter_MKP_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Churn_Dashboard {
    
    /**
     * Inicializa o dashboard
     */
    public static function init() {
        // Adiciona ações AJAX específicas para o dashboard
        self::add_ajax_actions();
        
        // Adiciona filtros para estatísticas
        add_filter('limiter_churn_stats', [__CLASS__, 'enhance_churn_stats']);
    }
    
    /**
     * Renderiza o dashboard de churn
     */
    public static function render_dashboard() {
        // Carrega as estatísticas
        $stats = self::get_churn_statistics();
        $at_risk_blogs = self::get_at_risk_blogs();
        $recent_cancellations = self::get_recent_cancellations();
        $upcoming_renewals = self::get_upcoming_renewals();
        
        // Carrega o template
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/partials/churn-dashboard.php';
    }
    
    /**
     * Obtém estatísticas de churn
     */
    public static function get_churn_statistics() {
        global $wpdb;
        
        // Estatísticas básicas
        $stats = [
            'total_blogs' => 0,
            'active_blogs' => 0,
            'at_risk_blogs' => 0,
            'suspended_blogs' => 0,
            'locked_blogs' => 0,
            'cancelled_this_month' => 0,
            'renewed_this_month' => 0,
            'churn_rate' => 0,
            'mrr' => 0,
            'arr' => 0
        ];
        
        // Total de blogs
        $stats['total_blogs'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->blogs} WHERE deleted = 0");
        
        // Blogs por status
        $status_counts = $wpdb->get_results("
            SELECT meta_value as status, COUNT(*) as count
            FROM {$wpdb->blogmeta}
            WHERE meta_key = 'limiter_blog_status'
            GROUP BY meta_value
        ");
        
        foreach ($status_counts as $row) {
            switch ($row->status) {
                case 'active':
                    $stats['active_blogs'] = $row->count;
                    break;
                case 'grace_period':
                    $stats['at_risk_blogs'] = $row->count;
                    break;
                case 'suspended':
                    $stats['suspended_blogs'] = $row->count;
                    break;
                case 'locked':
                    $stats['locked_blogs'] = $row->count;
                    break;
            }
        }
        
        // Cancelamentos deste mês
        $stats['cancelled_this_month'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT blog_id)
            FROM {$wpdb->blogmeta}
            WHERE meta_key = 'limiter_subscription_status'
            AND meta_value = 'cancelled'
            AND DATE(meta_date) >= %s
        ", date('Y-m-01')));
        
        // Renovações deste mês
        $stats['renewed_this_month'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT blog_id)
            FROM {$wpdb->blogmeta}
            WHERE meta_key = 'limiter_last_renewal_date'
            AND DATE(meta_value) >= %s
        ", date('Y-m-01')));
        
        // Calcula taxa de churn
        if ($stats['active_blogs'] > 0) {
            $stats['churn_rate'] = round(($stats['cancelled_this_month'] / $stats['active_blogs']) * 100, 2);
        }
        
        // Calcula MRR (Monthly Recurring Revenue)
        $stats['mrr'] = self::calculate_mrr();
        
        // Calcula ARR (Annual Recurring Revenue)
        $stats['arr'] = $stats['mrr'] * 12;
        
        return apply_filters('limiter_churn_stats', $stats);
    }
    
    /**
     * Obtém blogs em risco
     */
    public static function get_at_risk_blogs($limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                b.blog_id,
                b.domain,
                b.path,
                bm1.meta_value as customer_name,
                bm2.meta_value as customer_email,
                bm3.meta_value as subscription_status,
                bm4.meta_value as days_in_status,
                bm5.meta_value as last_payment_date,
                bm6.meta_value as plan_name,
                bm7.meta_value as monthly_amount
            FROM {$wpdb->blogs} b
            LEFT JOIN {$wpdb->blogmeta} bm1 ON b.blog_id = bm1.blog_id AND bm1.meta_key = 'limiter_nome_cliente'
            LEFT JOIN {$wpdb->blogmeta} bm2 ON b.blog_id = bm2.blog_id AND bm2.meta_key = 'limiter_email_cliente'
            LEFT JOIN {$wpdb->blogmeta} bm3 ON b.blog_id = bm3.blog_id AND bm3.meta_key = 'limiter_subscription_status'
            LEFT JOIN {$wpdb->blogmeta} bm4 ON b.blog_id = bm4.blog_id AND bm4.meta_key = 'limiter_days_in_status'
            LEFT JOIN {$wpdb->blogmeta} bm5 ON b.blog_id = bm5.blog_id AND bm5.meta_key = 'limiter_last_payment_date'
            LEFT JOIN {$wpdb->blogmeta} bm6 ON b.blog_id = bm6.blog_id AND bm6.meta_key = 'limiter_plan_name'
            LEFT JOIN {$wpdb->blogmeta} bm7 ON b.blog_id = bm7.blog_id AND bm7.meta_key = 'limiter_monthly_amount'
            WHERE b.deleted = 0
            AND bm3.meta_value IN ('on-hold', 'failed', 'past-due')
            ORDER BY bm4.meta_value DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Obtém cancelamentos recentes
     */
    public static function get_recent_cancellations($limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                b.blog_id,
                b.domain,
                b.path,
                bm1.meta_value as customer_name,
                bm2.meta_value as customer_email,
                bm3.meta_value as cancellation_date,
                bm4.meta_value as cancellation_reason,
                bm5.meta_value as plan_name,
                bm6.meta_value as monthly_amount_lost
            FROM {$wpdb->blogs} b
            LEFT JOIN {$wpdb->blogmeta} bm1 ON b.blog_id = bm1.blog_id AND bm1.meta_key = 'limiter_nome_cliente'
            LEFT JOIN {$wpdb->blogmeta} bm2 ON b.blog_id = bm2.blog_id AND bm2.meta_key = 'limiter_email_cliente'
            LEFT JOIN {$wpdb->blogmeta} bm3 ON b.blog_id = bm3.blog_id AND bm3.meta_key = 'limiter_cancellation_date'
            LEFT JOIN {$wpdb->blogmeta} bm4 ON b.blog_id = bm4.blog_id AND bm4.meta_key = 'limiter_cancellation_reason'
            LEFT JOIN {$wpdb->blogmeta} bm5 ON b.blog_id = bm5.blog_id AND bm5.meta_key = 'limiter_plan_name'
            LEFT JOIN {$wpdb->blogmeta} bm6 ON b.blog_id = bm6.blog_id AND bm6.meta_key = 'limiter_monthly_amount'
            WHERE b.deleted = 0
            AND bm3.meta_value IS NOT NULL
            ORDER BY bm3.meta_value DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Obtém renovações próximas
     */
    public static function get_upcoming_renewals($limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                b.blog_id,
                b.domain,
                b.path,
                bm1.meta_value as customer_name,
                bm2.meta_value as customer_email,
                bm3.meta_value as next_payment_date,
                bm4.meta_value as plan_name,
                bm5.meta_value as monthly_amount,
                DATEDIFF(bm3.meta_value, CURDATE()) as days_until_renewal
            FROM {$wpdb->blogs} b
            LEFT JOIN {$wpdb->blogmeta} bm1 ON b.blog_id = bm1.blog_id AND bm1.meta_key = 'limiter_nome_cliente'
            LEFT JOIN {$wpdb->blogmeta} bm2 ON b.blog_id = bm2.blog_id AND bm2.meta_key = 'limiter_email_cliente'
            LEFT JOIN {$wpdb->blogmeta} bm3 ON b.blog_id = bm3.blog_id AND bm3.meta_key = 'limiter_next_payment_date'
            LEFT JOIN {$wpdb->blogmeta} bm4 ON b.blog_id = bm4.blog_id AND bm4.meta_key = 'limiter_plan_name'
            LEFT JOIN {$wpdb->blogmeta} bm5 ON b.blog_id = bm5.blog_id AND bm5.meta_key = 'limiter_monthly_amount'
            WHERE b.deleted = 0
            AND bm3.meta_value IS NOT NULL
            AND bm3.meta_value >= CURDATE()
            ORDER BY bm3.meta_value ASC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Calcula o MRR (Monthly Recurring Revenue)
     */
    private static function calculate_mrr() {
        if (!class_exists('WC_Subscriptions')) {
            return 0;
        }
        
        $subscriptions = wcs_get_subscriptions([
            'subscription_status' => 'active',
            'limit' => -1
        ]);
        
        $mrr = 0;
        
        foreach ($subscriptions as $subscription) {
            // Verifica se esta assinatura está vinculada a um blog do plugin
            $blog_id = self::get_blog_id_from_subscription($subscription);
            
            if ($blog_id) {
                $mrr += $subscription->get_total();
            }
        }
        
        return round($mrr, 2);
    }
    
    /**
     * Adiciona ações AJAX
     */
    private static function add_ajax_actions() {
        // Exportar relatório de churn
        add_action('wp_ajax_limiter_export_churn_report', [__CLASS__, 'ajax_export_churn_report']);
        
        // Enviar lembretes em massa
        add_action('wp_ajax_limiter_send_bulk_reminders', [__CLASS__, 'ajax_send_bulk_reminders']);
        
        // Obter estatísticas em tempo real
        add_action('wp_ajax_limiter_get_realtime_stats', [__CLASS__, 'ajax_get_realtime_stats']);
    }
    
    /**
     * AJAX: Exporta relatório de churn
     */
    public static function ajax_export_churn_report() {
        check_ajax_referer('limiter_churn_nonce', 'nonce');
        
        if (!current_user_can('manage_network')) {
            wp_die('Permissão negada.');
        }
        
        $type = $_GET['type'] ?? 'csv';
        $date = date('Y-m-d');
        
        switch ($type) {
            case 'csv':
                self::export_csv_report();
                break;
            case 'pdf':
                self::export_pdf_report();
                break;
            case 'excel':
                self::export_excel_report();
                break;
            default:
                self::export_csv_report();
        }
        
        exit;
    }
    
    /**
     * Exporta relatório CSV
     */
    private static function export_csv_report() {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="churn-report-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Cabeçalho
        fputcsv($output, [
            'ID',
            'Blog',
            'Cliente',
            'Email',
            'Status',
            'Último Pagamento',
            'Plano',
            'Valor Mensal',
            'Dias em Risco',
            'Próximo Pagamento'
        ]);
        
        // Dados
        $blogs = self::get_at_risk_blogs(-1); // -1 para todos
        
        foreach ($blogs as $blog) {
            fputcsv($output, [
                $blog->blog_id,
                $blog->domain . $blog->path,
                $blog->customer_name,
                $blog->customer_email,
                $blog->subscription_status,
                $blog->last_payment_date,
                $blog->plan_name,
                $blog->monthly_amount,
                $blog->days_in_status,
                $blog->next_payment_date ?? 'N/A'
            ]);
        }
        
        fclose($output);
    }
    
    /**
     * AJAX: Envia lembretes em massa
     */
    public static function ajax_send_bulk_reminders() {
        check_ajax_referer('limiter_churn_nonce', 'nonce');
        
        if (!current_user_can('manage_network')) {
            wp_send_json_error(['message' => 'Permissão negada.']);
        }
        
        $blog_ids = $_POST['blog_ids'] ?? [];
        $template = $_POST['template'] ?? 'payment_failed_day1';
        $count = 0;
        
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
        
        foreach ($blog_ids as $blog_id) {
            $blog_id = intval($blog_id);
            
            if ($blog_id > 0) {
                // Obtém a assinatura do blog
                require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
                $subscription = Limiter_MKP_Pro_WooCommerce_Integration::get_subscription_for_blog($blog_id);
                
                if ($subscription) {
                    Limiter_MKP_Pro_Lifecycle_Manager::notify($blog_id, $template, $subscription);
                    $count++;
                }
            }
        }
        
        wp_send_json_success([
            'message' => "{$count} lembretes enviados com sucesso.",
            'count' => $count
        ]);
    }
    
    /**
     * AJAX: Obtém estatísticas em tempo real
     */
    public static function ajax_get_realtime_stats() {
        check_ajax_referer('limiter_churn_nonce', 'nonce');
        
        if (!current_user_can('manage_network')) {
            wp_send_json_error(['message' => 'Permissão negada.']);
        }
        
        $stats = self::get_churn_statistics();
        
        wp_send_json_success([
            'stats' => $stats,
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Melhora as estatísticas de churn
     */
    public static function enhance_churn_stats($stats) {
        // Adiciona métricas adicionais
        $stats['estimated_recovery'] = self::calculate_estimated_recovery();
        $stats['lifetime_value'] = self::calculate_average_lifetime_value();
        $stats['retention_rate'] = 100 - $stats['churn_rate'];
        
        return $stats;
    }
    
    /**
     * Calcula estimativa de recuperação
     */
    private static function calculate_estimated_recovery() {
        // Implementação simplificada
        // Em produção, use dados históricos
        return '35%';
    }
    
    /**
     * Calcula valor médio de vida do cliente
     */
    private static function calculate_average_lifetime_value() {
        // Implementação simplificada
        // Em produção, use dados históricos
        return '$1,250';
    }
    
    /**
     * Helper: Obtém ID do blog a partir da assinatura
     */
    private static function get_blog_id_from_subscription($subscription) {
        // Reutiliza a função do WooCommerce Hooks
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-subscription-hooks.php';
        return Limiter_MKP_Pro_WooCommerce_Subscription_Hooks::get_blog_id_from_subscription($subscription);
    }
}