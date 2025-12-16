<?php
/**
 * Handlers AJAX para o ciclo de vida
 * 
 * @since 2.0.0
 * @package Limiter_MKP_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Lifecycle_Handlers {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Handler para salvar configurações do ciclo de vida
     */
    public function handle_save_lifecycle_settings() {
        try {
            // DEBUG: Log inicial
            error_log('=== Limiter MKP Pro - Iniciando save_lifecycle_settings ===');
            error_log('POST data: ' . print_r($_POST, true));
            
            $this->verify_ajax_permissions();
            
            // CORREÇÃO: Usar o campo correto do formulário
            $nonce_field = '';
            
            // Verificar qual campo de nonce está sendo enviado
            if (isset($_POST['lifecycle_nonce']) && !empty($_POST['lifecycle_nonce'])) {
                $nonce_field = 'lifecycle_nonce';
                $nonce_value = sanitize_text_field($_POST['lifecycle_nonce']);
                $action = 'limiter_mkp_pro_lifecycle_settings';
            } elseif (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
                $nonce_field = 'nonce';
                $nonce_value = sanitize_text_field($_POST['nonce']);
                $action = 'limiter_lifecycle_nonce';
            } else {
                wp_send_json_error([
                    'message' => 'Token de segurança não encontrado. Campos disponíveis: ' . implode(', ', array_keys($_POST))
                ]);
                exit;
            }
            
            error_log("Usando campo: $nonce_field, valor: $nonce_value, ação: $action");
            
            // Verificar o nonce
            if (!wp_verify_nonce($nonce_value, $action)) {
                error_log('Nonce inválido! Valor: ' . $nonce_value . ', Ação: ' . $action);
                wp_send_json_error(['message' => 'Token de segurança inválido. Por favor, atualize a página e tente novamente.']);
                exit;
            }
            
            error_log('Nonce verificado com sucesso!');
            
            $settings = $this->sanitize_lifecycle_settings($_POST);
            
            // Salva as configurações
            $result = update_network_option(null, 'limiter_mkp_pro_lifecycle_settings', $settings);
            
            if ($result !== false) {
                // Limpa cache
                require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-cache-manager.php';
                Limiter_MKP_Pro_Cache_Manager::clear_all();
                
                // Log da ação
                require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
                Limiter_MKP_Pro_Database::log(0, 'lifecycle_settings_saved', 'Configurações do ciclo de vida salvas');
                
                error_log('Configurações salvas com sucesso!');
                wp_send_json_success(['message' => 'Configurações salvas com sucesso!']);
            } else {
                error_log('Falha ao salvar configurações no banco de dados');
                wp_send_json_error(['message' => 'Não foi possível salvar as configurações.']);
            }
            
        } catch (Exception $e) {
            error_log('Exceção em handle_save_lifecycle_settings: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => 'Erro interno: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handler para verificação manual
     */
    public function handle_run_manual_check() {
        try {
            $this->verify_ajax_permissions();
            
            // Verificar nonce - compatibilidade com ambos os formatos
            $nonce_value = '';
            $action = 'limiter_lifecycle_nonce';
            
            if (isset($_POST['lifecycle_nonce']) && !empty($_POST['lifecycle_nonce'])) {
                $nonce_value = sanitize_text_field($_POST['lifecycle_nonce']);
                $action = 'limiter_mkp_pro_lifecycle_settings';
            } elseif (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
                $nonce_value = sanitize_text_field($_POST['nonce']);
                $action = 'limiter_lifecycle_nonce';
            }
            
            if (empty($nonce_value) || !wp_verify_nonce($nonce_value, $action)) {
                wp_send_json_error(['message' => 'Token de segurança inválido.']);
                exit;
            }
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            
            // Executa verificações
            $processed = Limiter_MKP_Pro_Lifecycle_Manager::run_daily_checks();
            
            // Log da ação
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::log(0, 'manual_check_executed', 'Verificação manual executada');
            
            wp_send_json_success([
                'message' => 'Verificação manual concluída!',
                'processed' => $processed
            ]);
            
        } catch (Exception $e) {
            error_log('Erro em run_manual_check: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Erro: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handler para enviar email customizado
     */
    public function handle_send_custom_email() {
        try {
            $this->verify_ajax_permissions();
            
            // Verificar nonce - compatibilidade com ambos os formatos
            $nonce_value = '';
            $action = 'limiter_lifecycle_nonce';
            
            if (isset($_POST['lifecycle_nonce']) && !empty($_POST['lifecycle_nonce'])) {
                $nonce_value = sanitize_text_field($_POST['lifecycle_nonce']);
                $action = 'limiter_mkp_pro_lifecycle_settings';
            } elseif (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
                $nonce_value = sanitize_text_field($_POST['nonce']);
                $action = 'limiter_lifecycle_nonce';
            }
            
            if (empty($nonce_value) || !wp_verify_nonce($nonce_value, $action)) {
                wp_send_json_error(['message' => 'Token de segurança inválido.']);
                exit;
            }
            
            $blog_id = intval($_POST['blog_id'] ?? 0);
            $template = sanitize_text_field($_POST['template'] ?? '');
            $custom_message = wp_kses_post($_POST['custom_message'] ?? '');
            
            if ($blog_id <= 0) {
                wp_send_json_error(['message' => 'ID do blog inválido.']);
            }
            
            if (empty($template) && empty($custom_message)) {
                wp_send_json_error(['message' => 'Template ou mensagem é obrigatório.']);
            }
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
            
            // Obtém a assinatura
            $subscription = Limiter_MKP_Pro_WooCommerce_Integration::get_subscription_for_blog($blog_id);
            
            if ($subscription) {
                // Se tiver mensagem customizada, envia como template especial
                if (!empty($custom_message)) {
                    $this->send_custom_notification($blog_id, $custom_message, $subscription);
                } else {
                    Limiter_MKP_Pro_Lifecycle_Manager::notify($blog_id, $template, $subscription);
                }
                
                // Log da ação
                require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
                Limiter_MKP_Pro_Database::log($blog_id, 'custom_email_sent', 'Email customizado enviado');
                
                wp_send_json_success(['message' => 'Email enviado com sucesso!']);
            } else {
                wp_send_json_error(['message' => 'Assinatura não encontrada para este blog.']);
            }
            
        } catch (Exception $e) {
            error_log('Erro em send_custom_email: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Erro: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handler para estender período de carência
     */
    public function handle_extend_grace_period() {
        try {
            $this->verify_ajax_permissions();
            
            // Verificar nonce - compatibilidade com ambos os formatos
            $nonce_value = '';
            $action = 'limiter_lifecycle_nonce';
            
            if (isset($_POST['lifecycle_nonce']) && !empty($_POST['lifecycle_nonce'])) {
                $nonce_value = sanitize_text_field($_POST['lifecycle_nonce']);
                $action = 'limiter_mkp_pro_lifecycle_settings';
            } elseif (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
                $nonce_value = sanitize_text_field($_POST['nonce']);
                $action = 'limiter_lifecycle_nonce';
            }
            
            if (empty($nonce_value) || !wp_verify_nonce($nonce_value, $action)) {
                wp_send_json_error(['message' => 'Token de segurança inválido.']);
                exit;
            }
            
            $blog_id = intval($_POST['blog_id'] ?? 0);
            $days = intval($_POST['days'] ?? 7);
            $reason = sanitize_text_field($_POST['reason'] ?? 'Extensão manual pelo administrador');
            
            if ($blog_id <= 0) {
                wp_send_json_error(['message' => 'ID do blog inválido.']);
            }
            
            if ($days <= 0 || $days > 365) {
                wp_send_json_error(['message' => 'Número de dias inválido (1-365).']);
            }
            
            // Obtém a data atual de expiração da carência
            $current_grace_end = get_blog_option($blog_id, 'limiter_grace_period_ends', '');
            
            if (empty($current_grace_end)) {
                // Se não houver data, cria a partir de agora
                $new_grace_end = date('Y-m-d H:i:s', strtotime("+{$days} days"));
            } else {
                // Estende a data existente
                $new_grace_end = date('Y-m-d H:i:s', strtotime($current_grace_end . " +{$days} days"));
            }
            
            // Atualiza a data
            update_blog_option($blog_id, 'limiter_grace_period_ends', $new_grace_end);
            
            // Atualiza o status para grace_period se não estiver
            if (get_blog_option($blog_id, 'limiter_blog_status') !== 'grace_period') {
                update_blog_option($blog_id, 'limiter_blog_status', 'grace_period');
                update_blog_option($blog_id, 'limiter_blog_status_date', current_time('mysql'));
            }
            
            // Log da ação
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::log(
                $blog_id,
                'grace_period_extended',
                "Período de carência estendido em {$days} dias",
                [
                    'old_grace_end' => $current_grace_end,
                    'new_grace_end' => $new_grace_end,
                    'days_added' => $days,
                    'reason' => $reason
                ]
            );
            
            // Notifica o cliente
            $this->notify_grace_extension($blog_id, $days, $reason);
            
            wp_send_json_success([
                'message' => "Período de carência estendido em {$days} dias.",
                'new_grace_end' => $new_grace_end,
                'formatted_date' => date_i18n(get_option('date_format'), strtotime($new_grace_end))
            ]);
            
        } catch (Exception $e) {
            error_log('Erro em extend_grace_period: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Erro: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handler para restaurar blog
     */
    public function handle_restore_blog() {
        try {
            $this->verify_ajax_permissions();
            
            // Verificar nonce - compatibilidade com ambos os formatos
            $nonce_value = '';
            $action = 'limiter_lifecycle_nonce';
            
            if (isset($_POST['lifecycle_nonce']) && !empty($_POST['lifecycle_nonce'])) {
                $nonce_value = sanitize_text_field($_POST['lifecycle_nonce']);
                $action = 'limiter_mkp_pro_lifecycle_settings';
            } elseif (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
                $nonce_value = sanitize_text_field($_POST['nonce']);
                $action = 'limiter_lifecycle_nonce';
            }
            
            if (empty($nonce_value) || !wp_verify_nonce($nonce_value, $action)) {
                wp_send_json_error(['message' => 'Token de segurança inválido.']);
                exit;
            }
            
            $blog_id = intval($_POST['blog_id'] ?? 0);
            
            if ($blog_id <= 0) {
                wp_send_json_error(['message' => 'ID do blog inválido.']);
            }
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            
            // Restaura o blog
            Limiter_MKP_Pro_Lifecycle_Manager::restore_blog($blog_id);
            
            // Atualiza status da assinatura para active
            update_blog_option($blog_id, 'limiter_subscription_status', 'active');
            
            // Log da ação
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::log($blog_id, 'blog_restored', 'Blog restaurado manualmente pelo administrador');
            
            wp_send_json_success(['message' => 'Blog restaurado com sucesso!']);
            
        } catch (Exception $e) {
            error_log('Erro em restore_blog: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Erro: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handler para suspender blog manualmente
     */
    public function handle_suspend_blog() {
        try {
            $this->verify_ajax_permissions();
            
            // Verificar nonce - compatibilidade com ambos os formatos
            $nonce_value = '';
            $action = 'limiter_lifecycle_nonce';
            
            if (isset($_POST['lifecycle_nonce']) && !empty($_POST['lifecycle_nonce'])) {
                $nonce_value = sanitize_text_field($_POST['lifecycle_nonce']);
                $action = 'limiter_mkp_pro_lifecycle_settings';
            } elseif (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
                $nonce_value = sanitize_text_field($_POST['nonce']);
                $action = 'limiter_lifecycle_nonce';
            }
            
            if (empty($nonce_value) || !wp_verify_nonce($nonce_value, $action)) {
                wp_send_json_error(['message' => 'Token de segurança inválido.']);
                exit;
            }
            
            $blog_id = intval($_POST['blog_id'] ?? 0);
            $reason = sanitize_text_field($_POST['reason'] ?? 'Suspensão manual pelo administrador');
            
            if ($blog_id <= 0) {
                wp_send_json_error(['message' => 'ID do blog inválido.']);
            }
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            
            // Suspende o blog
            Limiter_MKP_Pro_Lifecycle_Manager::suspend_blog($blog_id);
            
            // Atualiza status
            update_blog_option($blog_id, 'limiter_blog_status', 'suspended');
            update_blog_option($blog_id, 'limiter_suspension_reason', $reason);
            
            // Log da ação
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::log(
                $blog_id,
                'blog_suspended_manual',
                "Blog suspenso manualmente: {$reason}"
            );
            
            wp_send_json_success(['message' => 'Blog suspenso com sucesso!']);
            
        } catch (Exception $e) {
            error_log('Erro em suspend_blog: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Erro: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handler para agendar exclusão
     */
    public function handle_schedule_deletion() {
        try {
            $this->verify_ajax_permissions();
            
            // Verificar nonce - compatibilidade com ambos os formatos
            $nonce_value = '';
            $action = 'limiter_lifecycle_nonce';
            
            if (isset($_POST['lifecycle_nonce']) && !empty($_POST['lifecycle_nonce'])) {
                $nonce_value = sanitize_text_field($_POST['lifecycle_nonce']);
                $action = 'limiter_mkp_pro_lifecycle_settings';
            } elseif (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
                $nonce_value = sanitize_text_field($_POST['nonce']);
                $action = 'limiter_lifecycle_nonce';
            }
            
            if (empty($nonce_value) || !wp_verify_nonce($nonce_value, $action)) {
                wp_send_json_error(['message' => 'Token de segurança inválido.']);
                exit;
            }
            
            $blog_id = intval($_POST['blog_id'] ?? 0);
            $deletion_date = sanitize_text_field($_POST['deletion_date'] ?? '');
            $reason = sanitize_text_field($_POST['reason'] ?? 'Exclusão manual pelo administrador');
            
            if ($blog_id <= 0) {
                wp_send_json_error(['message' => 'ID do blog inválido.']);
            }
            
            if (empty($deletion_date)) {
                $deletion_date = date('Y-m-d', strtotime('+7 days'));
            }
            
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-lifecycle-manager.php';
            
            // Agenda exclusão
            Limiter_MKP_Pro_Lifecycle_Manager::schedule_deletion($blog_id, $deletion_date);
            
            // Atualiza status
            update_blog_option($blog_id, 'limiter_blog_status', 'scheduled_deletion');
            update_blog_option($blog_id, 'limiter_deletion_reason', $reason);
            
            // Log da ação
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::log(
                $blog_id,
                'deletion_scheduled_manual',
                "Exclusão agendada para {$deletion_date}: {$reason}"
            );
            
            wp_send_json_success([
                'message' => "Exclusão agendada para {$deletion_date}!",
                'deletion_date' => $deletion_date
            ]);
            
        } catch (Exception $e) {
            error_log('Erro em schedule_deletion: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Erro: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handler para cancelar exclusão agendada
     */
    public function handle_cancel_deletion() {
        try {
            $this->verify_ajax_permissions();
            
            // Verificar nonce - compatibilidade com ambos os formatos
            $nonce_value = '';
            $action = 'limiter_lifecycle_nonce';
            
            if (isset($_POST['lifecycle_nonce']) && !empty($_POST['lifecycle_nonce'])) {
                $nonce_value = sanitize_text_field($_POST['lifecycle_nonce']);
                $action = 'limiter_mkp_pro_lifecycle_settings';
            } elseif (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
                $nonce_value = sanitize_text_field($_POST['nonce']);
                $action = 'limiter_lifecycle_nonce';
            }
            
            if (empty($nonce_value) || !wp_verify_nonce($nonce_value, $action)) {
                wp_send_json_error(['message' => 'Token de segurança inválido.']);
                exit;
            }
            
            $blog_id = intval($_POST['blog_id'] ?? 0);
            
            if ($blog_id <= 0) {
                wp_send_json_error(['message' => 'ID do blog inválido.']);
            }
            
            // Remove data de exclusão
            delete_blog_option($blog_id, 'limiter_scheduled_deletion_date');
            
            // Restaura status anterior ou define como active
            $previous_status = get_blog_option($blog_id, 'limiter_previous_status', 'active');
            update_blog_option($blog_id, 'limiter_blog_status', $previous_status);
            
            // Log da ação
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::log($blog_id, 'deletion_cancelled', 'Exclusão agendada cancelada manualmente');
            
            wp_send_json_success(['message' => 'Exclusão cancelada com sucesso!']);
            
        } catch (Exception $e) {
            error_log('Erro em cancel_deletion: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Erro: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Sanitiza configurações do ciclo de vida
     */
    private function sanitize_lifecycle_settings($data) {
        $settings = [
            'payment_failure' => [
                'grace_period_days' => intval($data['payment_failure']['grace_period_days'] ?? 7),
                'suspension_days' => intval($data['payment_failure']['suspension_days'] ?? 7),
                'lock_days' => intval($data['payment_failure']['lock_days'] ?? 14),
                'deletion_days' => intval($data['payment_failure']['deletion_days'] ?? 30)
            ],
            'cancellation' => [
                'immediate_suspend' => isset($data['cancellation']['immediate_suspend']) ? 1 : 0,
                'admin_access_days' => intval($data['cancellation']['admin_access_days'] ?? 7),
                'deletion_days' => intval($data['cancellation']['deletion_days'] ?? 30)
            ],
            'notification_emails' => sanitize_text_field($data['notification_emails'] ?? ''),
            'support_email' => sanitize_email($data['support_email'] ?? ''),
            'plans_url' => esc_url_raw($data['plans_url'] ?? ''),
            'suspension_page_template' => wp_kses_post($data['suspension_page_template'] ?? '')
        ];
        
        // Sanitiza templates de email
        if (isset($data['email_templates']) && is_array($data['email_templates'])) {
            $settings['email_templates'] = [];
            
            foreach ($data['email_templates'] as $key => $template) {
                $settings['email_templates'][sanitize_key($key)] = [
                    'subject' => sanitize_text_field($template['subject'] ?? ''),
                    'message' => wp_kses_post($template['message'] ?? '')
                ];
            }
        } else {
            // Templates padrão
            $settings['email_templates'] = [
                'payment_failed' => [
                    'subject' => 'Pagamento Pendente - [BLOG_NAME]',
                    'message' => 'Olá [CUSTOMER_NAME],\n\nSeu pagamento para o site [BLOG_NAME] está pendente.\nPor favor, regularize sua situação em até [DAYS_REMAINING] dias.\n\nAcesse: [PLANS_URL]\n\nSuporte: [SUPPORT_EMAIL]'
                ],
                'grace_period' => [
                    'subject' => 'Período de Carência - [BLOG_NAME]',
                    'message' => 'Olá [CUSTOMER_NAME],\n\nSeu site [BLOG_NAME] entrou em período de carência.\nVocê tem até [DAYS_REMAINING] dias para regularizar.\n\nAcesse: [PLANS_URL]\n\nSuporte: [SUPPORT_EMAIL]'
                ],
                'suspended' => [
                    'subject' => 'Site Suspenso - [BLOG_NAME]',
                    'message' => 'Olá [CUSTOMER_NAME],\n\nSeu site [BLOG_NAME] foi suspenso devido a falta de pagamento.\nPara reativar, acesse: [PLANS_URL]\n\nSuporte: [SUPPORT_EMAIL]'
                ],
                'scheduled_deletion' => [
                    'subject' => 'Exclusão Programada - [BLOG_NAME]',
                    'message' => 'Olá [CUSTOMER_NAME],\n\nSeu site [BLOG_NAME] será excluído em [DELETION_DATE].\nPara evitar a exclusão, regularize sua situação.\n\nAcesse: [PLANS_URL]\n\nSuporte: [SUPPORT_EMAIL]'
                ]
            ];
        }
        
        return $settings;
    }
    
    /**
     * Envia notificação customizada
     */
    private function send_custom_notification($blog_id, $message, $subscription) {
        $settings = get_network_option(null, 'limiter_mkp_pro_lifecycle_settings', []);
        $blog_details = get_blog_details($blog_id);
        
        // Dados para substituição
        $data = [
            'blog_name' => $blog_details->blogname,
            'blog_url' => $blog_details->siteurl,
            'customer_name' => get_blog_option($blog_id, 'limiter_nome_cliente', 'Cliente'),
            'customer_email' => get_blog_option($blog_id, 'limiter_email_cliente', ''),
            'support_email' => $settings['support_email'] ?? 'suporte@marketing-place.store',
            'plans_url' => $settings['plans_url'] ?? home_url('/planos/')
        ];
        
        // Substitui placeholders
        foreach ($data as $key => $value) {
            $message = str_replace("[{$key}]", $value, $message);
            $message = str_replace("[" . strtoupper($key) . "]", $value, $message);
        }
        
        $subject = "Notificação Importante - {$data['blog_name']}";
        
        // Envia para o cliente
        if ($data['customer_email']) {
            wp_mail($data['customer_email'], $subject, $message);
        }
        
        // Envia cópia para administradores
        $admin_emails = explode(',', $settings['notification_emails'] ?? '');
        foreach ($admin_emails as $email) {
            $email = trim($email);
            if (is_email($email)) {
                wp_mail($email, "[CÓPIA ADMIN] {$subject}", $message);
            }
        }
    }
    
    /**
     * Notifica extensão de carência
     */
    private function notify_grace_extension($blog_id, $days, $reason) {
        $blog_details = get_blog_details($blog_id);
        $customer_email = get_blog_option($blog_id, 'limiter_email_cliente', '');
        $customer_name = get_blog_option($blog_id, 'limiter_nome_cliente', 'Cliente');
        
        if (!$customer_email) {
            return;
        }
        
        $subject = "Extensão de Carência Concedida - {$blog_details->blogname}";
        
        $message = "Olá {$customer_name},\n\n";
        $message .= "Seu período de carência foi estendido em {$days} dias.\n\n";
        $message .= "**Detalhes:**\n";
        $message .= "- Site: {$blog_details->blogname}\n";
        $message .= "- URL: {$blog_details->siteurl}\n";
        $message .= "- Dias adicionados: {$days}\n";
        $message .= "- Razão: {$reason}\n\n";
        $message .= "Aproveite este tempo adicional para regularizar sua situação.\n\n";
        $message .= "Atenciosamente,\nEquipe Marketing Place Store";
        
        wp_mail($customer_email, $subject, $message);
    }
    
    /**
     * Verifica permissões AJAX
     */
    private function verify_ajax_permissions() {
        if (!current_user_can('manage_network')) {
            error_log('Permissão negada para usuário: ' . get_current_user_id());
            wp_send_json_error(['message' => 'Você não tem permissão para realizar esta operação.']);
            exit;
        }
    }
    
    /**
     * Verifica nonce AJAX (método mantido para compatibilidade)
     */
    private function verify_ajax_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            error_log("Nonce inválido para ação: $action");
            wp_send_json_error(['message' => 'Token de segurança inválido. Por favor, atualize a página e tente novamente.']);
            exit;
        }
    }
}