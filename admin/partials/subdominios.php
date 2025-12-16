<?php
/**
 * Template para a página de subdomínios.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/admin/partials
 */
?>
<div class="wrap limiter-mkp-pro-admin-subdominios">
    <h1><?php _e('Limiter MKP Pro - Subdomínios', 'limiter-mkp-pro'); ?></h1>
    
    <div class="limiter-mkp-pro-admin-form-container">
        <h2 id="limiter-mkp-pro-form-title"><?php _e('Editar Subdomínio', 'limiter-mkp-pro'); ?></h2>
        <form id="limiter-mkp-pro-subdominio-form" class="limiter-mkp-pro-admin-form">
            <input type="hidden" id="limiter-mkp-pro-subdominio-blog-id" name="blog_id" value="0">
            <input type="hidden" id="limiter-mkp-pro-subdominio-dominio" name="dominio" value="">
            
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
                <label for="limiter-mkp-pro-subdominio-limite"><?php _e('Limite Personalizado de Páginas (opcional)', 'limiter-mkp-pro'); ?></label>
                <input type="number" id="limiter-mkp-pro-subdominio-limite" name="limite_personalizado" min="1">
                <p class="description"><?php _e('Se definido, substitui o limite de páginas do plano selecionado.', 'limiter-mkp-pro'); ?></p>
            </div>
            
            <div class="limiter-mkp-pro-admin-form-row">
                <label for="limiter-mkp-pro-subdominio-limite-inodes"><?php _e('Limite Personalizado de Arquivos (opcional)', 'limiter-mkp-pro'); ?></label>
                <input type="number" id="limiter-mkp-pro-subdominio-limite-inodes" name="limite_personalizado_inodes" min="1">
                <p class="description"><?php _e('Se definido, substitui o limite de arquivos do plano selecionado.', 'limiter-mkp-pro'); ?></p>
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
                <button type="button" id="limiter-mkp-pro-subdominio-cancel" class="button"><?php _e('Cancelar', 'limiter-mkp-pro'); ?></button>
                <button type="submit" id="limiter-mkp-pro-subdominio-submit" class="button button-primary"><?php _e('Salvar Subdomínio', 'limiter-mkp-pro'); ?></button>
            </div>
        </form>
    </div>
    
    <div class="limiter-mkp-pro-admin-list-container">
        <h2><?php _e('Subdomínios Gerenciados', 'limiter-mkp-pro'); ?></h2>
        <?php if (!empty($subdominios)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Subdomínio', 'limiter-mkp-pro'); ?></th>
                        <th><?php _e('Cliente', 'limiter-mkp-pro'); ?></th>
                        <th><?php _e('Plano', 'limiter-mkp-pro'); ?></th>
                        <th><?php _e('Limite Páginas', 'limiter-mkp-pro'); ?></th>
                        <th><?php _e('Limite Arquivos', 'limiter-mkp-pro'); ?></th>
                        <th><?php _e('Páginas/Arquivos Utilizados', 'limiter-mkp-pro'); ?></th>
                        <th><?php _e('Ações', 'limiter-mkp-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subdominios as $subdominio) : 
                        // Obtém informações do site
                        $site_info = get_blog_details($subdominio->blog_id);
                        
                        // Obtém o plano
                        $plano = Limiter_MKP_Pro_Database::get_plano($subdominio->plano_id);
                        
                        // Conta conteúdo usando o método OTIMIZADO (cache no banco)
                        $total_pages = Limiter_MKP_Pro_Database::count_limited_content($subdominio->blog_id);
                        
                        // OTIMIZAÇÃO DE INODES:
                        // Em vez de wp_upload_dir (errado) ou switch_to_blog (lento),
                        // lemos a opção salva diretamente da tabela wp_x_options deste blog.
                        $count_files = (int) get_blog_option($subdominio->blog_id, 'limiter_mkp_pro_inode_count', 0);
                        
                        // Define os limites
                        $limite_paginas = $subdominio->limite_personalizado ? $subdominio->limite_personalizado : ($plano ? $plano->limite_paginas : 0);
                        $limite_inodes = $subdominio->limite_personalizado_inodes ? $subdominio->limite_personalizado_inodes : ($plano ? $plano->limite_inodes : 0);
                        
                        // Calcula percentuais
                        $percentual_paginas = $limite_paginas > 0 ? min(100, round(($total_pages / $limite_paginas) * 100)) : 0;
                        $percentual_inodes = $limite_inodes > 0 ? min(100, round(($count_files / $limite_inodes) * 100)) : 0;
                    ?>
                        <tr>
                            <td>
                                <?php if ($site_info) : ?>
                                    <strong><?php echo esc_html($site_info->blogname); ?></strong><br>
                                    <a href="<?php echo esc_url($site_info->siteurl); ?>" target="_blank"><?php echo esc_html($site_info->domain . $site_info->path); ?></a>
                                <?php else : ?>
                                    <strong><?php _e('Site Deletado', 'limiter-mkp-pro'); ?></strong><br>
                                    <span class="description">ID: <?php echo esc_html($subdominio->blog_id); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($subdominio->nome_cliente)) : ?>
                                    <?php echo esc_html($subdominio->nome_cliente); ?><br>
                                    <?php if (!empty($subdominio->email_cliente)) : ?>
                                        <a href="mailto:<?php echo esc_attr($subdominio->email_cliente); ?>"><?php echo esc_html($subdominio->email_cliente); ?></a><br>
                                    <?php endif; ?>
                                    <?php if (!empty($subdominio->telefone_cliente)) : ?>
                                        <?php echo esc_html($subdominio->telefone_cliente); ?>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <em><?php _e('Não informado', 'limiter-mkp-pro'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($plano ? $plano->nome : 'Plano Removido'); ?></td>
                            <td>
                                <?php echo esc_html($limite_paginas); ?>
                                <?php if ($subdominio->limite_personalizado) : ?>
                                    <br><small><?php _e('(Personalizado)', 'limiter-mkp-pro'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html($limite_inodes); ?>
                                <?php if ($subdominio->limite_personalizado_inodes) : ?>
                                    <br><small><?php _e('(Personalizado)', 'limiter-mkp-pro'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="margin-bottom: 8px;">
                                    <strong><?php _e('Páginas', 'limiter-mkp-pro'); ?>:</strong> <?php echo esc_html($total_pages); ?> / <?php echo esc_html($limite_paginas); ?>
                                    <div class="limiter-mkp-pro-admin-progress-bar">
                                        <?php 
                                        $class_paginas = $percentual_paginas >= 90 ? 'limiter-mkp-pro-admin-progress-warning' : '';
                                        ?>
                                        <div class="limiter-mkp-pro-admin-progress <?php echo esc_attr($class_paginas); ?>" style="width: <?php echo esc_attr($percentual_paginas); ?>%;">
                                            <?php echo esc_html($percentual_paginas); ?>%
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <strong><?php _e('Arquivos', 'limiter-mkp-pro'); ?>:</strong> <?php echo esc_html($count_files); ?> / <?php echo esc_html($limite_inodes); ?>
                                    <div class="limiter-mkp-pro-admin-progress-bar">
                                        <?php 
                                        $class_inodes = $percentual_inodes >= 90 ? 'limiter-mkp-pro-admin-progress-warning' : '';
                                        ?>
                                        <div class="limiter-mkp-pro-admin-progress <?php echo esc_attr($class_inodes); ?>" style="width: <?php echo esc_attr($percentual_inodes); ?>%;">
                                            <?php echo esc_html($percentual_inodes); ?>%
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="button limiter-mkp-pro-edit-subdominio" 
                                    data-blog-id="<?php echo esc_attr($subdominio->blog_id); ?>"
                                    data-dominio="<?php echo esc_attr(($site_info) ? $site_info->domain . $site_info->path : 'Site Deletado'); ?>"
                                    data-plano-id="<?php echo esc_attr($subdominio->plano_id); ?>"
                                    data-limite="<?php echo esc_attr($subdominio->limite_personalizado); ?>"
                                    data-limite-inodes="<?php echo esc_attr($subdominio->limite_personalizado_inodes); ?>"
                                    data-nome="<?php echo esc_attr($subdominio->nome_cliente); ?>"
                                    data-email="<?php echo esc_attr($subdominio->email_cliente); ?>"
                                    data-telefone="<?php echo esc_attr($subdominio->telefone_cliente); ?>">
                                    <?php _e('Editar', 'limiter-mkp-pro'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php _e('Nenhum subdomínio encontrado.', 'limiter-mkp-pro'); ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Esconde o formulário inicialmente
    $('.limiter-mkp-pro-admin-form-container').hide();
    
    // Editar subdomínio
    $('.limiter-mkp-pro-edit-subdominio').on('click', function() {
        var blog_id = $(this).data('blog-id');
        var dominio = $(this).data('dominio');
        var plano_id = $(this).data('plano-id');
        var limite = $(this).data('limite');
        var limite_inodes = $(this).data('limite-inodes');
        var nome = $(this).data('nome');
        var email = $(this).data('email');
        var telefone = $(this).data('telefone');
        
        $('#limiter-mkp-pro-subdominio-blog-id').val(blog_id);
        $('#limiter-mkp-pro-subdominio-dominio').val(dominio);
        $('#limiter-mkp-pro-subdominio-plano').val(plano_id);
        $('#limiter-mkp-pro-subdominio-limite').val(limite);
        $('#limiter-mkp-pro-subdominio-limite-inodes').val(limite_inodes);
        $('#limiter-mkp-pro-subdominio-nome-cliente').val(nome);
        $('#limiter-mkp-pro-subdominio-email-cliente').val(email);
        $('#limiter-mkp-pro-subdominio-telefone-cliente').val(telefone);
        
        $('.limiter-mkp-pro-admin-form-container').show();
        
        $('html, body').animate({
            scrollTop: $('.limiter-mkp-pro-admin-form-container').offset().top - 50
        }, 500);
    });
    
    // Cancelar edição
    $('#limiter-mkp-pro-subdominio-cancel').on('click', function() {
        $('.limiter-mkp-pro-admin-form-container').hide();
        $('#limiter-mkp-pro-subdominio-form')[0].reset();
    });
    
    // Salvar subdomínio
    $('#limiter-mkp-pro-subdominio-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            'action': 'limiter_mkp_pro_save_subdominio',
            'nonce': limiter_mkp_pro_admin.nonce,
            'blog_id': $('#limiter-mkp-pro-subdominio-blog-id').val(),
            'dominio': $('#limiter-mkp-pro-subdominio-dominio').val(),
            'plano_id': $('#limiter-mkp-pro-subdominio-plano').val(),
            'limite_personalizado': $('#limiter-mkp-pro-subdominio-limite').val(),
            'limite_personalizado_inodes': $('#limiter-mkp-pro-subdominio-limite-inodes').val(),
            'nome_cliente': $('#limiter-mkp-pro-subdominio-nome-cliente').val(),
            'email_cliente': $('#limiter-mkp-pro-subdominio-email-cliente').val(),
            'telefone_cliente': $('#limiter-mkp-pro-subdominio-telefone-cliente').val()
        };
        
        $.ajax({
            url: limiter_mkp_pro_admin.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(limiter_mkp_pro_admin.messages.error);
            }
        });
    });
});
</script>