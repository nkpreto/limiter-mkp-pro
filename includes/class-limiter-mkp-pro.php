<?php
/**
 * A classe principal do plugin.
 *
 * Esta é a classe principal que coordena todas as funcionalidades do plugin.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */
if (! defined( 'ABSPATH' ) ) {
    exit;
}

class Limiter_MKP_Pro {

    protected $loader = null;
    protected $plugin_name = 'limiter-mkp-pro';
    protected $version = '2.3.2'; // Versão incrementada para controle

    public function __construct() {
        if ( defined( 'LIMITER_MKP_PRO_VERSION' ) ) {
            $this->version = LIMITER_MKP_PRO_VERSION;
        }
        if ( defined( 'LIMITER_MKP_PRO_PLUGIN_NAME' ) ) {
            $this->plugin_name = LIMITER_MKP_PRO_PLUGIN_NAME;
        }

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Loader
        if ( file_exists( LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-loader.php' ) ) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-loader.php';
        }
        if ( file_exists( LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-i18n.php' ) ) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-i18n.php';
        }
        if ( file_exists( LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-security.php' ) ) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-security.php';
        }
        if ( file_exists( LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-security-trait.php' ) ) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-security-trait.php';
        }
        if ( file_exists( LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-helper-functions.php' ) ) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-helper-functions.php';
        }

        // Modelos
        $models = array(
            'models/class-database.php',
            'models/class-email.php',
            'models/class-database-get-estatisticas.php',
        );
        foreach ( $models as $m ) {
            $path = LIMITER_MKP_PRO_PLUGIN_DIR . $m;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }

        // Admin
        $admin_files = array(
            'admin/class-admin.php',
            'admin/class-dashboard.php',
            'admin/class-planos.php',
            'admin/class-subdominios.php',
            'admin/class-configuracoes.php',
            'admin/class-logs.php',
            'admin/class-admin-handlers.php',
        );
        foreach ( $admin_files as $a ) {
            $path = LIMITER_MKP_PRO_PLUGIN_DIR . $a;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }

        // Public controllers
        $public_files = array(
            'public/class-public.php',
            'public/class-widget.php',
            'public/class-limiter.php',
            'public/class-registration-form.php',
            'public/class-subdomain-checker.php',
        );
        foreach ( $public_files as $p ) {
            $path = LIMITER_MKP_PRO_PLUGIN_DIR . $p;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }

        // Loader
        if ( class_exists( 'Limiter_MKP_Pro_Loader' ) ) {
            $this->loader = new Limiter_MKP_Pro_Loader();
        }
    }

    private function set_locale() {
        if ( ! $this->loader ) return;
        if ( class_exists( 'Limiter_MKP_Pro_i18n' ) ) {
            $plugin_i18n = new Limiter_MKP_Pro_i18n();
            $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
        }
    }

    private function define_admin_hooks() {
        if ( ! $this->loader ) return;
        if ( class_exists( 'Limiter_MKP_Pro_Admin' ) ) {
            $plugin_admin = new Limiter_MKP_Pro_Admin( $this->get_plugin_name(), $this->get_version() );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
            $this->loader->add_action( 'network_admin_menu', $plugin_admin, 'add_network_admin_menu' );
        }
        if ( class_exists( 'Limiter_MKP_Pro_Admin_Handlers' ) ) {
            $plugin_handlers = new Limiter_MKP_Pro_Admin_Handlers( $this->get_plugin_name(), $this->get_version() );
            $this->loader->add_action( 'wp_ajax_limiter_mkp_pro_save_plano', $plugin_handlers, 'handle_save_plano' );
            $this->loader->add_action( 'wp_ajax_limiter_mkp_pro_delete_plano', $plugin_handlers, 'handle_delete_plano' );
            $this->loader->add_action( 'wp_ajax_limiter_mkp_pro_save_subdominio', $plugin_handlers, 'handle_save_subdominio' );
            $this->loader->add_action( 'wp_ajax_limiter_mkp_pro_save_configuracoes', $plugin_handlers, 'handle_save_configuracoes' );
        }
    }

    private function define_public_hooks() {
        if ( ! $this->loader ) return;
        if ( class_exists( 'Limiter_MKP_Pro_Public' ) ) {
            $plugin_public = new Limiter_MKP_Pro_Public( $this->get_plugin_name(), $this->get_version() );
            $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
            $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        }

        if ( class_exists( 'Limiter_MKP_Pro_Widget' ) ) {
            $plugin_widget = new Limiter_MKP_Pro_Widget( $this->get_plugin_name(), $this->get_version() );
            $this->loader->add_action( 'wp_dashboard_setup', $plugin_widget, 'add_dashboard_widget' );
        }

        if ( class_exists( 'Limiter_MKP_Pro_Limiter' ) ) {
            $plugin_limiter = new Limiter_MKP_Pro_Limiter( $this->get_plugin_name(), $this->get_version() );
            $this->loader->add_action( 'save_post', $plugin_limiter, 'check_limit', 10, 3 );
            $this->loader->add_action( 'untrash_post', $plugin_limiter, 'check_limit_untrash', 10, 1 );
        }

        $this->loader->add_action( 'save_post', $this, 'clear_post_count_cache', 20, 2 );
        $this->loader->add_action( 'delete_post', $this, 'clear_post_count_cache', 20, 1 );

        if ( file_exists( LIMITER_MKP_PRO_PLUGIN_DIR . 'public/class-subdomain-configuration.php' ) ) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'public/class-subdomain-configuration.php';
            if ( class_exists( 'Limiter_MKP_Pro_Subdomain_Configuration' ) ) {
                new Limiter_MKP_Pro_Subdomain_Configuration();
            }
        }

        // Gancho diário (O "Gerente") - Agora faz limpeza E backup do sistema
        $this->loader->add_action( 'limiter_mkp_pro_daily_cron', $this, 'handle_cron_tasks' );
        
        if ( class_exists( 'Limiter_MKP_Pro_Inode_Counter' ) ) {
            new Limiter_MKP_Pro_Inode_Counter();
        }

        if ( class_exists( 'Limiter_MKP_Pro_Inode_Shortcodes' ) ) {
            new Limiter_MKP_Pro_Inode_Shortcodes();
        }

        if ( class_exists( 'WC_Subscriptions' ) && file_exists( LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-subscription-handler.php' ) ) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-subscription-handler.php';
            if ( class_exists( 'Limiter_MKP_Pro_WooCommerce_Subscription_Handler' ) ) {
                new Limiter_MKP_Pro_WooCommerce_Subscription_Handler();
            }
        }

        // Interceptador de upload para verificar cota nativa (MB)
        $this->loader->add_filter('wp_handle_upload_prefilter', $this, 'custom_upload_quota_check');
    }

    /**
     * Verifica a cota de upload e injeta mensagem personalizada se estourar.
     */
    public function custom_upload_quota_check($file) {
        if (!is_multisite() || !is_user_logged_in()) {
            return $file;
        }

        $quota = get_site_option('blog_upload_space'); 
        if (!$quota) {
            $quota = get_blog_option(get_current_blog_id(), 'blog_upload_space');
        }

        if ($quota) {
            $quota_bytes = $quota * 1024 * 1024;
            $used_bytes = get_space_used() * 1024 * 1024; 
            $file_size = $file['size'];

            if (($used_bytes + $file_size) > $quota_bytes) {
                $mensagem = "⚠️ Limite de Armazenamento Excedido!\n\nSeu plano atual permite apenas {$quota}MB.\nPor favor, exclua arquivos antigos ou faça um upgrade para continuar enviando mídias.";
                $file['error'] = $mensagem; 
            }
        }

        return $file;
    }

    /**
     * Tarefas de manutenção diária (Limpeza + Backup do Sistema)
     * ATUALIZADO: Agora com logs detalhados de execução.
     */
    public function handle_cron_tasks() {
        global $wpdb;
        
        // Garante que a classe de banco de dados está carregada para logar
        if (!class_exists('Limiter_MKP_Pro_Database')) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        }

        // ----------------------------------------------------
        // 1. Limpeza de Logs (Log Rotation Otimizado)
        // ----------------------------------------------------
        $table_logs = $wpdb->base_prefix . 'limiter_mkp_pro_logs';
        $total_logs_removidos = 0;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_logs'") === $table_logs) {
            $dias_retencao = 60;
            $data_limite = date('Y-m-d H:i:s', strtotime("-{$dias_retencao} days"));
            
            $lote = 1000;
            do {
                $linhas_afetadas = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $table_logs WHERE timestamp < %s LIMIT %d",
                        $data_limite,
                        $lote
                    )
                );
                
                if ($linhas_afetadas > 0) {
                    $total_logs_removidos += $linhas_afetadas;
                    usleep(100000); // Pausa leve para não travar CPU
                }
            } while ($linhas_afetadas > 0);

            // LOG DA AÇÃO 1: Registra se houve limpeza
            if ($total_logs_removidos > 0) {
                Limiter_MKP_Pro_Database::log(
                    0, 
                    'manutencao_logs', 
                    "Limpeza automática: {$total_logs_removidos} logs antigos removidos."
                );
            }
        }

        // ----------------------------------------------------
        // 2. Limpeza de tokens expirados
        // ----------------------------------------------------
        if ( file_exists( LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-token-manager.php' ) && class_exists( 'Limiter_MKP_Pro_Token_Manager' ) ) {
            try {
                require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-token-manager.php';
                if ( method_exists( 'Limiter_MKP_Pro_Token_Manager', 'cleanup_expired_tokens' ) ) {
                    Limiter_MKP_Pro_Token_Manager::cleanup_expired_tokens();
                }
            } catch ( Throwable $e ) {
                error_log( 'Limiter MKP Pro cron error (tokens cleanup): ' . $e->getMessage() );
            }
        }

        // ----------------------------------------------------
        // 3. Backup Diário das Configurações do Sistema (JSON)
        // ----------------------------------------------------
        if ( file_exists( LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-backup.php' ) ) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-backup.php';
            
            try {
                // Prepara diretório
                $upload_dir = wp_upload_dir();
                $backup_dir = $upload_dir['basedir'] . '/limiter-mkp-pro-backups/';
                if (!file_exists($backup_dir)) {
                    wp_mkdir_p($backup_dir);
                    file_put_contents($backup_dir . '.htaccess', 'deny from all');
                    file_put_contents($backup_dir . 'index.php', '<?php // Silence is golden');
                }

                // Gera JSON com todas as configurações
                $data = Limiter_MKP_Pro_Backup::export_settings(['config', 'plans', 'subs', 'tokens']);
                $filename = 'limiter-system-backup-auto-' . date('Y-m-d') . '.json';
                $file_path = $backup_dir . $filename;

                if (file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT))) {
                    // LOG DA AÇÃO 3: Sucesso do Backup
                    Limiter_MKP_Pro_Database::log(
                        0,
                        'backup_sistema_sucesso',
                        "Backup diário de configurações gerado: {$filename}"
                    );
                }

                // Retenção: Mantém apenas os últimos 7 backups de sistema
                $files = glob($backup_dir . 'limiter-system-backup-auto-*.json');
                if ($files && count($files) > 7) {
                    usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
                    $files_to_delete = array_slice($files, 7);
                    $deleted_count = 0;
                    foreach ($files_to_delete as $file) {
                        if (file_exists($file)) {
                            unlink($file);
                            $deleted_count++;
                        }
                    }
                    if ($deleted_count > 0) {
                         Limiter_MKP_Pro_Database::log(0, 'backup_sistema_limpeza', "Removidos {$deleted_count} backups antigos de sistema.");
                    }
                }

            } catch ( Throwable $e ) {
                $erro_msg = 'Limiter MKP Pro cron error (system backup): ' . $e->getMessage();
                error_log( $erro_msg );
                Limiter_MKP_Pro_Database::log(0, 'backup_sistema_erro', $erro_msg);
            }
        }

        // LOG FINAL: Confirmação de execução total
        Limiter_MKP_Pro_Database::log(
            0,
            'cron_diario_concluido',
            'Rotina diária de manutenção finalizada com sucesso.'
        );
    }

    public function run() {
        if ( $this->loader ) {
            $this->loader->run();
        }
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }

    public function clear_post_count_cache($post_id, $post = null) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;

        $blog_id = get_current_blog_id();
        
        if (class_exists('Limiter_MKP_Pro_Database')) {
            Limiter_MKP_Pro_Database::force_update_content_count($blog_id);
            Limiter_MKP_Pro_Database::clear_count_cache($blog_id);
        }
    }
}