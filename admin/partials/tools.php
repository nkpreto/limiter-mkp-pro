<?php
/**
 * Template para a página de Ferramentas.
 * @since 2.1.0
 */
if (!defined('ABSPATH')) exit;

// Determina a aba ativa
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'geral';
?>

<div class="wrap">
    <h1><?php _e('Ferramentas do Sistema - Limiter MKP Pro', 'limiter-mkp-pro'); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="?page=limiter-mkp-pro-ferramentas&tab=geral" class="nav-tab <?php echo $active_tab == 'geral' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Geral / Sistema', 'limiter-mkp-pro'); ?>
        </a>
        <a href="?page=limiter-mkp-pro-ferramentas&tab=integracoes" class="nav-tab <?php echo $active_tab == 'integracoes' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Integrações', 'limiter-mkp-pro'); ?>
        </a>
        <a href="?page=limiter-mkp-pro-ferramentas&tab=tokens" class="nav-tab <?php echo $active_tab == 'tokens' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Tokens & Convites', 'limiter-mkp-pro'); ?>
        </a>
        <a href="?page=limiter-mkp-pro-ferramentas&tab=backups" class="nav-tab <?php echo $active_tab == 'backups' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Backups', 'limiter-mkp-pro'); ?>
        </a>
    </h2>

    <div class="limiter-tools-content" style="margin-top: 20px;">
        <?php if ($active_tab == 'geral'): ?>
            
            <div class="limiter-mkp-pro-admin-card" style="text-align: left; max-width: 800px; margin-bottom: 20px;">
                <div class="limiter-mkp-pro-card-header">
                    <h3>⚡ <?php _e('Status da Fila de Processamento', 'limiter-mkp-pro'); ?></h3>
                </div>
                <div class="limiter-mkp-pro-card-content">
                    <p><?php _e('Gerencia a verificação diária de pagamentos e cancelamentos.', 'limiter-mkp-pro'); ?></p>
                    
                    <div id="mkp-cron-status-display" style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin: 10px 0;">
                        Carregando status...
                    </div>

                    <button type="button" id="btn-force-queue" class="button button-secondary">
                        <?php _e('🔄 Forçar Processamento da Fila Agora', 'limiter-mkp-pro'); ?>
                    </button>
                    <p class="description"><?php _e('Use isso se a fila parecer travada. O sistema tentará processar o próximo lote imediatamente.', 'limiter-mkp-pro'); ?></p>
                </div>
            </div>

            <div class="limiter-mkp-pro-admin-card" style="text-align: left; max-width: 800px; margin-bottom: 20px; border-left-color: #f6c23e;">
                <div class="limiter-mkp-pro-card-header">
                    <h3>📂 <?php _e('Contador de Arquivos (Inodes)', 'limiter-mkp-pro'); ?></h3>
                </div>
                <div class="limiter-mkp-pro-card-content">
                    <p><?php _e('O contador de arquivos agora é otimizado e baseado em eventos. Se você enviou arquivos via FTP ou acha que os números estão errados, force um recálculo.', 'limiter-mkp-pro'); ?></p>
                    
                    <button type="button" id="btn-recalc-inodes" class="button button-primary">
                        <?php _e('🔎 Forçar Recálculo de Todos os Subdomínios', 'limiter-mkp-pro'); ?>
                    </button>
                    <span class="spinner" style="float: none; margin-top: 0;"></span>
                    
                    <div id="inode-recalc-result" style="margin-top: 10px; font-weight: bold;"></div>
                </div>
            </div>

            <div class="limiter-mkp-pro-admin-card" style="text-align: left; max-width: 800px; margin-bottom: 20px; border-left-color: #1cc88a;">
                <div class="limiter-mkp-pro-card-header">
                    <h3>🚀 <?php _e('Cache do Sistema', 'limiter-mkp-pro'); ?></h3>
                </div>
                <div class="limiter-mkp-pro-card-content">
                    <p><?php _e('Limpe os caches de contagem de posts e estatísticas para forçar uma atualização imediata no painel dos clientes.', 'limiter-mkp-pro'); ?></p>
                    <button type="button" class="button button-secondary quick-action-btn" data-action="clear_cache">
                        <?php _e('🧹 Limpar Cache do Plugin', 'limiter-mkp-pro'); ?>
                    </button>
                </div>
            </div>

        <?php else: ?>
            <div class="notice notice-info inline">
                <p><?php _e('Funcionalidade em desenvolvimento. Selecione a aba Geral.', 'limiter-mkp-pro'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // 1. Carregar Status do Cron ao abrir a página
    function loadCronStatus() {
        $.post(limiter_mkp_pro_admin.ajax_url, {
            action: 'limiter_mkp_tool_get_system_status',
            nonce: limiter_mkp_pro_admin.nonce
        }, function(response) {
            if(response.success) {
                let html = '<strong>Itens na Fila:</strong> ' + response.data.queue_count + '<br>';
                html += '<strong>Próximo Agendamento:</strong> ' + response.data.next_run;
                $('#mkp-cron-status-display').html(html);
            }
        });
    }
    loadCronStatus();

    // 2. Botão Forçar Fila
    $('#btn-force-queue').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Processando...');
        
        $.post(limiter_mkp_pro_admin.ajax_url, {
            action: 'limiter_mkp_tool_force_queue',
            nonce: limiter_mkp_pro_admin.nonce
        }, function(response) {
            alert(response.data.message);
            $btn.prop('disabled', false).text('🔄 Forçar Processamento da Fila Agora');
            loadCronStatus();
        });
    });

    // 3. Botão Recalcular Inodes
    $('#btn-recalc-inodes').on('click', function() {
        if(!confirm('Isso vai escanear as pastas de uploads de TODOS os sites. Pode levar alguns segundos. Continuar?')) return;
        
        var $btn = $(this);
        var $spinner = $btn.next('.spinner');
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $('#inode-recalc-result').text('');

        $.post(limiter_mkp_pro_admin.ajax_url, {
            action: 'limiter_mkp_tool_recalc_inodes',
            nonce: limiter_mkp_pro_admin.nonce
        }, function(response) {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
            if(response.success) {
                $('#inode-recalc-result').text('✅ Sucesso: ' + response.data.message).css('color', 'green');
            } else {
                $('#inode-recalc-result').text('❌ Erro: ' + response.data.message).css('color', 'red');
            }
        });
    });
});
</script>