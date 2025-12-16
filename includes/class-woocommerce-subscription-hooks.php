<?php
/**
 * Classe para gerenciar hooks do WooCommerce Subscriptions
 * 
 * @since 2.0.0
 * @package Limiter_MKP_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_WooCommerce_Subscription_Hooks {
    
    /**
     * Inicializa os hooks do WooCommerce Subscriptions
     */
    public static function init() {
        // Verifica se o WooCommerce Subscriptions está ativo
        if (!class_exists('WC_Subscriptions')) {
            return;
        }
        
        // Hooks para mudanças de status
        add_action('woocommerce_subscription_status_updated', 
            [__CLASS__, 'handle_subscription_status_change'], 10, 3);
        
        add_action('woocommerce_subscription_payment_failed', 
            [__CLASS__, 'handle_payment_failed'], 10, 2);
        
        add_action('woocommerce_subscription_payment_complete', 
            [__CLASS__, 'handle_payment_complete'], 10, 2);
        
        add_action('woocommerce_subscription_cancelled', 
            [__CLASS__, 'handle_cancellation'], 10, 2);
        
        add_action('woocommerce_subscription_expired', 
            [__CLASS__, 'handle_expiration'], 10, 2);
        
        add_action('woocommerce_subscription_status_changed', 
            [__CLASS__, 'handle_status_changed'], 10, 3);
        
        // Hook para renovação automática
        add_action('woocommerce_subscription_renewal_payment_complete', 
            [__CLASS__, 'handle_renewal_complete'], 10, 2);
        
        // Hook para quando o usuário troca de plano
        add_action('woocommerce_subscriptions_switched_item', 
            [__CLASS__, 'handle_plan_switch'], 10, 3);
    }
    
    /**
     * Handler para mudança de status da assinatura
     */
    public static function handle_subscription_status_change($subscription, $new_status, $old_status) {
        $blog_id = self::get_blog_id_from_subscription($subscription);
        
        if (!$blog_id) {
            return;
        }
        
        // Atualiza o status no banco de dados do plugin
        self::update_blog_subscription_status($blog_id, $new_status, $subscription->get_id());
        
        // Log da mudança
        self::log_status_change($blog_id, $old_status, $new_status, $subscription->get_id());
        
        // Dispara ação para o lifecycle manager
        do_action('limiter_mkp_pro_subscription_status_updated', $blog_id, $new_status, $old_status, $subscription);
    }
    
    /**
     * Handler para pagamento falhado
     */
    public static function handle_payment_failed($subscription, $last_order) {
        $blog_id = self::get_blog_id_from_subscription($subscription);
        
        if (!$blog_id) {
            return;
        }
        
        // Registra o pagamento falhado
        self::log_payment_failure($blog_id, $subscription->get_id(), $last_order);
        
        // Dispara ação para o lifecycle manager
        do_action('limiter_mkp_pro_payment_failed', $blog_id, $subscription, $last_order);
    }
    
    /**
     * Handler para pagamento completado
     */
    public static function handle_payment_complete($subscription, $last_order) {
        $blog_id = self::get_blog_id_from_subscription($subscription);
        
        if (!$blog_id) {
            return;
        }
        
        // Atualiza a data do último pagamento
        self::update_last_payment_date($blog_id, $last_order);
        
        // Dispara ação para o lifecycle manager
        do_action('limiter_mkp_pro_payment_complete', $blog_id, $subscription, $last_order);
    }
    
    /**
     * Handler para cancelamento
     */
    public static function handle_cancellation($subscription, $last_order) {
        $blog_id = self::get_blog_id_from_subscription($subscription);
        
        if (!$blog_id) {
            return;
        }
        
        // Obtém o motivo do cancelamento
        $cancellation_reason = self::get_cancellation_reason($subscription);
        
        // Atualiza o status para cancelado
        self::update_blog_subscription_status($blog_id, 'cancelled', $subscription->get_id());
        
        // Log do cancelamento
        self::log_cancellation($blog_id, $subscription->get_id(), $cancellation_reason);
        
        // Dispara ação para o lifecycle manager
        do_action('limiter_mkp_pro_subscription_cancelled', $blog_id, $subscription, $cancellation_reason);
    }
    
    /**
     * Handler para expiração
     */
    public static function handle_expiration($subscription, $last_order) {
        $blog_id = self::get_blog_id_from_subscription($subscription);
        
        if (!$blog_id) {
            return;
        }
        
        // Atualiza o status para expirado
        self::update_blog_subscription_status($blog_id, 'expired', $subscription->get_id());
        
        // Log da expiração
        self::log_expiration($blog_id, $subscription->get_id());
        
        // Dispara ação para o lifecycle manager
        do_action('limiter_mkp_pro_subscription_expired', $blog_id, $subscription);
    }
    
    /**
     * Handler para mudança de status geral
     */
    public static function handle_status_changed($subscription, $new_status, $old_status) {
        // Este é um hook genérico que captura todas as mudanças
        $blog_id = self::get_blog_id_from_subscription($subscription);
        
        if (!$blog_id) {
            return;
        }
        
        // Log da mudança
        self::log_status_change($blog_id, $old_status, $new_status, $subscription->get_id());
        
        // Para status específicos que precisam de ação imediata
        if ($new_status === 'on-hold') {
            do_action('limiter_mkp_pro_subscription_on_hold', $blog_id, $subscription);
        }
    }
    
    /**
     * Handler para renovação completada
     */
    public static function handle_renewal_complete($subscription, $last_order) {
        $blog_id = self::get_blog_id_from_subscription($subscription);
        
        if (!$blog_id) {
            return;
        }
        
        // Atualiza a data de renovação
        self::update_renewal_date($blog_id, $subscription->get_id());
        
        // Log da renovação
        self::log_renewal($blog_id, $subscription->get_id(), $last_order);
        
        // Dispara ação para o lifecycle manager
        do_action('limiter_mkp_pro_subscription_renewed', $blog_id, $subscription, $last_order);
    }
    
    /**
     * Handler para troca de plano
     */
    public static function handle_plan_switch($subscription, $new_order_item, $old_order_item) {
        $blog_id = self::get_blog_id_from_subscription($subscription);
        
        if (!$blog_id) {
            return;
        }
        
        // Obtém os IDs dos produtos antigo e novo
        $old_product_id = $old_order_item->get_product_id();
        $new_product_id = $new_order_item->get_product_id();
        
        // Encontra os planos correspondentes
        $old_plano_id = self::get_plano_id_from_product($old_product_id);
        $new_plano_id = self::get_plano_id_from_product($new_product_id);
        
        if ($old_plano_id && $new_plano_id) {
            // Log da troca de plano
            self::log_plan_switch($blog_id, $old_plano_id, $new_plano_id, $subscription->get_id());
            
            // Atualiza o plano do blog
            self::update_blog_plan($blog_id, $new_plano_id);
            
            // Dispara ação para o lifecycle manager
            do_action('limiter_mkp_pro_plan_switched', $blog_id, $old_plano_id, $new_plano_id, $subscription);
        }
    }
    
    /**
     * Obtém o ID do blog a partir de uma assinatura
     */
    private static function get_blog_id_from_subscription($subscription) {
        // Tenta obter pelo email do cliente
        $customer_email = $subscription->get_billing_email();
        
        if (!$customer_email) {
            return false;
        }
        
        global $wpdb;
        $table_subdominios = $wpdb->base_prefix . 'limiter_mkp_pro_subdominios';
        
        $blog_id = $wpdb->get_var($wpdb->prepare(
            "SELECT blog_id FROM {$table_subdominios} WHERE email_cliente = %s LIMIT 1",
            $customer_email
        ));
        
        // Se não encontrar pelo email, tenta pelo user_id
        if (!$blog_id) {
            $user_id = $subscription->get_user_id();
            $blog_id = $wpdb->get_var($wpdb->prepare(
                "SELECT blog_id FROM {$table_subdominios} WHERE user_id = %d LIMIT 1",
                $user_id
            ));
        }
        
        return $blog_id ? intval($blog_id) : false;
    }
    
    /**
     * Atualiza o status da assinatura no blog
     */
    private static function update_blog_subscription_status($blog_id, $status, $subscription_id) {
        update_blog_option($blog_id, 'limiter_subscription_status', $status);
        update_blog_option($blog_id, 'limiter_subscription_id', $subscription_id);
        update_blog_option($blog_id, 'limiter_last_status_update', current_time('mysql'));
        
        // Se for ativo, limpa datas de suspensão
        if ($status === 'active') {
            delete_blog_option($blog_id, 'limiter_blog_suspended_date');
            delete_blog_option($blog_id, 'limiter_blog_locked_date');
        }
    }
    
    /**
     * Obtém o motivo do cancelamento
     */
    private static function get_cancellation_reason($subscription) {
        // Tenta obter do metadado do WooCommerce
        $reason = $subscription->get_meta('_cancellation_reason');
        
        if (!$reason) {
            $reason = $subscription->get_meta('cancellation_reason');
        }
        
        return $reason ?: 'Razão não especificada';
    }
    
    /**
     * Atualiza a data do último pagamento
     */
    private static function update_last_payment_date($blog_id, $order) {
        $payment_date = $order->get_date_paid();
        
        if ($payment_date) {
            update_blog_option($blog_id, 'limiter_last_payment_date', $payment_date->date('Y-m-d H:i:s'));
        }
        
        // Também atualiza a próxima data de pagamento se disponível
        $next_payment = $order->get_meta('_next_payment_date');
        if ($next_payment) {
            update_blog_option($blog_id, 'limiter_next_payment_date', $next_payment);
        }
    }
    
    /**
     * Atualiza a data de renovação
     */
    private static function update_renewal_date($blog_id, $subscription_id) {
        update_blog_option($blog_id, 'limiter_last_renewal_date', current_time('mysql'));
    }
    
    /**
     * Obtém o ID do plano a partir do ID do produto
     */
    private static function get_plano_id_from_product($product_id) {
        global $wpdb;
        $table_plano_products = $wpdb->base_prefix . 'limiter_mkp_pro_plano_products';
        
        $plano_id = $wpdb->get_var($wpdb->prepare(
            "SELECT plano_id FROM {$table_plano_products} WHERE woocommerce_product_id = %d LIMIT 1",
            $product_id
        ));
        
        return $plano_id ? intval($plano_id) : false;
    }
    
    /**
     * Atualiza o plano do blog
     */
    private static function update_blog_plan($blog_id, $plano_id) {
        global $wpdb;
        $table_subdominios = $wpdb->base_prefix . 'limiter_mkp_pro_subdominios';
        
        $wpdb->update(
            $table_subdominios,
            ['plano_id' => $plano_id],
            ['blog_id' => $blog_id],
            ['%d'],
            ['%d']
        );
    }
    
    /**
     * Métodos de logging
     */
    private static function log_status_change($blog_id, $old_status, $new_status, $subscription_id) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'subscription_status_change',
            "Status da assinatura alterado de '{$old_status}' para '{$new_status}'",
            [
                'subscription_id' => $subscription_id,
                'old_status' => $old_status,
                'new_status' => $new_status
            ]
        );
    }
    
    private static function log_payment_failure($blog_id, $subscription_id, $order) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'payment_failed',
            'Pagamento da assinatura falhou',
            [
                'subscription_id' => $subscription_id,
                'order_id' => $order->get_id(),
                'order_total' => $order->get_total()
            ]
        );
    }
    
    private static function log_cancellation($blog_id, $subscription_id, $reason) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'subscription_cancelled',
            "Assinatura cancelada. Motivo: {$reason}",
            [
                'subscription_id' => $subscription_id,
                'cancellation_reason' => $reason
            ]
        );
    }
    
    private static function log_expiration($blog_id, $subscription_id) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'subscription_expired',
            'Assinatura expirou',
            ['subscription_id' => $subscription_id]
        );
    }
    
    private static function log_renewal($blog_id, $subscription_id, $order) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'subscription_renewed',
            'Assinatura renovada com sucesso',
            [
                'subscription_id' => $subscription_id,
                'order_id' => $order->get_id(),
                'renewal_amount' => $order->get_total()
            ]
        );
    }
    
    private static function log_plan_switch($blog_id, $old_plano_id, $new_plano_id, $subscription_id) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        
        // Obtém os nomes dos planos
        global $wpdb;
        $table_planos = $wpdb->base_prefix . 'limiter_mkp_pro_planos';
        
        $old_plano = $wpdb->get_var($wpdb->prepare(
            "SELECT nome FROM {$table_planos} WHERE id = %d",
            $old_plano_id
        ));
        
        $new_plano = $wpdb->get_var($wpdb->prepare(
            "SELECT nome FROM {$table_planos} WHERE id = %d",
            $new_plano_id
        ));
        
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'plan_switched',
            "Plano alterado de '{$old_plano}' para '{$new_plano}'",
            [
                'subscription_id' => $subscription_id,
                'old_plano_id' => $old_plano_id,
                'new_plano_id' => $new_plano_id,
                'old_plano_name' => $old_plano,
                'new_plano_name' => $new_plano
            ]
        );
    }
}