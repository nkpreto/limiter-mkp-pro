<?php
/**
 * Template para a página de Ferramentas Avançadas (Recursos Ocultos).
 *
 * @since      2.2.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'backups';

// Lógica de Download
if (isset($_GET['action']) && $_GET['action'] === 'download_backup' && isset($_GET['file'])) {
    check_admin_referer('download_backup_nonce');
    $file_name = sanitize_file_name($_GET['file']);
    $backup_dir = wp_upload_dir()['basedir'] . '/limiter-mkp-pro-backups/';
    $file_path = $backup_dir . $file_name;

    if (file_exists($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}
?>

<div class="wrap">
    <h1><?php _e('Ferramentas Avançadas - Limiter MKP Pro', 'limiter-mkp-pro'); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=limiter-mkp-pro-tools&tab=backups" class="nav-tab <?php echo $active_tab == 'backups' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Backups e Snapshots', 'limiter-mkp-pro'); ?>
        </a>
        <a href="?page=limiter-mkp-pro-tools&tab=webhooks" class="nav-tab <?php echo $active_tab == 'webhooks' ? 'nav-tab-active' : ''; ?>">Webhooks</a>
    </h2>

    <div class="limiter-tools-content" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 10px;">
        
        <?php if ($active_tab == 'backups') : ?>
            
            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                
                <div style="flex: 1; min-width: 300px; padding-right: 20px; border-right: 1px solid #eee;">
                    <h3><span class="dashicons dashicons-admin-site"></span> <?php _e('Backup Individual de Site', 'limiter-mkp-pro'); ?></h3>
                    <p><?php _e('Gera um arquivo SQL contendo todo o banco de dados de um subdomínio específico. Ideal para planos Business.', 'limiter-mkp-pro'); ?></p>
                    
                    <form id="limiter-site-backup-form">
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Selecione o Subdomínio:</label>
                        
                        <select id="limiter-tools-site-select" style="width: 100%; max-width: 400px;">
                            <option value=""><?php _e('Selecione um site na lista...', 'limiter-mkp-pro'); ?></option>
                            <?php
                            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
                            $subs = Limiter_MKP_Pro_Database::get_subdominios();
                            
                            if (empty($subs)) {
                                echo '<option value="" disabled>' . __('Nenhum subdomínio cadastrado ainda', 'limiter-mkp-pro') . '</option>';
                            } else {
                                foreach ($subs as $sub) {
                                    $blog_details = get_blog_details($sub->blog_id);
                                    $nome_site = $blog_details ? $blog_details->blogname : 'Site ID ' . $sub->blog_id;
                                    $dominio = $blog_details ? $blog_details->domain . $blog_details->path : $sub->dominio;
                                    echo '<option value="' . esc_attr($sub->blog_id) . '">' . esc_html($nome_site . ' (' . $dominio . ')') . '</option>';
                                }
                            }
                            ?>
                        </select>

                        <button type="button" id="limiter-generate-site-backup-btn" class="button button-primary button-large" disabled style="margin-top: 10px; width: 100%;">
                            <span class="dashicons dashicons-database-export" style="line-height: 1.5; margin-right:5px;"></span> 
                            <?php _e('Gerar Backup SQL Agora', 'limiter-mkp-pro'); ?>
                        </button>
                    </form>
                    
                    <div id="limiter-site-progress" class="limiter-progress-wrapper">
                        <div class="limiter-progress-container">
                            <div class="limiter-progress-fill" style="width: 0%"><span class="limiter-progress-text">0%</span></div>
                        </div>
                        <div class="limiter-progress-status"><?php _e('Exportando tabelas...', 'limiter-mkp-pro'); ?></div>
                    </div>
                </div>

                <div style="flex: 1; min-width: 300px;">
                    <h3><span class="dashicons dashicons-admin-settings"></span> <?php _e('Backup Geral do Plugin', 'limiter-mkp-pro'); ?></h3>
                    <p><?php _e('Salva configurações globais, planos e lista de vínculos.', 'limiter-mkp-pro'); ?></p>
                    
                    <form id="limiter-system-backup-form">
                        <fieldset style="margin-bottom: 15px; background: #f9f9f9; padding: 10px; border-radius: 4px;">
                            <label style="display:block; margin-bottom:5px;"><input type="checkbox" name="backup_includes[]" value="config" checked disabled> Configurações (Obrigatório)</label>
                            <label style="display:block; margin-bottom:5px;"><input type="checkbox" name="backup_includes[]" value="plans" checked> Planos e Limites</label>
                            <label style="display:block; margin-bottom:5px;"><input type="checkbox" name="backup_includes[]" value="subs" checked> Vínculos de Subdomínios</label>
                            <label style="display:block; margin-bottom:5px;"><input type="checkbox" name="backup_includes[]" value="clients" checked> Dados Cadastrais</label>
                            <label style="display:block; margin-bottom:5px;"><input type="checkbox" name="backup_includes[]" value="tokens" checked> Tokens</label>
                            <label style="display:block; margin-bottom:5px;"><input type="checkbox" name="backup_includes[]" value="logs" checked> Logs (Últimos 5000)</label>
                        </fieldset>

                        <button type="button" id="limiter-generate-system-btn" class="button button-secondary button-large" style="width: 100%;">
                            <?php _e('Gerar Backup JSON', 'limiter-mkp-pro'); ?>
                        </button>
                    </form>

                    <div id="limiter-system-progress" class="limiter-progress-wrapper">
                        <div class="limiter-progress-container">
                            <div class="limiter-progress-fill" style="width: 0%"><span class="limiter-progress-text">0%</span></div>
                        </div>
                        <div class="limiter-progress-status"><?php _e('Processando...', 'limiter-mkp-pro'); ?></div>
                    </div>
                </div>
            </div>

            <hr style="margin: 30px 0;">

            <h3><?php _e('Arquivos Disponíveis para Download', 'limiter-mkp-pro'); ?></h3>
            <?php
            $backup_dir = wp_upload_dir()['basedir'] . '/limiter-mkp-pro-backups/';
            if (is_dir($backup_dir)) {
                $files = scandir($backup_dir);
                $valid_files = [];
                foreach ($files as $f) if (strpos($f, 'limiter-') === 0 || strpos($f, 'backup-site-') === 0) $valid_files[] = $f;
                usort($valid_files, function($a, $b) use ($backup_dir) { return filemtime($backup_dir . $b) - filemtime($backup_dir . $a); });
                
                if (!empty($valid_files)) {
                    echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Arquivo</th><th>Tipo</th><th>Data</th><th>Tamanho</th><th>Ação</th></tr></thead><tbody>';
                    foreach ($valid_files as $file) {
                        $path = $backup_dir . $file;
                        $type = (strpos($file, '.sql') !== false) ? '<strong>Banco de Dados (SQL)</strong>' : 'Configurações (JSON)';
                        $download_url = wp_nonce_url(admin_url('admin.php?page=limiter-mkp-pro-tools&tab=backups&action=download_backup&file=' . $file), 'download_backup_nonce');
                        
                        // CORREÇÃO: Usamos wp_date passando o timestamp do arquivo.
                        // O WordPress detecta o timezone configurado no painel e converte corretamente.
                        $data_formatada = wp_date(
                            get_option('date_format') . ' ' . get_option('time_format'), 
                            filemtime($path)
                        );

                        echo '<tr>';
                        echo '<td>' . esc_html($file) . '</td>';
                        echo '<td>' . $type . '</td>';
                        echo '<td>' . esc_html($data_formatada) . '</td>';
                        echo '<td>' . size_format(filesize($path)) . '</td>';
                        echo '<td>';
                        // Botão Baixar
                        echo '<a href="' . $download_url . '" class="button button-secondary" style="margin-right:5px;" title="Baixar"><span class="dashicons dashicons-download" style="line-height: 1.3;"></span></a>';
                        // Botão Excluir
                        echo '<button type="button" class="button button-link-delete limiter-delete-backup-btn" data-file="' . esc_attr($file) . '" title="Excluir"><span class="dashicons dashicons-trash" style="line-height: 1.3; color: #a00;"></span></button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else { echo '<p>Nenhum arquivo encontrado.</p>'; }
            }
            ?>

        <?php elseif ($active_tab == 'webhooks') : ?>
            <h3><?php _e('Integrações via Webhook', 'limiter-mkp-pro'); ?></h3>
            <p><em><?php _e('Em construção... Este recurso será ativado no próximo passo.', 'limiter-mkp-pro'); ?></em></p>

        <?php elseif ($active_tab == 'users') : ?>
            <h3><?php _e('Cofre de Permissões de Usuários', 'limiter-mkp-pro'); ?></h3>
            <p><em><?php _e('Em construção... Este recurso será ativado no próximo passo.', 'limiter-mkp-pro'); ?></em></p>

        <?php elseif ($active_tab == 'shortcodes') : ?>
            <h3><?php _e('Shortcodes Disponíveis', 'limiter-mkp-pro'); ?></h3>
            <p><em><?php _e('Em construção... Este recurso será ativado no próximo passo.', 'limiter-mkp-pro'); ?></em></p>

        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 1. Monitora o Dropdown de Sites
    $('#limiter-tools-site-select').on('change', function() {
        var siteId = $(this).val();
        $('#limiter-generate-site-backup-btn').prop('disabled', !siteId);
    });

    // 2. Handler Backup SQL Individual
    $('#limiter-generate-site-backup-btn').on('click', function() {
        var $btn = $(this);
        var blogId = $('#limiter-tools-site-select').val();
        if(!blogId) { alert('Selecione um site primeiro.'); return; }
        runBackupProcess($btn, '#limiter-site-progress', 'limiter_mkp_pro_generate_site_backup', { blog_id: blogId });
    });

    // 3. Handler Backup JSON Sistema
    $('#limiter-generate-system-btn').on('click', function() {
        var $btn = $(this);
        var includes = [];
        $('input[name="backup_includes[]"]:checked').each(function() { includes.push($(this).val()); });
        runBackupProcess($btn, '#limiter-system-progress', 'limiter_mkp_pro_generate_backup', { includes: includes });
    });

    // 4. Handler de Exclusão de Backup
    $('.limiter-delete-backup-btn').on('click', function() {
        var filename = $(this).data('file');
        if(confirm('Tem certeza que deseja excluir o arquivo "' + filename + '"? Esta ação é irreversível.')) {
            $.post(limiter_mkp_pro_admin.ajax_url, {
                action: 'limiter_mkp_pro_delete_backup',
                file: filename,
                nonce: limiter_mkp_pro_admin.nonce
            }, function(res) {
                if(res.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + res.data.message);
                }
            }).fail(function() {
                alert('Erro de conexão.');
            });
        }
    });

    // Função genérica de animação
    function runBackupProcess($btn, progressId, action, extraData) {
        var $progressWrapper = $(progressId);
        var $bar = $progressWrapper.find('.limiter-progress-fill');
        var $text = $progressWrapper.find('.limiter-progress-text');
        var $status = $progressWrapper.find('.limiter-progress-status');
        
        $btn.prop('disabled', true);
        $progressWrapper.slideDown();
        $bar.css('width', '10%');
        
        var p = 10;
        var interval = setInterval(function() {
            if(p < 90) { p += 5; $bar.css('width', p + '%'); $text.text(p + '%'); }
        }, 500);

        var data = { action: action, nonce: limiter_mkp_pro_admin.nonce };
        $.extend(data, extraData);

        $.post(limiter_mkp_pro_admin.ajax_url, data, function(res) {
            clearInterval(interval);
            if(res.success) {
                $bar.css('width', '100%').css('background', '#28a745');
                $text.text('100%');
                $status.html('<span style="color:green; font-weight:bold;">' + res.data.message + '</span>');
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                $bar.css('background', '#dc3545');
                $status.html('<span style="color:red; font-weight:bold;">Erro: ' + res.data.message + '</span>');
                $btn.prop('disabled', false);
            }
        }).fail(function() {
            clearInterval(interval);
            $bar.css('background', '#dc3545');
            $status.text('Erro fatal no servidor.');
            $btn.prop('disabled', false);
        });
    }
});
</script>