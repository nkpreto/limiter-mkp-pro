<?php
/**
 * Classe para o formulário de configuração de subdomínio
 */
if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Subdomain_Configuration {
    private $blacklist_cache = null;
    
    public function __construct() {
        add_shortcode('mkp_subdomain_configuration', array($this, 'render_configuration_form'));
        add_action('wp_ajax_nopriv_mkp_check_subdomain', array($this, 'check_subdomain_availability'));
        add_action('wp_ajax_mkp_check_subdomain', array($this, 'check_subdomain_availability'));
        add_action('wp_ajax_nopriv_mkp_configure_subdomain', array($this, 'handle_subdomain_configuration'));
        add_action('wp_ajax_mkp_configure_subdomain', array($this, 'handle_subdomain_configuration'));
    }
    
    public function render_configuration_form() {
        $token = sanitize_text_field($_GET['token'] ?? '');
        if (!$token) {
            return '<div class="mkp-error">Token inválido ou expirado.</div>';
        }
        
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-token-manager.php';
        $token_data = Limiter_MKP_Pro_Token_Manager::validate_token($token);
        if (!$token_data) {
            return '<div class="mkp-error">Token inválido ou expirado.</div>';
        }
        
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $plano = Limiter_MKP_Pro_Database::get_plano($token_data->plano_id);
        if (!$plano) {
            return '<div class="mkp-error">Plano não encontrado. Por favor, entre em contato com o suporte.</div>';
        }
        
        // Obtém configurações personalizadas
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-configuracoes.php';
        $config_class = new Limiter_MKP_Pro_Configuracoes('limiter-mkp-pro', '1.0.0');
        $config = $config_class->get_configuracoes();
        
        $subdominio_disponivel = $config['subdominio_disponivel'];
        $subdominio_indisponivel = $config['subdominio_indisponivel'];
        $subdominio_curto = $config['subdominio_curto'];
        
        ob_start();
        ?>
        <div class="mkp-subdomain-configuration">
            <h2>Configurar Subdomínio</h2>
            <div class="mkp-plan-info">
                <h3>Plano: <?php echo esc_html($plano->nome); ?></h3>
                <p>Limite: <?php echo esc_html($plano->limite_paginas); ?> páginas/posts</p>
            </div>
            <form id="mkp-subdomain-form" class="mkp-config-form">
                <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                <input type="hidden" name="action" value="mkp_configure_subdomain">
                <?php wp_nonce_field('mkp_configure_subdomain', 'mkp_nonce'); ?>
                <div class="form-group">
                    <label for="subdomain_name">Nome do Subdomínio *</label>
                    <input type="text" id="subdomain_name" name="subdomain_name" required 
                           placeholder="ex: loja, empresa, meunegocio" pattern="[a-zA-Z0-9-]+" 
                           title="Use apenas letras, números e hífens">
                    <div class="subdomain-preview">
                        Seu subdomínio será: <span id="subdomain_preview">.marketing-place.store</span>
                    </div>
                    <div id="subdomain_availability" class="availability-status"></div>
                    <small>Use apenas letras, números e hífens. O sufixo será adicionado automaticamente.</small>
                </div>
                <div class="form-group">
                    <label for="password">Senha *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Senha *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div id="password_match" class="password-status"></div>
                </div>
                <div class="form-group">
                    <button type="submit" id="mkp-submit-btn" class="button button-primary">
                        Criar Subdomínio
                    </button>
                </div>
                <div id="mkp-message" class="mkp-message"></div>
            </form>
        </div>
        <style>
        .mkp-subdomain-configuration {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .mkp-plan-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .subdomain-preview {
            margin: 5px 0;
            font-style: italic;
            color: #666;
        }
        .availability-status, .password-status {
            margin: 5px 0;
            font-weight: bold;
        }
        .available { color: green; }
        .unavailable { color: red; }
        .mkp-message { margin-top: 15px; padding: 10px; border-radius: 4px; }
        .mkp-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mkp-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            var checkTimeout;
            $('#subdomain_name').on('input', function() {
                var name = $(this).val().toLowerCase().replace(/[^a-z0-9-]/g, '');
                var sufixo = '<?php echo esc_js(Limiter_MKP_Pro_Configuracoes::get_sufixo_subdominio()); ?>';
                $('#subdomain_preview').text(name + sufixo + '.marketing-place.store');
                clearTimeout(checkTimeout);
                checkTimeout = setTimeout(function() {
                    if (name.length > 2) {
                        checkSubdomainAvailability(name);
                    } else if (name.length > 0) {
                        $('#subdomain_availability').html('<?php echo esc_js($subdominio_curto); ?>').removeClass('available').addClass('unavailable');
                    } else {
                        $('#subdomain_availability').empty();
                    }
                }, 500);
            });
            $('#confirm_password').on('input', function() {
                var password = $('#password').val();
                var confirm = $(this).val();
                if (confirm.length > 0) {
                    if (password === confirm) {
                        $('#password_match').html('✓ Senhas coincidem').removeClass('unavailable').addClass('available');
                    } else {
                        $('#password_match').html('✗ Senhas não coincidem').removeClass('available').addClass('unavailable');
                    }
                } else {
                    $('#password_match').empty();
                }
            });
            function checkSubdomainAvailability(name) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'mkp_check_subdomain',
                        subdomain_name: name,
                        nonce: '<?php echo wp_create_nonce('mkp_check_subdomain'); ?>'
                    },
                    beforeSend: function() {
                        $('#subdomain_availability').html('Verificando...');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#subdomain_availability').html('<?php echo esc_js($subdominio_disponivel); ?>').removeClass('unavailable').addClass('available');
                        } else {
                            $('#subdomain_availability').html('<?php echo esc_js($subdominio_indisponivel); ?>').removeClass('available').addClass('unavailable');
                        }
                    },
                    error: function() {
                        $('#subdomain_availability').html('Erro na verificação. Tente novamente.').removeClass('available').addClass('unavailable');
                    }
                });
            }
            $('#mkp-subdomain-form').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                var $submitBtn = $('#mkp-submit-btn');
                var $message = $('#mkp-message');
                $submitBtn.prop('disabled', true).text('Criando...');
                $message.removeClass('mkp-success mkp-error').empty();
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $message.html(response.data.message).addClass('mkp-success');
                            $('#mkp-subdomain-form').hide();
                        } else {
                            $message.html(response.data.message).addClass('mkp-error');
                            $submitBtn.prop('disabled', false).text('Criar Subdomínio');
                        }
                    },
                    error: function() {
                        $message.html('Erro ao processar solicitação. Tente novamente.').addClass('mkp-error');
                        $submitBtn.prop('disabled', false).text('Criar Subdomínio');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function check_subdomain_availability() {
        try {
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mkp_check_subdomain')) {
                wp_send_json_error(array('message' => 'Erro de segurança.'));
                exit;
            }
            
            $subdomain_name = sanitize_text_field($_POST['subdomain_name'] ?? '');
            if (!$subdomain_name) {
                wp_send_json_error(array('message' => 'Nome do subdomínio é obrigatório.'));
                exit;
            }
            
            // Sufixo dinâmico
            $sufixo = Limiter_MKP_Pro_Configuracoes::get_sufixo_subdominio();
            $full_domain = $subdomain_name . $sufixo;
            
            // Blacklist APRIMORADA
            $reserved_words = $this->get_blacklist_cached();
            $current_site = get_current_site();
            
            // Verifica se alguma palavra reservada está contida no nome
            foreach ($reserved_words as $word) {
                if (strpos($subdomain_name, $word) !== false || strpos($full_domain, $word) !== false) {
                    wp_send_json_error(array('message' => 'Nome contém termos reservados.'));
                    exit;
                }
                
                // Verifica padrões como "admin1", "admin2", etc.
                if (preg_match('/^' . preg_quote($word, '/') . '\d+$/', $subdomain_name)) {
                    wp_send_json_error(array('message' => 'Este nome está em uma lista de nomes reservados.'));
                    exit;
                }
            }
            
            // Verifica se já existe no banco de dados
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            $existing = Limiter_MKP_Pro_Database::get_subdomain_by_name($full_domain);
            if ($existing) {
                wp_send_json_error(array('message' => 'Este subdomínio já está em uso. Escolha outro nome.'));
                exit;
            }
            
            // Verifica se o subdomínio existe no WordPress
            if (domain_exists($full_domain . '.' . $current_site->domain, '/', $current_site->id)) {
                wp_send_json_error(array('message' => 'Este subdomínio não está disponível. Escolha outro nome.'));
                exit;
            }
            
            wp_send_json_success(array('message' => 'Subdomínio disponível!'));
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error('Erro na verificação de subdomínio', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'subdomain_name' => $subdomain_name ?? ''
            ]);
            wp_send_json_error(array('message' => 'Ocorreu um erro ao verificar o subdomínio. Por favor, tente novamente.'));
        }
    }
    
    public function handle_subdomain_configuration() {
        try {
            if (!wp_verify_nonce($_POST['mkp_nonce'] ?? '', 'mkp_configure_subdomain')) {
                wp_send_json_error(array('message' => 'Erro de segurança.'));
                exit;
            }
            
            $token = sanitize_text_field($_POST['token'] ?? '');
            $subdomain_name = sanitize_text_field($_POST['subdomain_name'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (!$token || !$subdomain_name || !$password) {
                wp_send_json_error(array('message' => 'Todos os campos são obrigatórios.'));
                exit;
            }
            
            if (strlen($password) < 6) {
                wp_send_json_error(array('message' => 'A senha deve ter pelo menos 6 caracteres.'));
                exit;
            }
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-token-manager.php';
            $token_data = Limiter_MKP_Pro_Token_Manager::validate_token($token);
            if (!$token_data) {
                wp_send_json_error(array('message' => 'Token inválido ou expirado.'));
                exit;
            }
            
            // Sufixo dinâmico
            $sufixo = Limiter_MKP_Pro_Configuracoes::get_sufixo_subdominio();
            $full_domain = $subdomain_name . $sufixo;
            
            // Blacklist APRIMORADA
            $reserved_words = $this->get_blacklist_cached();
            $current_site = get_current_site();
            
            foreach ($reserved_words as $word) {
                if (strpos($subdomain_name, $word) !== false || strpos($full_domain, $word) !== false) {
                    wp_send_json_error(array('message' => 'Nome contém termos reservados.'));
                    exit;
                }
            }
            
            if (domain_exists($full_domain . '.' . $current_site->domain, '/', $current_site->id)) {
                wp_send_json_error(array('message' => 'Subdomínio não disponível.'));
                exit;
            }
            
            $result = $this->create_subdomain($token_data, $full_domain, $password);
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => 'Erro ao criar subdomínio: ' . $result->get_error_message()));
                exit;
            }
            
            Limiter_MKP_Pro_Token_Manager::mark_token_used($token, $full_domain);
            $this->send_welcome_email($token_data, $full_domain, $password);
            
            // Log da criação do subdomínio
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::log($result, 'subdominio_criado', "Subdomínio '{$full_domain}' criado com sucesso via token", [
                'token_data' => (array)$token_data,
                'user_id' => $token_data->user_id
            ]);
            
            wp_send_json_success(array(
                'message' => 'Subdomínio criado com sucesso! Verifique seu e-mail para obter as credenciais de acesso.'
            ));
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error('Erro na configuração de subdomínio', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'token' => $token ?? '',
                'subdomain_name' => $subdomain_name ?? ''
            ]);
            wp_send_json_error(['message' => 'Ocorreu um erro interno. Por favor, contate o suporte técnico.']);
        }
    }
    
    private function create_subdomain($token_data, $domain, $password) {
        try {
            $user_id = $token_data->user_id;
            $user = get_user_by('id', $user_id);
            if (!$user) {
                return new WP_Error('user_not_found', 'Usuário não encontrado.');
            }
            
            // Define a senha do usuário
            wp_set_password($password, $user_id);
            
            $current_site = get_current_site();
            $blog_id = wpmu_create_blog(
                $domain . '.' . $current_site->domain,
                $current_site->path,
                $domain,
                $user_id,
                array('public' => 1),
                $current_site->id
            );
            
            if (is_wp_error($blog_id)) {
                return $blog_id;
            }
            
            // Adiciona o usuário como administrador do blog
            add_user_to_blog($blog_id, $user_id, 'administrator');
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::save_subdominio(array(
                'blog_id' => $blog_id,
                'user_id' => $user_id,
                'dominio' => $domain . '.' . $current_site->domain,
                'plano_id' => $token_data->plano_id,
                'nome_cliente' => $user->display_name,
                'email_cliente' => $user->user_email,
                'status' => 'active',
                'regiao' => $token_data->regiao ?? 'global'
            ));
            
            return $blog_id;
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error("Erro ao criar subdomínio {$domain}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $token_data->user_id ?? 0
            ]);
            return new WP_Error('creation_error', 'Erro interno ao criar subdomínio: ' . $e->getMessage());
        }
    }
    
    private function send_welcome_email($token_data, $domain, $password) {
        try {
            $user = get_user_by('id', $token_data->user_id);
            if (!$user) {
                return false;
            }
            
            $plano = Limiter_MKP_Pro_Database::get_plano($token_data->plano_id);
            if (!$plano) {
                return false;
            }
            
            $current_site = get_current_site();
            $admin_url = "https://{$domain}.{$current_site->domain}/wp-admin";
            $site_url = "https://{$domain}.{$current_site->domain}";
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-email.php';
            
            $dados_usuario = array(
                'nome_cliente' => $user->display_name,
                'plano_nome' => $plano->nome,
                'limite_paginas' => $plano->limite_paginas,
                'nome_subdominio' => $domain,
                'email_usuario' => $user->user_email,
                'senha' => $password,
                'url_admin' => $admin_url,
                'url_site' => $site_url
            );
            
            return Limiter_MKP_Pro_Email::enviar_email_boas_vindas($user->user_email, $dados_usuario);
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error("Erro ao enviar e-mail de boas-vindas", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $token_data->user_id ?? 0,
                'domain' => $domain
            ]);
            return false;
        }
    }
    
    /**
     * Obtém a blacklist em cache para melhor performance
     */
    private function get_blacklist_cached() {
        if ($this->blacklist_cache === null) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-configuracoes.php';
            $this->blacklist_cache = Limiter_MKP_Pro_Configuracoes::get_subdomain_blacklist();
        }
        return $this->blacklist_cache;
    }
}