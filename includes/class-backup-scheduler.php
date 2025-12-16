<?php
/**
 * Gerenciador de Agendamento de Backups (Fila Assíncrona Inteligente)
 * Integração total com configurações de Planos, Retenção e Redundância por E-mail.
 *
 * @package Limiter_MKP_Pro
 * @since 2.3.4
 */

if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Backup_Scheduler {

    const QUEUE_OPTION = 'limiter_mkp_backup_queue';
    const CRON_PROCESS_HOOK = 'limiter_mkp_process_backup_queue';

    /**
     * Inicializa os ganchos do agendador
     */
    public static function init() {
        // 1. O GERENTE: Roda 1x por dia (gancho existente no seu plugin)
        add_action('limiter_mkp_pro_daily_cron', [__CLASS__, 'populate_daily_queue']);
        
        // 2. O OPERÁRIO: Roda a cada poucos minutos para processar a fila
        add_action(self::CRON_PROCESS_HOOK, [__CLASS__, 'process_queue_batch']);
    }

    /**
     * Passo 1: Verifica todos os sites e monta a fila do dia baseada nos PLANOS
     */
    public static function populate_daily_queue() {
        global $wpdb;
        
        // Tabelas do plugin (prefixo correto via classe Database)
        if (!class_exists('Limiter_MKP_Pro_Database')) {
            require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        }

        $table_subs = Limiter_MKP_Pro_Database::get_table_name('subdominios');
        $table_planos = Limiter_MKP_Pro_Database::get_table_name('planos');

        // Busca apenas sites ATIVOS com planos que tenham backup ativado
        $sql = "
            SELECT s.blog_id, p.backup_frequency, p.backup_retention 
            FROM {$table_subs} s
            INNER JOIN {$table_planos} p ON s.plano_id = p.id
            WHERE s.status = 'active' 
            AND p.backup_frequency != 'none'
        ";

        $sites = $wpdb->get_results($sql);
        $queue = [];

        foreach ($sites as $site) {
            // Verifica se "hoje" é dia de backup para este site específico
            if (self::should_backup_today($site->blog_id, $site->backup_frequency)) {
                $queue[] = [
                    'blog_id' => $site->blog_id,
                    'retention' => (int) $site->backup_retention
                ];
            }
        }

        // Se houver trabalho para hoje, salva a fila e acorda o operário
        if (!empty($queue)) {
            update_option(self::QUEUE_OPTION, $queue, false);
            
            // Agenda o primeiro processamento para daqui a 60 segundos
            if (!wp_next_scheduled(self::CRON_PROCESS_HOOK)) {
                wp_schedule_single_event(time() + 60, self::CRON_PROCESS_HOOK);
            }
            
            // Log de auditoria
            Limiter_MKP_Pro_Database::log(0, 'backup_fila_criada', 'Fila de backup diária gerada com ' . count($queue) . ' sites.');
        }
    }

    /**
     * Passo 2: Processa UM item da fila e para (Proteção de Performance)
     */
    public static function process_queue_batch() {
        // Recupera a fila atual
        $queue = get_option(self::QUEUE_OPTION, []);

        if (empty($queue)) {
            return; // Fila vazia, volta a dormir
        }

        // Pega o primeiro da fila (FIFO)
        $item = array_shift($queue);
        
        // Atualiza a fila no banco (remove o que pegamos)
        if (empty($queue)) {
            delete_option(self::QUEUE_OPTION);
        } else {
            update_option(self::QUEUE_OPTION, $queue, false);
            // Reagenda o próximo lote para daqui a 2 minutos (tempo seguro)
            wp_schedule_single_event(time() + 120, self::CRON_PROCESS_HOOK);
        }

        // Executa o trabalho pesado
        self::execute_backup($item['blog_id'], $item['retention']);
    }

    /**
     * Executa a geração do SQL, aplica a retenção e envia por E-MAIL (Redundância)
     */
    private static function execute_backup($blog_id, $retention) {
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'includes/class-backup.php';
        
        try {
            // Gera o arquivo
            $filename = Limiter_MKP_Pro_Backup::export_subdomain_sql($blog_id);
            
            if ($filename) {
                // Sucesso! Atualiza a data do último backup
                update_blog_option($blog_id, 'limiter_last_backup_date', current_time('mysql'));
                
                // Aplica a política de retenção (apaga os velhos do servidor)
                self::enforce_retention($blog_id, $retention);

                $site_info = get_blog_details($blog_id);
                $nome_site = $site_info ? $site_info->blogname : "ID $blog_id";
                $site_url = $site_info ? $site_info->siteurl : '';

                // --- NOVO: Envia Backup por E-mail (Redundância Off-site) ---
                $enviado_email = self::send_backup_email($filename, $nome_site, $site_url);
                $msg_extra = $enviado_email ? " (Cópia enviada por e-mail)" : " (Falha ao enviar por e-mail)";

                // Log de Sucesso
                Limiter_MKP_Pro_Database::log(
                    $blog_id, 
                    'backup_auto_sucesso', 
                    "Backup automático realizado para: {$nome_site}. Arquivo: {$filename}{$msg_extra}"
                );
            } else {
                Limiter_MKP_Pro_Database::log($blog_id, 'backup_auto_falha', "A exportação retornou vazio.");
            }

        } catch (Exception $e) {
            Limiter_MKP_Pro_Database::log($blog_id, 'backup_auto_erro', "Erro crítico: " . $e->getMessage());
        }
    }

    /**
     * Envia o arquivo de backup para o e-mail do administrador
     */
    private static function send_backup_email($filename, $site_name, $site_url) {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/limiter-mkp-pro-backups/' . $filename;
        
        if (!file_exists($file_path)) {
            return false;
        }

        // Verifica tamanho do arquivo (limite seguro de anexo ~10MB)
        if (filesize($file_path) > 10 * 1024 * 1024) {
            return false; // Arquivo muito grande para e-mail
        }

        $admin_email = get_network_option(null, 'admin_email');
        if (!is_email($admin_email)) {
            return false;
        }

        $subject = "[Backup Auto] {$site_name} - " . date('d/m/Y');
        $message = "Olá,\n\n";
        $message .= "Segue em anexo o backup automático do banco de dados do subdomínio:\n";
        $message .= "Site: {$site_name}\n";
        $message .= "URL: {$site_url}\n";
        $message .= "Data: " . current_time('d/m/Y H:i') . "\n\n";
        $message .= "Armazene este arquivo em local seguro.\n";
        $message .= "Limiter MKP Pro System.";

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        // Envia com anexo
        return wp_mail($admin_email, $subject, $message, $headers, array($file_path));
    }

    /**
     * Lógica de Frequência: Decide se deve fazer backup hoje
     */
    private static function should_backup_today($blog_id, $frequency) {
        $last_backup = get_blog_option($blog_id, 'limiter_last_backup_date');
        
        if (!$last_backup) return true; // Nunca fez, faz agora.

        $last = new DateTime($last_backup);
        $today = new DateTime(current_time('mysql'));
        $diff = $last->diff($today)->days;

        switch ($frequency) {
            case 'daily':
                return $diff >= 1; // 1 dia ou mais
            case 'weekly':
                return $diff >= 7; // 7 dias ou mais
            case 'monthly':
                return $diff >= 30; // 30 dias ou mais
            default:
                return false;
        }
    }

    /**
     * Lógica de Retenção: Apaga backups antigos excedentes
     */
    private static function enforce_retention($blog_id, $limit) {
        if ($limit <= 0) return; // Se limite for 0, guarda infinito

        $backup_dir = wp_upload_dir()['basedir'] . '/limiter-mkp-pro-backups/';
        
        // Encontra todos os backups deste site específico
        $files = glob($backup_dir . "backup-site-{$blog_id}-*");
        
        if ($files && count($files) > $limit) {
            // Ordena por data de modificação (mais novos primeiro)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            // Pega a lista dos que sobraram (além do limite)
            $files_to_delete = array_slice($files, $limit);
            
            foreach ($files_to_delete as $file) {
                if (file_exists($file)) {
                    @unlink($file); // @ suprime erros de permissão se houver
                }
            }
        }
    }
}