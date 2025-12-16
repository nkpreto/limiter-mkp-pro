<?php
/**
 * Classe responsável pelo gerenciamento de planos.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin
 */
class Limiter_MKP_Pro_Planos {
    use Limiter_MKP_Pro_Security_Trait;
    
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function get_planos() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        return Limiter_MKP_Pro_Database::get_planos();
    }

    public function get_plano($id) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        return Limiter_MKP_Pro_Database::get_plano($id);
    }

    /**
     * Salva um plano e sincroniza o espaço em disco.
     */
    public function save_plano($data) {
        // Se há produtos WooCommerce vinculados, usar a duração do primeiro produto
        if (!empty($data['woocommerce_product_ids'])) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
            $first_product_id = reset($data['woocommerce_product_ids']);
            $woocommerce_duration = Limiter_MKP_Pro_WooCommerce_Integration::get_product_duration_days($first_product_id);
            if ($woocommerce_duration > 0) {
                $data['duracao'] = $woocommerce_duration;
            }
        }
        
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $plano_id = Limiter_MKP_Pro_Database::save_plano($data);
        
        // --- Sincronizar espaço em disco (MB) para todos os sites deste plano ---
        if ($plano_id && isset($data['limite_upload_mb'])) {
            $this->sync_upload_space_for_plan($plano_id, intval($data['limite_upload_mb']));
        }

        return $plano_id;
    }

    public function delete_plano($id) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        return Limiter_MKP_Pro_Database::delete_plano($id);
    }

    /**
     * Atualiza a opção nativa do WP 'blog_upload_space' para todos os sites do plano.
     */
    private function sync_upload_space_for_plan($plano_id, $mb_limit) {
        global $wpdb;
        
        // Se o limite for inválido, não faz nada
        if ($mb_limit <= 0) return;

        // Pega tabela de subdomínios via classe Database para garantir prefixo correto
        $table_subs = Limiter_MKP_Pro_Database::get_table_name('subdominios');
        
        // Busca todos os blog_ids vinculados a este plano
        $blog_ids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $table_subs WHERE plano_id = %d", $plano_id));
        
        if (!empty($blog_ids)) {
            foreach ($blog_ids as $blog_id) {
                // update_blog_option atualiza a opção nativa que o WP usa para quota
                update_blog_option($blog_id, 'blog_upload_space', $mb_limit);
            }
        }
    }

    public function get_plano_woocommerce_products($plano_id) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
        return Limiter_MKP_Pro_WooCommerce_Integration::get_plano_products($plano_id);
    }

    public function save_plano_woocommerce_products($plano_id, $product_ids) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
        return Limiter_MKP_Pro_WooCommerce_Integration::save_plano_products($plano_id, $product_ids);
    }
    
    public function get_limite_padrao($plano_id) {
        $plano = $this->get_plano($plano_id);
        return $plano ? intval($plano->limite_paginas) : 10;
    }

    public function get_post_types_contaveis($plano_id) {
        $plano = $this->get_plano($plano_id);
        if ($plano && !empty($plano->post_types_contaveis)) {
            return json_decode($plano->post_types_contaveis, true) ?: ['page', 'post'];
        }
        return ['page', 'post'];
    }

    public function get_post_status_contaveis($plano_id) {
        $plano = $this->get_plano($plano_id);
        if ($plano && !empty($plano->post_status_contaveis)) {
            return json_decode($plano->post_status_contaveis, true) ?: ['publish', 'draft', 'trash'];
        }
        return ['publish', 'draft', 'trash'];
    }
}