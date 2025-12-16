<?php
if (!defined('ABSPATH')) exit;

class Limiter_MKP_Pro_Inode_Emails {
    public function __construct() {
        add_action('admin_init', [$this, 'check_email_alert']);
    }
    
    public function check_email_alert() {
        $count = get_option('limiter_mkp_pro_inode_count', 0);
        $limit = get_option('limiter_mkp_pro_inode_limit', 1000);
        $percent = round(($count / $limit) * 100);
        
        if (in_array($percent, [80, 100])) {
            $this->send_email($percent, $count, $limit);
        }
    }
    
    private function send_email($percent, $count, $limit) {
        $admin_email = get_option('admin_email');
        $network_admin = get_site_option('admin_email');
        $subject = "Aviso: Limite de Inodes ({$percent}%) atingido";
        $message = "Seu site atingiu {$percent}% do limite de arquivos: {$count}/{$limit}.
Recomenda-se revisar as mídias ou atualizar o plano.";
        wp_mail([$admin_email, $network_admin], $subject, $message);
    }
}