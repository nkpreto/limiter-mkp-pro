<?php
/**
 * Define a funcionalidade de internacionalização do plugin.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */
if (!defined('ABSPATH')) {
    exit;
}
class Limiter_MKP_Pro_i18n {

    /**
     * Carrega o domínio de texto do plugin para tradução.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'limiter-mkp-pro',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
