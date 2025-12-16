<?php
/**
 * Template para a página de planos.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin/partials
 */
// Segurança: impede acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Inicializa a variável para evitar Warning/Erro
$plano_products = isset($plano_products) ? $plano_products : array();
?>
<div class="wrap limiter-mkp-pro-admin-planos">
    <h1><?php esc_html_e('Limiter MKP Pro - Planos', 'limiter-mkp-pro'); ?></h1>
    <?php
    require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
    $woocommerce_active = Limiter_MKP_Pro_WooCommerce_Integration::is_woocommerce_active();
    $subscriptions_active = Limiter_MKP_Pro_WooCommerce_Integration::is_subscriptions_active();
    $subscription_products = $woocommerce_active ? Limiter_MKP_Pro_WooCommerce_Integration::get_subscription_products() : array();
    
    require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-planos.php';
    $planos_handler = new Limiter_MKP_Pro_Planos('limiter-mkp-pro', '1.0.0');
    ?>
    <div class="limiter-mkp-pro-admin-form-container">
        <h2 id="limiter-mkp-pro-form-title"><?php esc_html_e('Adicionar Novo Plano', 'limiter-mkp-pro'); ?></h2>
        <form id="limiter-mkp-pro-plano-form" class="limiter-mkp-pro-admin-form">
            <input type="hidden" id="limiter-mkp-pro-plano-id" name="id" value="0">
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-plano-nome"><?php esc_html_e('Nome', 'limiter-mkp-pro'); ?></label>
                <input type="text" id="limiter-mkp-pro-plano-nome" name="nome" required>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-plano-descricao"><?php esc_html_e('Descrição', 'limiter-mkp-pro'); ?></label>
                <textarea id="limiter-mkp-pro-plano-descricao" name="descricao" rows="3"></textarea>
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-plano-duracao">
                    <?php esc_html_e('Duração (dias)', 'limiter-mkp-pro'); ?>
                    <?php if (!empty($subscription_products)): ?>
                        <small style="color: #0073aa;">(Sincronizado com WooCommerce)</small>
                    <?php endif; ?>
                </label>
                <input type="number" id="limiter-mkp-pro-plano-duracao" name="duracao" min="1" value="30">
            </div>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-plano-limite"><?php esc_html_e('Limite de Páginas', 'limiter-mkp-pro'); ?></label>
                <input type="number" id="limiter-mkp-pro-plano-limite" name="limite_paginas" min="1" required>
            </div>
            
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-plano-limite-upload"><?php esc_html_e('Espaço em Disco (MB)', 'limiter-mkp-pro'); ?></label>
                <input type="number" id="limiter-mkp-pro-plano-limite-upload" name="limite_upload_mb" min="1" value="100" required>
                <p class="description">
                    <?php esc_html_e('Limite nativo de armazenamento físico (ex: 500 para 500MB). O WordPress bloqueará uploads se exceder.', 'limiter-mkp-pro'); ?>
                </p>
            </div>

            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-plano-limite-inodes"><?php esc_html_e('Limite de Arquivos (Inodes)', 'limiter-mkp-pro'); ?></label>
                <input type="number" id="limiter-mkp-pro-plano-limite-inodes" name="limite_inodes" min="1" value="1000" required>
                <p class="description">
                    <?php esc_html_e('Número máximo de arquivos permitidos (imagens, documentos, etc.) para este plano.', 'limiter-mkp-pro'); ?>
                </p>
            </div>

            <div class="limiter-mkp-pro-admin-form-section" style="background: #f8f9fc; padding: 15px; border-radius: 4px; border: 1px solid #e2e4e7; margin: 15px 0;">
                <h3><span class="dashicons dashicons-backup"></span> <?php _e('Rotina de Backup (Premium)', 'limiter-mkp-pro'); ?></h3>
                
                <div class="limiter-mkp-pro-admin-form-row">
                    <label for="limiter-mkp-pro-plano-backup-freq"><?php esc_html_e('Frequência de Backup Automático', 'limiter-mkp-pro'); ?></label>
                    <select id="limiter-mkp-pro-plano-backup-freq" name="backup_frequency">
                        <option value="none"><?php esc_html_e('Nenhum (Desativado)', 'limiter-mkp-pro'); ?></option>
                        <option value="daily"><?php esc_html_e('Diário (Todo dia)', 'limiter-mkp-pro'); ?></option>
                        <option value="weekly"><?php esc_html_e('Semanal (Toda semana)', 'limiter-mkp-pro'); ?></option>
                        <option value="monthly"><?php esc_html_e('Mensal (Todo mês)', 'limiter-mkp-pro'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Backup completo do banco de dados (SQL) do subdomínio.', 'limiter-mkp-pro'); ?></p>
                </div>

                <div class="limiter-mkp-pro-admin-form-row">
                    <label for="limiter-mkp-pro-plano-backup-ret"><?php esc_html_e('Retenção (Arquivos mantidos)', 'limiter-mkp-pro'); ?></label>
                    <input type="number" id="limiter-mkp-pro-plano-backup-ret" name="backup_retention" min="1" max="30" value="3">
                    <p class="description"><?php esc_html_e('Quantos backups recentes manter antes de apagar os antigos. Recomendado: 3 a 7.', 'limiter-mkp-pro'); ?></p>
                </div>
            </div>

            <div class="limiter-mkp-pro-admin-form-section">
                <h3><?php _e('Tipos de Conteúdo a Contabilizar', 'limiter-mkp-pro'); ?></h3>
                <?php
                $all_post_types = get_post_types(['public' => true], 'objects');
                $cpts = [];
                foreach ($all_post_types as $name => $obj) {
                    if (!in_array($name, ['post', 'page', 'attachment'])) {
                        $cpts[$name] = $obj->label;
                    }
                }
                ?>
                <div class="limiter-mkp-pro-admin-form-row">
                    <p><strong><?php _e('Tipos nativos:', 'limiter-mkp-pro'); ?></strong></p>
                    <label><input type="checkbox" name="post_types_contaveis[]" value="post" checked> Post</label><br>
                    <label><input type="checkbox" name="post_types_contaveis[]" value="page" checked> Página</label>
                </div>
                <?php if (!empty($cpts)): ?>
                <div class="limiter-mkp-pro-admin-form-row">
                    <p><strong><?php _e('Custom Post Types:', 'limiter-mkp-pro'); ?></strong></p>
                    <?php foreach ($cpts as $slug => $label): ?>
                        <label><input type="checkbox" name="post_types_contaveis[]" value="<?php echo esc_attr($slug); ?>"> <?php echo esc_html($label); ?> (<?php echo esc_html($slug); ?>)</label><br>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="limiter-mkp-pro-admin-form-section">
                <h3><?php _e('Status de Conteúdo a Contabilizar', 'limiter-mkp-pro'); ?></h3>
                <div class="limiter-mkp-pro-admin-form-row">
                    <label><input type="checkbox" name="post_status_contaveis[]" value="publish" checked> <?php _e('Publicado', 'limiter-mkp-pro'); ?></label><br>
                    <label><input type="checkbox" name="post_status_contaveis[]" value="draft" checked> <?php _e('Rascunho', 'limiter-mkp-pro'); ?></label><br>
                    <label><input type="checkbox" name="post_status_contaveis[]" value="trash" checked> <?php _e('Lixeira', 'limiter-mkp-pro'); ?></label><br>
                    <label><input type="checkbox" name="post_status_contaveis[]" value="pending"> <?php _e('Pendente', 'limiter-mkp-pro'); ?></label><br>
                    <label><input type="checkbox" name="post_status_contaveis[]" value="future"> <?php _e('Agendado', 'limiter-mkp-pro'); ?></label><br>
                    <label><input type="checkbox" name="post_status_contaveis[]" value="private"> <?php _e('Privado', 'limiter-mkp-pro'); ?></label>
                </div>
            </div>

            <?php if ($woocommerce_active && $subscriptions_active): ?>
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-plano-woocommerce-products"><?php esc_html_e('Produtos WooCommerce Subscription', 'limiter-mkp-pro'); ?></label>
                <select id="limiter-mkp-pro-plano-woocommerce-products" name="woocommerce_product_ids[]" multiple="multiple" style="width: 100%; max-width: 500px; height: 150px;">
                    <option value=""><?php esc_html_e('Nenhum produto vinculado', 'limiter-mkp-pro'); ?></option>
                    <?php foreach ($subscription_products as $product): ?>
                        <option value="<?php echo esc_attr($product['id']); ?>">
                            <?php echo esc_html($product['name']); ?> 
                            (<?php echo wc_price($product['price']); ?> / 
                            <?php echo esc_html($product['interval'] . ' ' . $product['period']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php esc_html_e('Vincule este plano a um ou mais produtos de assinatura do WooCommerce.', 'limiter-mkp-pro'); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="limiter-mkp-pro-admin-form-actions">
                <button type="button" id="limiter-mkp-pro-plano-cancel" class="button" style="display: none;"><?php esc_html_e('Cancelar', 'limiter-mkp-pro'); ?></button>
                <button type="submit" id="limiter-mkp-pro-plano-submit" class="button button-primary"><?php esc_html_e('Salvar Plano', 'limiter-mkp-pro'); ?></button>
            </div>
        </form>
    </div>
    <div class="limiter-mkp-pro-admin-list-container">
        <h2><?php esc_html_e('Planos Disponíveis', 'limiter-mkp-pro'); ?></h2>
        <?php 
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $planos = Limiter_MKP_Pro_Database::get_planos();
        
        if (!empty($planos)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Nome', 'limiter-mkp-pro'); ?></th>
                        <th><?php esc_html_e('Duração', 'limiter-mkp-pro'); ?></th>
                        <th><?php esc_html_e('Limites (Pag/MB/File)', 'limiter-mkp-pro'); ?></th>
                        <th><?php esc_html_e('Backup', 'limiter-mkp-pro'); ?></th>
                        <th><?php esc_html_e('Ações', 'limiter-mkp-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($planos as $plano) : 
                        $plano_products_linked = $planos_handler->get_plano_woocommerce_products($plano->id);
                        $plano_product_ids = array();
                        $plano_product_names = array();
                        foreach ($plano_products_linked as $product) {
                            $plano_product_ids[] = $product['id'];
                            $plano_product_names[] = $product['name'];
                        }
                        
                        $mb_display = isset($plano->limite_upload_mb) ? $plano->limite_upload_mb : 100;
                        
                        // Ícones de Backup
                        $backup_icon = 'dashicons-no';
                        $backup_color = '#ccc';
                        $backup_text = 'Nenhum';
                        
                        if ($plano->backup_frequency === 'daily') {
                            $backup_icon = 'dashicons-backup';
                            $backup_color = '#28a745';
                            $backup_text = 'Diário';
                        } elseif ($plano->backup_frequency === 'weekly') {
                            $backup_icon = 'dashicons-backup';
                            $backup_color = '#0073aa';
                            $backup_text = 'Semanal';
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html($plano->nome); ?></td>
                            <td><?php echo esc_html($plano->duracao); ?> dias</td>
                            <td>
                                <strong><?php echo esc_html($plano->limite_paginas); ?></strong> págs<br>
                                <strong><?php echo esc_html($mb_display); ?></strong> MB<br>
                                <strong><?php echo esc_html($plano->limite_inodes); ?></strong> arq.
                            </td>
                            <td>
                                <span class="dashicons <?php echo $backup_icon; ?>" style="color: <?php echo $backup_color; ?>;"></span> 
                                <?php echo esc_html($backup_text); ?>
                            </td>
                            <td>
                                <button type="button" class="button limiter-mkp-pro-edit-plano" 
                                    data-id="<?php echo esc_attr($plano->id); ?>" 
                                    data-nome="<?php echo esc_attr($plano->nome); ?>" 
                                    data-descricao="<?php echo esc_attr($plano->descricao); ?>" 
                                    data-duracao="<?php echo esc_attr($plano->duracao); ?>" 
                                    data-limite="<?php echo esc_attr($plano->limite_paginas); ?>"
                                    data-limite-inodes="<?php echo esc_attr($plano->limite_inodes); ?>"
                                    data-limite-upload="<?php echo esc_attr($mb_display); ?>"
                                    data-backup-freq="<?php echo esc_attr($plano->backup_frequency); ?>"
                                    data-backup-ret="<?php echo esc_attr($plano->backup_retention); ?>"
                                    data-produto-ids="<?php echo esc_attr(json_encode($plano_product_ids)); ?>"
                                    data-plano-types="<?php echo esc_attr($plano->post_types_contaveis); ?>"
                                    data-plano-status="<?php echo esc_attr($plano->post_status_contaveis); ?>">
                                    <?php esc_html_e('Editar', 'limiter-mkp-pro'); ?>
                                </button>
                                <button type="button" class="button limiter-mkp-pro-delete-plano" data-id="<?php echo esc_attr($plano->id); ?>">
                                    <?php esc_html_e('Excluir', 'limiter-mkp-pro'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('Nenhum plano encontrado.', 'limiter-mkp-pro'); ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // 2. Preencher Formulário ao Clicar em Editar (ATUALIZADO)
    $('.limiter-mkp-pro-edit-plano').on('click', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var nome = $(this).data('nome');
        var descricao = $(this).data('descricao');
        var duracao = $(this).data('duracao');
        var limite = $(this).data('limite');
        var limiteInodes = $(this).data('limite-inodes');
        var limiteUpload = $(this).data('limite-upload');
        
        // NOVOS DADOS
        var backupFreq = $(this).data('backup-freq');
        var backupRet = $(this).data('backup-ret');
        
        var produtoIds = $(this).data('produto-ids'); 
        var planoTypes = $(this).data('plano-types');
        var planoStatus = $(this).data('plano-status');

        $('#limiter-mkp-pro-plano-id').val(id);
        $('#limiter-mkp-pro-plano-nome').val(nome);
        $('#limiter-mkp-pro-plano-descricao').val(descricao);
        $('#limiter-mkp-pro-plano-duracao').val(duracao);
        $('#limiter-mkp-pro-plano-limite').val(limite);
        $('#limiter-mkp-pro-plano-limite-inodes').val(limiteInodes);
        if ($('#limiter-mkp-pro-plano-limite-upload').length) {
            $('#limiter-mkp-pro-plano-limite-upload').val(limiteUpload);
        }
        
        // Preenche Backup
        $('#limiter-mkp-pro-plano-backup-freq').val(backupFreq || 'none');
        $('#limiter-mkp-pro-plano-backup-ret').val(backupRet || 3);

        // Preenche WooCommerce
        if (produtoIds) {
            if (typeof produtoIds === 'string') { try { produtoIds = JSON.parse(produtoIds); } catch(e) {} }
            $('#limiter-mkp-pro-plano-woocommerce-products').val(produtoIds);
        } else {
            $('#limiter-mkp-pro-plano-woocommerce-products').val([]);
        }

        // Preenche Checkboxes Types
        $('input[name="post_types_contaveis[]"]').prop('checked', false);
        if (planoTypes) {
            if (typeof planoTypes === 'string') { try { planoTypes = JSON.parse(planoTypes); } catch(e) {} }
            if (Array.isArray(planoTypes)) {
                planoTypes.forEach(function(type) {
                    $('input[name="post_types_contaveis[]"][value="' + type + '"]').prop('checked', true);
                });
            }
        }

        // Preenche Checkboxes Status
        $('input[name="post_status_contaveis[]"]').prop('checked', false);
        if (planoStatus) {
            if (typeof planoStatus === 'string') { try { planoStatus = JSON.parse(planoStatus); } catch(e) {} }
            if (Array.isArray(planoStatus)) {
                planoStatus.forEach(function(status) {
                    $('input[name="post_status_contaveis[]"][value="' + status + '"]').prop('checked', true);
                });
            }
        }

        // UI Updates
        $('#limiter-mkp-pro-form-title').text('Editar Plano: ' + nome);
        $('#limiter-mkp-pro-plano-submit').text('Atualizar Plano');
        $('#limiter-mkp-pro-plano-cancel').show();

        $('html, body').animate({
            scrollTop: $('.limiter-mkp-pro-admin-form-container').offset().top - 50
        }, 500);
    });

    // 3. Cancelar Edição (Reset)
    $('#limiter-mkp-pro-plano-cancel').on('click', function(e) {
        e.preventDefault();
        $('#limiter-mkp-pro-plano-form')[0].reset();
        $('#limiter-mkp-pro-plano-id').val(0);
        $('#limiter-mkp-pro-plano-woocommerce-products').val([]);
        
        // Reset Backup fields to default
        $('#limiter-mkp-pro-plano-backup-freq').val('none');
        $('#limiter-mkp-pro-plano-backup-ret').val(3);

        $('input[name="post_types_contaveis[]"]').prop('checked', false);
        $('input[name="post_types_contaveis[]"][value="post"]').prop('checked', true);
        $('input[name="post_types_contaveis[]"][value="page"]').prop('checked', true);

        $('input[name="post_status_contaveis[]"]').prop('checked', false);
        $('input[name="post_status_contaveis[]"][value="publish"]').prop('checked', true);
        $('input[name="post_status_contaveis[]"][value="draft"]').prop('checked', true);
        $('input[name="post_status_contaveis[]"][value="trash"]').prop('checked', true);

        $('#limiter-mkp-pro-form-title').text('Adicionar Novo Plano');
        $('#limiter-mkp-pro-plano-submit').text('Salvar Plano');
        $(this).hide();
    });
});
</script>