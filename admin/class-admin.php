<?php
/**
 * Classe responsável pelo painel de administração do plugin.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin
 */
if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Admin {
    
    private $plugin_name;
    private $version;

    /**
     * Inicializa a classe e define suas propriedades.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       O nome do plugin.
     * @param    string    $version           A versão do plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = sanitize_text_field($plugin_name);
        $this->version = sanitize_text_field($version);
    }

    /**
     * Carrega CSS do admin.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/admin.css',
            array(),
            $this->version,
            'all'
        
        );
    }

    /**
     * Carrega JS do admin.
     * * ATUALIZAÇÃO: Adicionada dependência 'jquery-ui-autocomplete' para busca AJAX de sites.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Enqueue jQuery UI Autocomplete para a busca de sites otimizada
        wp_enqueue_script('jquery-ui-autocomplete');

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/admin.js',
            array('jquery', 'jquery-ui-autocomplete'), // Dependência necessária para o campo de busca
            $this->version,
            true
        );

        wp_localize_script($this->plugin_name, 'limiter_mkp_pro_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('limiter_mkp_pro_admin_nonce'),
            'messages' => array(
                'confirm_delete' => __('Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.', 'limiter-mkp-pro'),
                'success' => __('Operação realizada com sucesso!', 'limiter-mkp-pro'),
                'error' => __('Ocorreu um erro. Por favor, tente novamente.', 'limiter-mkp-pro')
            )
        ));
    }

    /**
     * Menu no admin da rede.
     *
     * @since    1.0.0
     */
    public function add_network_admin_menu() {
        if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
            return;
        }
        
        add_menu_page(
            __('Limiter MKP Pro', 'limiter-mkp-pro'),
            __('Limiter MKP Pro', 'limiter-mkp-pro'),
            'manage_network',
            'limiter-mkp-pro',
            array($this, 'display_dashboard_page'),
            'dashicons-chart-area',
            3
        );

        add_submenu_page('limiter-mkp-pro', __('Dashboard', 'limiter-mkp-pro'), __('Dashboard', 'limiter-mkp-pro'),
            'manage_network', 'limiter-mkp-pro', array($this, 'display_dashboard_page'));

        add_submenu_page('limiter-mkp-pro', __('Planos', 'limiter-mkp-pro'), __('Planos', 'limiter-mkp-pro'),
            'manage_network', 'limiter-mkp-pro-planos', array($this, 'display_planos_page'));

        add_submenu_page('limiter-mkp-pro', __('Subdomínios', 'limiter-mkp-pro'), __('Subdomínios', 'limiter-mkp-pro'),
            'manage_network', 'limiter-mkp-pro-subdominios', array($this, 'display_subdominios_page'));

        add_submenu_page('limiter-mkp-pro', __('Adicionar Subdomínio', 'limiter-mkp-pro'), __('Adicionar Subdomínio', 'limiter-mkp-pro'),
            'manage_network', 'limiter-mkp-pro-adicionar-subdominio', array($this, 'display_adicionar_subdominio_page'));

        add_submenu_page('limiter-mkp-pro', __('Configurações', 'limiter-mkp-pro'), __('Configurações', 'limiter-mkp-pro'),
            'manage_network', 'limiter-mkp-pro-configuracoes', array($this, 'display_configuracoes_page'));
            
        // NOVO MENU FERRAMENTAS
        add_submenu_page('limiter-mkp-pro', __('Ferramentas', 'limiter-mkp-pro'), __('Ferramentas', 'limiter-mkp-pro'),
            'manage_network', 'limiter-mkp-pro-tools', array($this, 'display_tools_page'));

        add_submenu_page('limiter-mkp-pro', __('Logs', 'limiter-mkp-pro'), __('Logs', 'limiter-mkp-pro'),
            'manage_network', 'limiter-mkp-pro-logs', array($this, 'display_logs_page'));

        // Menus de Ciclo de Vida e Churn
        add_submenu_page('limiter-mkp-pro', 
            __('Ciclo de Vida', 'limiter-mkp-pro'), 
            __('Ciclo de Vida', 'limiter-mkp-pro'),
            'manage_network', 
            'limiter-mkp-pro-lifecycle', 
            array($this, 'display_lifecycle_page')
        );

        add_submenu_page('limiter-mkp-pro', 
            __('Gestão de Churn', 'limiter-mkp-pro'), 
            __('Gestão de Churn', 'limiter-mkp-pro'),
            'manage_network', 
            'limiter-mkp-pro-churn', 
            array($this, 'display_churn_page')
        );
    }

    /**
     * Dashboard.
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'limiter-mkp-pro'));
        }
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database-get-estatisticas.php';
        $estatisticas = Limiter_MKP_Pro_Database_Get_Estatisticas::get_estatisticas_gerais();
        $distribuicao_planos = Limiter_MKP_Pro_Database_Get_Estatisticas::get_distribuicao_planos();
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Página de planos.
     *
     * @since    1.0.0
     */
    public function display_planos_page() {
        if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'limiter-mkp-pro'));
        }
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $planos = Limiter_MKP_Pro_Database::get_planos();
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/partials/planos.php';
    }

    /**
     * Página de subdomínios.
     *
     * @since    1.0.0
     */
    public function display_subdominios_page() {
        if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'limiter-mkp-pro'));
        }
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $subdominios = Limiter_MKP_Pro_Database::get_subdominios();
        $planos = Limiter_MKP_Pro_Database::get_planos();
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/partials/subdominios.php';
    }

    /**
     * Página para adicionar subdomínio.
     * * OTIMIZAÇÃO: Removida a chamada get_sites() que carregava todos os sites da rede.
     * A seleção agora é feita via AJAX Search no partial.
     *
     * @since    1.0.0
     */
    public function display_adicionar_subdominio_page() {
        if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'limiter-mkp-pro'));
        }
        
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        // Mantemos os subdomínios já cadastrados para validação visual se necessário, 
        // mas em redes gigantes isso também poderia ser otimizado no futuro.
        $subdominios = Limiter_MKP_Pro_Database::get_subdominios();
        $blog_ids_cadastrados = array_map(function($s) { return intval($s->blog_id); }, $subdominios);
        
        $planos = Limiter_MKP_Pro_Database::get_planos();
        // O partial agora usará o campo de busca AJAX
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/partials/adicionar-subdominio.php';
    }

    /**
     * Página de configurações.
     *
     * @since    1.0.0
     */
    public function display_configuracoes_page() {
        if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'limiter-mkp-pro'));
        }
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $config = wp_parse_args($config, array(
            'email_notificacao' => 'alterar-plano@marketing-place.store',
            'limite_alerta' => 1,
            'mensagem_limite' => 'Desculpe o inconveniente, mas seu plano tem suporte a criação de [X] páginas. Considere fazer upgrade.',
            'mensagem_alerta' => 'Você está na penúltima página. Considere fazer upgrade.'
        ));
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/partials/configuracoes.php';
    }

    /**
     * NOVA PÁGINA: Ferramentas (Recursos Ocultos)
     */
    public function display_tools_page() {
        if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'limiter-mkp-pro'));
        }
        // Inclui o template de ferramentas
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/partials/ferramentas.php';
    }

    /**
     * Página de logs.
     *
     * @since    1.0.0
     */
    public function display_logs_page() {
        if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'limiter-mkp-pro'));
        }
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $blog_id = isset($_GET['blog_id']) ? intval($_GET['blog_id']) : null;
        $logs = Limiter_MKP_Pro_Database::get_logs(100, $blog_id);
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/partials/logs.php';
    }

    // Métodos para exibir as páginas de Ciclo de Vida e Gestão de Churn
    public function display_lifecycle_page() {
        if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'limiter-mkp-pro'));
        }
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/partials/lifecycle-settings.php';
    }

    public function display_churn_page() {
        if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'limiter-mkp-pro'));
        }
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/partials/churn-dashboard.php';
    }
}