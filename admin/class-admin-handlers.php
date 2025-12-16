<?php
if (!defined('ABSPATH')) {
    exit;
}

// Carrega a Trait de segurança
require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-security-trait.php';

class Limiter_MKP_Pro_Admin_Handlers {
    use Limiter_MKP_Pro_Security_Trait;
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        // Hooks AJAX existentes
        add_action('wp_ajax_limiter_mkp_pro_search_sites', array($this, 'handle_search_sites_ajax'));
        add_action('wp_ajax_limiter_mkp_pro_generate_backup', array($this, 'handle_generate_backup_ajax'));
        add_action('wp_ajax_limiter_mkp_pro_generate_site_backup', array($this, 'handle_generate_site_backup_ajax'));
        add_action('wp_ajax_limiter_mkp_pro_delete_backup', array($this, 'handle_delete_backup_ajax'));
        
        // Save Plano
        add_action('wp_ajax_limiter_mkp_pro_save_plano', array($this, 'handle_save_plano'));
        add_action('wp_ajax_limiter_mkp_pro_delete_plano', array($this, 'handle_delete_plano'));
        add_action('wp_ajax_limiter_mkp_pro_save_subdominio', array($this, 'handle_save_subdominio'));
        add_action('wp_ajax_limiter_mkp_pro_save_configuracoes', array($this, 'handle_save_configuracoes'));
        add_action('wp_ajax_limiter_mkp_pro_limpar_logs', array($this, 'handle_limpar_logs'));
    }

    private function log_error($message, $data = []) {
        $log_msg = '[Limiter MKP Pro Error]: ' . $message;
        if (!empty($data)) {
            $log_msg .= ' ' . json_encode($data);
        }
        error_log($log_msg);
    }

    private function verify_ajax_permissions() {
        if (!current_user_can('manage_network')) {
            wp_send_json_error(['message' => 'Você não tem permissão para realizar esta operação.']);
            exit;
        }
        if (!is_multisite()) {
            wp_send_json_error(['message' => 'Este plugin requer uma instalação WordPress Multisite.']);
            exit;
        }
    }

    private function verify_ajax_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_send_json_error(['message' => 'Token de segurança inválido.']);
            exit;
        }
    }

    // --- HANDLER ATUALIZADO DE SALVAR PLANO ---

    public function handle_save_plano() {
        try {
            $this->verify_ajax_permissions();
            $this->verify_ajax_nonce($_POST['nonce'] ?? '', 'limiter_mkp_pro_admin_nonce');
            
            $id = intval($_POST['id'] ?? 0);
            $nome = sanitize_text_field($_POST['nome'] ?? '');
            $descricao = sanitize_textarea_field($_POST['descricao'] ?? '');
            $duracao = intval($_POST['duracao'] ?? 30);
            $limite_paginas = intval($_POST['limite_paginas'] ?? 10);
            $limite_inodes = intval($_POST['limite_inodes'] ?? 1000);
            $limite_upload_mb = intval($_POST['limite_upload_mb'] ?? 100);
            
            // NOVOS CAMPOS
            $backup_frequency = sanitize_text_field($_POST['backup_frequency'] ?? 'none');
            $backup_retention = intval($_POST['backup_retention'] ?? 3);

            $post_types_contaveis = isset($_POST['post_types_contaveis']) ? 
                array_map('sanitize_text_field', $_POST['post_types_contaveis']) : ['page', 'post'];
            $post_status_contaveis = isset($_POST['post_status_contaveis']) ?
                array_map('sanitize_text_field', $_POST['post_status_contaveis']) : ['publish', 'draft', 'trash'];
            
            $woocommerce_product_ids = [];
            if (isset($_POST['woocommerce_product_ids']) && is_array($_POST['woocommerce_product_ids'])) {
                $woocommerce_product_ids = array_map('intval', $_POST['woocommerce_product_ids']);
            }
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-planos.php';
            $planos = new Limiter_MKP_Pro_Planos($this->plugin_name, $this->version);
            
            $resultado = $planos->save_plano([
                'id' => $id,
                'nome' => $nome,
                'descricao' => $descricao,
                'duracao' => $duracao,
                'limite_paginas' => $limite_paginas,
                'limite_inodes' => $limite_inodes,
                'limite_upload_mb' => $limite_upload_mb,
                
                // Passa os novos campos
                'backup_frequency' => $backup_frequency,
                'backup_retention' => $backup_retention,
                
                'post_types_contaveis' => $post_types_contaveis,
                'post_status_contaveis' => $post_status_contaveis
            ]);
            
            if ($resultado) {
                $planos->save_plano_woocommerce_products($resultado, $woocommerce_product_ids);
                require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-cache-manager.php';
                Limiter_MKP_Pro_Cache_Manager::clear_all();
                wp_send_json_success(['message' => 'Plano salvo com sucesso!']);
            } else {
                $this->log_error('Falha ao salvar plano', ['nome' => $nome]);
                wp_send_json_error(['message' => 'Não foi possível salvar o plano.']);
            }
        } catch (Exception $e) {
            $this->log_error($e->getMessage());
            wp_send_json_error(['message' => 'Erro interno ao salvar plano.']);
        }
    }

    public function handle_delete_plano() {
        try {
            $this->verify_ajax_permissions();
            $this->verify_ajax_nonce($_POST['nonce'] ?? '', 'limiter_mkp_pro_admin_nonce');
            $id = intval($_POST['id'] ?? 0);
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-planos.php';
            $planos = new Limiter_MKP_Pro_Planos($this->plugin_name, $this->version);
            if ($planos->delete_plano($id)) {
                require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-cache-manager.php';
                Limiter_MKP_Pro_Cache_Manager::clear_all();
                wp_send_json_success(['message' => 'Plano excluído com sucesso!']);
            } else {
                wp_send_json_error(['message' => 'Não foi possível excluir o plano. Verifique se ele está sendo usado por algum subdomínio.']);
            }
        } catch (Exception $e) {
            $this->log_error($e->getMessage());
            wp_send_json_error(['message' => 'Erro interno ao excluir plano.']);
        }
    }

    public function handle_save_subdominio() {
        try {
            $this->verify_ajax_permissions();
            $this->verify_ajax_nonce($_POST['nonce'] ?? '', 'limiter_mkp_pro_admin_nonce');
            
            $blog_id = intval($_POST['blog_id'] ?? 0);
            $plano_id = intval($_POST['plano_id'] ?? 0);
            if ($blog_id <= 0 || $plano_id <= 0) {
                wp_send_json_error(['message' => 'Dados inválidos.']);
                exit;
            }

            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-subdominios.php';
            $subdominios = new Limiter_MKP_Pro_Subdominios($this->plugin_name, $this->version);
            
            $resultado = $subdominios->save_subdominio([
                'blog_id' => $blog_id,
                'dominio' => sanitize_text_field($_POST['dominio'] ?? ''),
                'plano_id' => $plano_id,
                'limite_personalizado' => !empty($_POST['limite_personalizado']) ? intval($_POST['limite_personalizado']) : null,
                'limite_personalizado_inodes' => !empty($_POST['limite_personalizado_inodes']) ? intval($_POST['limite_personalizado_inodes']) : null,
                'nome_cliente' => sanitize_text_field($_POST['nome_cliente'] ?? ''),
                'email_cliente' => sanitize_email($_POST['email_cliente'] ?? ''),
                'telefone_cliente' => sanitize_text_field($_POST['telefone_cliente'] ?? ''),
                'status' => 'active',
                'regiao' => sanitize_text_field($_POST['regiao'] ?? 'global')
            ]);
            if ($resultado) {
                require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-cache-manager.php';
                Limiter_MKP_Pro_Cache_Manager::clear_all();
                wp_send_json_success(['message' => 'Subdomínio salvo com sucesso!']);
            } else {
                wp_send_json_error(['message' => 'Erro ao salvar subdomínio.']);
            }
        } catch (Exception $e) {
            $this->log_error($e->getMessage());
            wp_send_json_error(['message' => 'Erro interno.']);
        }
    }

    public function handle_save_configuracoes() {
        try {
            $this->verify_ajax_permissions();
            $this->verify_ajax_nonce($_POST['nonce'] ?? '', 'limiter_mkp_pro_admin_nonce');
            
            $config_data = [
                'email_notificacao' => sanitize_email($_POST['email_notificacao'] ?? ''),
                'limite_alerta' => intval($_POST['limite_alerta'] ?? 1),
                'mensagem_limite' => sanitize_textarea_field($_POST['mensagem_limite'] ?? ''),
                'mensagem_alerta' => sanitize_textarea_field($_POST['mensagem_alerta'] ?? ''),
                'planos_url_global' => esc_url_raw($_POST['planos_url_global'] ?? ''),
                'planos_url_jp' => esc_url_raw($_POST['planos_url_jp'] ?? ''),
                'sufixo_subdominio' => sanitize_text_field($_POST['sufixo_subdominio'] ?? '-mkp'),
                'nome_sistema' => sanitize_text_field($_POST['nome_sistema'] ?? 'Marketing Place Store'),
                'widget_alerta_limite' => sanitize_textarea_field($_POST['widget_alerta_limite'] ?? ''),
                'widget_sem_plano' => sanitize_textarea_field($_POST['widget_sem_plano'] ?? ''),
                'alerta_limite_arquivos_80' => sanitize_textarea_field($_POST['alerta_limite_arquivos_80'] ?? ''),
                'alerta_limite_arquivos_100' => sanitize_textarea_field($_POST['alerta_limite_arquivos_100'] ?? ''),
                'subdominio_disponivel' => sanitize_text_field($_POST['subdominio_disponivel'] ?? ''),
                'subdominio_indisponivel' => sanitize_text_field($_POST['subdominio_indisponivel'] ?? ''),
                'subdominio_curto' => sanitize_text_field($_POST['subdominio_curto'] ?? ''),
                'registro_concluido' => sanitize_text_field($_POST['registro_concluido'] ?? ''),
                'email_solicitacao_titulo' => sanitize_text_field($_POST['email_solicitacao_titulo'] ?? ''),
                'email_solicitacao_corpo' => sanitize_textarea_field($_POST['email_solicitacao_corpo'] ?? ''),
                'email_confirmacao_aprovada_titulo' => sanitize_text_field($_POST['email_confirmacao_aprovada_titulo'] ?? ''),
                'email_confirmacao_aprovada_corpo' => sanitize_textarea_field($_POST['email_confirmacao_aprovada_corpo'] ?? ''),
                'email_configuracao_subdominio_titulo' => sanitize_text_field($_POST['email_configuracao_subdominio_titulo'] ?? ''),
                'email_configuracao_subdominio_corpo' => sanitize_textarea_field($_POST['email_configuracao_subdominio_corpo'] ?? ''),
                'botao_ver_planos' => sanitize_text_field($_POST['botao_ver_planos'] ?? ''),
                'botao_acessar_loja' => sanitize_text_field($_POST['botao_acessar_loja'] ?? '')
            ];
            
            $subdomain_blacklist = array();
            if (isset($_POST['subdomain_blacklist']) && is_array($_POST['subdomain_blacklist'])) {
                foreach ($_POST['subdomain_blacklist'] as $word) {
                    $clean_word = sanitize_text_field(trim($word));
                    if (!empty($clean_word)) {
                        $subdomain_blacklist[] = $clean_word;
                    }
                }
            }
            $config_data['subdomain_blacklist'] = $subdomain_blacklist;
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-configuracoes.php';
            $configuracoes = new Limiter_MKP_Pro_Configuracoes($this->plugin_name, $this->version);
            
            if ($configuracoes->save_configuracoes($config_data)) {
                require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-cache-manager.php';
                Limiter_MKP_Pro_Cache_Manager::clear_all();
                wp_send_json_success(['message' => 'Configurações salvas com sucesso!']);
            } else {
                wp_send_json_success(['message' => 'Configurações salvas.']);
            }
        } catch (Exception $e) {
            $this->log_error($e->getMessage());
            wp_send_json_error(['message' => 'Erro interno ao salvar configurações.']);
        }
    }

    public function handle_limpar_logs() {
        try {
            $this->verify_ajax_permissions();
            $this->verify_ajax_nonce($_POST['nonce'] ?? '', 'limiter_mkp_pro_admin_nonce');
            
            $dias = intval($_POST['dias'] ?? 90);
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-logs.php';
            $logs = new Limiter_MKP_Pro_Logs($this->plugin_name, $this->version);
            $removidos = $logs->limpar_logs_antigos($dias);
            
            wp_send_json_success(['message' => sprintf('Removidos %d logs.', $removidos)]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erro ao limpar logs.']);
        }
    }

    public function handle_search_sites_ajax() {
        try {
            $this->verify_ajax_permissions();
            if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'limiter_mkp_pro_admin_nonce')) {
               wp_send_json_error(['message' => 'Nonce inválido']);
               exit;
            }

            $term = sanitize_text_field($_GET['term'] ?? '');
            if (strlen($term) < 3) {
                wp_send_json_success([]);
                exit;
            }

            global $wpdb;
            $query = $wpdb->prepare(
                "SELECT blog_id, domain, path FROM {$wpdb->blogs} 
                 WHERE (domain LIKE %s OR path LIKE %s) 
                 AND deleted = 0 AND archived = '0' AND spam = '0'
                 LIMIT 20",
                '%' . $wpdb->esc_like($term) . '%',
                '%' . $wpdb->esc_like($term) . '%'
            );
            $sites = $wpdb->get_results($query);
            $results = [];
            
            foreach ($sites as $site) {
                $details = get_blog_details($site->blog_id);
                $label = $details ? $details->blogname . ' (' . $site->domain . $site->path . ')' : $site->domain;
                $results[] = [
                    'id' => $site->blog_id,
                    'label' => $label,
                    'value' => $label,
                    'domain' => $site->domain . $site->path
                ];
            }
            
            wp_send_json_success($results);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erro na busca']);
        }
    }

    public function handle_generate_backup_ajax() {
        try {
            $this->verify_ajax_permissions();
            $this->verify_ajax_nonce($_POST['nonce'] ?? '', 'limiter_mkp_pro_admin_nonce');

            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-backup.php';
            
            $upload_dir = wp_upload_dir();
            $backup_dir = $upload_dir['basedir'] . '/limiter-mkp-pro-backups/';
            if (!file_exists($backup_dir)) {
                wp_mkdir_p($backup_dir);
                file_put_contents($backup_dir . '.htaccess', 'deny from all');
                file_put_contents($backup_dir . 'index.php', '<?php // Silence is golden');
            }

            $includes = isset($_POST['includes']) ? array_map('sanitize_text_field', $_POST['includes']) : ['config'];
            $data = Limiter_MKP_Pro_Backup::export_settings($includes);
            $filename = 'limiter-system-backup-' . date('Y-m-d-H-i-s') . '.json';
            $file_path = $backup_dir . $filename;

            if (file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT))) {
                wp_send_json_success(['message' => 'Backup gerado com sucesso!', 'file' => $filename]);
            } else {
                wp_send_json_error(['message' => 'Falha ao salvar arquivo. Verifique permissões.']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_generate_site_backup_ajax() {
        try {
            $this->verify_ajax_permissions();
            $this->verify_ajax_nonce($_POST['nonce'] ?? '', 'limiter_mkp_pro_admin_nonce');

            $blog_id = intval($_POST['blog_id']);
            if ($blog_id <= 0) {
                wp_send_json_error(['message' => 'Selecione um site válido.']);
                exit;
            }

            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-backup.php';
            
            $filename = Limiter_MKP_Pro_Backup::export_subdomain_sql($blog_id);

            if ($filename) {
                wp_send_json_success([
                    'message' => 'Backup do Site (SQL) gerado com sucesso!',
                    'file' => $filename
                ]);
            } else {
                wp_send_json_error(['message' => 'Falha ao exportar banco de dados.']);
            }

        } catch (Exception $e) {
            $this->log_error($e->getMessage());
            wp_send_json_error(['message' => 'Erro crítico: ' . $e->getMessage()]);
        }
    }

    public function handle_delete_backup_ajax() {
        try {
            $this->verify_ajax_permissions();
            $this->verify_ajax_nonce($_POST['nonce'] ?? '', 'limiter_mkp_pro_admin_nonce');

            $filename = sanitize_file_name($_POST['file']);
            
            $upload_dir = wp_upload_dir();
            $backup_dir = $upload_dir['basedir'] . '/limiter-mkp-pro-backups/';
            $file_path = $backup_dir . $filename;

            if (file_exists($file_path) && is_file($file_path) && (strpos($filename, 'limiter-') === 0 || strpos($filename, 'backup-site-') === 0)) {
                if (unlink($file_path)) {
                    wp_send_json_success(['message' => 'Backup excluído com sucesso.']);
                } else {
                    wp_send_json_error(['message' => 'Erro ao excluir o arquivo. Verifique as permissões.']);
                }
            } else {
                wp_send_json_error(['message' => 'Arquivo não encontrado ou inválido.']);
            }

        } catch (Exception $e) {
            $this->log_error($e->getMessage());
            wp_send_json_error(['message' => 'Erro ao excluir: ' . $e->getMessage()]);
        }
    }
}