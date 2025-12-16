<?php
/**
 * Template para a página de Health Check.
 *
 * @since      1.2.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin/partials
 */
?>
<div class="wrap limiter-mkp-pro-admin-health-check">
    <h1><?php _e('Limiter MKP Pro - Health Check', 'limiter-mkp-pro'); ?></h1>
    <p><?php _e('Este painel verifica a integridade do sistema e identifica possíveis problemas.', 'limiter-mkp-pro'); ?></p>

    <div class="limiter-mkp-pro-admin-section">
        <h2><?php _e('Relatório de Saúde', 'limiter-mkp-pro'); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Verificação', 'limiter-mkp-pro'); ?></th>
                    <th><?php _e('Status', 'limiter-mkp-pro'); ?></th>
                    <th><?php _e('Detalhes', 'limiter-mkp-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check_key => $result): ?>
                    <?php
                    $status_icon = '✅';
                    $status_class = 'limiter-success';
                    if ($result['status'] === 'warning') {
                        $status_icon = '⚠️';
                        $status_class = 'limiter-warning';
                    } elseif ($result['status'] === 'error') {
                        $status_icon = '❌';
                        $status_class = 'limiter-error';
                    }
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html(str_replace('_', ' ', ucfirst($check_key))); ?></strong>
                        </td>
                        <td>
                            <span class="<?php echo esc_attr($status_class); ?>">
                                <?php echo $status_icon; ?> <?php echo esc_html($result['status']); ?>
                            </span>
                        </td>
                        <td>
                            <p><?php echo esc_html($result['message']); ?></p>
                            <?php if (!empty($result['details'])): ?>
                                <details>
                                    <summary><?php _e('Ver detalhes técnicos', 'limiter-mkp-pro'); ?></summary>
                                    <pre style="background:#f8f9fa; padding:10px; border-radius:4px; overflow:auto; max-height:150px;">
                                        <?php echo esc_html(wp_json_encode($result['details'], JSON_PRETTY_PRINT)); ?>
                                    </pre>
                                </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="limiter-mkp-pro-admin-section">
        <h2><?php _e('Ações de Manutenção', 'limiter-mkp-pro'); ?></h2>
        <p><?php _e('Use estas ferramentas para corrigir problemas comuns.', 'limiter-mkp-pro'); ?></p>
        <form method="post" action="">
            <?php submit_button(__('Gerar Relatório Completo (TXT)', 'limiter-mkp-pro'), 'secondary', 'generate_full_report'); ?>
        </form>
    </div>
</div>

<?php
// Geração de relatório completo
if (isset($_POST['generate_full_report'])) {
    require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-health-check.php';
    $report = Limiter_MKP_Pro_Health_Check::generate_report();
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="limiter-mkp-pro-health-check-' . date('Y-m-d') . '.txt"');
    echo $report;
    exit;
}
?>

<style>
.limiter-success { color: #28a745; }
.limiter-warning { color: #ffc107; }
.limiter-error { color: #dc3545; }
</style>