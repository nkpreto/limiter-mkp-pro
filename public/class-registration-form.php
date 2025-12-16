<?php
/**
 * Classe responsável pelo formulário de registro de subdomínios.
 *
 * @since      1.2.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/public
 */
class Limiter_MKP_Pro_Registration_Form {
  
    public function __construct() {
        add_shortcode('mkp_registration_form', array($this, 'render_registration_form'));
        add_action('wp_ajax_process_mkp_registration', array($this, 'process_registration'));
        add_action('wp_ajax_nopriv_process_mkp_registration', array($this, 'process_registration'));
        add_action('wp_ajax_check_subdomain_availability', array($this, 'check_subdomain_availability'));
        add_action('wp_ajax_nopriv_check_subdomain_availability', array($this, 'check_subdomain_availability'));
    }

    public function render_registration_form($atts) {
        if (is_user_logged_in()) {
            return '<p>Você já está logado. <a href="' . wp_logout_url(home_url()) . '">Sair</a></p>';
        }
        
        $planos = shortcode_atts(array(
            'plano_id' => '',
            'plano_nome' => 'Plano Básico'
        ), $atts);

        // Obtém configurações personalizadas
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-configuracoes.php';
        $config_class = new Limiter_MKP_Pro_Configuracoes('limiter-mkp-pro', '1.0.0');
        $config = $config_class->get_configuracoes();
        
        $subdominio_disponivel = $config['subdominio_disponivel'];
        $subdominio_indisponivel = $config['subdominio_indisponivel'];
        $subdominio_curto = $config['subdominio_curto'];
        $registro_concluido = $config['registro_concluido'];
        
        ob_start();
        ?>
        <div id="mkp-registration-form-container">
            <form id="mkp-registration-form" enctype="multipart/form-data">
                <?php wp_nonce_field('mkp_registration_nonce', 'registration_nonce'); ?>
                <input type="hidden" name="plano_id" value="<?php echo esc_attr($planos['plano_id']); ?>">
                <input type="hidden" name="plano_nome" value="<?php echo esc_attr($planos['plano_nome']); ?>">
                <input type="hidden" name="action" value="process_mkp_registration">
            
                <h3>Dados Pessoais</h3>
                <div class="form-row">
                    <label for="mkp_nome">Nome Completo *</label>
                    <input type="text" id="mkp_nome" name="nome" required>
                </div>
                
                <div class="form-row">
                    <label for="mkp_email">E-mail *</label>
                    <input type="email" id="mkp_email" name="email" required>
                </div>

                <div class="form-row">
                    <label for="mkp_endereco">Endereço Completo *</label>
                    <textarea id="mkp_endereco" name="endereco" required></textarea>
                </div>
                
                <div class="form-row">
                    <label for="mkp_telefone">Telefone *</label>
                    <input type="text" id="mkp_telefone" name="telefone" required>
                </div>
                
                <div class="form-row">
                    <label for="mkp_cpf">CPF ou Passaporte *</label>
                    <input type="text" id="mkp_cpf" name="cpf_passaporte" required>
                </div>
                <div class="form-row">
                    <label for="mkp_documento">Documento (RG ou Passaporte) *</label>
                    <input type="file" id="mkp_documento" name="documento" accept=".jpg,.jpeg,.png,.pdf" required>
                    <small>Formatos: JPG, PNG, PDF (máx. 5MB)</small>
                </div>
                
                <h3>Dados de Acesso</h3>
                <div class="form-row">
                    <label for="mkp_subdominio">Nome do Subdomínio *</label>
                    <div class="mkp-input-wrapper" style="position: relative;">
                        <input type="text" id="mkp_subdominio" name="subdominio" autocomplete="off" required>
                        <span class="mkp-loading-spinner" style="display:none; position: absolute; right: 10px; top: 10px;">Checking...</span>
                    </div>
                    <small>Seu subdomínio será: <span id="subdomain-preview">...</span></small>
                    <div id="subdomain-feedback-container"></div>
                </div>
                <div class="form-row">
                    <label for="mkp_username">Nome de Usuário *</label>
                    <input type="text" id="mkp_username" name="username" required>
                </div>
                <div class="form-row">
                    <label for="mkp_password">Senha *</label>
                    <input type="password" id="mkp_password" name="password" minlength="6" required>
                    <small>Mínimo 6 caracteres</small>
                </div>
                <div class="form-row">
                    <label for="mkp_confirm_password">Confirmar Senha *</label>
                    <input type="password" id="mkp_confirm_password" name="confirm_password" minlength="6" required>
                </div>
                <div class="form-row">
                    <button type="submit" id="mkp-submit-btn">Criar Subdomínio</button>
                </div>
                <div id="mkp-message"></div>
            </form>
        </div>
        
        <style>
        .form-row { margin-bottom: 15px; }
        .form-row label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-row input, .form-row textarea, .form-row select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-row small { color: #666; font-size: 12px; }
        /* Estados visuais */
        #mkp_subdominio.subdomain-available { border-color: #28a745; background-color: #f8fff9; }
        #mkp_subdominio.subdomain-taken { border-color: #dc3545; background-color: #fff8f8; }
        #mkp_subdominio.checking { border-color: #ffc107; background-color: #fffdf5; }
        /* Mensagens */
        .subdomain-message { margin-top: 5px; padding: 5px 10px; border-radius: 4px; font-size: 12px; }
        .subdomain-message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .subdomain-message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .subdomain-message.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        #mkp-submit-btn { background: #007cba; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        #mkp-submit-btn:hover { background: #005a87; }
        #mkp-submit-btn:disabled { background: #ccc; cursor: not-allowed; }
        #mkp-message { margin-top: 15px; padding: 10px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        </style>
       
        <script>
        jQuery(document).ready(function($) {
            var subdomainAvailable = false;
            var checkTimeout = null;
            var currentRequest = null;
            var $input = $('#mkp_subdominio');
            var $feedback = $('#subdomain-feedback-container');
            var $submitBtn = $('#mkp-submit-btn');
            var $preview = $('#subdomain-preview');
            
            // Sufixo vindo do PHP
            var sufixo = '<?php 
                $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
                echo esc_js(isset($config['sufixo_subdominio']) ? $config['sufixo_subdominio'] : '-mkp');
            ?>';
            
            $input.on('input', function() {
                var rawValue = $(this).val().toLowerCase();
                var cleanValue = rawValue.replace(/[^a-z0-9-]/g, '');
                if (rawValue !== cleanValue) {
                    $(this).val(cleanValue);
                }
                $preview.text(cleanValue + sufixo + '.' + window.location.hostname);
                
                // Resetar estados
                subdomainAvailable = false;
                $input.removeClass('subdomain-available subdomain-taken checking');
                $feedback.empty();
                clearTimeout(checkTimeout);
                
                // Abortar requisição anterior se existir
                if (currentRequest) {
                    currentRequest.abort();
                    currentRequest = null;
                }
                
                if (cleanValue.length < 3) {
                    if(cleanValue.length > 0) showSubdomainMessage('<?php echo esc_js($subdominio_curto); ?>', 'warning');
                    return;
                }
                
                // Iniciar delay
                $input.addClass('checking');
                checkTimeout = setTimeout(function() {
                    checkSubdomainAvailability(cleanValue);
                }, 600);
            });
            
            function checkSubdomainAvailability(subdomain) {
                // Desabilita botão enquanto verifica
                $submitBtn.prop('disabled', true);
                currentRequest = $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: { 
                        'action': 'check_subdomain_availability',
                        'subdominio': subdomain
                    },
                    success: function(response) {
                        $input.removeClass('checking');
                        if (response.success) {
                            $input.addClass('subdomain-available');
                            subdomainAvailable = true;
                            showSubdomainMessage('<?php echo esc_js($subdominio_disponivel); ?>', 'success');
                            $submitBtn.prop('disabled', false);
                        } else {
                            $input.addClass('subdomain-taken');
                            subdomainAvailable = false;
                            showSubdomainMessage('<?php echo esc_js($subdominio_indisponivel); ?>', 'error');
                        }
                    },
                    error: function(jqXHR) {
                        if (jqXHR.statusText !== 'abort') {
                            $input.removeClass('checking');
                            showSubdomainMessage('Erro de conexão. Tente novamente.', 'error');
                        }
                    },
                    complete: function() {
                        currentRequest = null;
                    }
                });
            }
           
            function showSubdomainMessage(message, type) {
                var html = '<div class="subdomain-message ' + type + '">' + message + '</div>';
                $feedback.html(html);
            }
            
            $('#mkp-registration-form').on('submit', function(e) {
                e.preventDefault();
                if (!subdomainAvailable) {
                    $('#mkp-message').html('<div class="error">Por favor, escolha um subdomínio disponível.</div>');
                    return;
                }
                var password = $('#mkp_password').val();
                var confirmPassword = $('#mkp_confirm_password').val();
                if (password !== confirmPassword) {
                    $('#mkp-message').html('<div class="error">As senhas não coincidem.</div>');
                    return;
                }
                
                var formData = new FormData(this);
                $submitBtn.prop('disabled', true).text('Processando...');
                $('#mkp-message').empty();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#mkp-message').html('<div class="success"><?php echo esc_js($registro_concluido); ?></div>');
                            $('#mkp-registration-form')[0].reset();
                            subdomainAvailable = false;
                            $input.removeClass('subdomain-available subdomain-taken');
                            $feedback.empty();
                            $preview.text('...');
                        } else {
                            $('#mkp-message').html('<div class="error">' + response.data.message + '</div>');
                            $submitBtn.prop('disabled', false).text('Criar Subdomínio');
                        }
                    },
                    error: function() {
                        $('#mkp-message').html('<div class="error">Erro crítico ao processar. Contate o suporte.</div>');
                        $submitBtn.prop('disabled', false).text('Criar Subdomínio');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function process_registration() {
        if (!isset($_POST['registration_nonce']) || !wp_verify_nonce($_POST['registration_nonce'], 'mkp_registration_nonce')) {
            wp_send_json_error(array('message' => 'Erro de segurança.'));
        }
        
        $nome = sanitize_text_field($_POST['nome']);
        // SANITIZAÇÃO DE EMAIL ADICIONADA
        $email = sanitize_email($_POST['email']);
        $endereco = sanitize_textarea_field($_POST['endereco']);
        $telefone = sanitize_text_field($_POST['telefone']);
        $cpf_passaporte = sanitize_text_field($_POST['cpf_passaporte']);
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $plano_id = intval($_POST['plano_id']);

        // VALIDAÇÃO DE EMAIL ADICIONADA
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => 'O e-mail informado é inválido.'));
        }

        // Limpa e valida o subdomínio
        $subdominio = strtolower(sanitize_text_field($_POST['subdominio'] ?? ''));
        $subdominio = preg_replace('/[^a-z0-9-]/', '', $subdominio);
        
        if (empty($subdominio) || strlen($subdominio) < 3) {
            wp_send_json_error(array('message' => 'Nome do subdomínio inválido (mínimo 3 caracteres).'));
        }
        
        // Instanciação da classe de configurações
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-configuracoes.php';
        $config_class = new Limiter_MKP_Pro_Configuracoes('limiter-mkp-pro', '1.4.1');
        $config = $config_class->get_configuracoes();
        $sufixo = !empty($config['sufixo_subdominio']) ? $config['sufixo_subdominio'] : '-mkp';
        $subdominio_completo = $subdominio . $sufixo;
        
        // Verificação avançada de blacklist
        if (Limiter_MKP_Pro_Configuracoes::is_subdomain_blacklisted($subdominio)) {
            wp_send_json_error(array('message' => 'Este nome de subdomínio é reservado e não pode ser utilizado.'));
        }
        
        global $current_site;
        if (domain_exists($subdominio_completo . '.' . $current_site->domain, '/')) {
            wp_send_json_error(array('message' => 'Este subdomínio já foi escolhido por outro usuário.'));
        }
        
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        if (!isset($_FILES['documento'])) {
            wp_send_json_error(array('message' => 'O documento é obrigatório.'));
        }
        
        $uploaded_file = $_FILES['documento'];
        
        // --- INÍCIO CORREÇÃO DE SEGURANÇA (BLINDAGEM DE UPLOAD) ---
        // 1. Verifica extensão e tipo via WordPress
        $file_check = wp_check_filetype_and_ext($uploaded_file['tmp_name'], $uploaded_file['name']);
        $ext = $file_check['ext'];
        $type = $file_check['type'];
        
        // 2. Lista de permitidos
        $allowed_mimes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'pdf'  => 'application/pdf'
        ];
        
