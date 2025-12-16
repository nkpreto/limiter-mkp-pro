<?php
/**
 * Plugin Name: Limiter MKP Pro
 * Plugin URI: https://marketing-place.store/plugins/limiter-mkp-pro
 * Description: Plugin para WordPress multisite que limita a quantidade de páginas e posts que cada subdomínio pode criar. Inclui sistema completo de gestão do ciclo de vida baseado no WooCommerce Subscriptions.
 * Version: 2.3.1
 * Author: Marketing Place Store
 * Author URI: https://marketing-place.store
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: limiter-mkp-pro
 * Domain Path: /languages
 * Network: true
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 7.0
 *
 * @package Limiter_MKP_Pro
 */

// Se este arquivo é chamado diretamente, aborta.
if (!defined('WPINC')) {
    die;
}

/*
 * Declara compatibilidade com HPOS (High-Performance Order Storage) do WooCommerce.
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

// Define constantes do plugin
define('LIMITER_MKP_PRO_VERSION', '2.3.1');
define('LIMITER_MKP_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LIMITER_MKP_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LIMITER_MKP_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Verifica requisitos mínimos antes de ativar
add_action('admin_notices', function() {
    if (!is_multisite()) {
        echo '<div class="notice notice-error"><p>';
        _e('O plugin <strong>Limiter MKP Pro</strong> requer uma instalação WordPress Multisite.', 'limiter-mkp-pro');
        echo '</p></div>';
    }
    
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('O plugin <strong>Limiter MKP Pro</strong> requer PHP 7.4 ou superior. Você está usando PHP %s.', 'limiter-mkp-pro'),
            PHP_VERSION
        );
        echo '</p></div>';
    }
    
    if (defined('WC_VERSION') && version_compare(WC_VERSION, '6.0', '<')) {
        echo '<div class="notice notice-warning"><p>';
        printf(
            __('O plugin <strong>Limiter MKP Pro</strong> funciona melhor com WooCommerce 6.0+. Você está usando WooCommerce %s.', 'limiter-mkp-pro'),
            WC_VERSION
        );
        echo '</p></div>';
    }
});

/**
 * Código executado durante a ativação do plugin.
 */
function activate_limiter_mkp_pro() {
    require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-activator.php';
    Limiter_MKP_Pro_Activator::activate();
}

/**
 * Código executado durante a desativação do plugin.
 */
function deactivate_limiter_mkp_pro() {
    require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-deactivator.php';
    Limiter_MKP_Pro_Deactivator::deactivate();
}

/**
 * Remove agendamentos de cron durante a desativação
 */
function limiter_mkp_pro_unschedule_cron_on_deactivation() {
    // Remove o evento principal
    $timestamp = wp_next_scheduled('limiter_mkp_pro_daily_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'limiter_mkp_pro_daily_cron');
    }
    
    // Remove a fila de processamento se existir
    $queue_timestamp = wp_next_scheduled('limiter_mkp_pro_process_queue');
    if ($queue_timestamp) {
        wp_unschedule_event($queue_timestamp, 'limiter_mkp_pro_process_queue');
    }
}

/**
 * Agenda cron jobs durante a ativação - DEFINIDO PARA 03:00 AM
 */
function limiter_mkp_pro_schedule_cron_on_activation() {
    if (!wp_next_scheduled('limiter_mkp_pro_daily_cron')) {
        // Agenda para as 03:00 da manhã do dia seguinte
        wp_schedule_event(strtotime('tomorrow 03:00:00'), 'daily', 'limiter_mkp_pro_daily_cron');
    }
}

// Registra hooks de ativação/desativação
register_activation_hook(__FILE__, 'activate_limiter_mkp_pro');
register_activation_hook(__FILE__, 'limiter_mkp_pro_schedule_cron_on_activation');
register_deactivation_hook(__FILE__, 'deactivate_limiter_mkp_pro');
register_deactivation_hook(__FILE__, 'limiter_mkp_pro_unschedule_cron_on_deactivation');

/**
 * Inicializa o plugin após o WordPress carregar
 */
