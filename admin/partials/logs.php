<?php
/**
 * Template para a página de logs com Visual Aprimorado e Tooltip via Clique.
 *
 * @since      2.3.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin/partials
 */

// Helper para definir estilo com base na ação
function limiter_get_log_style($acao) {
    $acao = strtolower($acao);
    
    // Padrões de Erro
    if (strpos($acao, 'erro') !== false || strpos($acao, 'falha') !== false || strpos($acao, 'failed') !== false || strpos($acao, 'excedido') !== false) {
        return ['class' => 'log-status-error', 'icon' => 'dashicons-warning'];
    }
    
    // Padrões de Sucesso
    if (strpos($acao, 'sucesso') !== false || strpos($acao, 'concluido') !== false || strpos($acao, 'criado') !== false || strpos($acao, 'success') !== false) {
        return ['class' => 'log-status-success', 'icon' => 'dashicons-yes-alt'];
    }
    
    // Padrões de Aviso/Manutenção
    if (strpos($acao, 'limpeza') !== false || strpos($acao, 'manutencao') !== false || strpos($acao, 'desativacao') !== false) {
        return ['class' => 'log-status-warning', 'icon' => 'dashicons-performance'];
    }
    
    // Padrão Informativo (Default)
    return ['class' => 'log-status-info', 'icon' => 'dashicons-info'];
}
?>

