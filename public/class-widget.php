<?php
/**
 * Widget Otimizado para o dashboard do subdomínio.
 * @since 2.1.0
 */
class Limiter_MKP_Pro_Widget {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'limiter_mkp_pro_dashboard_widget',
            __('Limiter MKP Pro - Status do Plano', 'limiter-mkp-pro'),
            array($this, 'display_dashboard_widget')
        );
    }

    public function display_dashboard_widget() {
        $blog_id = get_current_blog_id();
        
        // Carregamento otimizado de classes
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-configuracoes.php';
        
        // Busca dados do cache (não faz query pesada)
        $subdominio = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        
        // Configurações
        $config = Limiter_MKP_Pro_Configuracoes::get_mensagem('widget_sem_plano'); // Uso direto do método estático se disponível
        if (!$config) {
             $config_inst = new Limiter_MKP_Pro_Configuracoes('limiter-mkp-pro', '1.0.0');
             $all_configs = $config_inst->get_configuracoes();
             $msg_sem_plano = $all_configs['widget_sem_plano'];
             $msg_alerta = $all_configs['widget_alerta_limite'];
             $msg_auto = $all_configs['widget_sistema_automatico'];
             $txt_botao = $all_configs['botao_ver_planos'];
        }

        if (!$subdominio || empty($subdominio->plano_id)) {
            echo '<div class="notice notice-warning"><p>' . esc_html($msg_sem_plano ?? 'Sem plano configurado.') . '</p></div>';
            return;
        }
        
        $plano = Limiter_MKP_Pro_Database::get_plano($subdominio->plano_id);
        if (!$plano) return;
        
        // --- OTIMIZAÇÃO AQUI ---
        // Em vez de wp_count_posts(), usamos a função que lê do cache
        $total_pages = Limiter_MKP_Pro_Database::count_limited_content($blog_id);
        // -----------------------

        $limite = !empty($subdominio->limite_personalizado) ? (int) $subdominio->limite_personalizado : (int) $plano->limite_paginas;
        $percentual = $limite > 0 ? min(100, round(($total_pages / $limite) * 100)) : 0;
        
        ?>
        <div class="limiter-mkp-pro-widget">
            <div class="limiter-mkp-pro-logo-header">
                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../images/marketing-place-store-logo.png'); ?>" 
                     alt="Marketing Place Store" 
                     style="max-width: 150px; height: auto; margin: 0 auto 10px; display: block;">
            </div>

            <div class="limiter-widget-section">
                <p style="font-size: 14px; margin-bottom: 5px;"><strong>Plano:</strong> <?php echo esc_html($plano->nome); ?></p>
                <p style="font-size: 14px;"><strong>Páginas:</strong> <?php echo esc_html($total_pages); ?> / <?php echo esc_html($limite); ?></p>
                
                <div class="limiter-progress-bar" style="background:#eee; height:15px; border-radius:10px; overflow:hidden; margin-top:5px;">
                    <div style="width: <?php echo esc_attr($percentual); ?>%; 
                                height:100%; 
                                background-color: <?php echo $percentual >= 90 ? '#dc3545' : ($percentual >= 70 ? '#ffc107' : '#28a745'); ?>;
                                text-align:center; 
                                color:white; 
                                font-size:10px; 
                                line-height:15px;">
                        <?php echo esc_html($percentual); ?>%
                    </div>
                </div>
            </div>

            <div class="limiter-widget-section" style="margin-top: 15px; text-align: center;">
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="button button-primary" target="_blank">
                    <?php echo esc_html($txt_botao ?? 'Fazer Upgrade'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}