        // 3. Validação Cruzada (Extensão permitida e compatível com MIME do WP)
        if ( !$ext || !$type || !array_key_exists($ext, $allowed_mimes) ) {
            wp_send_json_error(array('message' => 'Tipo de arquivo não permitido. Apenas JPG, PNG e PDF.'));
            exit;
        }

        // 4. Verificação Profunda (Magic Numbers) - BLINDAGEM REAL
        // Garante que o conteúdo do arquivo corresponde à extensão declarada
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $real_mime = finfo_file($finfo, $uploaded_file['tmp_name']);
            finfo_close($finfo);

            // Se o tipo real detectado não bater com o esperado para aquela extensão, bloqueia
            if ($real_mime !== $allowed_mimes[$ext]) {
                 // Tenta registrar tentativa maliciosa se o handler de erro existir
                 if (class_exists('Limiter_MKP_Pro_Error_Handler')) {
                     Limiter_MKP_Pro_Error_Handler::log_security_violation('upload_fake_extension', [
                         'filename' => $uploaded_file['name'],
                         'real_mime' => $real_mime,
                         'claimed_ext' => $ext
                     ]);
                 }
                 wp_send_json_error(array('message' => 'Arquivo corrompido ou inseguro detectado. Upload bloqueado.'));
                 exit;
            }
        }
        // --- FIM CORREÇÃO DE SEGURANÇA ---

        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);
        
        if (isset($movefile['error'])) {
            wp_send_json_error(array('message' => 'Erro no upload do documento: ' . $movefile['error']));
        }
        
        $result = $this->create_subdomain_and_user(
            $nome, $email, $endereco, $telefone, $cpf_passaporte, 
            $movefile['url'], $subdominio_completo, $username, $password, $plano_id
        );

        if ($result['success']) {
            wp_send_json_success(array('message' => 'Subdomínio criado com sucesso! Você receberá um e-mail com as instruções de acesso.'));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    public function check_subdomain_availability() {
        global $current_site;
        $subdominio = strtolower(sanitize_text_field($_POST['subdominio'] ?? ''));
        $subdominio = preg_replace('/[^a-z0-9-]/', '', $subdominio);

        // 1. Validações básicas
        if (strlen($subdominio) < 3) {
            wp_send_json_error(array('message' => 'Muito curto (mínimo 3 caracteres).'));
        }
        
        // 2. Sufixo e verificação
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'admin/class-configuracoes.php';
        $config_class = new Limiter_MKP_Pro_Configuracoes('limiter-mkp-pro', '1.4.1');
        $config = $config_class->get_configuracoes();
        $sufixo = !empty($config['sufixo_subdominio']) ? $config['sufixo_subdominio'] : '-mkp';
        $subdominio_completo = $subdominio . $sufixo;
        
        // 3. Verificação de blacklist avançada
        if (Limiter_MKP_Pro_Configuracoes::is_subdomain_blacklisted($subdominio)) {
            wp_send_json_error(array('message' => 'Este nome de subdomínio é reservado.'));
        }
        
        // 4. Verifica se já existe
        if (domain_exists($subdominio_completo . '.' . $current_site->domain, '/', $current_site->id)) {
            wp_send_json_error(array('message' => 'Indisponível (já existe).'));
        } else {
            // Verifica tabela customizada do plugin para reservas pendentes
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            $existing = Limiter_MKP_Pro_Database::get_subdomain_by_name($subdominio_completo);
            if ($existing) {
                wp_send_json_error(array('message' => 'Indisponível.'));
            } else {
                wp_send_json_success(array('message' => 'Disponível!'));
            }
        }
    }

    private function create_subdomain_and_user($nome, $email, $endereco, $telefone, $cpf_passaporte, $documento_url, $subdominio, $username, $password, $plano_id) {
        global $wpdb, $current_site;

        if (username_exists($username)) {
            return array('success' => false, 'message' => 'Este nome de usuário já está em uso.');
        }

        if (email_exists($email)) {
            return array('success' => false, 'message' => 'Este e-mail já está em uso.');
        }
        
        $table_clientes = $wpdb->base_prefix . 'limiter_mkp_pro_clientes';
        $cpf_existe = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_clientes WHERE cpf_passaporte = %s",
            $cpf_passaporte
        ));

        if ($cpf_existe > 0) {
            return array('success' => false, 'message' => 'Este CPF/Passaporte já foi cadastrado.');
        }
        
        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) {
            return array('success' => false, 'message' => 'Erro ao criar usuário: ' . $user_id->get_error_message());
        }
        
        $domain = $subdominio . '.' . $current_site->domain;
        $path = '/';
        $title = $subdominio;
        
        $blog_id = wpmu_create_blog($domain, $path, $title, $user_id, array('public' => 1));
        if (is_wp_error($blog_id)) {
            wp_delete_user($user_id);
            return array('success' => false, 'message' => 'Erro ao criar subdomínio: ' . $blog_id->get_error_message());
        }
        
        add_user_to_blog($blog_id, $user_id, 'administrator');
        $wpdb->insert(
            $table_clientes,
            array(
                'blog_id' => $blog_id,
                'nome' => $nome,
                'endereco' => $endereco,
                'telefone' => $telefone,
                'cpf_passaporte' => $cpf_passaporte,
                'documento_url' => $documento_url,
                'subdominio' => $subdominio,
                'username' => $username
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        Limiter_MKP_Pro_Database::save_subdominio(array(
            'blog_id' => $blog_id,
            'dominio' => $domain,
            'plano_id' => $plano_id,
            'nome_cliente' => $nome,
            'email_cliente' => $email,
            'telefone_cliente' => $telefone
        ));
        Limiter_MKP_Pro_Database::log($blog_id, 'subdominio_criado', 'Subdomínio criado via formulário de registro');
        
        return array('success' => true, 'blog_id' => $blog_id, 'user_id' => $user_id);
    }
}
new Limiter_MKP_Pro_Registration_Form();