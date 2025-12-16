<?php
/**
 * Template para configurações do ciclo de vida
 */
if (!defined('ABSPATH')) {
    exit;
}

$settings = Limiter_MKP_Pro_Lifecycle_Manager::get_settings();
?>
<div class="wrap limiter-mkp-pro-lifecycle-settings">
    <h1><?php _e('Ciclo de Vida & Cobrança', 'limiter-mkp-pro'); ?></h1>
    
    <form id="limiter-lifecycle-settings-form">
        <?php wp_nonce_field('limiter_mkp_pro_lifecycle_settings', 'lifecycle_nonce'); ?>
        
        <div class="limiter-settings-section">
            <h2><?php _e('Falha de Pagamento', 'limiter-mkp-pro'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php _e('Dias de Carência', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <input type="number" name="payment_failure[grace_period_days]" 
                               value="<?php echo esc_attr($settings['payment_failure']['grace_period_days']); ?>" 
                               min="0" max="30" class="small-text">
                        <p class="description">Dias antes de suspender o site após falha de pagamento</p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Dias até Suspensão', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <input type="number" name="payment_failure[suspension_days]" 
                               value="<?php echo esc_attr($settings['payment_failure']['suspension_days']); ?>" 
                               min="1" max="30" class="small-text">
                        <p class="description">Dias até bloquear visualização do site</p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Dias até Bloqueio Total', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <input type="number" name="payment_failure[lock_days]" 
                               value="<?php echo esc_attr($settings['payment_failure']['lock_days']); ?>" 
                               min="1" max="60" class="small-text">
                        <p class="description">Dias até remover acesso de todos os usuários</p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Dias até Exclusão', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <input type="number" name="payment_failure[deletion_days]" 
                               value="<?php echo esc_attr($settings['payment_failure']['deletion_days']); ?>" 
                               min="1" max="365" class="small-text">
                        <p class="description">Dias até excluir completamente o site</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="limiter-settings-section">
            <h2><?php _e('Cancelamento', 'limiter-mkp-pro'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php _e('Suspender Imediatamente', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="cancellation[immediate_suspend]" value="1" 
                                <?php checked($settings['cancellation']['immediate_suspend'], true); ?>>
                            Suspender visualização imediatamente ao cancelar
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Dias de Acesso Admin', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <input type="number" name="cancellation[admin_access_days]" 
                               value="<?php echo esc_attr($settings['cancellation']['admin_access_days']); ?>" 
                               min="0" max="30" class="small-text">
                        <p class="description">Dias que o administrador mantém acesso após cancelamento</p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Dias até Exclusão', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <input type="number" name="cancellation[deletion_days]" 
                               value="<?php echo esc_attr($settings['cancellation']['deletion_days']); ?>" 
                               min="1" max="365" class="small-text">
                        <p class="description">Dias até excluir o site após cancelamento</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="limiter-settings-section">
            <h2><?php _e('Notificações', 'limiter-mkp-pro'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php _e('E-mails Administrativos', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <textarea name="notification_emails" rows="3" class="large-text"><?php 
                            echo esc_textarea($settings['notification_emails']); 
                        ?></textarea>
                        <p class="description">E-mails para notificações (separados por vírgula)</p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('E-mail de Suporte', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <input type="email" name="support_email" 
                               value="<?php echo esc_attr($settings['support_email']); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><?php _e('URL de Planos', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <input type="url" name="plans_url" 
                               value="<?php echo esc_attr($settings['plans_url']); ?>" 
                               class="regular-text">
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="limiter-settings-section">
            <h2><?php _e('Template da Página de Suspensão', 'limiter-mkp-pro'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php _e('HTML Personalizado', 'limiter-mkp-pro'); ?></th>
                    <td>
                        <textarea name="suspension_page_template" rows="10" class="large-text code"><?php 
                            echo esc_textarea($settings['suspension_page_template']); 
                        ?></textarea>
                        <p class="description">
                            Use os placeholders: [BLOG_NAME], [SUPPORT_EMAIL], [PLANS_URL]<br>
                            Deixe em branco para usar o template padrão
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="limiter-settings-section">
            <h2><?php _e('Templates de E-mail', 'limiter-mkp-pro'); ?></h2>
            
            <?php foreach ($settings['email_templates'] as $key => $template): ?>
                <h3><?php echo esc_html($key); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Assunto', 'limiter-mkp-pro'); ?></th>
                        <td>
                            <input type="text" name="email_templates[<?php echo $key; ?>][subject]" 
                                   value="<?php echo esc_attr($template['subject']); ?>" 
                                   class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Mensagem', 'limiter-mkp-pro'); ?></th>
                        <td>
                            <textarea name="email_templates[<?php echo $key; ?>][message]" 
                                      rows="5" class="large-text"><?php 
                                echo esc_textarea($template['message']); 
                            ?></textarea>
                            <p class="description">
                                Placeholders disponíveis: [BLOG_NAME], [BLOG_URL], [CUSTOMER_NAME], 
                                [CUSTOMER_EMAIL], [DAYS_REMAINING], [SUSPENSION_DATE], [DELETION_DATE]
                            </p>
                        </td>
                    </tr>
                </table>
            <?php endforeach; ?>
        </div>
        
        <p class="submit">
            <button type="submit" class="button button-primary"><?php _e('Salvar Configurações', 'limiter-mkp-pro'); ?></button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#limiter-lifecycle-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData + '&action=limiter_save_lifecycle_settings',
            beforeSend: function() {
                $('.submit button').prop('disabled', true).text('Salvando...');
            },
            success: function(response) {
                if (response.success) {
                    alert('Configurações salvas com sucesso!');
                } else {
                    alert('Erro: ' + response.data.message);
                }
            },
            error: function() {
                alert('Erro ao salvar configurações');
            },
            complete: function() {
                $('.submit button').prop('disabled', false).text('Salvar Configurações');
            }
        });
    });
});
</script>