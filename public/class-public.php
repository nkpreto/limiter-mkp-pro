<?php
/**
 * A funcionalidade voltada para o público (frontend) do plugin.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/public
 */
class Limiter_MKP_Pro_Public {
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_menu', array($this, 'add_subdomain_dashboard_menu'));
        add_shortcode('mkp_dashboard_paginas', array($this, 'paginas_shortcode'));
    }
    
    /**
     * Verifica se a página atual contém um dos shortcodes do plugin.
     * Helper para otimização de carregamento de assets.
     *
     * @return bool
     */
    private function has_mkp_shortcodes() {
        global $post;
        
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return false;
        }

        // Lista de todos os shortcodes usados pelo plugin
        $shortcodes = array(
            'mkp_dashboard_paginas',
            'mkp_registration_form',
            'mkp_subdomain_checker',
            'mkp_subdomain_configuration'
        );

        foreach ( $shortcodes as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registra os estilos públicos do plugin.
     * OTIMIZAÇÃO: Carrega apenas se o shortcode estiver presente na página.
     *
     * @since    1.4.0
     */
    public function enqueue_styles() {
        // Se não tiver shortcode, não carrega o CSS
        if ( ! $this->has_mkp_shortcodes() ) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/public.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    /**
     * Registra os scripts públicos do plugin.
     * OTIMIZAÇÃO: Carrega apenas se o shortcode estiver presente na página.
     *
     * @since    1.4.0
     */
    public function enqueue_scripts() {
        // Se não tiver shortcode, não carrega o JS
        if ( ! $this->has_mkp_shortcodes() ) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/public.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script($this->plugin_name, 'limiter_mkp_pro_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('limiter_mkp_pro_public_nonce')
        ));
    }
    
    public function add_subdomain_dashboard_menu() {
        if (is_main_site() || !current_user_can('manage_options')) return;
        
        $blog_id = get_current_blog_id();
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        
        // Verifica se o subdomínio existe no sistema antes de adicionar o menu
        $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        if (!$sub) return;
        
        add_menu_page(
            'Meu MKP Pro',
            'Meu MKP Pro',
            'manage_options',
            'meu-mkp-pro',
            array($this, 'dashboard_page'),
            'dashicons-tickets',
            60
        );
        add_submenu_page(
            'meu-mkp-pro',
            'Minhas Páginas',
            'Minhas Páginas',
            'manage_options',
            'meu-mkp-pro-paginas',
            array($this, 'paginas_page')
        );
        add_submenu_page(
            'meu-mkp-pro',
            'Upgrade de Plano',
            'Upgrade de Plano',
            'manage_options',
            'meu-mkp-pro-upgrade',
            array($this, 'upgrade_page')
        );
    }
    
    public function dashboard_page() {
        $blog_id = get_current_blog_id();
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database-get-estatisticas.php';
        // Usa a versão com cache das estatísticas se disponível, ou busca direto
        $estatisticas = Limiter_MKP_Pro_Database_Get_Estatisticas::get_estatisticas_blog($blog_id);
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'public/partials/client-dashboard.php';
    }
    
    public function paginas_page() {
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'public/partials/client-paginas.php';
    }
    
    public function upgrade_page() {
        $blog_id = get_current_blog_id();
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        $regiao = !empty($sub->regiao) ? $sub->regiao : 'global';
        $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', []);
        
        $url = ($regiao === 'jp') ? 
            ($config['planos_url_jp'] ?? home_url('/planos-jp/')) : 
            ($config['planos_url_global'] ?? home_url('/planos/'));
            
        echo '<div class="wrap"><h1>Upgrade de Plano</h1>';
        echo '<p>Acesse nossa loja para alterar seu plano:</p>';
        echo '<a href="' . esc_url($url) . '" class="button button-primary" target="_blank">🛒 Ver Planos</a>';
        echo '</div>';
    }
    
    public function paginas_shortcode() {
        ob_start();
        include LIMITER_MKP_PRO_PLUGIN_DIR . 'public/partials/client-paginas.php';
        return ob_get_clean();
    }
}