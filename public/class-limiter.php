<?php
/**
 * Classe responsável pela limitação de páginas e posts.
 *
 * @since      1.0.0
 * @package    Limiter_MKP_Pro
 * @subpackage Limiter_MKP_Pro/public
 */
class Limiter_MKP_Pro_Limiter {
    /**
     * O identificador único deste plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    O nome ou identificador único deste plugin.
     */
    private $plugin_name;
    /**
     * A versão atual do plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    A versão atual do plugin.
     */
    private $version;
    /**
     * Inicializa a classe e define suas propriedades.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       O nome do plugin.
     * @param    string    $version           A versão do plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        // Verifica o limite antes de exibir a tela de criação de nova página/post
        add_action('load-post-new.php', array($this, 'check_limit_before_new_post_screen'));
        // Adiciona filtro para bloquear a criação de novas páginas/posts quando o limite for atingido
        add_filter('wp_insert_post_empty_content', array($this, 'block_new_post_if_limit_reached'), 10, 2);
        // Adiciona verificação para REST API
        add_filter('rest_pre_dispatch', array($this, 'check_rest_api_limits'), 10, 3);
        // Adiciona verificação para XML-RPC
        add_filter('xmlrpc_methods', array($this, 'check_xmlrpc_limits'));
    }
    /**
     * Verifica o limite antes de exibir a tela de criação de nova página/post.
     * Se o limite for atingido, exibe a mensagem usando wp_die().
     *
     * @since    1.0.0
     */
    public function check_limit_before_new_post_screen() {
        global $typenow;
        // Obtém o ID do blog atual
        $blog_id = get_current_blog_id();
        // Obtém o subdomínio
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        if (!$sub || !$sub->plano_id) {
            return; // Sem limite definido
        }
        // Obtém o plano
        $plano = Limiter_MKP_Pro_Database::get_plano($sub->plano_id);
        if (!$plano) {
            return;
        }
        // Obtém os tipos de post que contam para este plano
        $post_types = json_decode($plano->post_types_contaveis, true) ?: ['page', 'post'];
        // Verifica se o tipo atual conta para o limite
        if (!in_array($typenow, $post_types)) {
            return;
        }
        // Conta o conteúdo
        $total_pages_posts = Limiter_MKP_Pro_Database::count_limited_content($blog_id);
        // Obtém o limite
        $limite = !empty($sub->limite_personalizado) ? $sub->limite_personalizado : $plano->limite_paginas;
        // Se estiver dentro do limite, permite a criação
        if ($total_pages_posts < $limite) {
            return;
        }
        // Se chegou aqui, está tentando criar além do limite
        // Obtém a mensagem personalizada
        $configuracoes = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $mensagem = isset($configuracoes['mensagem_limite']) ? 
                    $configuracoes['mensagem_limite'] : 
                    'Desculpe o inconveniente, mas seu plano tem suporte a criação de [X] paginas. Considere fazer upgrade do seu plano para continuar criando novas páginas.';
        // Substitui o placeholder [X] pelo limite real
        $mensagem = str_replace('[X]', $limite, $mensagem);
        // Registra no log
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'limite_excedido',
            sprintf('Tentativa de criar página/post além do limite de %d.', $limite)
        );
        // Exibe a mensagem usando wp_die com botão de voltar
        wp_die(
            $mensagem,
            __('Limite de páginas atingido', 'limiter-mkp-pro'),
            array('back_link' => true)
        );
    }
    /**
     * Bloqueia a criação de novas páginas/posts quando o limite for atingido.
     * Este filtro é chamado antes da criação do post e retorna true para bloquear.
     *
     * @since    1.0.0
     * @param    boolean   $maybe_empty  Se o conteúdo está vazio.
     * @param    array     $postarr      Array com os dados do post.
     * @return   boolean                 True para bloquear, false para permitir.
     */
    public function block_new_post_if_limit_reached($maybe_empty, $postarr) {
        // Se já está vazio ou é uma atualização, não interfere
        if ($maybe_empty || !empty($postarr['ID'])) {
            return $maybe_empty;
        }
        // Obtém o ID do blog atual
        $blog_id = get_current_blog_id();
        // Obtém o subdomínio
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        if (!$sub || !$sub->plano_id) {
            return $maybe_empty; // Sem limite definido
        }
        // Obtém o plano
        $plano = Limiter_MKP_Pro_Database::get_plano($sub->plano_id);
        if (!$plano) {
            return $maybe_empty;
        }
        // Obtém os tipos de post que contam para este plano
        $post_types = json_decode($plano->post_types_contaveis, true) ?: ['page', 'post'];
        // Ignora tipos de post que não contam para o limite
        if (!in_array($postarr['post_type'], $post_types)) {
            return $maybe_empty;
        }
        // Conta o conteúdo
        $total_pages_posts = Limiter_MKP_Pro_Database::count_limited_content($blog_id);
        // Obtém o limite
        $limite = !empty($sub->limite_personalizado) ? $sub->limite_personalizado : $plano->limite_paginas;
        // Se estiver dentro do limite, permite a criação
        if ($total_pages_posts < $limite) {
            return $maybe_empty;
        }
        // Se chegou aqui, está tentando criar além do limite
        // Obtém a mensagem personalizada
        $configuracoes = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $mensagem = isset($configuracoes['mensagem_limite']) ? 
                    $configuracoes['mensagem_limite'] : 
                    'Desculpe o inconveniente, mas seu plano tem suporte a criação de [X] paginas. Considere fazer upgrade do seu plano para continuar criando novas páginas.';
        // Substitui o placeholder [X] pelo limite real
        $mensagem = str_replace('[X]', $limite, $mensagem);
        // Registra no log
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'limite_excedido',
            sprintf('Tentativa de criar página/post além do limite de %d.', $limite)
        );
        // Exibe a mensagem usando wp_die com botão de voltar
        wp_die(
            $mensagem,
            __('Limite de páginas atingido', 'limiter-mkp-pro'),
            array('back_link' => true)
        );
        // Retorna true para bloquear a criação (caso wp_die não funcione)
        return true;
    }
    /**
     * Verifica o limite de páginas/posts ao salvar um post.
     * Esta é uma verificação secundária, caso o filtro block_new_post_if_limit_reached falhe.
     *
     * @since    1.0.0
     * @param    int       $post_id     ID do post.
     * @param    object    $post        Objeto do post.
     * @param    boolean   $update      Se é uma atualização ou novo post.
     */
    public function check_limit($post_id, $post, $update) {
        // Ignora autosaves e revisões
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        // Ignora atualizações, apenas verifica novas criações
        if ($update) {
            return;
        }
        // Obtém o ID do blog atual
        $blog_id = get_current_blog_id();
        // Obtém o subdomínio
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        if (!$sub || !$sub->plano_id) {
            return; // Sem limite definido
        }
        // Obtém o plano
        $plano = Limiter_MKP_Pro_Database::get_plano($sub->plano_id);
        if (!$plano) {
            return;
        }
        // Obtém os tipos de post que contam para este plano
        $post_types = json_decode($plano->post_types_contaveis, true) ?: ['page', 'post'];
        // Ignora tipos de post que não contam para o limite
        if (!in_array($post->post_type, $post_types)) {
            return;
        }
        // Conta o conteúdo
        $total_pages_posts = Limiter_MKP_Pro_Database::count_limited_content($blog_id);
        // Obtém o limite
        $limite = !empty($sub->limite_personalizado) ? $sub->limite_personalizado : $plano->limite_paginas;
        // Se estiver dentro do limite, permite a criação
        if ($total_pages_posts < $limite) {
            return;
        }
        // Se chegou aqui, está tentando criar além do limite
        // Remove o post que estava sendo criado
        wp_delete_post($post_id, true);
        // Obtém a mensagem personalizada
        $configuracoes = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $mensagem = isset($configuracoes['mensagem_limite']) ? 
                    $configuracoes['mensagem_limite'] : 
                    'Desculpe o inconveniente, mas seu plano tem suporte a criação de [X] paginas. Considere fazer upgrade do seu plano para continuar criando novas páginas.';
        // Substitui o placeholder [X] pelo limite real
        $mensagem = str_replace('[X]', $limite, $mensagem);
        // Registra no log
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'limite_excedido',
            sprintf('Tentativa de criar página/post além do limite de %d.', $limite)
        );
        // Exibe a mensagem usando wp_die com botão de voltar
        wp_die(
            $mensagem,
            __('Limite de páginas atingido', 'limiter-mkp-pro'),
            array('back_link' => true)
        );
    }
    /**
     * Verifica o limite ao restaurar itens da lixeira.
     *
     * @since    1.0.0
     * @param    int       $post_id     ID do post.
     */
    public function check_limit_untrash($post_id) {
        $post = get_post($post_id);
        // Obtém o ID do blog atual
        $blog_id = get_current_blog_id();
        // Obtém o subdomínio
        require_once LIMITER_MKP_PRO_PLUGIN_DIR . 'models/class-database.php';
        $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
        if (!$sub || !$sub->plano_id) {
            return; // Sem limite definido
        }
        // Obtém o plano
        $plano = Limiter_MKP_Pro_Database::get_plano($sub->plano_id);
        if (!$plano) {
            return;
        }
        // Obtém os tipos de post que contam para este plano
        $post_types = json_decode($plano->post_types_contaveis, true) ?: ['page', 'post'];
        // Ignora tipos de post que não contam para o limite
        if (!in_array($post->post_type, $post_types)) {
            return;
        }
        // Conta o conteúdo
        $total_pages_posts = Limiter_MKP_Pro_Database::count_limited_content($blog_id);
        // Obtém o limite
        $limite = !empty($sub->limite_personalizado) ? $sub->limite_personalizado : $plano->limite_paginas;
        // Se estiver dentro do limite, permite a restauração
        if ($total_pages_posts < $limite) {
            return;
        }
        // Se chegou aqui, está tentando restaurar além do limite
        // Mantém o post na lixeira
        wp_trash_post($post_id);
        // Obtém a mensagem personalizada
        $configuracoes = get_network_option(null, 'limiter_mkp_pro_configuracoes', array());
        $mensagem = isset($configuracoes['mensagem_limite']) ? 
                    $configuracoes['mensagem_limite'] : 
                    'Desculpe o inconveniente, mas seu plano tem suporte a criação de [X] paginas. Considere fazer upgrade do seu plano para continuar criando novas páginas.';
        // Substitui o placeholder [X] pelo limite real
        $mensagem = str_replace('[X]', $limite, $mensagem);
        // Registra no log
        Limiter_MKP_Pro_Database::log(
            $blog_id,
            'limite_excedido_restauracao',
            sprintf('Tentativa de restaurar página/post da lixeira além do limite de %d.', $limite)
        );
        // Exibe a mensagem usando wp_die com botão de voltar
        wp_die(
            $mensagem,
            __('Limite de páginas atingido', 'limiter-mkp-pro'),
            array('back_link' => true)
        );
    }
    /**
     * Verifica limites na REST API.
     *
     * @since    1.1.0
     * @param    mixed           $response    Resposta atual.
     * @param    WP_REST_Server  $handler     Instância do servidor REST.
     * @param    WP_REST_Request $request     Requisição atual.
     * @return   mixed                        Resposta modificada ou erro.
     */
    public function check_rest_api_limits($response, $handler, $request) {
        $method = $request->get_method();
        $route = $request->get_route();
        if ($method === 'POST' && in_array($route, ['/wp/v2/pages', '/wp/v2/posts'])) {
            $blog_id = get_current_blog_id();
            $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
            if (!$sub || !$sub->plano_id) {
                return $response; // Sem limite definido
            }
            $plano = Limiter_MKP_Pro_Database::get_plano($sub->plano_id);
            if (!$plano) {
                return $response;
            }
            $post_types = json_decode($plano->post_types_contaveis, true) ?: ['page', 'post'];
            if (!in_array('page', $post_types) && !in_array('post', $post_types)) {
                return $response; // Nenhum dos tipos que contam
            }
            $total_pages_posts = Limiter_MKP_Pro_Database::count_limited_content($blog_id);
            $limite = !empty($sub->limite_personalizado) ? $sub->limite_personalizado : $plano->limite_paginas;
            if ($total_pages_posts >= $limite && $limite > 0) {
                // Registra no log
                Limiter_MKP_Pro_Database::log(
                    $blog_id,
                    'limite_excedido_rest_api',
                    sprintf('Tentativa de criar página/post via REST API além do limite de %d.', $limite)
                );
                return new WP_Error(
                    'limite_atingido',
                    'Limite de páginas/posts atingido para este plano.',
                    array('status' => 403)
                );
            }
        }
        return $response;
    }
    /**
     * Verifica limites no XML-RPC.
     *
     * @since    1.1.0
     * @param    array     $methods    Métodos XML-RPC.
     * @return   array                 Métodos XML-RPC.
     */
    public function check_xmlrpc_limits($methods) {
        add_filter('xmlrpc_call', array($this, 'block_xmlrpc_if_limit_reached'));
        return $methods;
    }
    /**
     * Bloqueia criação via XML-RPC se o limite for atingido.
     *
     * @since    1.1.0
     * @param    string    $method    Método XML-RPC.
     */
    public function block_xmlrpc_if_limit_reached($method) {
        if (in_array($method, array('wp.newPost', 'wp.newPage'))) {
            $blog_id = get_current_blog_id();
            $sub = Limiter_MKP_Pro_Database::get_subdominio_by_blog_id($blog_id);
            if (!$sub || !$sub->plano_id) {
                return; // Sem limite definido
            }
            $plano = Limiter_MKP_Pro_Database::get_plano($sub->plano_id);
            if (!$plano) {
                return;
            }
            $post_types = json_decode($plano->post_types_contaveis, true) ?: ['page', 'post'];
            if (!in_array('page', $post_types) && !in_array('post', $post_types)) {
                return; // Nenhum dos tipos que contam
            }
            $total_pages_posts = Limiter_MKP_Pro_Database::count_limited_content($blog_id);
            $limite = !empty($sub->limite_personalizado) ? $sub->limite_personalizado : $plano->limite_paginas;
            if ($total_pages_posts >= $limite && $limite > 0) {
                // Registra no log
                Limiter_MKP_Pro_Database::log(
                    $blog_id,
                    'limite_excedido_xmlrpc',
                    sprintf('Tentativa de criar página/post via XML-RPC além do limite de %d.', $limite)
                );
                wp_die(__('Limite de páginas atingido', 'limiter-mkp-pro'));
            }
        }
    }
}