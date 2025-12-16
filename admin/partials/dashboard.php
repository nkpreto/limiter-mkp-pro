<?php
/**
 * Template para o dashboard do painel de administração.
 *
 * @since      1.4.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin/partials
 */

function limiter_mkp_get_mrr() {
    if (!class_exists('WC_Subscriptions') || !function_exists('wcs_get_subscriptions')) {
        return 0;
    }
    $subscriptions = wcs_get_subscriptions(['subscription_status' => 'active', 'limit' => -1]);
    $total = 0;
    foreach ($subscriptions as $sub) {
        foreach ($sub->get_items() as $item) {
            $product_id = $item->get_product_id();
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
            $plano_id = Limiter_MKP_Pro_WooCommerce_Integration::get_plano_by_product($product_id);
            if ($plano_id) {
                $total += $item->get_total();
                break;
            }
        }
    }
    return round($total, 2);
}

function limiter_mkp_get_active_subscriptions_count() {
    if (!class_exists('WC_Subscriptions')) {
        return 0;
    }
    $subscriptions = wcs_get_subscriptions(['subscription_status' => 'active', 'limit' => -1]);
    $count = 0;
    foreach ($subscriptions as $sub) {
        foreach ($sub->get_items() as $item) {
            $product_id = $item->get_product_id();
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
            if (Limiter_MKP_Pro_WooCommerce_Integration::get_plano_by_product($product_id)) {
                $count++;
                break;
            }
        }
    }
    return $count;
}

