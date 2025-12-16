<?php
/**
 * Template para a página de adicionar subdomínio.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin/partials
 */
?>

<div class="wrap limiter-mkp-pro-admin-adicionar-subdominio">
    <h1><?php _e('Limiter MKP Pro - Adicionar Subdomínio', 'limiter-mkp-pro'); ?></h1>
    
    <div class="limiter-mkp-pro-admin-form-container">
        <h2><?php _e('Adicionar Subdomínio Individual', 'limiter-mkp-pro'); ?></h2>
        
        <form id="limiter-mkp-pro-subdominio-form" class="limiter-mkp-pro-admin-form">
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-site-search"><?php _e('Buscar Site (Digite para pesquisar)', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-site-search" class="regular-text" placeholder="<?php _e('Digite o nome ou domínio do site...', 'limiter-mkp-pro'); ?>" style="width: 100%; max-width: 500px;">
                <input type="hidden" id="limiter-mkp-pro-subdominio-site" name="blog_id" required>
                <input type="hidden" id="limiter-mkp-pro-subdominio-dominio-hidden" name="dominio_hidden">
                <p class="description"><?php _e('Comece a digitar (mínimo 3 caracteres) para encontrar o site na rede.', 'limiter-mkp-pro'); ?></p>
            </div>
            
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-subdominio-plano"><?php _e('Plano', 'limiter-mkp-pro'); ?></label>
                <select id="limiter-mkp-pro-subdominio-plano" name="plano_id" required>
                    <option value=""><?php _e('Selecione um plano', 'limiter-mkp-pro'); ?></option>
                    <?php foreach ($planos as $plano) : ?>
                        <option value="<?php echo esc_attr($plano->id); ?>"><?php echo esc_html($plano->nome); ?> (<?php echo esc_html($plano->limite_paginas); ?> <?php _e('páginas', 'limiter-mkp-pro'); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-subdominio-limite"><?php _e('Limite Personalizado (opcional)', 'limiter-mkp-pro'); ?></label>
                <input type="number" id="limiter-mkp-pro-subdominio-limite" name="limite_personalizado" min="1">
                <p class="description"><?php _e('Se definido, substitui o limite do plano selecionado.', 'limiter-mkp-pro'); ?></p>
            </div>
            
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-subdominio-nome-cliente"><?php _e('Nome do Cliente', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-subdominio-nome-cliente" name="nome_cliente">
            </div>
            
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-subdominio-email-cliente"><?php _e('E-mail do Cliente', 'limiter-mkp-pro'); ?></label>
                <input type="email" id="limiter-mkp-pro-subdominio-email-cliente" name="email_cliente">
            </div>
            
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-subdominio-telefone-cliente"><?php _e('Telefone do Cliente', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-subdominio-telefone-cliente" name="telefone_cliente">
            </div>
            
            <div class="limiter-mkp-pro-admin-form-actions">
                <button type="submit" id="limiter-mkp-pro-subdominio-submit" class="button button-primary"><?php _e('Adicionar Subdomínio', 'limiter-mkp-pro'); ?></button>
            </div>
        </form>
    </div>
    
    <div class="limiter-mkp-pro-admin-form-container">
        <h2><?php _e('Adicionar Subdomínios em Massa', 'limiter-mkp-pro'); ?></h2>
        <p class="description">
            <?php _e('Para garantir a performance e estabilidade do seu painel em redes grandes, a listagem automática de todos os sites foi desativada nesta tela. Recomendamos adicionar subdomínios individualmente usando a busca acima.', 'limiter-mkp-pro'); ?>
        </p>
    </div>
</div>

<style>
/* Estilo para o Autocomplete do jQuery UI no admin */
.ui-autocomplete {
    background: #fff;
    border: 1px solid #ddd;
    max-height: 250px;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 99999;
    width: 300px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-radius: 0 0 4px 4px;
}
.ui-menu-item {
    padding: 0;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}
.ui-menu-item-wrapper {
    padding: 10px 12px;
    display: block;
}
.ui-menu-item-wrapper:hover, 
.ui-state-active,
.ui-state-focus {
    background-color: #4e73df !important;
    color: #fff !important;
    border: none;
    margin: 0;
}
.ui-helper-hidden-accessible { display: none; }
</style>

<script>
jQuery(document).ready(function($) {
    
    // Inicializa Autocomplete para busca de sites
    $('#limiter-mkp-pro-site-search').autocomplete({
        source: function(request, response) {
            // Adiciona classe de carregando
            $('#limiter-mkp-pro-site-search').addClass('ui-autocomplete-loading');
            
            $.ajax({
                url: limiter_mkp_pro_admin.ajax_url,
                dataType: "json",
                data: {
                    action: 'limiter_mkp_pro_search_sites',
                    term: request.term,
                    nonce: limiter_mkp_pro_admin.nonce
                },
                success: function(data) {
                    $('#limiter-mkp-pro-site-search').removeClass('ui-autocomplete-loading');
                    if(data.success && data.data.length > 0) {
                        response(data.data);
                    } else {
                        // Feedback visual se não encontrar nada
                        response([{
                            label: '<?php _e("Nenhum site encontrado", "limiter-mkp-pro"); ?>',
                            value: '',
                            id: 0
                        }]);
                    }
                },
                error: function() {
                    $('#limiter-mkp-pro-site-search').removeClass('ui-autocomplete-loading');
                }
            });
        },
        minLength: 3, // Otimização: Começa a buscar apenas após 3 caracteres
        select: function(event, ui) {
            // Impede seleção do item "Nenhum site encontrado"
            if (ui.item.id === 0) {
                event.preventDefault();
                return false;
            }
            
            // Ao selecionar, preenche os campos ocultos e visuais
            $('#limiter-mkp-pro-subdominio-site').val(ui.item.id);
            $('#limiter-mkp-pro-subdominio-dominio-hidden').val(ui.item.domain);
            
            // Opcional: Feedback visual de seleção
            $(this).val(ui.item.label);
            return false; // Impede que o valor do input seja substituído pelo value do objeto
        }
    });

    // Lógica de envio do formulário ajustada para pegar do campo hidden
    $('#limiter-mkp-pro-subdominio-form').on('submit', function(e) {
        e.preventDefault();
        
        var blog_id = $('#limiter-mkp-pro-subdominio-site').val();
        var dominio = $('#limiter-mkp-pro-subdominio-dominio-hidden').val();
        var plano_id = $('#limiter-mkp-pro-subdominio-plano').val();
        
        // Coleta outros campos
        var limite_personalizado = $('#limiter-mkp-pro-subdominio-limite').val();
        var nome_cliente = $('#limiter-mkp-pro-subdominio-nome-cliente').val();
        var email_cliente = $('#limiter-mkp-pro-subdominio-email-cliente').val();
        var telefone_cliente = $('#limiter-mkp-pro-subdominio-telefone-cliente').val();
        
        // Validação
        if (!blog_id) {
            alert('<?php _e('Por favor, busque e selecione um site válido na lista.', 'limiter-mkp-pro'); ?>');
            $('#limiter-mkp-pro-site-search').focus();
            return;
        }
        
        if (!plano_id) {
            alert('<?php _e('Por favor, selecione um plano.', 'limiter-mkp-pro'); ?>');
            return;
        }
        
        var $btn = $('#limiter-mkp-pro-subdominio-submit');
        $btn.prop('disabled', true).text('<?php _e('Processando...', 'limiter-mkp-pro'); ?>');
        
        $.ajax({
            url: limiter_mkp_pro_admin.ajax_url,
            type: 'POST',
            data: {
                'action': 'limiter_mkp_pro_save_subdominio',
                'nonce': limiter_mkp_pro_admin.nonce,
                'blog_id': blog_id,
                'dominio': dominio,
                'plano_id': plano_id,
                'limite_personalizado': limite_personalizado,
                'nome_cliente': nome_cliente,
                'email_cliente': email_cliente,
                'telefone_cliente': telefone_cliente
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = '<?php echo network_admin_url('admin.php?page=limiter-mkp-pro-subdominios'); ?>';
                } else {
                    alert(response.data.message);
                    $btn.prop('disabled', false).text('<?php _e('Adicionar Subdomínio', 'limiter-mkp-pro'); ?>');
                }
            },
            error: function() {
                alert(limiter_mkp_pro_admin.messages.error);
                $btn.prop('disabled', false).text('<?php _e('Adicionar Subdomínio', 'limiter-mkp-pro'); ?>');
            }
        });
    });
});
</script>