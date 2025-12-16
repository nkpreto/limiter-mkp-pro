<?php
/**
 * Template para o dashboard completo do subdomínio.
 *
 * @since      1.4.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/public/partials
 */
?>
<div class="limiter-mkp-pro-dashboard-wrapper">
    <div class="limiter-mkp-pro-dashboard-header">
        <div class="limiter-mkp-pro-logo-header">
            <h1>Limiter MKP Pro</h1>
        </div>
        <div class="limiter-mkp-pro-subtitle">
            <h2><?php _e('Dashboard de Controle de Subdomínios', 'limiter-mkp-pro'); ?></h2>
        </div>
    </div>
    <?php if ($estatisticas['tem_plano']) : ?>
        <div class="limiter-mkp-pro-stats-cards">
            <div class="limiter-mkp-pro-card">
                <div class="limiter-mkp-pro-card-header">
                    <h3><?php _e('Plano Atual', 'limiter-mkp-pro'); ?></h3>
                    <span class="limiter-mkp-pro-card-icon dashicons dashicons-chart-pie"></span>
                </div>
                <div class="limiter-mkp-pro-card-content">
                    <div class="limiter-mkp-pro-card-value"><?php echo esc_html($estatisticas['plano_nome']); ?></div>
                    <div class="limiter-mkp-pro-card-description"><?php _e('Limite de', 'limiter-mkp-pro'); ?> <?php echo esc_html($estatisticas['limite_paginas']); ?> <?php _e('páginas', 'limiter-mkp-pro'); ?></div>
                </div>
            </div>
            <div class="limiter-mkp-pro-card">
                <div class="limiter-mkp-pro-card-header">
                    <h3><?php _e('Páginas Utilizadas', 'limiter-mkp-pro'); ?></h3>
                    <span class="limiter-mkp-pro-card-icon dashicons dashicons-admin-page"></span>
                </div>
                <div class="limiter-mkp-pro-card-content">
                    <div class="limiter-mkp-pro-card-value"><?php echo esc_html($estatisticas['paginas_total']); ?></div>
                    <div class="limiter-mkp-pro-card-description"><?php _e('Publicadas + Lixeira', 'limiter-mkp-pro'); ?></div>
                </div>
            </div>
            <div class="limiter-mkp-pro-card">
                <div class="limiter-mkp-pro-card-header">
                    <h3><?php _e('Restantes', 'limiter-mkp-pro'); ?></h3>
                    <span class="limiter-mkp-pro-card-icon dashicons dashicons-plus-alt"></span>
                </div>
                <div class="limiter-mkp-pro-card-content">
                    <div class="limiter-mkp-pro-card-value"><?php echo esc_html($estatisticas['paginas_restantes']); ?></div>
                    <div class="limiter-mkp-pro-card-description"><?php _e('Páginas disponíveis', 'limiter-mkp-pro'); ?></div>
                </div>
            </div>
            <div class="limiter-mkp-pro-card">
                <div class="limiter-mkp-pro-card-header">
                    <h3><?php _e('Uso', 'limiter-mkp-pro'); ?></h3>
                    <span class="limiter-mkp-pro-card-icon dashicons dashicons-leftright"></span>
                </div>
                <div class="limiter-mkp-pro-card-content">
                    <div class="limiter-mkp-pro-card-value"><?php echo esc_html($estatisticas['percentual_uso']); ?>%</div>
                    <div class="limiter-mkp-pro-card-description"><?php _e('Do limite total', 'limiter-mkp-pro'); ?></div>
                </div>
            </div>
        </div>
        <div class="limiter-mkp-pro-progress-section">
            <h3><?php _e('Utilização do Plano', 'limiter-mkp-pro'); ?></h3>
            <div class="limiter-mkp-pro-progress-bar">
                <div class="limiter-mkp-pro-progress <?php echo $estatisticas['percentual_uso'] > 90 ? 'limiter-mkp-pro-progress-warning' : ''; ?>" 
                     style="width: <?php echo esc_attr($estatisticas['percentual_uso']); ?>%;">
                    <?php echo esc_html($estatisticas['percentual_uso']); ?>%
                </div>
            </div>
            <?php if ($estatisticas['percentual_uso'] >= 70) : ?>
                <div class="limiter-mkp-pro-progress-warning-text">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('Você está próximo do limite. Faça upgrade na loja.', 'limiter-mkp-pro'); ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="limiter-mkp-pro-solicitations-section">
            <h3><?php _e('Mudança de Plano', 'limiter-mkp-pro'); ?></h3>
            <div class="notice notice-info">
                <p>
                    <strong>Automação Total via WooCommerce</strong><br>
                    Não é mais necessário solicitar manualmente. Basta comprar um novo plano na loja.
                </p>
                <p>
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="button button-primary" target="_blank">
                        🛒 Acessar Loja de Planos
                    </a>
                </p>
            </div>
        </div>
    <?php else : ?>
        <div class="limiter-mkp-pro-no-plan">
            <div class="limiter-mkp-pro-no-plan-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <h3><?php _e('Plano não configurado', 'limiter-mkp-pro'); ?></h3>
            <p><?php _e('Este subdomínio não possui um plano. Entre em contato com o administrador da rede.', 'limiter-mkp-pro'); ?></p>
        </div>
    <?php endif; ?>
</div>