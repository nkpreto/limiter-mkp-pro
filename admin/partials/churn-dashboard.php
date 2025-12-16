<?php
/**
 * Dashboard de Gestão de Churn
 * OTIMIZADO para performance
 *
 * @since 1.4.0
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!Limiter_MKP_Pro_Security::check_network_permissions()) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'limiter-mkp-pro'));
}

require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';

// OTIMIZAÇÃO: Usamos funções COUNT(*) diretas do SQL
// Isso evita carregar milhares de objetos PHP na memória (causa do Erro 520)
$count_active      = Limiter_MKP_Pro_Database::count_blogs_by_lifecycle_status('active');
$count_suspended   = Limiter_MKP_Pro_Database::count_blogs_by_lifecycle_status('suspended');
$count_cancelled   = Limiter_MKP_Pro_Database::count_blogs_by_lifecycle_status('cancelled');
$overdue_count     = Limiter_MKP_Pro_Database::count_overdue_subscriptions();

// Carrega apenas os últimos 20 cancelados para a tabela (Limitado para performance)
$recent_cancelled_blogs = Limiter_MKP_Pro_Database::get_blogs_by_lifecycle_status('cancelled', 20);
?>

<div class="wrap">
    <h1><?php _e('Gestão de Churn - Limiter MKP Pro', 'limiter-mkp-pro'); ?></h1>

    <div class="limiter-mkp-pro-admin-stats-cards">
        <div class="limiter-mkp-pro-admin-card">
            <div class="limiter-mkp-pro-admin-card-header">
                <h3><?php _e('Assinaturas Ativas', 'limiter-mkp-pro'); ?></h3>
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="limiter-mkp-pro-admin-card-value"><?php echo esc_html($count_active); ?></div>
        </div>
        <div class="limiter-mkp-pro-admin-card">
            <div class="limiter-mkp-pro-admin-card-header">
                <h3><?php _e('Suspensas', 'limiter-mkp-pro'); ?></h3>
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="limiter-mkp-pro-admin-card-value"><?php echo esc_html($count_suspended); ?></div>
        </div>
        <div class="limiter-mkp-pro-admin-card">
            <div class="limiter-mkp-pro-admin-card-header">
                <h3><?php _e('Canceladas', 'limiter-mkp-pro'); ?></h3>
                <span class="dashicons dashicons-no"></span>
            </div>
            <div class="limiter-mkp-pro-admin-card-value"><?php echo esc_html($count_cancelled); ?></div>
        </div>
        <div class="limiter-mkp-pro-admin-card">
            <div class="limiter-mkp-pro-admin-card-header">
                <h3><?php _e('Vencidas (Overdue)', 'limiter-mkp-pro'); ?></h3>
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="limiter-mkp-pro-admin-card-value"><?php echo esc_html($overdue_count); ?></div>
        </div>
    </div>

    <h2><?php _e('Subdomínios Cancelados Recentes (Últimos 20)', 'limiter-mkp-pro'); ?></h2>
    <?php if (!empty($recent_cancelled_blogs)): ?>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Subdomínio', 'limiter-mkp-pro'); ?></th>
                    <th><?php _e('Cliente', 'limiter-mkp-pro'); ?></th>
                    <th><?php _e('Plano', 'limiter-mkp-pro'); ?></th>
                    <th><?php _e('Início', 'limiter-mkp-pro'); ?></th>
                    <th><?php _e('Expiração', 'limiter-mkp-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_cancelled_blogs as $blog): 
                    $site_info = get_blog_details($blog->blog_id);
                    ?>
                    <tr>
                        <td><?php echo esc_html($site_info ? $site_info->domain . $site_info->path : 'N/A'); ?></td>
                        <td>
                            <?php echo esc_html($blog->nome_cliente ?: $blog->email_cliente ?: '—'); ?>
                            <?php if (!empty($blog->telefone_cliente)): ?>
                                <br><small><?php echo esc_html($blog->telefone_cliente); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($blog->plano_nome ?: '—'); ?></td>
                        <td><?php echo esc_html($blog->data_inicio); ?></td>
                        <td><?php echo esc_html($blog->data_expiracao ?: '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php _e('Nenhum subdomínio cancelado encontrado.', 'limiter-mkp-pro'); ?></p>
    <?php endif; ?>

    <div class="limiter-mkp-pro-admin-quick-actions" style="margin-top: 30px;">
        <a href="<?php echo network_admin_url('admin.php?page=limiter-mkp-pro-lifecycle'); ?>" class="limiter-mkp-pro-admin-quick-action">
            <span class="dashicons dashicons-update"></span>
            <span class="limiter-mkp-pro-admin-quick-action-label"><?php _e('Configurar Ciclo de Vida', 'limiter-mkp-pro'); ?></span>
        </a>
        <a href="<?php echo network_admin_url('admin.php?page=limiter-mkp-pro-subdominios'); ?>" class="limiter-mkp-pro-admin-quick-action">
            <span class="dashicons dashicons-admin-site"></span>
            <span class="limiter-mkp-pro-admin-quick-action-label"><?php _e('Gerenciar Subdomínios', 'limiter-mkp-pro'); ?></span>
        </a>
    </div>
</div>