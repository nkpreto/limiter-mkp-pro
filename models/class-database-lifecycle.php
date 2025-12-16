<?php
/**
 * Queries específicas para gerenciamento de ciclo de vida
 */

class Limiter_MKP_Pro_Database_Lifecycle {
    
    /**
     * Obtém blogs com status específico
     */
    public static function get_blogs_by_status($status, $limit = 100) {
        global $wpdb;
        
        $blogs = $wpdb->get_col($wpdb->prepare(
            "SELECT blog_id 
            FROM {$wpdb->blogmeta} 
            WHERE meta_key = 'limiter_blog_status' 
            AND meta_value = %s 
            LIMIT %d",
            $status, $limit
        ));
        
        return array_map('intval', $blogs);
    }
    
    /**
     * Obtém blogs agendados para exclusão
     */
    public static function get_blogs_scheduled_for_deletion() {
        global $wpdb;
        
        $today = current_time('mysql');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT b.blog_id, bm.meta_value as deletion_date
            FROM {$wpdb->blogmeta} bm
            INNER JOIN {$wpdb->blogs} b ON b.blog_id = bm.blog_id
            WHERE bm.meta_key = 'limiter_scheduled_deletion_date'
            AND bm.meta_value <= %s
            AND b.deleted = 0",
            $today
        ));
    }
    
    /**
     * Obtém estatísticas de ciclo de vida
     */
    public static function get_lifecycle_stats() {
        global $wpdb;
        
        $stats = [
            'total_blogs' => 0,
            'active' => 0,
            'grace_period' => 0,
            'suspended' => 0,
            'locked' => 0,
            'scheduled_deletion' => 0,
            'cancelled_today' => 0,
            'failed_payments' => 0
        ];
        
        // Total de blogs
        $stats['total_blogs'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->blogs} WHERE deleted = 0");
        
        // Por status
        $status_counts = $wpdb->get_results(
            "SELECT meta_value as status, COUNT(*) as count
            FROM {$wpdb->blogmeta}
            WHERE meta_key = 'limiter_blog_status'
            GROUP BY meta_value"
        );
        
        foreach ($status_counts as $row) {
            if (isset($stats[$row->status])) {
                $stats[$row->status] = (int) $row->count;
            }
        }
        
        // Cancelamentos hoje
        $stats['cancelled_today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT s.blog_id)
            FROM {$wpdb->blogmeta} s
            WHERE s.meta_key = 'limiter_subscription_status'
            AND s.meta_value = 'cancelled'
            AND DATE(s.meta_date) = %s",
            current_time('Y-m-d')
        ));
        
        return $stats;
    }
    
    /**
     * Processa exclusões agendadas
     */
    public static function process_scheduled_deletions() {
        $blogs = self::get_blogs_scheduled_for_deletion();
        $deleted = [];
        
        foreach ($blogs as $blog) {
            // Backup antes de deletar
            self::create_pre_deletion_backup($blog->blog_id);
            
            // Marca como deletado (não deleta fisicamente ainda)
            update_blog_status($blog->blog_id, 'deleted', 1);
            
            // Agenda exclusão física para 30 dias depois
            $physical_deletion = date('Y-m-d', strtotime('+30 days'));
            update_blog_option($blog->blog_id, 'limiter_physical_deletion_date', $physical_deletion);
            
            $deleted[] = $blog->blog_id;
        }
        
        return $deleted;
    }
    
    /**
     * Cria backup antes da exclusão
     */
    private static function create_pre_deletion_backup($blog_id) {
        // Implementar sistema de backup
        // Pode ser exportação XML + compactação de uploads
        // Salvar em diretório seguro ou S3
        
        $backup_dir = WP_CONTENT_DIR . '/limiter-backups/';
        wp_mkdir_p($backup_dir);
        
        $filename = "blog-{$blog_id}-" . date('Y-m-d-H-i-s') . '.json';
        $backup_data = [
            'blog_id' => $blog_id,
            'backup_date' => current_time('mysql'),
            'metadata' => self::get_blog_metadata($blog_id)
        ];
        
        file_put_contents(
            $backup_dir . $filename,
            json_encode($backup_data, JSON_PRETTY_PRINT)
        );
        
        return $backup_dir . $filename;
    }
}