function limiter_mkp_pro_init() {
    // Carrega traduções
    load_plugin_textdomain(
        'limiter-mkp-pro',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );

    // Verifica se é multisite (requisito obrigatório)
    if (!is_multisite()) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            _e('Limiter MKP Pro requer WordPress Multisite. O plugin foi desativado.', 'limiter-mkp-pro');
            echo '</p></div>';
        });

        // Desativa o plugin
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins(plugin_basename(__FILE__));
        return;
    }
    
    // Carrega classes principais
    require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-limiter-mkp-pro.php';
    require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
    require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
    require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-backup-scheduler.php';
    
    // Inicializa o gerenciador do ciclo de vida
    Limiter_MKP_Pro_Lifecycle_Manager::init();

    // Inicializa o agendador de backups
    Limiter_MKP_Pro_Backup_Scheduler::init();

    // Inicializa o plugin principal
    $plugin = new Limiter_MKP_Pro();
    $plugin->run();
}

/**
 * Adiciona itens de menu para o ciclo de vida
 */
function limiter_mkp_pro_add_lifecycle_menu_items($menu_items) {
    $menu_items['lifecycle'] = [
        'title' => __('Ciclo de Vida', 'limiter-mkp-pro'),
        'capability' => 'manage_network',
        'callback' => 'display_lifecycle_settings_page'
    ];
    $menu_items['churn'] = [
        'title' => __('Gestão de Churn', 'limiter-mkp-pro'),
        'capability' => 'manage_network',
        'callback' => 'display_churn_dashboard_page'
    ];
    return $menu_items;
}
add_filter('limiter_mkp_pro_admin_menu_items', 'limiter_mkp_pro_add_lifecycle_menu_items');

/**
 * Funções de Callback para as páginas de administração
 */
function display_lifecycle_settings_page() {
    if (!class_exists('Limiter_MKP_Pro_Admin')) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-admin.php';
    }
    $admin = new Limiter_MKP_Pro_Admin('limiter-mkp-pro', LIMITER_MKP_PRO_VERSION);
    $admin->display_lifecycle_page();
}

function display_churn_dashboard_page() {
    if (!class_exists('Limiter_MKP_Pro_Admin')) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-admin.php';
    }
    $admin = new Limiter_MKP_Pro_Admin('limiter-mkp-pro', LIMITER_MKP_PRO_VERSION);
    $admin->display_churn_page();
}

/**
 * Handlers AJAX para o ciclo de vida
 */
function limiter_mkp_pro_register_lifecycle_ajax_handlers() {
    // Handler para salvar configurações do ciclo de vida
    add_action('wp_ajax_limiter_save_lifecycle_settings', function() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-lifecycle-handlers.php';
        $handler = new Limiter_MKP_Pro_Lifecycle_Handlers('limiter-mkp-pro', LIMITER_MKP_PRO_VERSION);
        $handler->handle_save_lifecycle_settings();
    });
    // Handler para verificação manual
    add_action('wp_ajax_limiter_run_manual_check', function() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-lifecycle-handlers.php';
        $handler = new Limiter_MKP_Pro_Lifecycle_Handlers('limiter-mkp-pro', LIMITER_MKP_PRO_VERSION);
        $handler->handle_run_manual_check();
    });
    // Handler para enviar email customizado
    add_action('wp_ajax_limiter_send_custom_email', function() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-lifecycle-handlers.php';
        $handler = new Limiter_MKP_Pro_Lifecycle_Handlers('limiter-mkp-pro', LIMITER_MKP_PRO_VERSION);
        $handler->handle_send_custom_email();
    });
    // Handler para estender período de carência
    add_action('wp_ajax_limiter_extend_grace_period', function() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-lifecycle-handlers.php';
        $handler = new Limiter_MKP_Pro_Lifecycle_Handlers('limiter-mkp-pro', LIMITER_MKP_PRO_VERSION);
        $handler->handle_extend_grace_period();
    });
}
add_action('init', 'limiter_mkp_pro_register_lifecycle_ajax_handlers');

/**
 * Registra endpoints REST para o ciclo de vida
 */
