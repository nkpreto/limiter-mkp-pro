<?php
/**
 * Classe responsável por limitar taxas de requisição.
 *
 * @since      1.1.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */

class Limiter_MKP_Pro_Rate_Limiting {
    
    /**
     * Verifica se a taxa de requisição foi excedida.
     *
     * @since    1.1.0
     * @param    int       $blog_id    ID do blog.
     * @param    string    $action     Ação sendo realizada.
     * @return   boolean               Verdadeiro se dentro do limite, falso se excedido.
     */
    public static function check_rate_limit($blog_id, $action) {
        $key = "limiter_rate_{$blog_id}_{$action}";
        $attempts = get_transient($key) ?: 0;
        
        if ($attempts > 10) { // 10 tentativas por minuto
            Limiter_MKP_Pro_Database::log(
                $blog_id,
                'rate_limit_exceeded',
                "Muitas tentativas da ação: $action"
            );
            return false;
        }
        
        set_transient($key, $attempts + 1, 60); // 1 minuto
        return true;
    }
}