<?php
if (!defined('ABSPATH')) exit;

class Limiter_MKP_Pro_Inode_Shortcodes {
    
    public function __construct() {
        add_shortcode('inode_usage', [$this, 'usage']);
        add_shortcode('inode_limit', [$this, 'limit']);
        add_shortcode('inode_percent', [$this, 'percent']);
        add_shortcode('inode_bar', [$this, 'bar']);
        add_shortcode('inode_panel', [$this, 'panel']);
    }
    
    private function get_data() {
        $count = get_option('limiter_mkp_pro_inode_count', 0);
        $limit = get_option('limiter_mkp_pro_inode_limit', 1000);
        $percent = min(100, round(($count / $limit) * 100));
        return compact('count', 'limit', 'percent');
    }
    
    public function usage() {
        return $this->get_data()['count'];
    }
    
    public function limit() {
        return $this->get_data()['limit'];
    }
    
    public function percent() {
        return $this->get_data()['percent'] . '%';
    }
    
    public function bar() {
        $p = $this->get_data()['percent'];
        return '<div class="limiter-mkp-pro-progress-bar"><div class="limiter-mkp-pro-progress" style="width:' . $p . '%"></div></div>';
    }
    
    public function panel() {
        $data = $this->get_data();
        ob_start(); ?>
        <div class="limiter-mkp-pro-inode-panel" style="background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; margin: 10px 0;">
            <h4 style="margin-top: 0;"><?php _e('Uso de Arquivos', 'limiter-mkp-pro'); ?></h4>
            <p><strong><?php _e('Arquivos usados:', 'limiter-mkp-pro'); ?></strong> <?php echo $data['count']; ?> / <?php echo $data['limit']; ?> (<?php echo $data['percent']; ?>%)</p>
            <?php echo $this->bar(); ?>
        </div>
        <?php return ob_get_clean();
    }
}