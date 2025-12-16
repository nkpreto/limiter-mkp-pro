<?php
/**
 * Acionado durante a ativação do plugin.
 *
 * @since       1.0.0
 * @package     Limiter_MKP_Pro
 * @subpackage  Limiter_MKP_Pro/includes
 */
// Segurança: impede acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Activator {
    /**
     * Método executado durante a ativação do plugin.
     *
     * Cria as tabelas necessárias no banco de dados e configura os planos padrão.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        // Verifica se é uma instalação multisite
        if (!is_multisite()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Este plugin requer uma instalação WordPress Multisite.', 'limiter-mkp-pro'));
        }
        
        // Define o charset do banco de dados
        $charset_collate = $wpdb->get_charset_collate();
        // Prefixo para as tabelas do plugin
        $table_prefix = $wpdb->base_prefix . 'limiter_mkp_pro_';
        
        // Tabela de planos
        $table_planos = $table_prefix . 'planos';
        $sql_planos = "CREATE TABLE $table_planos (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nome varchar(100) NOT NULL,
            descricao text NOT NULL,
            duracao int(11) NOT NULL DEFAULT 30,
            limite_paginas int(11) NOT NULL DEFAULT 10,
            limite_inodes int(11) NOT NULL DEFAULT 1000,
            limite_upload_mb int(11) NOT NULL DEFAULT 100,
            backup_frequency varchar(20) NOT NULL DEFAULT 'none',
            backup_retention int(11) NOT NULL DEFAULT 3,
            post_types_contaveis text NULL, 
            post_status_contaveis text NULL,
            data_criacao datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Tabela de subdomínios
        $table_subdominios = $table_prefix . 'subdominios';
        $sql_subdominios = "CREATE TABLE $table_subdominios (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            blog_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            dominio varchar(255) NOT NULL,
            plano_id mediumint(9) NOT NULL,
            limite_personalizado int(11) NULL,
            limite_personalizado_inodes int(11) NULL,
            nome_cliente varchar(255) NULL,
            email_cliente varchar(255) NULL,
            telefone_cliente varchar(50) NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            regiao varchar(10) NOT NULL DEFAULT 'global',
            data_inicio datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            data_expiracao datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY  blog_id (blog_id),
            KEY  user_id (user_id),
            KEY  status (status),
            KEY  plano_id (plano_id),
            KEY  email_cliente (email_cliente),
            KEY  status_regiao (status, regiao),
            KEY  user_status (user_id, status)
        ) $charset_collate;";
        
        // Tabela de logs
        $table_logs = $table_prefix . 'logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            blog_id bigint(20) NOT NULL,
            acao varchar(100) NOT NULL,
            descricao text NOT NULL,
            dados_extras longtext NULL,
            user_id bigint(20) NOT NULL DEFAULT 0,
            ip varchar(45) NOT NULL DEFAULT 'unknown',
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY  blog_id (blog_id),
            KEY  acao (acao),
            KEY  timestamp (timestamp),
            KEY  blog_acao_time (blog_id, acao, timestamp)
        ) $charset_collate;";
        
        // Tabela de clientes/assinantes
        $table_clientes = $table_prefix . 'clientes';
        $sql_clientes = "CREATE TABLE $table_clientes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            blog_id bigint(20) NOT NULL,
            nome varchar(255) NOT NULL,
            endereco text NOT NULL,
            telefone varchar(50) NOT NULL,
            cpf_passaporte varchar(100) NOT NULL,
            documento_url varchar(255) NOT NULL,
            subdominio varchar(255) NOT NULL,
            username varchar(100) NOT NULL,
            data_cadastro datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY  cpf_passaporte (cpf_passaporte),
            KEY  blog_id (blog_id)
        ) $charset_collate;";
        
        // Tabela de vinculação planos-produtos WooCommerce
        $table_plano_products = $table_prefix . 'plano_products';
        $sql_plano_products = "CREATE TABLE $table_plano_products (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            plano_id mediumint(9) NOT NULL,
            woocommerce_product_id bigint(20) NOT NULL,
            ordem int(11) NOT NULL DEFAULT 0,
            data_vinculo datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY  unique_plano_product (plano_id, woocommerce_product_id),
            KEY  plano_id (plano_id),
            KEY  product_id (woocommerce_product_id)
        ) $charset_collate;";
        
        // Tabela de tokens
        $table_tokens = $table_prefix . 'tokens';
        $sql_tokens = "CREATE TABLE $table_tokens (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            subscription_id bigint(20) NOT NULL,
            token varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            plano_id mediumint(9) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            subdomain_name varchar(255) NULL,
            expires_at datetime NOT NULL,
            regiao varchar(10) NULL DEFAULT 'global',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY  token (token),
            UNIQUE KEY  email (email),
            KEY  user_id (user_id),
            KEY  subscription_id (subscription_id),
            KEY  status_expires (status, expires_at)
        ) $charset_collate;";
        
        // Inclui o arquivo necessário para a função dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Cria as tabelas
        dbDelta($sql_planos);
        dbDelta($sql_subdominios);
        dbDelta($sql_logs);
        dbDelta($sql_clientes);
        dbDelta($sql_plano_products);
        dbDelta($sql_tokens);
        
        // Insere os planos padrão se a tabela estiver vazia
        $count_planos = $wpdb->get_var("SELECT COUNT(*) FROM $table_planos");
        if ($count_planos == 0) {
            $default_post_types = json_encode(array("post", "page"));
            $default_post_status = json_encode(array("publish", "draft", "trash"));
            $wpdb->insert(
                $table_planos,
                array(
                    'nome' => 'Starter',
                    'descricao' => 'Plano básico com limite de 10 páginas/posts.',
                    'duracao' => 30,
                    'limite_paginas' => 10,
                    'limite_inodes' => 1000,
                    'limite_upload_mb' => 100,
                    'backup_frequency' => 'none',
                    'backup_retention' => 0,
                    'post_types_contaveis' => $default_post_types,
                    'post_status_contaveis' => $default_post_status,
                )
            );
            $wpdb->insert(
                $table_planos,
                array(
                    'nome' => 'Business',
                    'descricao' => 'Plano avançado com backup diário.',
                    'duracao' => 30,
                    'limite_paginas' => 100,
                    'limite_inodes' => 6000,
                    'limite_upload_mb' => 1000,
                    'backup_frequency' => 'daily',
                    'backup_retention' => 7,
                    'post_types_contaveis' => $default_post_types,
                    'post_status_contaveis' => $default_post_status,
                )
            );
        }
        
        // --- CHECKS DE ATUALIZAÇÃO (COLUNAS NOVAS) ---
        
        // 1. Limite Upload MB
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_planos LIKE 'limite_upload_mb'");
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $table_planos ADD COLUMN limite_upload_mb int(11) NOT NULL DEFAULT 100 AFTER limite_inodes");
        }

        // 2. Limite Inodes
        $column_exists_inodes = $wpdb->get_var("SHOW COLUMNS FROM $table_planos LIKE 'limite_inodes'");
        if (!$column_exists_inodes) {
            $wpdb->query("ALTER TABLE $table_planos ADD COLUMN limite_inodes int(11) NOT NULL DEFAULT 1000 AFTER limite_paginas");
        }

        // 3. Backup Frequency (NOVO)
        $column_exists_bkp = $wpdb->get_var("SHOW COLUMNS FROM $table_planos LIKE 'backup_frequency'");
        if (!$column_exists_bkp) {
            $wpdb->query("ALTER TABLE $table_planos ADD COLUMN backup_frequency varchar(20) NOT NULL DEFAULT 'none' AFTER limite_upload_mb");
        }

        // 4. Backup Retention (NOVO)
        $column_exists_ret = $wpdb->get_var("SHOW COLUMNS FROM $table_planos LIKE 'backup_retention'");
        if (!$column_exists_ret) {
            $wpdb->query("ALTER TABLE $table_planos ADD COLUMN backup_retention int(11) NOT NULL DEFAULT 3 AFTER backup_frequency");
        }

        // 5. Post Types e Status
        $column_exists_types = $wpdb->get_var("SHOW COLUMNS FROM $table_planos LIKE 'post_types_contaveis'");
        if (!$column_exists_types) {
            $wpdb->query("ALTER TABLE $table_planos ADD COLUMN post_types_contaveis text NULL AFTER backup_retention");
        }
        $column_exists_status = $wpdb->get_var("SHOW COLUMNS FROM $table_planos LIKE 'post_status_contaveis'");
        if (!$column_exists_status) {
            $wpdb->query("ALTER TABLE $table_planos ADD COLUMN post_status_contaveis text NULL AFTER post_types_contaveis");
        }
        
        // Colunas de Subdomínios
        $column_exists_sub = $wpdb->get_var("SHOW COLUMNS FROM $table_subdominios LIKE 'limite_personalizado_inodes'");
        if (!$column_exists_sub) {
            $wpdb->query("ALTER TABLE $table_subdominios ADD COLUMN limite_personalizado_inodes int(11) NULL DEFAULT NULL");
        }
        
        // Atualiza versão
        add_network_option(null, 'limiter_mkp_pro_version', LIMITER_MKP_PRO_VERSION);
    }
}