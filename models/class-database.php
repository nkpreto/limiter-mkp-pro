<?php
/**
 * Classe responsável pelas operações de banco de dados do plugin.
 * OTIMIZADA: Compatibilidade HPOS e Índices de Alta Performance.
 *
 * @since 2.3.3
 */
if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Database {
    
    protected static $allowed_tables = array(
        'planos', 'subdominios', 'logs', 'solicitacoes', 'tokens', 'plano_products', 'clientes'
    );

    /**
     * Retorna o nome da tabela com o prefixo correto do WP.
     */
    public static function get_table_name($table_name) {
        global $wpdb;
        $table_name = sanitize_key($table_name);
        
        // Garante que não duplique o prefixo se já vier com ele
        if (strpos($table_name, 'limiter_mkp_pro_') === false) {
            return $wpdb->base_prefix . 'limiter_mkp_pro_' . $table_name;
        }
        
        return $wpdb->base_prefix . $table_name;
    }
   
    public static function validate_table_name($table_full_name) {
        global $wpdb;
        return strpos($table_full_name, $wpdb->base_prefix . 'limiter_mkp_pro_') === 0;
    }

    // --- MÉTODOS DE GET E SAVE (PLANOS) ---
    
    public static function get_planos() {
        global $wpdb;
        $table = self::get_table_name('planos');
        
        // Cache simples para evitar query repetitiva na mesma requisição
        $cache_key = 'limiter_mkp_all_plans';
        $planos = wp_cache_get($cache_key, 'limiter_mkp');
        
        if (false === $planos) {
            $planos = $wpdb->get_results("SELECT * FROM {$table} ORDER BY nome ASC");
            
            foreach ($planos as $plano) {
                // Decodifica JSONs e define defaults para evitar erros de PHP
                if (!isset($plano->post_types_contaveis)) $plano->post_types_contaveis = json_encode(['page', 'post']);
                if (!isset($plano->post_status_contaveis)) $plano->post_status_contaveis = json_encode(['publish', 'draft', 'trash']);
                if (!isset($plano->limite_upload_mb)) $plano->limite_upload_mb = 100;
                if (!isset($plano->backup_frequency)) $plano->backup_frequency = 'none';
                if (!isset($plano->backup_retention)) $plano->backup_retention = 3;
            }
            wp_cache_set($cache_key, $planos, 'limiter_mkp', 3600);
        }
        
        return $planos;
    }

    public static function get_plano($id) {
        global $wpdb;
        $id = intval($id);
        if ($id <= 0) return null;

        $table = self::get_table_name('planos');
        $plano = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        
        if ($plano) {
            if (!isset($plano->post_types_contaveis)) $plano->post_types_contaveis = json_encode(['page', 'post']);
            if (!isset($plano->post_status_contaveis)) $plano->post_status_contaveis = json_encode(['publish', 'draft', 'trash']);
            if (!isset($plano->limite_upload_mb)) $plano->limite_upload_mb = 100;
            if (!isset($plano->backup_frequency)) $plano->backup_frequency = 'none';
            if (!isset($plano->backup_retention)) $plano->backup_retention = 3;
        }
        return $plano;
    }

    public static function save_plano($data) {
        global $wpdb;
        $table = self::get_table_name('planos');
        
        $post_types = isset($data['post_types_contaveis']) ? (array) $data['post_types_contaveis'] : ['page', 'post'];
        $post_status = isset($data['post_status_contaveis']) ? (array) $data['post_status_contaveis'] : ['publish', 'draft', 'trash'];
        
        $plano_data = array(
            'nome' => sanitize_text_field($data['nome']),
            'descricao' => sanitize_textarea_field($data['descricao']),
            'duracao' => intval($data['duracao']),
            'limite_paginas' => intval($data['limite_paginas']),
            'limite_inodes' => intval($data['limite_inodes']),
            'limite_upload_mb' => isset($data['limite_upload_mb']) ? intval($data['limite_upload_mb']) : 100,
            'backup_frequency' => isset($data['backup_frequency']) ? sanitize_text_field($data['backup_frequency']) : 'none',
            'backup_retention' => isset($data['backup_retention']) ? intval($data['backup_retention']) : 3,
            'post_types_contaveis' => json_encode($post_types),
            'post_status_contaveis' => json_encode($post_status)
        );
        
        // Limpa cache ao salvar
        wp_cache_delete('limiter_mkp_all_plans', 'limiter_mkp');
        
        if (!empty($data['id']) && intval($data['id']) > 0) {
            $wpdb->update($table, $plano_data, ['id' => intval($data['id'])]);
            return intval($data['id']);
        } else {
            $wpdb->insert($table, $plano_data);
            return intval($wpdb->insert_id);
        }
    }

    public static function delete_plano($id) {
        global $wpdb;
        $t_planos = self::get_table_name('planos');
        $t_subs = self::get_table_name('subdominios');
        
        // Impede exclusão se houver subdomínios vinculados (Integridade Referencial)
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t_subs} WHERE plano_id = %d", $id));
        if ($count > 0) return false;
        
        wp_cache_delete('limiter_mkp_all_plans', 'limiter_mkp');
        return $wpdb->delete($t_planos, ['id' => $id]) !== false;
    }

    // --- MÉTODOS DE SUBDOMÍNIOS ---

    public static function get_subdominios() {
        global $wpdb;
        $t_subs = self::get_table_name('subdominios');
        $t_planos = self::get_table_name('planos');
        
        // Query otimizada com JOIN
        return $wpdb->get_results("
            SELECT s.*, p.nome as plano_nome 
            FROM {$t_subs} s 
            LEFT JOIN {$t_planos} p ON s.plano_id = p.id 
            ORDER BY s.dominio ASC
        ");
    }

    public static function get_subdominio_by_blog_id($blog_id) {
        global $wpdb;
        $blog_id = intval($blog_id);
        if ($blog_id <= 0) return null;

        $t_subs = self::get_table_name('subdominios');
        $t_planos = self::get_table_name('planos');
        
        // Usa o índice UNIQUE KEY blog_id criado no activator
        return $wpdb->get_row($wpdb->prepare("
            SELECT s.*, p.post_types_contaveis, p.post_status_contaveis, 
                   p.limite_paginas as plano_limite, p.limite_inodes as plano_limite_inodes
            FROM {$t_subs} s 
            LEFT JOIN {$t_planos} p ON s.plano_id = p.id
            WHERE s.blog_id = %d AND s.status = 'active'
        ", $blog_id));
    }

    public static function save_subdominio($data) {
        global $wpdb;
        $table = self::get_table_name('subdominios');
        $blog_id = intval($data['blog_id']);
        
        $sub_data = array(
            'blog_id' => $blog_id,
            'user_id' => isset($data['user_id']) ? intval($data['user_id']) : get_current_user_id(),
            'dominio' => sanitize_text_field($data['dominio']),
            'plano_id' => intval($data['plano_id']),
            'limite_personalizado' => !empty($data['limite_personalizado']) ? intval($data['limite_personalizado']) : null,
            'limite_personalizado_inodes' => !empty($data['limite_personalizado_inodes']) ? intval($data['limite_personalizado_inodes']) : null,
            'nome_cliente' => sanitize_text_field($data['nome_cliente']),
            'email_cliente' => sanitize_email($data['email_cliente']),
            'telefone_cliente' => sanitize_text_field($data['telefone_cliente']),
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active',
            'regiao' => isset($data['regiao']) ? sanitize_text_field($data['regiao']) : 'global'
        );
        
        // Verifica existência usando o índice blog_id
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE blog_id = %d", $blog_id));
        
        if ($exists) {
            $wpdb->update($table, $sub_data, ['blog_id' => $blog_id]);
            return intval($exists);
        } else {
            $wpdb->insert($table, $sub_data);
            return intval($wpdb->insert_id);
        }
    }

    public static function get_subdomain_by_name($domain_name) {
        global $wpdb;
        $table = self::get_table_name('subdominios');
        // Sanitização extra para busca
        $domain_name = sanitize_text_field($domain_name);
        $like = '%' . $wpdb->esc_like($domain_name) . '%';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE dominio LIKE %s", $like));
    }

    public static function get_limite_paginas($blog_id) {
        $sub = self::get_subdominio_by_blog_id($blog_id);
        if (!$sub) return 0;
        $limite_plano = isset($sub->plano_limite) ? intval($sub->plano_limite) : 0;
        return !empty($sub->limite_personalizado) ? intval($sub->limite_personalizado) : $limite_plano;
    }

    // --- MÉTODOS DE CONTAGEM OTIMIZADOS (PERFORMANCE CRÍTICA) ---

    /**
     * Conta conteúdo limitado com cache no banco para não pesar o carregamento do admin.
     */
    public static function count_limited_content($blog_id) {
        $blog_id = intval($blog_id);
        if ($blog_id <= 0) return 0;
        
        // Tenta pegar do cache da tabela wp_X_options
        $cached_count = get_blog_option($blog_id, 'limiter_mkp_total_content_count', false);
        
        // Se existir e for numérico, retorna
        if ($cached_count !== false && is_numeric($cached_count)) {
            return intval($cached_count);
        }
        
        // Se não, calcula e salva
        return self::count_pages_posts($blog_id, false, true);
    }

    public static function count_pages_posts($blog_id, $only_published = false, $count_all = false) {
        $sub = self::get_subdominio_by_blog_id($blog_id);
        if (!$sub) return 0;

        $post_types = json_decode($sub->post_types_contaveis, true) ?: ['page', 'post'];
        $post_status = json_decode($sub->post_status_contaveis, true) ?: ['publish', 'draft', 'trash'];
        
        if ($only_published) {
            $post_status = ['publish'];
        }

        // Switch to blog é necessário para ler a tabela wp_X_posts correta
        switch_to_blog($blog_id);
        global $wpdb;
        
        $types_placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        $status_placeholders = implode(',', array_fill(0, count($post_status), '%s'));
        
        // Query direta na tabela de posts (HPOS não afeta wp_posts)
        $query = "SELECT COUNT(*) FROM {$wpdb->posts} 
                  WHERE post_type IN ($types_placeholders) 
                  AND post_status IN ($status_placeholders)";
                  
        $params = array_merge($post_types, $post_status);
        $total = (int) $wpdb->get_var($wpdb->prepare($query, $params));
        
        // Atualiza o cache se solicitado (usado quando um post é salvo/deletado)
        if ($count_all) {
            update_blog_option($blog_id, 'limiter_mkp_total_content_count', $total);
        }
        
        restore_current_blog();
        return $total;
    }

    public static function count_published_pages_posts($blog_id) {
        return self::count_pages_posts($blog_id, true);
    }

    public static function count_trash_pages_posts($blog_id) {
        $sub = self::get_subdominio_by_blog_id($blog_id);
        if (!$sub) return 0;

        $post_types = json_decode($sub->post_types_contaveis, true) ?: ['page', 'post'];
        
        switch_to_blog($blog_id);
        global $wpdb;
        
        $types_placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        $query = "SELECT COUNT(*) FROM {$wpdb->posts} 
                  WHERE post_type IN ($types_placeholders) 
                  AND post_status = 'trash'";
                  
        $total = (int) $wpdb->get_var($wpdb->prepare($query, $post_types));
        restore_current_blog();
        return $total;
    }

    /**
     * Força a atualização da contagem (chamado ao salvar/deletar post).
     */
    public static function force_update_content_count($blog_id) {
        self::count_pages_posts($blog_id, false, true);
    }
    
    public static function clear_count_cache($blog_id) {
        delete_blog_option($blog_id, 'limiter_mkp_total_content_count');
        self::force_update_content_count($blog_id);
    }

    // --- MÉTODOS DE DASHBOARD E CHURN (USANDO OS NOVOS ÍNDICES) ---

    public static function count_blogs_by_lifecycle_status($status) {
        global $wpdb;
        $table = self::get_table_name('subdominios');
        // Usa o índice KEY status
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", $status));
    }

    public static function get_blogs_by_lifecycle_status($status, $limit = 50) {
        global $wpdb;
        $table = self::get_table_name('subdominios');
        $limit = intval($limit);
        
        // Otimização: SELECT apenas o necessário para a tabela do dashboard
        return $wpdb->get_results($wpdb->prepare("
            SELECT s.blog_id, s.dominio, s.nome_cliente, s.email_cliente, s.telefone_cliente, 
                   s.data_inicio, s.data_expiracao, s.plano_id,
                   p.nome as plano_nome
            FROM {$table} s
            LEFT JOIN " . self::get_table_name('planos') . " p ON s.plano_id = p.id
            WHERE s.status = %s
            ORDER BY s.data_inicio DESC
            LIMIT %d", 
            $status, $limit
        ));
    }

    public static function count_overdue_subscriptions() {
        global $wpdb;
        $table = self::get_table_name('subdominios');
        $now = current_time('mysql');
        // Otimizado para verificar status e data
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'active' AND data_expiracao IS NOT NULL AND data_expiracao < %s", 
            $now
        ));
    }

    public static function get_all_blogs_with_subscriptions() {
        global $wpdb;
        $table = self::get_table_name('subdominios');
        // Exclui arquivados para não processar sites mortos
        return $wpdb->get_results("SELECT blog_id FROM {$table} WHERE status != 'archived'");
    }

    // --- SISTEMA DE LOGS BLINDADO ---
    
    public static function log($blog_id, $acao, $descricao, $dados_extras = []) {
        global $wpdb;
        $table = self::get_table_name('logs');
        
        // Verificação de segurança extra para garantir que a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) return false;
        
        $wpdb->insert($table, [
            'blog_id' => intval($blog_id),
            'acao' => sanitize_text_field($acao),
            'descricao' => sanitize_text_field($descricao),
            'dados_extras' => maybe_serialize($dados_extras),
            'user_id' => get_current_user_id(),
            'ip' => self::get_client_ip() // Helper method
        ]);
        return $wpdb->insert_id;
    }

    public static function log_enhanced($log_data) {
        return self::log(
            isset($log_data['blog_id']) ? $log_data['blog_id'] : 0, 
            isset($log_data['acao']) ? $log_data['acao'] : 'sistema', 
            isset($log_data['descricao']) ? $log_data['descricao'] : '', 
            isset($log_data['dados_extras']) ? $log_data['dados_extras'] : []
        );
    }

    public static function get_logs($limit = 100, $blog_id = null) {
        global $wpdb;
        $table = self::get_table_name('logs');
        $sql = "SELECT * FROM {$table} ";
        
        if ($blog_id) {
            $sql .= $wpdb->prepare("WHERE blog_id = %d ", $blog_id);
        }
        
        // Usa o índice KEY timestamp para ordenação rápida
        $sql .= "ORDER BY timestamp DESC LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }

    /**
     * Obtém IP real do cliente (Suporte a Cloudflare/Proxy)
     */
    private static function get_client_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return sanitize_text_field($ip);
    }
}