$mrr = limiter_mkp_get_mrr();
$active_subs = limiter_mkp_get_active_subscriptions_count();
?>
<div class="wrap limiter-mkp-pro-admin-dashboard">
    <h1><?php _e('Limiter MKP Pro - Dashboard', 'limiter-mkp-pro'); ?></h1>

    <div class="limiter-mkp-pro-admin-stats-cards">
        <div class="limiter-mkp-pro-admin-card">
            <div class="limiter-mkp-pro-admin-card-header">
                <h3><?php _e('Subdomínios Ativos', 'limiter-mkp-pro'); ?></h3>
                <span class="dashicons dashicons-admin-site"></span>
            </div>
            <div class="limiter-mkp-pro-admin-card-content">
                <div class="limiter-mkp-pro-admin-card-value"><?php echo esc_html($estatisticas['total_subdominios']); ?></div>
                <div class="limiter-mkp-pro-admin-card-description"><?php _e('Sites gerenciados', 'limiter-mkp-pro'); ?></div>
            </div>
            <div class="limiter-mkp-pro-admin-card-footer">
                <a href="<?php echo network_admin_url('admin.php?page=limiter-mkp-pro-subdominios'); ?>" class="button button-secondary">
                    <?php _e('Ver Detalhes', 'limiter-mkp-pro'); ?>
                </a>
            </div>
        </div>

        <div class="limiter-mkp-pro-admin-card">
            <div class="limiter-mkp-pro-admin-card-header">
                <h3><?php _e('Assinaturas Ativas', 'limiter-mkp-pro'); ?></h3>
                <span class="dashicons dashicons-products"></span>
            </div>
            <div class="limiter-mkp-pro-admin-card-content">
                <div class="limiter-mkp-pro-admin-card-value"><?php echo esc_html($active_subs); ?></div>
                <div class="limiter-mkp-pro-admin-card-description"><?php _e('WooCommerce Subscriptions', 'limiter-mkp-pro'); ?></div>
            </div>
            <div class="limiter-mkp-pro-admin-card-footer">
                <a href="<?php echo admin_url('edit.php?post_type=shop_subscription'); ?>" class="button button-secondary" target="_blank">
                    <?php _e('Ver Assinaturas', 'limiter-mkp-pro'); ?>
                </a>
            </div>
        </div>

        <div class="limiter-mkp-pro-admin-card">
            <div class="limiter-mkp-pro-admin-card-header">
                <h3><?php _e('Receita Mensal (MRR)', 'limiter-mkp-pro'); ?></h3>
                <span class="dashicons dashicons-money"></span>
            </div>
            <div class="limiter-mkp-pro-admin-card-content">
                <div class="limiter-mkp-pro-admin-card-value"><?php echo wc_price($mrr); ?></div>
                <div class="limiter-mkp-pro-admin-card-description"><?php _e('Receita recorrente', 'limiter-mkp-pro'); ?></div>
            </div>
            <div class="limiter-mkp-pro-admin-card-footer">
                <a href="<?php echo admin_url('admin.php?page=wc-reports'); ?>" class="button button-secondary" target="_blank">
                    <?php _e('Relatórios', 'limiter-mkp-pro'); ?>
                </a>
            </div>
        </div>

        <div class="limiter-mkp-pro-admin-card">
            <div class="limiter-mkp-pro-admin-card-header">
                <h3><?php _e('Gerenciar Planos', 'limiter-mkp-pro'); ?></h3>
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="limiter-mkp-pro-admin-card-content">
                <div class="limiter-mkp-pro-admin-card-value"><?php _e('Produtos WooCommerce', 'limiter-mkp-pro'); ?></div>
                <div class="limiter-mkp-pro-admin-card-description"><?php _e('Sistema automático', 'limiter-mkp-pro'); ?></div>
            </div>
            <div class="limiter-mkp-pro-admin-card-footer">
                <a href="<?php echo wc_get_page_permalink('shop'); ?>" class="button button-primary" target="_blank">
                    🛒 <?php _e('Ir para a Loja', 'limiter-mkp-pro'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="limiter-mkp-pro-admin-section">
        <h2><?php _e('Distribuição de Planos', 'limiter-mkp-pro'); ?></h2>
        <div class="limiter-mkp-pro-admin-planos-distribuicao">
            <?php if (!empty($distribuicao_planos)) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Plano', 'limiter-mkp-pro'); ?></th>
                            <th><?php _e('Subdomínios', 'limiter-mkp-pro'); ?></th>
                            <th><?php _e('Distribuição', 'limiter-mkp-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_subdominios = $estatisticas['total_subdominios'];
                        foreach ($distribuicao_planos as $plano) : 
                            $percentual = $total_subdominios > 0 ? round(($plano->total / $total_subdominios) * 100) : 0;
                        ?>
                            <tr>
                                <td><?php echo esc_html($plano->nome); ?></td>
                                <td><?php echo esc_html($plano->total); ?></td>
                                <td>
                                    <div class="limiter-mkp-pro-admin-progress-bar">
                                        <div class="limiter-mkp-pro-admin-progress" style="width: <?php echo esc_attr($percentual); ?>%;">
                                            <?php echo esc_html($percentual); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('Nenhum plano encontrado.', 'limiter-mkp-pro'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="limiter-mkp-pro-admin-section">
        <h2><?php _e('Ações Rápidas', 'limiter-mkp-pro'); ?></h2>
        <div class="limiter-mkp-pro-admin-quick-actions">
            <a href="<?php echo network_admin_url('admin.php?page=limiter-mkp-pro-planos'); ?>" class="limiter-mkp-pro-admin-quick-action">
                <span class="dashicons dashicons-chart-pie"></span>
                <span class="limiter-mkp-pro-admin-quick-action-label"><?php _e('Gerenciar Planos', 'limiter-mkp-pro'); ?></span>
            </a>
            <a href="<?php echo network_admin_url('admin.php?page=limiter-mkp-pro-adicionar-subdominio'); ?>" class="limiter-mkp-pro-admin-quick-action">
                <span class="dashicons dashicons-plus"></span>
                <span class="limiter-mkp-pro-admin-quick-action-label"><?php _e('Adicionar Subdomínio', 'limiter-mkp-pro'); ?></span>
            </a>
            <a href="<?php echo wc_get_page_permalink('shop'); ?>" class="limiter-mkp-pro-admin-quick-action" target="_blank">
                <span class="dashicons dashicons-cart"></span>
                <span class="limiter-mkp-pro-admin-quick-action-label"><?php _e('Loja de Planos', 'limiter-mkp-pro'); ?></span>
            </a>
        </div>
    </div>
</div>