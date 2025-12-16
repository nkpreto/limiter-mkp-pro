<?php
if (!defined('ABSPATH')) exit;

class Limiter_MKP_Pro_Helper_Functions {
    
    /**
     * Conta arquivos (inodes) na pasta de uploads
     */
    public static function count_files($dir) {
        if (!is_dir($dir)) return 0;
        $count = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $count++;
        }
        return $count;
    }
    
    /**
     * Detecta o plano ativo do usuário via WooCommerce Subscriptions
     */
    public static function get_user_plan($user_id = null) {
        if (!class_exists('WC_Subscriptions') || !function_exists('wcs_get_users_subscriptions')) return 'starter';
        if (!$user_id) $user_id = get_current_user_id();
        $subscriptions = wcs_get_users_subscriptions($user_id);
        if (empty($subscriptions)) return 'starter';
        foreach ($subscriptions as $sub) {
            if ($sub->has_status('active')) {
                $plan_name = strtolower($sub->get_items()[0]->get_name());
                if (strpos($plan_name, 'pro') !== false) return 'pro';
                if (strpos($plan_name, 'business') !== false || strpos($plan_name, 'bussiness') !== false) return 'business';
                return 'starter';
            }
        }
        return 'starter';
    }
}