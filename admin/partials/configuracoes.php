<?php
/**
 * Template para a página de configurações.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin/partials
 */

if (!isset($configuracoes) || !is_array($configuracoes)) {
    $configuracoes = array();
}

// Garante que a classe de configurações esteja carregada para pegar os defaults
if (!class_exists('Limiter_MKP_Pro_Configuracoes')) {
    require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-configuracoes.php';
}

// Recarrega as configurações do banco para garantir que temos os dados mais frescos ao renderizar
$config_obj = new Limiter_MKP_Pro_Configuracoes('limiter-mkp-pro', LIMITER_MKP_PRO_VERSION);
$configuracoes = $config_obj->get_configuracoes();

?>

<div class="wrap limiter-mkp-pro-admin-configuracoes">
    <h1><?php _e('Limiter MKP Pro - Configurações', 'limiter-mkp-pro'); ?></h1>
    
    <div class="notice notice-info inline" style="margin-bottom: 20px;">
        <p><strong>💡 Dica:</strong> As alterações feitas aqui afetam toda a rede. Certifique-se de salvar para aplicar as mudanças.</p>
    </div>

    <form id="limiter-mkp-pro-configuracoes-form" class="limiter-mkp-pro-admin-form">
        
        <div class="limiter-mkp-pro-admin-form-section">
            <h2><?php _e('Configurações de Subdomínios', 'limiter-mkp-pro'); ?></h2>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-sufixo-subdominio"><?php _e('Sufixo para Subdomínios', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-sufixo-subdominio" name="sufixo_subdominio" value="<?php echo esc_attr($configuracoes['sufixo_subdominio']); ?>" required>
                <p class="description"><?php _e('Sufixo que será adicionado automaticamente aos nomes de subdomínios (ex: "-mkp").', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label><?php _e('Blacklist de Subdomínios', 'limiter-mkp-pro'); ?></label>
                <p class="description"><?php _e('Nomes de subdomínios que não podem ser utilizados. Separe os nomes com vírgula.', 'limiter-mkp-pro'); ?></p>
                <textarea id="limiter-mkp-pro-subdomain-blacklist" name="subdomain_blacklist" rows="5" style="width: 100%; max-width: 500px;"><?php 
                    echo esc_textarea(implode(', ', $configuracoes['subdomain_blacklist'])); 
                ?></textarea>
                <p class="description">
                    <?php _e('Padrões bloqueados:', 'limiter-mkp-pro'); ?><br>
                    <?php _e('• Nomes exatos (ex: "admin")', 'limiter-mkp-pro'); ?><br>
                    <?php _e('• Nomes com números (ex: "admin1", "admin2")', 'limiter-mkp-pro'); ?>
                </p>
            </div>
        </div>

        <div class="limiter-mkp-pro-admin-form-section">
            <h2><?php _e('Configurações de Notificação', 'limiter-mkp-pro'); ?></h2>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-email-notificacao"><?php _e('E-mail para Notificações', 'limiter-mkp-pro'); ?></label>
                <input type="email" id="limiter-mkp-pro-email-notificacao" name="email_notificacao" value="<?php echo esc_attr($configuracoes['email_notificacao']); ?>" required>
                <p class="description"><?php _e('E-mail para receber notificações.', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-limite-alerta"><?php _e('Limite para Alerta', 'limiter-mkp-pro'); ?></label>
                <input type="number" id="limiter-mkp-pro-limite-alerta" name="limite_alerta" min="1" max="10" value="<?php echo esc_attr($configuracoes['limite_alerta']); ?>" required>
                <p class="description"><?php _e('Número de páginas restantes para exibir alerta.', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-nome-sistema"><?php _e('Nome do Sistema/Empresa', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-nome-sistema" name="nome_sistema" value="<?php echo esc_attr($configuracoes['nome_sistema']); ?>" required>
                <p class="description"><?php _e('Nome que aparecerá nos e-mails e mensagens do sistema.', 'limiter-mkp-pro'); ?></p>
            </div>
        </div>

        <div class="limiter-mkp-pro-admin-form-section">
            <h2><?php _e('Configuração de Planos por Região', 'limiter-mkp-pro'); ?></h2>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-planos-url-global"><?php _e('URL de Planos (Global)', 'limiter-mkp-pro'); ?></label>
                <input type="url" id="limiter-mkp-pro-planos-url-global" name="planos_url_global" 
                       value="<?php echo esc_attr($configuracoes['planos_url_global']); ?>" required>
                <p class="description"><?php _e('Ex: https://marketing-place.store/planos/', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-planos-url-jp"><?php _e('URL de Planos (Japão)', 'limiter-mkp-pro'); ?></label>
                <input type="url" id="limiter-mkp-pro-planos-url-jp" name="planos_url_jp" 
                       value="<?php echo esc_attr($configuracoes['planos_url_jp']); ?>" required>
                <p class="description"><?php _e('Ex: https://marketing-place.store/planos-jp/', 'limiter-mkp-pro'); ?></p>
            </div>
        </div>

        <div class="limiter-mkp-pro-admin-form-section">
            <h2><?php _e('Mensagens Personalizadas', 'limiter-mkp-pro'); ?></h2>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-mensagem-limite"><?php _e('Mensagem de Limite Atingido', 'limiter-mkp-pro'); ?></label>
                <textarea id="limiter-mkp-pro-mensagem-limite" name="mensagem_limite" rows="4" required><?php echo esc_textarea($configuracoes['mensagem_limite']); ?></textarea>
                <p class="description"><?php _e('Use [X] para substituir pelo limite do plano.', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-mensagem-alerta"><?php _e('Mensagem de Alerta', 'limiter-mkp-pro'); ?></label>
                <textarea id="limiter-mkp-pro-mensagem-alerta" name="mensagem_alerta" rows="4" required><?php echo esc_textarea($configuracoes['mensagem_alerta']); ?></textarea>
                <p class="description"><?php _e('Exibida na penúltima página.', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-widget-alerta"><?php _e('Mensagem de Alerta no Widget', 'limiter-mkp-pro'); ?></label>
                <textarea id="limiter-mkp-pro-widget-alerta" name="widget_alerta_limite" rows="4"><?php echo esc_textarea($configuracoes['widget_alerta_limite']); ?></textarea>
                <p class="description"><?php _e('Use [PERCENTUAL] para substituir pelo percentual de uso.', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-widget-sem-plano"><?php _e('Mensagem Widget Sem Plano', 'limiter-mkp-pro'); ?></label>
                <textarea id="limiter-mkp-pro-widget-sem-plano" name="widget_sem_plano" rows="3"><?php echo esc_textarea($configuracoes['widget_sem_plano']); ?></textarea>
                <p class="description"><?php _e('Mensagem exibida quando o subdomínio não tem plano configurado.', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-alerta-arquivos-80"><?php _e('Alerta Limite de Arquivos (80%)', 'limiter-mkp-pro'); ?></label>
                <textarea id="limiter-mkp-pro-alerta-arquivos-80" name="alerta_limite_arquivos_80" rows="3"><?php echo esc_textarea($configuracoes['alerta_limite_arquivos_80']); ?></textarea>
                <p class="description"><?php _e('Use [ARQUIVOS_USADOS], [ARQUIVOS_LIMITE], [NOME_PLANO].', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-alerta-arquivos-100"><?php _e('Alerta Limite de Arquivos (100%)', 'limiter-mkp-pro'); ?></label>
                <textarea id="limiter-mkp-pro-alerta-arquivos-100" name="alerta_limite_arquivos_100" rows="3"><?php echo esc_textarea($configuracoes['alerta_limite_arquivos_100']); ?></textarea>
                <p class="description"><?php _e('Use [ARQUIVOS_USADOS], [ARQUIVOS_LIMITE], [NOME_PLANO].', 'limiter-mkp-pro'); ?></p>
            </div>
        </div>

        <div class="limiter-mkp-pro-admin-form-section">
            <h2><?php _e('Mensagens de Registro e Configuração', 'limiter-mkp-pro'); ?></h2>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-subdominio-disponivel"><?php _e('Subdomínio Disponível', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-subdominio-disponivel" name="subdominio_disponivel" value="<?php echo esc_attr($configuracoes['subdominio_disponivel']); ?>" required>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-subdominio-indisponivel"><?php _e('Subdomínio Indisponível', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-subdominio-indisponivel" name="subdominio_indisponivel" value="<?php echo esc_attr($configuracoes['subdominio_indisponivel']); ?>" required>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-subdominio-curto"><?php _e('Subdomínio Muito Curto', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-subdominio-curto" name="subdominio_curto" value="<?php echo esc_attr($configuracoes['subdominio_curto']); ?>" required>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-registro-concluido"><?php _e('Registro Concluído', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-registro-concluido" name="registro_concluido" value="<?php echo esc_attr($configuracoes['registro_concluido']); ?>" required>
            </div>
        </div>

        <div class="limiter-mkp-pro-admin-form-section">
            <h2><?php _e('Mensagens de E-mails', 'limiter-mkp-pro'); ?></h2>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-email-solicitacao-titulo"><?php _e('E-mail: Título Solicitação', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-email-solicitacao-titulo" name="email_solicitacao_titulo" value="<?php echo esc_attr($configuracoes['email_solicitacao_titulo']); ?>" required>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-email-solicitacao-corpo"><?php _e('E-mail: Corpo Solicitação', 'limiter-mkp-pro'); ?></label>
                <textarea id="limiter-mkp-pro-email-solicitacao-corpo" name="email_solicitacao_corpo" rows="8"><?php echo esc_textarea($configuracoes['email_solicitacao_corpo']); ?></textarea>
                <p class="description"><?php _e('Use [SITE], [URL_SITE], [BLOG_ID], [PLANO_ATUAL], [LIMITE_ATUAL], [PLANO_SOLICITADO], [LIMITE_SOLICITADO], [NOME_CLIENTE], [EMAIL_CLIENTE], [TELEFONE_CLIENTE], [PAINEL_LINK].', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-email-confirmacao-aprovada-titulo"><?php _e('E-mail: Título Confirmação (Aprovada)', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-email-confirmacao-aprovada-titulo" name="email_confirmacao_aprovada_titulo" value="<?php echo esc_attr($configuracoes['email_confirmacao_aprovada_titulo']); ?>" required>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-email-confirmacao-aprovada-corpo"><?php _e('E-mail: Corpo Confirmação (Aprovada)', 'limiter-mkp-pro'); ?></label>
                <textarea id="limiter-mkp-pro-email-confirmacao-aprovada-corpo" name="email_confirmacao_aprovada_corpo" rows="8"><?php echo esc_textarea($configuracoes['email_confirmacao_aprovada_corpo']); ?></textarea>
                <p class="description"><?php _e('Use [NOME_CLIENTE], [SITE], [URL_SITE], [NOVO_PLANO], [NOVO_LIMITE].', 'limiter-mkp-pro'); ?></p>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-email-configuracao-subdominio-titulo"><?php _e('E-mail: Título Configuração Subdomínio', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-email-configuracao-subdominio-titulo" name="email_configuracao_subdominio_titulo" value="<?php echo esc_attr($configuracoes['email_configuracao_subdominio_titulo']); ?>" required>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-email-configuracao-subdominio-corpo"><?php _e('E-mail: Corpo Configuração Subdomínio', 'limiter-mkp-pro'); ?></label>
                <textarea id="limiter-mkp-pro-email-configuracao-subdominio-corpo" name="email_configuracao_subdominio_corpo" rows="10"><?php echo esc_textarea($configuracoes['email_configuracao_subdominio_corpo']); ?></textarea>
                <p class="description"><?php _e('Use [NOME_CLIENTE], [URL_CONFIGURACAO].', 'limiter-mkp-pro'); ?></p>
            </div>
        </div>

        <div class="limiter-mkp-pro-admin-form-section">
            <h2><?php _e('Textos do Sistema', 'limiter-mkp-pro'); ?></h2>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-botao-ver-planos"><?php _e('Botão Ver Planos', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-botao-ver-planos" name="botao_ver_planos" value="<?php echo esc_attr($configuracoes['botao_ver_planos']); ?>" required>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-botao-acessar-loja"><?php _e('Botão Acessar Loja', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-botao-acessar-loja" name="botao_acessar_loja" value="<?php echo esc_attr($configuracoes['botao_acessar_loja']); ?>" required>
            </div>
        </div>

        <div class="limiter-mkp-pro-admin-form-actions">
            <button type="submit" id="limiter-mkp-pro-configuracoes-submit" class="button button-primary button-large"><?php _e('Salvar Configurações', 'limiter-mkp-pro'); ?></button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // CORREÇÃO CRÍTICA: Desvincula eventos anteriores antes de vincular o novo para evitar duplicação
    $('#limiter-mkp-pro-configuracoes-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation(); // Garante que nenhum outro handler interfira
        
        var $btn = $('#limiter-mkp-pro-configuracoes-submit');
        
        // Evita cliques duplos se já estiver processando
        if ($btn.data('submitting')) {
            return;
        }
        
        // Bloqueia a interface
        $btn.data('submitting', true);
        $btn.prop('disabled', true).text('<?php _e('Salvando...', 'limiter-mkp-pro'); ?>');
        
        // Processa a blacklist
        var blacklistText = $('#limiter-mkp-pro-subdomain-blacklist').val();
        var blacklistArray = blacklistText.split(',').map(function(item) {
            return item.trim().toLowerCase();
        }).filter(function(item) {
            return item !== '';
        });
        
        // Constrói objeto manual para garantir a estrutura correta
        var formData = {
            'action': 'limiter_mkp_pro_save_configuracoes',
            'nonce': limiter_mkp_pro_admin.nonce,
            'sufixo_subdominio': $('#limiter-mkp-pro-sufixo-subdominio').val(),
            'email_notificacao': $('#limiter-mkp-pro-email-notificacao').val(),
            'limite_alerta': $('#limiter-mkp-pro-limite-alerta').val(),
            'mensagem_limite': $('#limiter-mkp-pro-mensagem-limite').val(),
            'mensagem_alerta': $('#limiter-mkp-pro-mensagem-alerta').val(),
            'planos_url_global': $('#limiter-mkp-pro-planos-url-global').val(),
            'planos_url_jp': $('#limiter-mkp-pro-planos-url-jp').val(),
            'subdomain_blacklist': blacklistArray,
            'nome_sistema': $('#limiter-mkp-pro-nome-sistema').val(),
            'widget_alerta_limite': $('#limiter-mkp-pro-widget-alerta').val(),
            'widget_sem_plano': $('#limiter-mkp-pro-widget-sem-plano').val(),
            'alerta_limite_arquivos_80': $('#limiter-mkp-pro-alerta-arquivos-80').val(),
            'alerta_limite_arquivos_100': $('#limiter-mkp-pro-alerta-arquivos-100').val(),
            'subdominio_disponivel': $('#limiter-mkp-pro-subdominio-disponivel').val(),
            'subdominio_indisponivel': $('#limiter-mkp-pro-subdominio-indisponivel').val(),
            'subdominio_curto': $('#limiter-mkp-pro-subdominio-curto').val(),
            'registro_concluido': $('#limiter-mkp-pro-registro-concluido').val(),
            'email_solicitacao_titulo': $('#limiter-mkp-pro-email-solicitacao-titulo').val(),
            'email_solicitacao_corpo': $('#limiter-mkp-pro-email-solicitacao-corpo').val(),
            'email_confirmacao_aprovada_titulo': $('#limiter-mkp-pro-email-confirmacao-aprovada-titulo').val(),
            'email_confirmacao_aprovada_corpo': $('#limiter-mkp-pro-email-confirmacao-aprovada-corpo').val(),
            'email_configuracao_subdominio_titulo': $('#limiter-mkp-pro-email-configuracao-subdominio-titulo').val(),
            'email_configuracao_subdominio_corpo': $('#limiter-mkp-pro-email-configuracao-subdominio-corpo').val(),
            'botao_ver_planos': $('#limiter-mkp-pro-botao-ver-planos').val(),
            'botao_acessar_loja': $('#limiter-mkp-pro-botao-acessar-loja').val()
        };
        
        $.ajax({
            url: limiter_mkp_pro_admin.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Opcional: Recarregar a página para mostrar os dados frescos do banco
                    // location.reload();
                } else {
                    alert('Erro: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Ocorreu um erro de conexão ao salvar. Tente novamente.');
                console.error('Erro AJAX:', error, xhr.responseText);
            },
            complete: function() {
                // Libera a interface
                $btn.data('submitting', false);
                $btn.prop('disabled', false).text('<?php _e('Salvar Configurações', 'limiter-mkp-pro'); ?>');
            }
        });
    });
});
</script>