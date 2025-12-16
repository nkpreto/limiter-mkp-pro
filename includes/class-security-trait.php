<?php
/**
 * Trait para funcionalidades de segurança.
 *
 * @since      1.2.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Limiter_MKP_Pro_Security_Trait {
    
    /**
     * Verifica permissões de rede.
     *
     * @since    1.2.0
     * @return   boolean
     */
    public function verify_network_permissions() {
        if (!is_multisite() || !current_user_can('manage_network')) {
            wp_die('Você não tem permissão para acessar esta página.');
        }
        return true;
    }

    /**
     * Verifica permissões de rede para AJAX.
     *
     * @since    1.2.0
     * @return   boolean
     */
    public function verify_ajax_network_permissions() {
        if (!is_multisite() || !current_user_can('manage_network')) {
            wp_send_json_error(['message' => 'Permissão negada.']);
        }
        return true;
    }

    /**
     * Verifica nonce para requisições AJAX.
     *
     * @since    1.2.0
     * @param    string    $nonce    Nonce.
     * @param    string    $action   Ação.
     * @return   boolean
     */
    public function verify_ajax_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_send_json_error(['message' => 'Erro de segurança. Recarregue a página e tente novamente.']);
        }
        return true;
    }

    /**
     * Sanitiza dados de entrada de forma rigorosa.
     *
     * @since    1.2.0
     * @param    mixed     $data    Dados a sanitizar.
     * @param    string    $type    Tipo de sanitização.
     * @return   mixed
     */
    public function sanitize_input($data, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($data);
            case 'url':
                return esc_url_raw($data);
            case 'int':
                return intval($data);
            case 'float':
                return floatval($data);
            case 'textarea':
                return sanitize_textarea_field($data);
            case 'html':
                return wp_kses_post($data);
            case 'array':
                if (!is_array($data)) return [];
                return array_map('sanitize_text_field', $data);
            case 'text':
            default:
                return sanitize_text_field($data);
        }
    }

    /**
     * Valida dados de um plano.
     *
     * @since    1.2.0
     * @param    array    $data    Dados do plano.
     * @return   array             Array de erros.
     */
    public function validate_plano_data($data) {
        $errors = [];

        // Nome
        if (empty($data['nome']) || !is_string($data['nome'])) {
            $errors[] = 'O nome do plano é obrigatório.';
        } elseif (strlen($data['nome']) > 100) {
            $errors[] = 'O nome do plano não pode exceder 100 caracteres.';
        }

        // Descrição
        if (!empty($data['descricao']) && strlen($data['descricao']) > 1000) {
            $errors[] = 'A descrição não pode exceder 1000 caracteres.';
        }

        // Duração
        if (empty($data['duracao']) || $data['duracao'] <= 0) {
            $errors[] = 'A duração deve ser maior que zero.';
        } elseif ($data['duracao'] > 3650) {
            $errors[] = 'A duração não pode exceder 3650 dias.';
        }

        // Limite de páginas
        if (empty($data['limite_paginas']) || $data['limite_paginas'] <= 0) {
            $errors[] = 'O limite de páginas deve ser maior que zero.';
        } elseif ($data['limite_paginas'] > 100000) {
            $errors[] = 'O limite de páginas não pode exceder 100.000.';
        }

        return $errors;
    }
}