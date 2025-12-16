<?php
/**
 * Classe responsável pelo Shortcode de consulta de disponibilidade de subdomínio no frontend.
 *
 * @since      1.4.1
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/public
 */
if (!defined('ABSPATH')) {
    exit;
} // <--- Chave adicionada aqui para corrigir o erro de sintaxe

class Limiter_MKP_Pro_Subdomain_Checker {
    public function __construct() {
        // Shortcode: [mkp_subdomain_checker]
        add_shortcode('mkp_subdomain_checker', array($this, 'render_checker_form'));
    }

    public function render_checker_form($atts) {
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }
        $atts = shortcode_atts(array(
            'button_text' => 'Verificar Disponibilidade',
        ), $atts);
        
        // Correção: Instanciar a classe de configurações corretamente
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-configuracoes.php';
        $configuracoes = new Limiter_MKP_Pro_Configuracoes('limiter-mkp-pro', '1.4.1');
        $config = $configuracoes->get_configuracoes();
        
        $sufixo = isset($config['sufixo_subdominio']) ? $config['sufixo_subdominio'] : '-mkp';
        $site_domain = defined('DOMAIN_CURRENT_SITE') ? DOMAIN_CURRENT_SITE : $_SERVER['HTTP_HOST'];
        ob_start();
        ?>
        <div id="mkp-checker-container">
            <div class="mkp-form-group">
                <label for="mkp_checker_input">Verifique o nome do seu site:</label>
                <div class="mkp-input-wrapper" style="position:relative;">
                    <input type="text" id="mkp_checker_input" name="subdominio" placeholder="Ex: minhaempresa" autocomplete="off">
                    <span class="mkp-checker-spinner" style="display:none; position:absolute; right:10px; top:10px;">↻</span>
                </div>
            </div>
            <div id="subdomain-feedback-checker"></div>
            <div class="mkp-preview-wrapper">
                <small>Seu endereço será: <strong><span id="checker-preview-name">...</span><?php echo esc_html($sufixo); ?>.<span id="checker-domain"><?php echo esc_html($site_domain); ?></span></strong></small>
            </div>
        </div>
        <style>
            #mkp-checker-container { max-width: 500px; margin: 20px 0; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px; background: #f9f9f9; }
            .mkp-form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
            .mkp-form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
            #mkp_checker_input.available { border-color: #28a745; background-color: #f8fff9; }
            #mkp_checker_input.taken { border-color: #dc3545; background-color: #fff8f8; }
            #mkp_checker_input.checking { border-color: #ffc107; }
            .checker-message { margin-top: 10px; padding: 10px; border-radius: 4px; font-size: 14px; }
            .checker-message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .checker-message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .checker-message.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
            .mkp-preview-wrapper { margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ccc; color: #555; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            var checkTimeout = null;
            var currentRequest = null; 
            var $input = $('#mkp_checker_input');
            var $spinner = $('.mkp-checker-spinner');
            var $previewName = $('#checker-preview-name');
            var $feedback = $('#subdomain-feedback-checker');
            
            $input.on('input', function() {
                var rawValue = $(this).val().toLowerCase();
                var cleanValue = rawValue.replace(/[^a-z0-9-]/g, '');
                if (rawValue !== cleanValue) $input.val(cleanValue);
                $previewName.text(cleanValue);
                $input.removeClass('available taken checking');
                $feedback.empty();
                
                if (currentRequest) { currentRequest.abort(); currentRequest = null; }
                clearTimeout(checkTimeout);
                
                if (cleanValue.length < 3) {
                    if(cleanValue.length > 0) showCheckerMessage('Mínimo 3 caracteres.', 'warning');
                    return;
                }
                
                $spinner.show();
                $input.addClass('checking');
                checkTimeout = setTimeout(function() {
                    checkAvailability(cleanValue);
                }, 600); 
            });
            
            function checkAvailability(subdomain) {
                currentRequest = $.ajax({
                    // Reutiliza o endpoint do formulário principal
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: { 'action': 'check_subdomain_availability', 'subdominio': subdomain },
                    success: function(response) {
                        $spinner.hide();
                        $input.removeClass('checking');
                        if (response.success) {
                            $input.addClass('available');
                            showCheckerMessage('✓ ' + response.data.message, 'success');
                        } else {
                            $input.addClass('taken');
                            showCheckerMessage('✗ ' + response.data.message, 'error');
                        }
                    },
                    error: function(jqXHR) {
                        if(jqXHR.statusText !== 'abort') {
                            $spinner.hide(); $input.removeClass('checking');
                        }
                    }
                });
            }
            
            function showCheckerMessage(message, type) {
                $feedback.html('<div class="checker-message ' + type + '">' + message + '</div>');
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
new Limiter_MKP_Pro_Subdomain_Checker();