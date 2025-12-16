<?php
if (!defined('ABSPATH')) {
    exit;
}

class Limiter_MKP_Pro_Validator {

    /**
     * Valida dados de um plano
     */
    public static function validate_plano_data($data) {
        $errors = array();

        // Nome
        if (empty($data['nome']) || !is_string($data['nome'])) {
            $errors[] = __('O nome do plano é obrigatório.', 'limiter-mkp-pro');
        } elseif (strlen($data['nome']) > 100) {
            $errors[] = __('O nome do plano não pode exceder 100 caracteres.', 'limiter-mkp-pro');
        }

        // Descrição
        if (!empty($data['descricao']) && strlen($data['descricao']) > 1000) {
            $errors[] = __('A descrição não pode exceder 1000 caracteres.', 'limiter-mkp-pro');
        }

        // Duração
        if (empty($data['duracao']) || $data['duracao'] <= 0) {
            $errors[] = __('A duração deve ser maior que zero.', 'limiter-mkp-pro');
        } elseif ($data['duracao'] > 3650) { // 10 anos
            $errors[] = __('A duração não pode exceder 3650 dias.', 'limiter-mkp-pro');
        }

        // Limite de páginas
        if (empty($data['limite_paginas']) || $data['limite_paginas'] <= 0) {
            $errors[] = __('O limite de páginas deve ser maior que zero.', 'limiter-mkp-pro');
        } elseif ($data['limite_paginas'] > 100000) {
            $errors[] = __('O limite de páginas não pode exceder 100.000.', 'limiter-mkp-pro');
        }

        return $errors;
    }

    /**
     * Valida dados de subdomínio
     */
    public static function validate_subdominio_data($data) {
        $errors = array();

        // Blog ID
        if (empty($data['blog_id']) || $data['blog_id'] <= 0) {
            $errors[] = __('ID do blog inválido.', 'limiter-mkp-pro');
        }

        // Domínio
        if (empty($data['dominio']) || !is_string($data['dominio'])) {
            $errors[] = __('O domínio é obrigatório.', 'limiter-mkp-pro');
        } elseif (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $data['dominio'])) {
            $errors[] = __('Formato de domínio inválido.', 'limiter-mkp-pro');
        }

        // Plano ID
        if (empty($data['plano_id']) || $data['plano_id'] <= 0) {
            $errors[] = __('É necessário selecionar um plano.', 'limiter-mkp-pro');
        }

        // Limite personalizado
        if (!empty($data['limite_personalizado']) && $data['limite_personalizado'] <= 0) {
            $errors[] = __('O limite personalizado deve ser maior que zero.', 'limiter-mkp-pro');
        }

        // Email
        if (!empty($data['email_cliente']) && !is_email($data['email_cliente'])) {
            $errors[] = __('E-mail do cliente inválido.', 'limiter-mkp-pro');
        }

        return $errors;
    }

    /**
     * Sanitiza dados de entrada
     */
    public static function sanitize_input($input, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'int':
                return intval($input);
            case 'float':
                return floatval($input);
            case 'textarea':
                return sanitize_textarea_field($input);
            case 'html':
                return wp_kses_post($input);
            case 'array':
                if (!is_array($input)) return array();
                return array_map('sanitize_text_field', $input);
            case 'text':
            default:
                return sanitize_text_field($input);
        }
    }
}