<div class="wrap limiter-mkp-pro-admin-logs">
    <h1 class="wp-heading-inline"><?php _e('Limiter MKP Pro - Logs do Sistema', 'limiter-mkp-pro'); ?></h1>
    <hr class="wp-header-end">
    
    <div class="limiter-mkp-pro-admin-filters" style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; margin-top: 20px; border-radius: 4px; display: flex; align-items: center; justify-content: space-between;">
        <form method="get" style="display: flex; align-items: center; gap: 10px;">
            <input type="hidden" name="page" value="limiter-mkp-pro-logs">
            
            <label for="limiter-mkp-pro-filter-blog-id" style="font-weight: 600;"><?php _e('Filtrar por:', 'limiter-mkp-pro'); ?></label>
            <select id="limiter-mkp-pro-filter-blog-id" name="blog_id">
                <option value=""><?php _e('Todos os Subdomínios', 'limiter-mkp-pro'); ?></option>
                <?php 
                $subdominios = Limiter_MKP_Pro_Database::get_subdominios();
                foreach ($subdominios as $subdominio) : 
                    $site_info = get_blog_details($subdominio->blog_id);
                    $selected = isset($_GET['blog_id']) && $_GET['blog_id'] == $subdominio->blog_id ? 'selected' : '';
                    $blogname = $site_info ? $site_info->blogname : 'Site ID ' . $subdominio->blog_id;
                    $domain = $site_info ? $site_info->domain . $site_info->path : $subdominio->dominio;
                ?>
                    <option value="<?php echo esc_attr($subdominio->blog_id); ?>" <?php echo $selected; ?>>
                        <?php echo esc_html($blogname); ?> (<?php echo esc_html($domain); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="button button-secondary"><?php _e('Aplicar Filtro', 'limiter-mkp-pro'); ?></button>
        </form>

        <form id="limiter-mkp-pro-limpar-logs-form" style="margin:0;">
            <input type="hidden" id="limiter-mkp-pro-dias-logs" name="dias" value="30">
            <button type="submit" id="limiter-mkp-pro-limpar-logs-submit" class="button button-link-delete" title="Limpar logs com mais de 30 dias">
                <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> Limpar Antigos
            </button>
        </form>
    </div>
    
    <div class="limiter-mkp-pro-admin-list-container" style="margin-top: 20px;">
        
        <?php if (!empty($logs)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 160px;"><?php _e('Data/Hora', 'limiter-mkp-pro'); ?></th>
                        <th style="width: 200px;"><?php _e('Origem (Subdomínio)', 'limiter-mkp-pro'); ?></th>
                        <th style="width: 220px;"><?php _e('Ação', 'limiter-mkp-pro'); ?></th>
                        <th><?php _e('Descrição', 'limiter-mkp-pro'); ?></th>
                        <th style="width: 80px; text-align: center;"><?php _e('Dados', 'limiter-mkp-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : 
                        $site_info = ($log->blog_id > 0) ? get_blog_details($log->blog_id) : null;
                        $style = limiter_get_log_style($log->acao);
                        
                        // Prepara dados extras
                        $has_details = !empty($log->dados_extras) && $log->dados_extras !== 'a:0:{}';
                        $details_json = '';
                        if ($has_details) {
                            $unserialized = maybe_unserialize($log->dados_extras);
                            $details_json = json_encode($unserialized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }
                    ?>
                        <tr>
                            <td>
                                <?php echo date_i18n(get_option('date_format'), strtotime($log->timestamp)); ?><br>
                                <small style="color: #888;"><?php echo date_i18n(get_option('time_format'), strtotime($log->timestamp)); ?></small>
                            </td>
                            <td>
                                <?php if ($log->blog_id == 0) : ?>
                                    <span class="dashicons dashicons-admin-network" style="color:#888; font-size:16px; width:16px; height:16px; vertical-align:middle;"></span> 
                                    <strong>Rede (Global)</strong>
                                <?php elseif ($site_info) : ?>
                                    <a href="<?php echo esc_url($site_info->siteurl); ?>" target="_blank" style="text-decoration:none; font-weight:500;">
                                        <?php echo esc_html($site_info->blogname); ?>
                                        <span class="dashicons dashicons-external" style="font-size:12px; width:12px; height:12px;"></span>
                                    </a>
                                    <br><small style="color:#888;"><?php echo esc_html($site_info->domain); ?></small>
                                <?php else : ?>
                                    <span style="color:#aa0000;"><?php _e('Site Deletado', 'limiter-mkp-pro'); ?></span> (ID: <?php echo esc_html($log->blog_id); ?>)
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="limiter-log-badge <?php echo esc_attr($style['class']); ?>">
                                    <span class="dashicons <?php echo esc_attr($style['icon']); ?>"></span>
                                    <?php echo esc_html($log->acao); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html($log->descricao); ?>
                                <br>
                                <small style="color: #999;">IP: <?php echo esc_html($log->ip); ?> | User ID: <?php echo esc_html($log->user_id); ?></small>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($has_details) : ?>
                                    <button type="button" class="limiter-view-details" 
                                            title="Clique para ver os detalhes"
                                            data-json="<?php echo htmlspecialchars($details_json, ENT_QUOTES, 'UTF-8'); ?>">
                                        <span class="dashicons dashicons-visibility" style="font-size: 18px;"></span>
                                    </button>
                                <?php else: ?>
                                    <span style="color:#ccc;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="notice notice-info inline">
                <p><?php _e('Nenhum log encontrado para os critérios selecionados.', 'limiter-mkp-pro'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="limiter-log-tooltip" class="limiter-log-tooltip"></div>

<script>
jQuery(document).ready(function($) {
    var $tooltip = $('#limiter-log-tooltip');
    
    // Move o tooltip para o final do body para evitar problemas de overflow
    $('body').append($tooltip);

    $('.limiter-view-details').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        var jsonContent = $btn.data('json');
        
        // Se clicar no mesmo botão que já está aberto, fecha
        if ($tooltip.is(':visible') && $tooltip.data('trigger') === this) {
            $tooltip.hide();
            return;
        }

        if (typeof jsonContent === 'object') {
            jsonContent = JSON.stringify(jsonContent, null, 2);
        }

        $tooltip.text(jsonContent).data('trigger', this).show();

        // Posicionamento absoluto usando offset()
        var btnOffset = $btn.offset();
        var tooltipWidth = $tooltip.outerWidth();
        
        // Calcula posição (ao lado esquerdo do botão por padrão)
        var top = btnOffset.top - 10;
        var left = btnOffset.left - tooltipWidth - 15;

        // Se ficar fora da tela à esquerda, joga para a direita
        if (left < 10) {
            left = btnOffset.left + $btn.outerWidth() + 15;
        }

        $tooltip.css({
            top: top,
            left: left
        });
    });

    // Fecha ao clicar fora
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.limiter-log-tooltip').length && !$(e.target).closest('.limiter-view-details').length) {
            $tooltip.hide();
        }
    });

    // Lógica de Limpeza de Logs
    $('#limiter-mkp-pro-limpar-logs-submit').on('click', function(e) {
        e.preventDefault();
        if (confirm('<?php _e('Tem certeza que deseja limpar os logs mais antigos que 30 dias?', 'limiter-mkp-pro'); ?>')) {
            $.ajax({
                url: limiter_mkp_pro_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'limiter_mkp_pro_limpar_logs',
                    'nonce': limiter_mkp_pro_admin.nonce,
                    'dias': 30
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });
});
</script>