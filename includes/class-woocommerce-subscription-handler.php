<?php
/**
 * Classe para integração com WooCommerce Subscriptions
 *
 * @since      1.3.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */
if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_WooCommerce_Subscription_Handler {
    /**
     * Cache para planos processados
     * * @var array
     */
    private $processed_subscriptions = [];

    public function __construct() {
        // Hooks para integração com WooCommerce Subscriptions
        add_action('woocommerce_subscription_status_updated', array($this, 'handle_subscription_status_change'), 10, 3);
        add_action('woocommerce_subscription_payment_complete', array($this, 'handle_subscription_payment'), 10, 1);
        add_action('woocommerce_subscription_item_switched', array($this, 'handle_subscription_item_switched'), 10, 4);

        // Campo personalizado no produto
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_plano_field_to_product'));
        add_action('woocommerce_process_product_meta', array($this, 'save_plano_field'));

        // AJAX para verificação de atualização de plano
        add_action('wp_ajax_limiter_mkp_pro_check_plan_update', array($this, 'ajax_check_plan_update'));

        // Inicializa cache de assinaturas processadas
        $this->load_processed_subscriptions();
    }
    
    /**
     * Carrega o cache de assinaturas processadas
     */
    private function load_processed_subscriptions() {
        $this->processed_subscriptions = get_transient('mkp_processed_subscriptions') ?: [];
    }
    
    /**
     * Salva uma assinatura como processada
     */
    private function mark_subscription_processed($subscription_id, $plan_id) {
        $this->processed_subscriptions[$subscription_id] = [
            'plan_id' => $plan_id,
            'processed_at' => current_time('mysql')
        ];
        set_transient('mkp_processed_subscriptions', $this->processed_subscriptions, WEEK_IN_SECONDS);
    }
    
    /**
     * Verifica se uma assinatura já foi processada
     */
    private function is_subscription_processed($subscription_id) {
        return isset($this->processed_subscriptions[$subscription_id]);
    }
    
    /**
     * Adiciona campo para vincular produto a um plano
     */
    public function add_plano_field_to_product() {
        global $post;
        echo '<div class="options_group">';
        echo '<h3>Configurações MKP Pro - Subdomínios</h3>';
        
        // Campo para selecionar plano
        woocommerce_wp_select(array(
            'id' => '_mkp_plano_id',
            'label' => 'Plano Vinculado',
            'description' => 'Selecione qual plano este produto de assinatura fornecerá',
            'desc_tip' => true,
            'options' => $this->get_planos_options()
        ));
        
        echo '</div>';
    }
    
    /**
     * Salva o campo do plano
     */
    public function save_plano_field($post_id) {
        $plano_id = isset($_POST['_mkp_plano_id']) ? intval($_POST['_mkp_plano_id']) : 0;
        update_post_meta($post_id, '_mkp_plano_id', $plano_id);
        
        // Vincular produto ao plano no sistema MKP Pro
        if ($plano_id > 0) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
            Limiter_MKP_Pro_WooCommerce_Integration::save_plano_products($plano_id, array($post_id));
        }
    }
    
    /**
     * Obtém opções de planos para o select
     */
    private function get_planos_options() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $planos = Limiter_MKP_Pro_Database::get_planos();
        $options = array(0 => 'Nenhum plano vinculado');
        foreach ($planos as $plano) {
            $options[$plano->id] = $plano->nome . ' (' . $plano->limite_paginas . ' páginas)';
        }
        
        return $options;
    }
    
    /**
     * Lida com mudanças no status da assinatura
     */
    public function handle_subscription_status_change($subscription, $new_status, $old_status) {
        try {
            // Ignora se não é ativa ou o status não mudou para ativo
            if ($new_status !== 'active' && $new_status !== 'on-hold') {
                return;
            }
            
            // Verifica se já foi processada
            if ($this->is_subscription_processed($subscription->get_id())) {
                return;
            }
            
            // Verifica se é uma mudança de plano válida
            if ($this->is_valid_plan_change($subscription)) {
                $this->process_automatic_plan_change($subscription);
                $this->mark_subscription_processed($subscription->get_id(), $this->get_current_plan_id($subscription));
            }
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error('Erro no status change da assinatura', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'subscription_id' => $subscription->get_id() ?? 0,
                'new_status' => $new_status,
                'old_status' => $old_status
            ]);
        }
    }
    
    /**
     * Lida com pagamento completo da assinatura
     */
    public function handle_subscription_payment($subscription) {
        try {
            // Ignora se não está ativa
            if (!$subscription->has_status('active')) {
                return;
            }
            
            // Verifica se já foi processada
            if ($this->is_subscription_processed($subscription->get_id())) {
                return;
            }
            
            // Verifica se é uma mudança de plano válida
            if ($this->is_valid_plan_change($subscription)) {
                $this->process_automatic_plan_change($subscription);
                $this->mark_subscription_processed($subscription->get_id(), $this->get_current_plan_id($subscription));
            }
            
            // ENVIA E-MAIL DE CONFIGURAÇÃO APÓS PAGAMENTO CONFIRMADO
            $new_plan_id = $this->get_current_plan_id($subscription);
            if ($new_plan_id && $new_plan_id > 0) {
                $this->send_configuration_email($subscription, $new_plan_id);
            }
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error('Erro no pagamento da assinatura', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'subscription_id' => $subscription->get_id() ?? 0
            ]);
        }
    }
    
    /**
     * Lida com a troca de itens na assinatura
     */
    public function handle_subscription_item_switched($subscription, $new_item, $old_item, $subscription_key) {
        try {
            // Verifica se já foi processada
            if ($this->is_subscription_processed($subscription->get_id())) {
                return;
            }
            
            // Aguarda o pagamento ser processado
            if ($subscription->has_status('active')) {
                $new_plan_id = $this->get_current_plan_id($subscription);
                $old_plan_id = $this->get_current_plan_id_from_item($old_item);
                
                if ($new_plan_id && $new_plan_id != $old_plan_id) {
                    $this->process_automatic_plan_change($subscription);
                    $this->mark_subscription_processed($subscription->get_id(), $new_plan_id);
                }
            }
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error('Erro na troca de itens da assinatura', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'subscription_id' => $subscription->get_id() ?? 0,
                'subscription_key' => $subscription_key ?? ''
            ]);
        }
    }
    
    /**
     * Verifica se é uma mudança de plano válida
     */
    private function is_valid_plan_change($subscription) {
        try {
            // 1. Verifica se a assinatura está ativa
            if (!$subscription->has_status('active')) {
                return false;
            }
            
            // 2. Verifica se o último pagamento foi bem-sucedido
            $last_order = $subscription->get_last_order();
            if (!$last_order || !$last_order->is_paid()) {
                return false;
            }
            
            // 3. Verifica se há um produto vinculado a um plano
            $new_plan_id = $this->get_current_plan_id($subscription);
            if (!$new_plan_id || $new_plan_id <= 0) {
                return false;
            }
            
            // 4. Verifica se o usuário tem um subdomínio
            $user_id = $subscription->get_user_id();
            $blog_id = $this->get_user_blog_id($user_id);
            
            // Permite criação de novo subdomínio se não existir
            return true;
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error('Erro na validação de mudança de plano', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'subscription_id' => $subscription->get_id() ?? 0
            ]);
            return false;
        }
    }
    
    /**
     * Processa a mudança automática de plano
     */
    private function process_automatic_plan_change($subscription) {
        try {
            $user_id = $subscription->get_user_id();
            $blog_id = $this->get_user_blog_id($user_id);
            $new_plan_id = $this->get_current_plan_id($subscription);
            
            if (!$new_plan_id || $new_plan_id <= 0) {
                throw new Exception('Plano não encontrado para a assinatura ID: ' . $subscription->get_id());
            }
            
            $new_plan = Limiter_MKP_Pro_Database::get_plano($new_plan_id);
            if (!$new_plan) {
                throw new Exception('Dados do plano não encontrados (ID: ' . $new_plan_id . ')');
            }
            
            // Se não tem blog_id, cria um novo blog
            if (!$blog_id) {
                $blog_id = $this->create_new_blog_for_user($user_id, $subscription, $new_plan_id);
            }
            
            $old_plan_id = $this->get_current_user_plan_id($blog_id);

            // 3. Valida a mudança
            $this->validate_plan_change($subscription, $user_id, $new_plan_id);

            // 4. Executa a mudança
            $this->update_blog_plan($blog_id, $new_plan_id, $subscription->get_id());

            // 5. Notifica usuário
            $this->send_success_notification($user_id, $new_plan_id, $blog_id, $subscription->get_id());

            // 6. Log da operação bem-sucedida
            $this->log_success('Mudança de plano processada com sucesso', $subscription, $new_plan_id, $old_plan_id, $blog_id);
            
            return true;
        } catch (Exception $e) {
            $this->handle_plan_change_error($e, $subscription);
            return false;
        }
    }
    
    /**
     * Obtém o ID do plano atual da assinatura
     */
    private function get_current_plan_id($subscription) {
        foreach ($subscription->get_items() as $item) {
            $product_id = $item->get_product_id();
            $plano_id = get_post_meta($product_id, '_mkp_plano_id', true);
            if ($plano_id && intval($plano_id) > 0) {
                return intval($plano_id);
            }
        }
        return null;
    }
    
    /**
     * Obtém o plano atual de um item da assinatura
     */
    private function get_current_plan_id_from_item($item) {
        $product_id = $item->get_product_id();
        $plano_id = get_post_meta($product_id, '_mkp_plano_id', true);
        return $plano_id ? intval($plano_id) : 0;
    }
    
    /**
     * Obtém o ID do blog associado ao usuário
     */
    private function get_user_blog_id($user_id) {
        global $wpdb;
        $table_subdominios = Limiter_MKP_Pro_Database::get_table_name('subdominios');
        
        if (!Limiter_MKP_Pro_Database::validate_table_name($table_subdominios)) {
            return false;
        }
        
        $user_id = intval($user_id);
        if ($user_id <= 0) {
            return false;
        }
        
        $blog_id = $wpdb->get_var($wpdb->prepare(
            "SELECT blog_id FROM $table_subdominios 
             WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        
        return $blog_id ? intval($blog_id) : false;
    }
    
    /**
     * Obtém o plano atual do usuário
     */
    private function get_current_user_plan_id($blog_id) {
        global $wpdb;
        $table_subdominios = Limiter_MKP_Pro_Database::get_table_name('subdominios');
        
        if (!Limiter_MKP_Pro_Database::validate_table_name($table_subdominios)) {
            return 0;
        }
        
        $blog_id = intval($blog_id);
        if ($blog_id <= 0) {
            return 0;
        }
        
        $plan_id = $wpdb->get_var($wpdb->prepare(
            "SELECT plano_id FROM $table_subdominios 
             WHERE blog_id = %d AND status = 'active'",
            $blog_id
        ));
        
        return $plan_id ? intval($plan_id) : 0;
    }
    
    /**
     * Valida a mudança de plano
     */
    private function validate_plan_change($subscription, $user_id, $new_plan_id) {
        try {
            // 1. Verifica se usuário tem permissão
            if (!$this->user_owns_subscription($user_id, $subscription)) {
                throw new Exception('Usuário não é proprietário desta assinatura');
            }
            
            // 2. Verifica se o plano existe
            $new_plan = Limiter_MKP_Pro_Database::get_plano($new_plan_id);
            if (!$new_plan) {
                throw new Exception('Plano não encontrado (ID: ' . $new_plan_id . ')');
            }
            
            // 3. Verifica se o produto está vinculado a um plano válido
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
            $product_id = $this->get_product_id_from_subscription($subscription);
            if (!$product_id) {
                throw new Exception('Produto não encontrado na assinatura');
            }
            
            $plano_by_product = Limiter_MKP_Pro_WooCommerce_Integration::get_plano_by_product($product_id);
            if (!$plano_by_product) {
                throw new Exception('Produto não está vinculado a um plano válido');
            }
            
            return true;
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error('Erro na validação de mudança de plano', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'subscription_id' => $subscription->get_id() ?? 0,
                'user_id' => $user_id,
                'new_plan_id' => $new_plan_id
            ]);
            throw $e;
        }
    }
    
    /**
     * Verifica se o usuário é dono da assinatura
     */
    private function user_owns_subscription($user_id, $subscription) {
        return $subscription->get_user_id() == $user_id;
    }
    
    /**
     * Obtém o ID do produto da assinatura
     */
    private function get_product_id_from_subscription($subscription) {
        foreach ($subscription->get_items() as $item) {
            return $item->get_product_id();
        }
        return null;
    }
    
    /**
     * Atualiza o plano do blog
     */
    private function update_blog_plan($blog_id, $new_plan_id, $subscription_id) {
        global $wpdb;
        $table_subdominios = Limiter_MKP_Pro_Database::get_table_name('subdominios');
        
        if (!Limiter_MKP_Pro_Database::validate_table_name($table_subdominios)) {
            throw new Exception('Tabela de subdomínios inválida');
        }
        
        $blog_id = intval($blog_id);
        $new_plan_id = intval($new_plan_id);
        $subscription_id = intval($subscription_id);
        
        if ($blog_id <= 0 || $new_plan_id <= 0) {
            throw new Exception('IDs de blog ou plano inválidos');
        }
        
        // 1. Obtém plano atual para log
        $current_plan_id = $wpdb->get_var($wpdb->prepare(
            "SELECT plano_id FROM $table_subdominios 
             WHERE blog_id = %d AND status = 'active'", 
            $blog_id
        ));
        
        $current_plan = null;
        if ($current_plan_id) {
            $current_plan = Limiter_MKP_Pro_Database::get_plano($current_plan_id);
        }
        
        $new_plan = Limiter_MKP_Pro_Database::get_plano($new_plan_id);
        if (!$new_plan) {
            throw new Exception('Novo plano não encontrado (ID: ' . $new_plan_id . ')');
        }
        
        // 2. Atualiza para novo plano
        $data_expiracao = $this->calculate_new_expiration($new_plan_id);
        
        $result = $wpdb->update(
            $table_subdominios,
            [
                'plano_id' => $new_plan_id,
                'limite_personalizado' => NULL, // Remove limite personalizado
                'data_expiracao' => $data_expiracao
            ],
            ['blog_id' => $blog_id],
            ['%d', '%s', '%s'],
            ['%d']
        );
        
        // Verifica se a atualização foi bem-sucedida
        if ($result === false || $wpdb->rows_affected == 0) {
            throw new Exception('Erro ao atualizar plano no banco de dados: ' . $wpdb->last_error);
        }
        
        // 3. Log da mudança automática
        $log_message = $current_plan_id ? 
            "Plano alterado de '{$current_plan->nome}' para '{$new_plan->nome}' via WooCommerce. Assinatura: {$subscription_id}" :
            "Novo plano '{$new_plan->nome}' atribuído via WooCommerce. Assinatura: {$subscription_id}";
            
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'plano_alterado_auto',
            $log_message
        );
        
        // 4. Limpa cache
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-cache-manager.php';
        Limiter_MKP_Pro_Cache_Manager::clear_blog_cache($blog_id);
        
        return true;
    }
    
    /**
     * Calcula nova data de expiração
     */
    private function calculate_new_expiration($plan_id) {
        $plan = Limiter_MKP_Pro_Database::get_plano($plan_id);
        if (!$plan) {
            return null;
        }
        return date('Y-m-d H:i:s', strtotime("+{$plan->duracao} days"));
    }
    
    /**
     * Cria um novo blog para o usuário
     */
    private function create_new_blog_for_user($user_id, $subscription, $plano_id) {
        try {
            // Verifica se usuário existe
            $user = get_user_by('id', $user_id);
            if (!$user) {
                throw new Exception('Usuário não encontrado (ID: ' . $user_id . ')');
            }
            
            // Cria um nome de blog baseado no nome do usuário
            $blogname = sanitize_title($user->display_name . '-' . $user_id);
            $blogname = preg_replace('/[^a-z0-9-]/', '', $blogname);
            $blogname = substr($blogname, 0, 20);
            
            // Usa sufixo configurado
            $config = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
            $sufixo = !empty($config['sufixo_subdominio']) ? $config['sufixo_subdominio'] : '-mkp';
            $domain = $blogname . $sufixo;
            
            $current_site = get_current_site();
            
            $blog_id = wpmu_create_blog(
                $domain . '.' . $current_site->domain,
                $current_site->path,
                $blogname,
                $user_id,
                array('public' => 1),
                $current_site->id
            );
            
            if (is_wp_error($blog_id)) {
                throw new Exception('Erro ao criar blog: ' . $blog_id->get_error_message());
            }
            
            // Adiciona o usuário como administrador do blog
            add_user_to_blog($blog_id, $user_id, 'administrator');
            
            // Salva o subdomínio no banco de dados
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::save_subdominio(array(
                'blog_id' => $blog_id,
                'user_id' => $user_id,
                'dominio' => $domain . '.' . $current_site->domain,
                'plano_id' => $plano_id,
                'nome_cliente' => $user->display_name,
                'email_cliente' => $user->user_email,
                'status' => 'active',
                'regiao' => $this->detect_user_region()
            ));
            
            return $blog_id;
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error("Erro ao criar novo blog para usuário", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user_id,
                'plano_id' => $plano_id
            ]);
            throw $e;
        }
    }
    
    /**
     * Detecta região do usuário usando a Geolocalização Nativa do WooCommerce.
     * Substitui a chamada insegura HTTP externa por consulta ao banco de dados local da MaxMind.
     */
    private function detect_user_region() {
        // Fallback padrão
        $regiao = 'global';

        // Verifica se a classe do WooCommerce Geolocation está disponível
        if ( class_exists( 'WC_Geolocation' ) ) {
            // O método geolocate_ip() já trata proxies (Cloudflare) e identifica o IP real
            $location = WC_Geolocation::geolocate_ip();

            // Verifica se o país retornado é Japão
            // O código 'JP' é o padrão ISO para Japão
            if ( ! empty( $location['country'] ) && 'JP' === $location['country'] ) {
                $regiao = 'jp';
            }
        }

        return $regiao;
    }
    
    /**
     * Envia notificação de sucesso para o usuário
     */
    private function send_success_notification($user_id, $new_plan_id, $blog_id, $subscription_id) {
        $user = get_user_by('id', $user_id);
        $new_plan = Limiter_MKP_Pro_Database::get_plano($new_plan_id);
        $blog = get_blog_details($blog_id);
        
        if (!$user || !$new_plan || !$blog) {
            return;
        }
        
        $site_url = $blog->siteurl;
        $admin_url = $blog->siteurl . '/wp-admin';
        
        $subject = "✅ Mudança de Plano Confirmada - Marketing Place Store";
        $message = "
        Olá {$user->display_name},
        
        Sua mudança para o plano <strong>{$new_plan->nome}</strong> foi processada com sucesso!
        
        📊 <strong>Novo limite:</strong> {$new_plan->limite_paginas} páginas/posts
        📅 <strong>Expira em:</strong> " . date('d/m/Y', strtotime("+{$new_plan->duracao} days")) . "
        
        🔗 <strong>Acessos:</strong>
        - Administração: {$admin_url}
        - Seu site: {$site_url}
        
        Você já pode usar todos os recursos do seu novo plano.
        
        Atenciosamente,
        Equipe Marketing Place Store
        ";
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Trata erros na mudança de plano
     */
    private function handle_plan_change_error($exception, $subscription) {
        $error_message = $exception->getMessage();
        $subscription_id = $subscription->get_id() ?? 0;
        $user_id = $subscription->get_user_id() ?? 0;

        // Log de erro detalhado
        error_log('ERRO MKP Pro - Mudança automática de plano: ' . $error_message);
        
        // Log no sistema do plugin
        Limiter_MKP_Pro_Database::log(
            0,
            'erro_mudanca_plano_auto',
            "Assinatura {$subscription_id}: {$error_message}",
            ['exception' => $error_message, 'trace' => $exception->getTraceAsString()]
        );
        
        // Notifica administradores
        $this->notify_admins_of_failure($subscription, $error_message);
        
        // Notifica usuário sobre o problema
        $this->send_error_notification($user_id, $error_message, $subscription_id);
    }
    
    /**
     * Notifica administradores sobre a falha
     */
    private function notify_admins_of_failure($subscription, $error_message) {
        $admin_email = get_network_option(null, 'admin_email');
        $subject = "🚨 Falha na Mudança Automática de Plano - MKP Pro";
        $message = "
        Uma falha ocorreu ao processar a mudança de plano via WooCommerce.
        
        🔍 <strong>Detalhes do Erro:</strong>
        - Assinatura: {$subscription->get_id()}
        - Usuário: {$subscription->get_user_id()}
        - Erro: {$error_message}
        
        ⚠️ <strong>Ação necessária:</strong>
        Verifique os logs do sistema e entre em contato com o usuário.
        
        Atenciosamente,
        Sistema Automático MKP Pro
        ";
        
        wp_mail($admin_email, $subject, $message);
        
        // Notifica também os super administradores
        foreach (get_super_admins() as $admin_login) {
            $user = get_user_by('login', $admin_login);
            if ($user && $user->user_email !== $admin_email) {
                wp_mail($user->user_email, $subject, $message);
            }
        }
    }
    
    /**
     * Envia notificação de erro para o usuário
     */
    private function send_error_notification($user_id, $error_message, $subscription_id = 0) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        $subject = "⚠️ Problema na Mudança de Plano - Marketing Place Store";
        $message = "
        Olá {$user->display_name},
        
        Ocorreu um problema ao processar sua mudança de plano.
        
        🔍 <strong>Detalhes do problema:</strong>
        {$error_message}
        
        ID da Assinatura: {$subscription_id}
        
        Nossa equipe foi notificada e está trabalhando para resolver o problema.
        Se o problema persistir, entre em contato com nosso suporte.
        
        Atenciosamente,
        Equipe Marketing Place Store
        ";
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Log de sucesso
     */
    private function log_success($message, $subscription, $new_plan_id, $old_plan_id, $blog_id) {
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'mudanca_plano_sucesso',
            "{$message} - Assinatura: {$subscription->get_id()}, Plano Antigo: {$old_plan_id}, Novo Plano: {$new_plan_id}",
            [
                'subscription_id' => $subscription->get_id(),
                'old_plan_id' => $old_plan_id,
                'new_plan_id' => $new_plan_id,
                'user_id' => $subscription->get_user_id(),
                'blog_id' => $blog_id
            ]
        );
    }
    
    /**
     * DETECTA REGIÃO E ENVIA E-MAIL DE CONFIGURAÇÃO
     */
    private function send_configuration_email($subscription, $plano_id) {
        $user_id = $subscription->get_user_id();
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Verifica se já existe um subdomínio ativo para este usuário
        $blog_id = $this->get_user_blog_id($user_id);
        if ($blog_id) {
            // Já tem subdomínio, não envia e-mail de configuração
            return false;
        }
        
        // Verifica se já tem token pendente para este usuário
        if (Limiter_MKP_Pro_Token_Manager::has_pending_token($user->user_email)) {
            return false;
        }
        
        // === DETECÇÃO DE REGIÃO PELO IP ===
        $regiao = $this->detect_user_region();
        
        // Gera token com região
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-token-manager.php';
        $token = Limiter_MKP_Pro_Token_Manager::generate_token_with_region(
            $user_id,
            $subscription->get_id(),
            $user->user_email,
            $plano_id,
            $regiao
        );
        
        if (!$token) {
            throw new Exception('Falha ao gerar token de configuração');
        }
        
        // URL de configuração
        $current_site = get_current_site();
        $config_url = home_url('/configurar-subdominio?token=' . $token);
        
        $subject = '🚀 Configure seu Subdomínio - Marketing Place Store';
        $message = "
        Olá {$user->display_name},
        
        Sua assinatura foi confirmada com sucesso! 🎉
        
        Agora você precisa configurar seu subdomínio para começar a usar nossa plataforma.
        
        📝 <strong>Configure seu subdomínio:</strong>
        {$config_url}
        
        ⚠️ <strong>Este link expira em 7 dias</strong>
        
        <strong>Como funciona:</strong>
        1. Clique no link acima
        2. Escolha o nome do seu subdomínio (exemplo: 'loja' se tornará 'loja-mkp.marketing-place.store')
        3. Crie sua senha
        4. Seu subdomínio será criado automaticamente
        
        Se tiver qualquer dúvida, entre em contato conosco.
        
        Atenciosamente,
        Equipe Marketing Place Store
        ";
        
        return wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Verifica atualização de plano via AJAX
     */
    public function ajax_check_plan_update() {
        try {
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'limiter_mkp_pro_public_nonce')) {
                wp_send_json_error(['message' => 'Token de segurança inválido']);
                exit;
            }
            
            $blog_id = get_current_blog_id();
            $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
            
            if (!$sub) {
                wp_send_json_success([
                    'has_update' => false,
                    'current_plan' => 'Não configurado',
                    'current_plan_id' => 0
                ]);
                exit;
            }
            
            $plano = Limiter_MKP_Pro_Database::get_plano($sub->plano_id);
            if (!$plano) {
                wp_send_json_success([
                    'has_update' => false,
                    'current_plan' => 'Plano não encontrado',
                    'current_plan_id' => $sub->plano_id
                ]);
                exit;
            }
            
            wp_send_json_success([
                'has_update' => false,
                'current_plan' => $plano->nome,
                'current_plan_id' => $plano->id
            ]);
        } catch (Exception $e) {
            Limiter_MKP_Pro_Error_Handler::log_error('Erro na verificação de atualização de plano via AJAX', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            wp_send_json_error(['message' => 'Erro interno']);
        }
    }
}