function limiter_mkp_pro_register_rest_routes() {
    register_rest_route('limiter/v1', '/lifecycle/status/(?P<blog_id>\d+)', [
        'methods' => 'GET',
        'callback' => function($request) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            $blog_id = $request->get_param('blog_id');
            return [
                'blog_id' => $blog_id,
                'status' => Limiter_MKP_Pro_Lifecycle_Manager::get_blog_status($blog_id),
                'days_in_status' => Limiter_MKP_Pro_Lifecycle_Manager::get_days_in_current_status($blog_id),
                'subscription_status' => get_blog_option($blog_id, 'limiter_subscription_status', 'unknown')
            ];
        },
        'permission_callback' => function() {
            return current_user_can('manage_network');
        }
    ]);
    register_rest_route('limiter/v1', '/churn/metrics', [
        'methods' => 'GET',
        'callback' => function() {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database-lifecycle.php';
            return Limiter_MKP_Pro_Database_Lifecycle::get_lifecycle_stats();
        },
        'permission_callback' => function() {
            return current_user_can('manage_network');
        }
    ]);
}
add_action('rest_api_init', 'limiter_mkp_pro_register_rest_routes');

/**
 * Adiciona webhooks para eventos do ciclo de vida
 */
function limiter_mkp_pro_setup_webhooks() {
    $webhooks = get_network_option(null, 'limiter_mkp_pro_webhooks', []);
    if (!empty($webhooks)) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-webhooks.php';
        Limiter_MKP_Pro_Webhooks::init($webhooks);
    }
}
add_action('plugins_loaded', 'limiter_mkp_pro_setup_webhooks');

/**
 * Adiciona scripts e estilos para o ciclo de vida
 */
function limiter_mkp_pro_enqueue_lifecycle_assets() {
    $screen = get_current_screen();
    if (strpos($screen->id, 'limiter-mkp-pro') !== false) {
        // CSS do ciclo de vida
        wp_enqueue_style(
            'limiter-mkp-pro-lifecycle',
            LIMITER_MKP_PRO_PLUGIN_URL . 'assets/css/lifecycle.css',
            [],
            LIMITER_MKP_PRO_VERSION
        );
        // JS do ciclo de vida
        wp_enqueue_script(
            'limiter-mkp-pro-lifecycle',
            LIMITER_MKP_PRO_PLUGIN_URL . 'assets/js/lifecycle.js',
            ['jquery', 'wp-util'],
            LIMITER_MKP_PRO_VERSION,
            true
        );
        wp_localize_script('limiter-mkp-pro-lifecycle', 'limiter_lifecycle', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('limiter_lifecycle_nonce'),
            'i18n' => [
                'confirm_action' => __('Tem certeza que deseja executar esta ação?', 'limiter-mkp-pro'),
                'processing' => __('Processando...', 'limiter-mkp-pro'),
                'success' => __('Ação concluída com sucesso!', 'limiter-mkp-pro'),
                'error' => __('Ocorreu um erro. Por favor, tente novamente.', 'limiter-mkp-pro')
            ]
        ]);
    }
}
add_action('admin_enqueue_scripts', 'limiter_mkp_pro_enqueue_lifecycle_assets');

/**
 * Hooks para eventos do WooCommerce Subscriptions
 */
function limiter_mkp_pro_woocommerce_hooks() {
    if (class_exists('WC_Subscriptions')) {
        add_action('woocommerce_subscription_status_updated', function($subscription, $new_status, $old_status) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            Limiter_MKP_Pro_Lifecycle_Manager::handle_subscription_status_change($subscription, $new_status, $old_status);
        }, 10, 3);
        add_action('woocommerce_subscription_payment_failed', function($subscription) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            Limiter_MKP_Pro_Lifecycle_Manager::handle_payment_failed($subscription);
        });
        add_action('woocommerce_subscription_payment_complete', function($subscription) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            Limiter_MKP_Pro_Lifecycle_Manager::handle_payment_complete($subscription);
        });
        add_action('woocommerce_subscription_cancelled', function($subscription) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            Limiter_MKP_Pro_Lifecycle_Manager::handle_cancellation($subscription);
        });
        add_action('woocommerce_subscription_expired', function($subscription) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            Limiter_MKP_Pro_Lifecycle_Manager::handle_expiration($subscription);
        });
    }
}
add_action('woocommerce_loaded', 'limiter_mkp_pro_woocommerce_hooks');

/**
 * Inicializa o plugin
 */
function run_limiter_mkp_pro() {
    // ALTERAÇÃO: Mudança de 'plugins_loaded' com prioridade 15 para 'init' com prioridade 20
    // Isso garante que o WooCommerce já carregou suas traduções, evitando o Notice.
    add_action('init', 'limiter_mkp_pro_init', 20);
}

// Executa o plugin
run_limiter_mkp_pro();