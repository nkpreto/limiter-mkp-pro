<?php
/**
 * Template para o widget no dashboard do subdomínio.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/public/partials
 */

// Segurança: impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="limiter-mkp-pro-widget">
    <div class="limiter-mkp-pro-logo">
        <img src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'images/marketing-place-logo.jpeg'); ?>" alt="Marketing Place Store" style="max-width: 50%; height: auto; display: block; margin: 0 auto;">
    </div>

    <?php if (isset($estatisticas['tem_plano']) && $estatisticas['tem_plano']) : ?>
        <div class="limiter-mkp-pro-info">
            <h3><?php _e('Plano Atual', 'limiter-mkp-pro'); ?>: <span class="limiter-mkp-pro-plano"><?php echo esc_html(isset($estatisticas['plano_nome']) ? $estatisticas['plano_nome'] : ''); ?></span></h3>
            
            <div class="limiter-mkp-pro-progress-container">
                <div class="limiter-mkp-pro-progress-bar">
                    <div class="limiter-mkp-pro-progress" style="width: <?php echo esc_attr(isset($estatisticas['percentual_uso']) ? $estatisticas['percentual_uso'] : 0); ?>%;">
                        <span class="limiter-mkp-pro-progress-text"><?php echo esc_html(isset($estatisticas['percentual_uso']) ? $estatisticas['percentual_uso'] : 0); ?>%</span>
                    </div>
                </div>
            </div>

            <div class="limiter-mkp-pro-stats">
                <div class="limiter-mkp-pro-stat">
                    <span class="limiter-mkp-pro-stat-label"><?php _e('Limite', 'limiter-mkp-pro'); ?>:</span>
                    <span class="limiter-mkp-pro-stat-value"><?php echo esc_html(isset($estatisticas['limite_paginas']) ? $estatisticas['limite_paginas'] : 0); ?> <?php _e('páginas', 'limiter-mkp-pro'); ?></span>
                </div>
                <div class="limiter-mkp-pro-stat">
                    <span class="limiter-mkp-pro-stat-label"><?php _e('Utilizadas', 'limiter-mkp-pro'); ?>:</span>
                    <span class="limiter-mkp-pro-stat-value"><?php echo esc_html(isset($estatisticas['paginas_total']) ? $estatisticas['paginas_total'] : 0); ?> <?php _e('páginas', 'limiter-mkp-pro'); ?></span>
                </div>
                <div class="limiter-mkp-pro-stat">
                    <span class="limiter-mkp-pro-stat-label"><?php _e('Restantes', 'limiter-mkp-pro'); ?>:</span>
                    <span class="limiter-mkp-pro-stat-value"><?php echo esc_html(isset($estatisticas['paginas_restantes']) ? $estatisticas['paginas_restantes'] : 0); ?> <?php _e('páginas', 'limiter-mkp-pro'); ?></span>
                </div>
            </div>

            <?php
            $blog_id = get_current_blog_id();
            
            // Recupera dados do banco de forma segura
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            $subdominio = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
            $plano = ($subdominio) ? Limiter_MKP_Pro_Database::get_plano($subdominio->plano_id) : null;

            if ($plano) {
                // Obtém limite personalizado de inodes ou usa o do plano
                $limite_inodes = !empty($subdominio->limite_personalizado_inodes) ? 
                                 $subdominio->limite_personalizado_inodes : 
                                 $plano->limite_inodes;

                // CORREÇÃO CRÍTICA DE PERFORMANCE:
                // Substituída a varredura de disco recursiva por leitura de cache ou contagem no banco
                $inode_count = get_option('limiter_mkp_pro_inode_count');

                if ($inode_count === false) {
                    // Fallback rápido via banco de dados se o cache não existir (muito mais rápido que ler disco)
                    global $wpdb;
                    $inode_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'");
                    // Atualiza a option para futuras leituras
                    update_option('limiter_mkp_pro_inode_count', $inode_count, false);
                }

                $inode_percent = $limite_inodes > 0 ? min(100, round(($inode_count / $limite_inodes) * 100)) : 0;
                
                // Formata informações adicionais sobre o limite
                $limite_tipo = !empty($subdominio->limite_personalizado_inodes) ? 
                               __('(Personalizado)', 'limiter-mkp-pro') : 
                               __('(Do plano)', 'limiter-mkp-pro');
            ?>
                <div class="limiter-mkp-pro-stat" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
                    <span class="limiter-mkp-pro-stat-label"><?php _e('Arquivos (Mídia)', 'limiter-mkp-pro'); ?>:</span>
                    <span class="limiter-mkp-pro-stat-value"><?php echo esc_html($inode_count); ?> / <?php echo esc_html($limite_inodes); ?> <small style="font-weight:normal;"><?php echo esc_html($limite_tipo); ?></small></span>
                </div>
                
                <div class="limiter-mkp-pro-progress-container" style="margin-top: 5px;">
                    <h4 style="margin-bottom: 5px; font-size: 12px; color: #666;"><?php _e('Uso de Armazenamento', 'limiter-mkp-pro'); ?></h4>
                    <div class="limiter-mkp-pro-progress-bar">
                        <div class="limiter-mkp-pro-progress" style="width: <?php echo esc_attr($inode_percent); ?>%; background-color: <?php echo $inode_percent > 90 ? '#e74a3b' : ($inode_percent > 70 ? '#f39c12' : '#2ecc71'); ?>;">
                            <span class="limiter-mkp-pro-progress-text"><?php echo esc_html($inode_percent); ?>%</span>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php 
            if (isset($estatisticas['mostrar_alerta']) && $estatisticas['mostrar_alerta']) : 
                // Obtém a mensagem personalizada para o alerta
                $configuracoes = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
                $mensagem_alerta = isset($configuracoes['mensagem_alerta']) ? 
                                   $configuracoes['mensagem_alerta'] : 
                                   __('Você está na penúltima página. Considere fazer upgrade.', 'limiter-mkp-pro');
            ?>
                <div class="limiter-mkp-pro-alert" style="margin-top: 15px; padding: 10px 15px; background-color: #fcf8e3; border-left: 4px solid #f0ad4e; color: #8a6d3b;">
                    <p><strong><?php _e('Atenção!', 'limiter-mkp-pro'); ?></strong> <?php echo esc_html($mensagem_alerta); ?></p>
                </div>
            <?php endif; ?>

            <div class="limiter-mkp-pro-actions">
                <div class="notice notice-info" style="margin: 15px 0 0 0;">
                    <p style="margin-bottom: 10px;">
                        <strong><?php _e('Sistema Automático via WooCommerce', 'limiter-mkp-pro'); ?></strong><br>
                        <?php _e('Para mudar de plano, acesse nossa loja.', 'limiter-mkp-pro'); ?>
                    </p>
                    <p>
                        <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#'); ?>" class="button button-primary" target="_blank" style="width: 100%; text-align: center;">
                            <?php _e('🛒 Ver Planos e Fazer Upgrade', 'limiter-mkp-pro'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="limiter-mkp-pro-no-plan">
            <p><?php _e('Este subdomínio não possui um plano configurado. Entre em contato com o administrador da rede.', 'limiter-mkp-pro'); ?></p>
        </div>
    <?php endif; ?>
</div>