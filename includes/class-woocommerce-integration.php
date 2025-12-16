<?php
/**
 * Classe responsável pela integração com WooCommerce Subscriptions.
 *
 * @since      1.1.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */
class Limiter_MKP_Pro_WooCommerce_Integration {
    /**
     * Verifica se o WooCommerce está ativo.
     *
     * @since    1.1.0
     * @return   boolean    Verdadeiro se WooCommerce está ativo, falso caso contrário.
     */
    public static function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    /**
     * Verifica se o WooCommerce Subscriptions está ativo.
     *
     * @since    1.1.0
     * @return   boolean    Verdadeiro se WooCommerce Subscriptions está ativo, falso caso contrário.
     */
    public static function is_subscriptions_active() {
        return class_exists('WC_Subscriptions');
    }
    /**
     * Obtém a duração em dias de um produto de assinatura.
     *
     * @since    1.2.0
     * @param    int       $product_id    ID do produto.
     * @return   int                      Duração em dias.
     */
    public static function get_product_duration_days($product_id) {
        if (!self::is_subscriptions_active()) {
            return 30; // Fallback padrão
        }
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('subscription')) {
            return 30;
        }
        $interval = $product->get_meta('_subscription_period_interval', true);
        $period = $product->get_meta('_subscription_period', true);
        // Converter para dias
        $days_multiplier = [
            'day' => 1,
            'week' => 7,
            'month' => 30, // Aproximação
            'year' => 365
        ];
        if (isset($days_multiplier[$period])) {
            return $interval * $days_multiplier[$period];
        }
        return 30; // Fallback
    }
    /**
     * Obtém todos os produtos de assinatura.
     *
     * @since    1.1.0
     * @return   array    Array com produtos de assinatura.
     */
    public static function get_subscription_products() {
        if (!self::is_woocommerce_active() || !self::is_subscriptions_active()) {
            return array();
        }
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_subscription_price',
                    'compare' => 'EXISTS'
                )
            )
        );
        $products = get_posts($args);
        $subscription_products = array();
        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if ($wc_product && $wc_product->is_type('subscription')) {
                $subscription_products[] = array(
                    'id' => $product->ID,
                    'name' => $product->post_title,
                    'price' => $wc_product->get_meta('_subscription_price'),
                    'period' => $wc_product->get_meta('_subscription_period'),
                    'interval' => $wc_product->get_meta('_subscription_period_interval'),
                    'duration_days' => self::get_product_duration_days($product->ID)
                );
            }
        }
        return $subscription_products;
    }
    /**
     * Obtém informações de um produto específico.
     *
     * @since    1.1.0
     * @param    int       $product_id    ID do produto.
     * @return   array|null               Array com informações do produto ou null se não encontrado.
     */
    public static function get_product_info($product_id) {
        if (!self::is_woocommerce_active()) {
            return null;
        }
        $product = wc_get_product($product_id);
        if (!$product || $product->get_status() !== 'publish') {
            return null;
        }
        $info = array(
            'id' => $product_id,
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'permalink' => get_permalink($product_id)
        );
        // Informações específicas de assinatura
        if (self::is_subscriptions_active() && $product->is_type('subscription')) {
            $info['subscription_price'] = $product->get_meta('_subscription_price');
            $info['subscription_period'] = $product->get_meta('_subscription_period');
            $info['subscription_interval'] = $product->get_meta('_subscription_period_interval');
            $info['subscription_duration_days'] = self::get_product_duration_days($product_id);
            $info['is_subscription'] = true;
        } else {
            $info['is_subscription'] = false;
        }
        return $info;
    }
    /**
     * Verifica se um produto é uma assinatura válida.
     *
     * @since    1.1.0
     * @param    int       $product_id    ID do produto.
     * @return   boolean                  Verdadeiro se é uma assinatura válida, falso caso contrário.
     */
    public static function is_valid_subscription($product_id) {
        $product_info = self::get_product_info($product_id);
        if (!$product_info) {
            return false;
        }
        return $product_info['is_subscription'] && $product_info['status'] === 'publish';
    }
    /**
     * Gera o link de compra para um produto.
     *
     * @since    1.1.0
     * @param    int       $product_id    ID do produto.
     * @return   string                   URL para adicionar ao carrinho.
     */
    public static function get_purchase_link($product_id) {
        if (!self::is_woocommerce_active()) {
            return '';
        }
        return wc_get_checkout_url() . '?add-to-cart=' . $product_id;
    }
    /**
     * Obtém os produtos WooCommerce vinculados a um plano.
     *
     * @since    1.1.0
     * @param    int       $plano_id    ID do plano.
     * @return   array                  Array com produtos vinculados.
     */
    public static function get_plano_products($plano_id) {
        global $wpdb;
        $table_plano_products = Limiter_MKP_Pro_Database::get_table_name('plano_products');
        $query = $wpdb->prepare(
            "SELECT woocommerce_product_id, ordem 
             FROM $table_plano_products 
             WHERE plano_id = %d 
             ORDER BY ordem ASC, data_vinculo ASC",
            $plano_id
        );
        $product_ids = $wpdb->get_results($query);
        $products = array();
        foreach ($product_ids as $row) {
            $product_info = self::get_product_info($row->woocommerce_product_id);
            if ($product_info) {
                $product_info['ordem'] = $row->ordem;
                $products[] = $product_info;
            }
        }
        return $products;
    }
    /**
     * Vincula produtos WooCommerce a um plano.
     *
     * @since    1.1.0
     * @param    int       $plano_id     ID do plano.
     * @param    array     $product_ids  Array de IDs de produtos.
     * @return   boolean                 Verdadeiro se vinculado com sucesso, falso caso contrário.
     */
    public static function save_plano_products($plano_id, $product_ids) {
        global $wpdb;
        $table_plano_products = Limiter_MKP_Pro_Database::get_table_name('plano_products');
        // Remove vinculações existentes
        $wpdb->delete($table_plano_products, array('plano_id' => $plano_id), array('%d'));
        // Insere as novas vinculações
        $ordem = 0;
        foreach ($product_ids as $product_id) {
            if (!empty($product_id) && $product_id > 0) {
                $wpdb->insert(
                    $table_plano_products,
                    array(
                        'plano_id' => $plano_id,
                        'woocommerce_product_id' => $product_id,
                        'ordem' => $ordem++
                    ),
                    array('%d', '%d', '%d')
                );
            }
        }
        return true;
    }
    /**
     * Remove todos os produtos vinculados a um plano.
     *
     * @since    1.1.0
     * @param    int       $plano_id    ID do plano.
     * @return   boolean                Verdadeiro se removido com sucesso, falso caso contrário.
     */
    public static function delete_plano_products($plano_id) {
        global $wpdb;
        $table_plano_products = Limiter_MKP_Pro_Database::get_table_name('plano_products');
        return $wpdb->delete($table_plano_products, array('plano_id' => $plano_id), array('%d')) !== false;
    }
    /**
     * Verifica se um produto está vinculado a algum plano.
     *
     * @since    1.1.0
     * @param    int       $product_id    ID do produto.
     * @return   boolean                  Verdadeiro se está vinculado, falso caso contrário.
     */
    public static function is_product_linked($product_id) {
        global $wpdb;
        $table_plano_products = Limiter_MKP_Pro_Database::get_table_name('plano_products');
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_plano_products WHERE woocommerce_product_id = %d",
            $product_id
        ));
        return $count > 0;
    }
    /**
     * Obtém o plano vinculado a um produto.
     *
     * @since    1.1.0
     * @param    int       $product_id    ID do produto.
     * @return   int|null                 ID do plano vinculado ou null se não encontrado.
     */
    public static function get_plano_by_product($product_id) {
        global $wpdb;
        $table_plano_products = Limiter_MKP_Pro_Database::get_table_name('plano_products');
        $plano_id = $wpdb->get_var($wpdb->prepare(
            "SELECT plano_id FROM $table_plano_products WHERE woocommerce_product_id = %d LIMIT 1",
            $product_id
        ));
        return $plano_id ? intval($plano_id) : null;
    }

    /**
     * Obtém a assinatura WooCommerce ativa associada a um blog_id (subdomínio).
     * 
     * @since 1.4.0
     * @param int $blog_id ID do blog/subdomínio.
     * @return object|null Objeto da assinatura ou null se não encontrada.
     */
    public static function get_subscription_for_blog($blog_id) {
        if (!self::is_subscriptions_active()) {
            return null;
        }

        global $wpdb;
        $table_subdominios = Limiter_MKP_Pro_Database::get_table_name('subdominios');
        if (!Limiter_MKP_Pro_Database::validate_table_name($table_subdominios)) {
            return null;
        }

        // Obtém o user_id do subdomínio
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$table_subdominios} WHERE blog_id = %d AND status = 'active'",
            $blog_id
        ));

        if (!$user_id) {
            return null;
        }

        // Usa a API do WooCommerce Subscriptions para buscar assinaturas do usuário
        $subscriptions = wcs_get_users_subscriptions($user_id);
        if (empty($subscriptions)) {
            return null;
        }

        foreach ($subscriptions as $sub) {
            if ($sub->has_status('active')) {
                return $sub;
            }
        }

        return null;
    }

    /**
     * Obtém o histórico de status de uma assinatura (timeline).
     * 
     * @since 1.4.0
     * @param int $subscription_id ID da assinatura WooCommerce.
     * @return array Array com entradas de status e timestamps.
     */
    public static function get_subscription_status_timeline($subscription_id) {
        if (!self::is_subscriptions_active()) {
            return array();
        }

        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            return array();
        }

        $timeline = array();
        $status_log = $subscription->get_meta('_subscription_status_log', true);
        
        if (!empty($status_log) && is_array($status_log)) {
            foreach ($status_log as $entry) {
                $timeline[] = array(
                    'status' => $entry['status'] ?? 'unknown',
                    'timestamp' => $entry['timestamp'] ?? '',
                    'note' => $entry['note'] ?? ''
                );
            }
        } else {
            // Fallback: apenas status atual
            $timeline[] = array(
                'status' => $subscription->get_status(),
                'timestamp' => $subscription->get_date_created() ? $subscription->get_date_created()->format('Y-m-d H:i:s') : current_time('mysql'),
                'note' => 'Status atual (sem histórico detalhado)'
            );
        }

        return $timeline;
    }

    /**
     * Dispara um webhook personalizado para integração externa com base em eventos do ciclo de vida.
     * 
     * @since 1.4.0
     * @param string $event Nome do evento (ex: 'subscription_created', 'plan_changed', 'grace_period_started').
     * @param array $data Dados contextuais a serem enviados.
     * @return bool True se webhook foi disparado com sucesso.
     */
    public static function trigger_subscription_webhook($event, $data) {
        // Obtém endpoint configurado (opcional)
        $webhook_url = get_network_option(null, 'limiter_mkp_pro_lifecycle_webhook_url', '');
        if (empty($webhook_url)) {
            return false; // Nenhum webhook configurado
        }

        $payload = array(
            'event' => sanitize_text_field($event),
            'timestamp' => current_time('mysql'),
            'data' => $data,
            'plugin_version' => LIMITER_MKP_PRO_VERSION,
            'site_url' => home_url()
        );

        $response = wp_remote_post($webhook_url, array(
            'body' => wp_json_encode($payload),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 10,
            'blocking' => false // não bloqueia a execução
        ));

        if (is_wp_error($response)) {
            Limiter_MKP_Pro_Error_Handler::log_error('Falha ao enviar webhook', [
                'event' => $event,
                'error' => $response->get_error_message(),
                'url' => $webhook_url
            ]);
            return false;
        }

        Limiter_MKP_Pro_Database::log(0, 'webhook_disparado', "Webhook disparado para evento: {$event}", [
            'url' => $webhook_url,
            'event' => $event
        ]);

        return true;
    }
}