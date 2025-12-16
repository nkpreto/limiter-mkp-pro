<?php
/**
 * Classe principal de gerenciamento do ciclo de vida
 * OTIMIZADA COM PROCESSAMENTO EM FILA E PROTEÇÃO DE USUÁRIOS
 * * @since 2.1.0
 * @package Limiter_MKP_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Lifecycle_Manager {
    
    /**
     * Status personalizados do plugin
     */
    const BLOG_STATUS_ACTIVE = 'active';
    const BLOG_STATUS_GRACE_PERIOD = 'grace_period';
    const BLOG_STATUS_SUSPENDED = 'suspended';
    const BLOG_STATUS_LOCKED = 'locked';
    const BLOG_STATUS_SCHEDULED_DELETION = 'scheduled_deletion';
    const BLOG_STATUS_ARCHIVED = 'archived';

    /**
     * Configurações da Fila
     */
    const QUEUE_OPTION_NAME = 'limiter_mkp_lifecycle_queue';
    const BATCH_SIZE = 30; // Processa 30 sites por vez
    
    /**
     * Ações disponíveis
     */
    const ACTION_NOTIFY = 'notify';
    const ACTION_SUSPEND = 'suspend';
    const ACTION_LOCK = 'lock';
    const ACTION_SCHEDULE_DELETION = 'schedule_deletion';
    const ACTION_DELETE = 'delete';
    const ACTION_RESTORE = 'restore';

    /**
     * Inicializa o sistema
     */
    public static function init() {
        // Hooks do WordPress
        add_action('limiter_mkp_pro_daily_cron', [__CLASS__, 'init_daily_queue']);
        add_action('limiter_mkp_pro_process_queue', [__CLASS__, 'process_queue_batch']);
        add_action('template_redirect', [__CLASS__, 'check_blog_access'], 1);

        // Hooks do WooCommerce
        if (class_exists('WC_Subscriptions')) {
            self::init_woocommerce_hooks();
        }
        
        // Agenda o cron job diário se não existir
        self::schedule_cron();
    }
    
    /**
     * Inicializa hooks do WooCommerce
     */
    private static function init_woocommerce_hooks() {
        add_action('woocommerce_subscription_status_updated', [__CLASS__, 'handle_subscription_status_change'], 10, 3);
        add_action('woocommerce_subscription_payment_failed', [__CLASS__, 'handle_payment_failed'], 10, 2);
        add_action('woocommerce_subscription_payment_complete', [__CLASS__, 'handle_payment_complete'], 10, 2);
        add_action('woocommerce_subscription_cancelled', [__CLASS__, 'handle_cancellation'], 10, 2);
        add_action('woocommerce_subscription_expired', [__CLASS__, 'handle_expiration'], 10, 2);
    }
    
    /**
     * Agenda o cron job diário
     */
    public static function schedule_cron() {
        if (!wp_next_scheduled('limiter_mkp_pro_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'limiter_mkp_pro_daily_cron');
        }
    }
    
    /**
     * Remove o cron job
     */
    public static function unschedule_cron() {
        $timestamp = wp_next_scheduled('limiter_mkp_pro_daily_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'limiter_mkp_pro_daily_cron');
        }
        // Limpa também o agendamento da fila se houver
        $queue_timestamp = wp_next_scheduled('limiter_mkp_pro_process_queue');
        if ($queue_timestamp) {
            wp_unschedule_event($queue_timestamp, 'limiter_mkp_pro_process_queue');
        }
        delete_option(self::QUEUE_OPTION_NAME);
    }
    
    /**
     * PASSO 1: Inicia a Fila Diária
     */
    public static function init_daily_queue() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';

        $blogs = Limiter_MKP_Pro_Database::get_all_blogs_with_subscriptions();
        $blog_ids = array();
        
        foreach ($blogs as $blog) {
            $blog_ids[] = $blog->blog_id;
        }
        
        if (!empty($blog_ids)) {
            update_option(self::QUEUE_OPTION_NAME, $blog_ids, false);
            if (!wp_next_scheduled('limiter_mkp_pro_process_queue')) {
                wp_schedule_single_event(time() + 60, 'limiter_mkp_pro_process_queue');
            }
        }
        
        self::process_scheduled_deletions();
    }

    /**
     * PASSO 2: Processa um Lote da Fila
     */
    public static function process_queue_batch() {
        $queue = get_option(self::QUEUE_OPTION_NAME, array());
        if (empty($queue)) {
            return;
        }
        
        $batch = array_splice($queue, 0, self::BATCH_SIZE);

        if (empty($queue)) {
            delete_option(self::QUEUE_OPTION_NAME);
        } else {
            update_option(self::QUEUE_OPTION_NAME, $queue, false);
            wp_schedule_single_event(time() + 120, 'limiter_mkp_pro_process_queue');
        }
        
        foreach ($batch as $blog_id) {
            self::process_blog_lifecycle($blog_id);
        }
    }
    
    public static function run_daily_checks() {
        self::init_daily_queue();
    }
    
    public static function process_scheduled_deletions() {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database-lifecycle.php';
        $deleted_blogs = Limiter_MKP_Pro_Database_Lifecycle::process_scheduled_deletions();
        
        if (!empty($deleted_blogs)) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
            Limiter_MKP_Pro_Database::log(
                0,
                'exclusao_automatica_lote',
                "Rotina de limpeza executada. Sites arquivados: " . implode(', ', $deleted_blogs)
            );
        }
    }
    
    public static function process_blog_lifecycle($blog_id) {
        $settings = self::get_settings();
        $subscription = self::get_subscription_for_blog($blog_id);
        
        if (!$subscription) {
            return;
        }
        
        $wc_status = $subscription->get_status();
        $days_in_status = self::get_days_in_current_status($blog_id);
        
        switch ($wc_status) {
            case 'on-hold':
            case 'failed':
            case 'past-due':
                self::process_payment_failure($blog_id, $subscription, $settings, $days_in_status);
                break;
                
            case 'cancelled':
                self::process_cancellation($blog_id, $subscription, $settings, $days_in_status);
                break;
                
            case 'expired':
                self::process_expiration($blog_id, $subscription, $settings, $days_in_status);
                break;
                
            case 'pending-cancel':
                self::process_pending_cancellation($blog_id, $subscription, $settings, $days_in_status);
                break;
                
            case 'active':
                self::process_active_status($blog_id, $subscription, $settings);
                break;
        }
    }
    
    private static function process_payment_failure($blog_id, $subscription, $settings, $days) {
        $config = $settings['payment_failure'];
        $grace_days = intval($config['grace_period_days']);
        $suspension_days = intval($config['suspension_days']);
        $lock_days = intval($config['lock_days']);
        $deletion_days = intval($config['deletion_days']);

        if ($days <= $grace_days) {
            if ($days == 1) self::notify($blog_id, 'payment_failed_day1', $subscription);
            self::update_blog_status($blog_id, self::BLOG_STATUS_GRACE_PERIOD);
            
        } elseif ($days <= ($grace_days + $suspension_days)) {
            if ($days == $grace_days + 1) {
                self::suspend_blog($blog_id);
                self::notify($blog_id, 'suspension_activated', $subscription);
            }
            self::update_blog_status($blog_id, self::BLOG_STATUS_SUSPENDED);

        } elseif ($days <= ($grace_days + $suspension_days + $lock_days)) {
            if ($days == $grace_days + $suspension_days + 1) {
                self::lock_blog($blog_id);
                self::notify($blog_id, 'lock_activated', $subscription);
            }
            self::update_blog_status($blog_id, self::BLOG_STATUS_LOCKED);

        } else {
            $current_status = self::get_blog_status($blog_id);
            if ($current_status !== self::BLOG_STATUS_SCHEDULED_DELETION) {
                $deletion_date = date('Y-m-d', strtotime("+{$deletion_days} days"));
                self::schedule_deletion($blog_id, $deletion_date);
                self::notify($blog_id, 'scheduled_for_deletion', $subscription);
                self::update_blog_status($blog_id, self::BLOG_STATUS_SCHEDULED_DELETION);
            }
        }
    }
    
    private static function process_cancellation($blog_id, $subscription, $settings, $days) {
        $config = $settings['cancellation'];
        if (!empty($config['immediate_suspend']) && $days == 0) {
            self::suspend_blog($blog_id);
            self::notify($blog_id, 'cancellation_immediate', $subscription);
        }
        
        $admin_days = intval($config['admin_access_days']);
        $del_days = intval($config['deletion_days']);
        
        if ($days <= $admin_days) {
            self::update_blog_status($blog_id, self::BLOG_STATUS_SUSPENDED);
        } elseif ($days <= ($admin_days + $del_days)) {
            if ($days == $admin_days + 1) {
                self::lock_blog($blog_id);
            }
            self::update_blog_status($blog_id, self::BLOG_STATUS_LOCKED);
        } else {
            $current_status = self::get_blog_status($blog_id);
            if ($current_status !== self::BLOG_STATUS_SCHEDULED_DELETION) {
                $deletion_date = date('Y-m-d', strtotime("+{$del_days} days"));
                self::schedule_deletion($blog_id, $deletion_date);
                self::update_blog_status($blog_id, self::BLOG_STATUS_SCHEDULED_DELETION);
            }
        }
    }
    
    private static function process_expiration($blog_id, $subscription, $settings, $days) {
        self::process_cancellation($blog_id, $subscription, $settings, $days);
    }
    
    private static function process_pending_cancellation($blog_id, $subscription, $settings, $days) {
        self::update_blog_status($blog_id, self::BLOG_STATUS_ACTIVE);
    }
    
    private static function process_active_status($blog_id, $subscription, $settings) {
        $current_status = self::get_blog_status($blog_id);
        
        if (in_array($current_status, [
            self::BLOG_STATUS_GRACE_PERIOD,
            self::BLOG_STATUS_SUSPENDED,
            self::BLOG_STATUS_LOCKED,
            self::BLOG_STATUS_SCHEDULED_DELETION
        ])) {
            self::restore_blog($blog_id);
            self::notify($blog_id, 'subscription_reactivated', $subscription);
        }
        
        self::update_blog_status($blog_id, self::BLOG_STATUS_ACTIVE);
    }
    
    /**
     * Ações do Sistema - Métodos Modificados para Proteção de Usuários
     */
    
    public static function suspend_blog($blog_id) {
        update_blog_option($blog_id, 'limiter_blog_suspended', true);
        update_blog_option($blog_id, 'limiter_blog_suspended_date', current_time('mysql'));
        self::log_action($blog_id, self::ACTION_SUSPEND, 'Blog suspenso visualmente');
    }
    
    /**
     * Bloqueia o blog e remove usuários, MAS salva backup para restauração.
     */
    public static function lock_blog($blog_id) {
        $users = get_users(['blog_id' => $blog_id]);
        $users_to_restore = [];

        foreach ($users as $user) {
            if (!is_super_admin($user->ID)) {
                // CORREÇÃO: Salva os dados do usuário antes de remover
                $user_object = new WP_User($user->ID, '', $blog_id);
                $users_to_restore[$user->ID] = $user_object->roles;

                remove_user_from_blog($user->ID, $blog_id);
            }
        }

        // Salva o backup dos usuários removidos
        update_blog_option($blog_id, 'limiter_locked_users_backup', $users_to_restore);

        update_blog_option($blog_id, 'limiter_blog_locked', true);
        update_blog_option($blog_id, 'limiter_blog_locked_date', current_time('mysql'));
        
        self::log_action($blog_id, self::ACTION_LOCK, 'Blog bloqueado e usuários removidos temporariamente (Backup salvo)');
    }
    
    public static function schedule_deletion($blog_id, $deletion_date) {
        update_blog_option($blog_id, 'limiter_scheduled_deletion_date', $deletion_date);
        self::log_action($blog_id, self::ACTION_SCHEDULE_DELETION, "Exclusão agendada para {$deletion_date}");
    }
    
    /**
     * Restaura o blog e readiciona os usuários salvos.
     */
    public static function restore_blog($blog_id) {
        delete_blog_option($blog_id, 'limiter_blog_suspended');
        delete_blog_option($blog_id, 'limiter_blog_locked');
        delete_blog_option($blog_id, 'limiter_scheduled_deletion_date');
        delete_blog_option($blog_id, 'limiter_blog_suspended_date');
        delete_blog_option($blog_id, 'limiter_blog_locked_date'); 
        
        // CORREÇÃO: Restaura os usuários que foram removidos no bloqueio
        $locked_users = get_blog_option($blog_id, 'limiter_locked_users_backup', []);
        
        if (!empty($locked_users) && is_array($locked_users)) {
            foreach ($locked_users as $user_id => $roles) {
                // Tenta restaurar a role original, senão define como administrator
                $role = !empty($roles) ? reset($roles) : 'administrator';
                add_user_to_blog($blog_id, $user_id, $role);
            }
            // Limpa o backup após restaurar
            delete_blog_option($blog_id, 'limiter_locked_users_backup');
            $restored_count = count($locked_users);
        } else {
            $restored_count = 0;
        }
        
        update_blog_status($blog_id, 'public', 1);
        self::log_action($blog_id, self::ACTION_RESTORE, "Blog restaurado para status normal ({$restored_count} usuários recuperados)");
    }
    
    public static function check_blog_access() {
        if (!is_multisite()) return;
        $blog_id = get_current_blog_id();
        
        if (get_blog_option($blog_id, 'limiter_blog_suspended')) {
            if (!current_user_can('manage_network') && !is_admin()) {
                self::show_suspension_page($blog_id);
            }
        }
    }
    
    private static function show_suspension_page($blog_id) {
        $settings = self::get_settings();
        $template = $settings['suspension_page_template'] ?: self::get_default_suspension_template();
        
        $template = str_replace(
            ['[BLOG_NAME]', '[SUPPORT_EMAIL]', '[PLANS_URL]'],
            [get_bloginfo('name'), $settings['support_email'], $settings['plans_url']],
            $template
        );

        status_header(503);
        nocache_headers();
        
        die($template);
    }
    
    /**
     * Helpers e Utilitários
     */
    public static function notify($blog_id, $event, $subscription = null) {
        $settings = self::get_settings();
        if (empty($settings['email_templates'][$event])) return;
        
        $template = $settings['email_templates'][$event];
        $blog_details = get_blog_details($blog_id);
        
        $data = [
            'blog_name' => $blog_details->blogname,
            'blog_url' => $blog_details->siteurl,
            'customer_name' => get_blog_option($blog_id, 'limiter_nome_cliente', 'Cliente'),
            'customer_email' => get_blog_option($blog_id, 'limiter_email_cliente', ''),
            'days_remaining' => self::get_days_until_next_action($blog_id), 
            'suspension_date' => get_blog_option($blog_id, 'limiter_blog_suspended_date', ''),
            'deletion_date' => get_blog_option($blog_id, 'limiter_scheduled_deletion_date', '')
        ];
        
        $subject = self::replace_placeholders($template['subject'], $data);
        $message = self::replace_placeholders($template['message'], $data);
        
        if ($data['customer_email']) {
            wp_mail($data['customer_email'], $subject, $message);
        }
        
        if (!empty($settings['notification_emails'])) {
            $emails = explode(',', $settings['notification_emails']);
            foreach ($emails as $email) {
                if (is_email(trim($email))) wp_mail(trim($email), "[CÓPIA] {$subject}", $message);
            }
        }
        
        self::log_action($blog_id, self::ACTION_NOTIFY, "Notificação '{$event}' enviada");
    }
    
    public static function handle_subscription_status_change($subscription, $new_status, $old_status) {
        $blog_id = self::get_blog_id_from_subscription($subscription);
        if ($blog_id) {
            update_blog_option($blog_id, 'limiter_subscription_status', $new_status);
            update_blog_option($blog_id, 'limiter_subscription_status_date', current_time('mysql'));
        }
    }
    
    private static function get_subscription_for_blog($blog_id) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
        return Limiter_MKP_Pro_WooCommerce_Integration::get_subscription_for_blog($blog_id);
    }
    
    private static function get_blog_id_from_subscription($subscription) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-woocommerce-subscription-hooks.php';
        return Limiter_MKP_Pro_WooCommerce_Subscription_Hooks::get_blog_id_from_subscription($subscription);
    }
    
    private static function get_blog_status($blog_id) {
        return get_blog_option($blog_id, 'limiter_blog_status', self::BLOG_STATUS_ACTIVE);
    }
    
    private static function update_blog_status($blog_id, $status) {
        if (self::get_blog_status($blog_id) !== $status) {
            update_blog_option($blog_id, 'limiter_blog_status', $status);
            update_blog_option($blog_id, 'limiter_blog_status_date', current_time('mysql'));
        }
    }
    
    private static function get_days_in_current_status($blog_id) {
        $date = get_blog_option($blog_id, 'limiter_subscription_status_date');
        if (!$date) return 0;
        $now = new DateTime(current_time('mysql'));
        $then = new DateTime($date);
        return $then->diff($now)->days;
    }
    
    /**
     * Correção: Calcula os dias reais para a próxima ação
     */
    private static function get_days_until_next_action($blog_id) {
        $status = self::get_blog_status($blog_id);
        $settings = self::get_settings();
        $days_in_status = self::get_days_in_current_status($blog_id);
        
        // Padrão para evitar erros
        $remaining = 0;

        switch ($status) {
            case self::BLOG_STATUS_ACTIVE: // Se acabou de falhar o pagamento
                $remaining = intval($settings['payment_failure']['grace_period_days']) - $days_in_status;
                break;
            case self::BLOG_STATUS_GRACE_PERIOD:
                $total_days = intval($settings['payment_failure']['grace_period_days']) + intval($settings['payment_failure']['suspension_days']);
                $remaining = $total_days - $days_in_status;
                break;
            case self::BLOG_STATUS_SUSPENDED:
                $remaining = intval($settings['payment_failure']['lock_days']); // Simplificação
                break;
            default:
                $remaining = 7;
        }

        return max(0, $remaining);
    }
    
    public static function get_settings() {
        $defaults = [
            'payment_failure' => ['grace_period_days' => 7, 'suspension_days' => 7, 'lock_days' => 14, 'deletion_days' => 30],
            'cancellation' => ['immediate_suspend' => true, 'admin_access_days' => 7, 'deletion_days' => 30],
            'notification_emails' => get_option('admin_email'),
            'support_email' => 'suporte@marketing-place.store',
            'plans_url' => home_url('/planos/'),
            'suspension_page_template' => '',
            'email_templates' => self::get_default_email_templates()
        ];
        $settings = get_network_option(null, 'limiter_mkp_pro_lifecycle_settings', []);
        return array_replace_recursive($defaults, $settings);
    }
    
    private static function get_default_email_templates() {
        return [
            'payment_failed_day1' => ['subject' => 'Pagamento Pendente - [BLOG_NAME]', 'message' => "Olá [CUSTOMER_NAME],\n\nSeu pagamento falhou. Você tem 7 dias para regularizar."],
            'suspension_activated' => ['subject' => 'Site Suspenso - [BLOG_NAME]', 'message' => "Olá [CUSTOMER_NAME],\n\nSeu site foi suspenso por falta de pagamento."]
        ];
    }
    
    private static function get_default_suspension_template() {
        return '<!DOCTYPE html><html><head><title>Site Suspenso</title></head><body><h1>Site Suspenso</h1><p>Entre em contato com o suporte: [SUPPORT_EMAIL]</p></body></html>';
    }
    
    private static function replace_placeholders($text, $data) {
        foreach ($data as $key => $value) {
            $text = str_replace("[{$key}]", $value, $text);
            $text = str_replace("[" . strtoupper($key) . "]", $value, $text);
        }
        return $text;
    }
    
    private static function log_action($blog_id, $action, $description) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        Limiter_MKP_Pro_Database::log($blog_id, $action, $description);
    }
}