jQuery(document).ready(function($) {
    
    // --- GERENCIAMENTO DE PLANOS ---

    // 1. Salvar Plano (Criar ou Editar)
    $('#limiter-mkp-pro-plano-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Salvando...');

        var formData = $(this).serialize();
        // Adiciona a action e o nonce manualmente ao payload
        formData += '&action=limiter_mkp_pro_save_plano&nonce=' + limiter_mkp_pro_admin.nonce;

        $.ajax({
            url: limiter_mkp_pro_admin.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Erro: ' + response.data.message);
                    $btn.prop('disabled', false).text('Salvar Plano');
                }
            },
            error: function() {
                alert('Erro de conexão.');
                $btn.prop('disabled', false).text('Salvar Plano');
            }
        });
    });

    // 2. Preencher Formulário ao Clicar em Editar
    $('.limiter-mkp-pro-edit-plano').on('click', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var nome = $(this).data('nome');
        var descricao = $(this).data('descricao');
        var duracao = $(this).data('duracao');
        var limite = $(this).data('limite');
        var limiteInodes = $(this).data('limite-inodes');
        var limiteUpload = $(this).data('limite-upload'); // Novo campo de MB
        
        var produtoIds = $(this).data('produto-ids'); 
        var planoTypes = $(this).data('plano-types');
        var planoStatus = $(this).data('plano-status');

        // Preenche os campos
        $('#limiter-mkp-pro-plano-id').val(id);
        $('#limiter-mkp-pro-plano-nome').val(nome);
        $('#limiter-mkp-pro-plano-descricao').val(descricao);
        $('#limiter-mkp-pro-plano-duracao').val(duracao);
        $('#limiter-mkp-pro-plano-limite').val(limite);
        $('#limiter-mkp-pro-plano-limite-inodes').val(limiteInodes);
        
        // Verifica se o campo de MB existe antes de tentar preencher (compatibilidade)
        if ($('#limiter-mkp-pro-plano-limite-upload').length) {
            $('#limiter-mkp-pro-plano-limite-upload').val(limiteUpload);
        }

        // Preenche os Produtos WooCommerce (Select Multiple)
        var hasLinkedProducts = false;
        if (produtoIds) {
            if (typeof produtoIds === 'string') {
                try { produtoIds = JSON.parse(produtoIds); } catch(e) {}
            }
            $('#limiter-mkp-pro-plano-woocommerce-products').val(produtoIds);
            if (Array.isArray(produtoIds) && produtoIds.length > 0) {
                hasLinkedProducts = true;
            }
        } else {
            $('#limiter-mkp-pro-plano-woocommerce-products').val([]);
        }

        // Lógica de bloqueio de campo de duração se houver produtos
        var $duracaoInput = $('#limiter-mkp-pro-plano-duracao');
        if (hasLinkedProducts) {
            $duracaoInput
                .prop('readonly', true)
                .css('background-color', '#e9ecef')
                .css('cursor', 'not-allowed')
                .attr('title', 'A duração é gerenciada automaticamente pelo produto do WooCommerce.');
        } else {
            $duracaoInput
                .prop('readonly', false)
                .css('background-color', '#fff')
                .css('cursor', 'text')
                .attr('title', '');
        }

        // Preenche Checkboxes de Tipos de Post
        $('input[name="post_types_contaveis[]"]').prop('checked', false);
        if (planoTypes) {
            if (typeof planoTypes === 'string') { try { planoTypes = JSON.parse(planoTypes); } catch(e) {} }
            if (Array.isArray(planoTypes)) {
                planoTypes.forEach(function(type) {
                    $('input[name="post_types_contaveis[]"][value="' + type + '"]').prop('checked', true);
                });
            }
        }

        // Preenche Checkboxes de Status
        $('input[name="post_status_contaveis[]"]').prop('checked', false);
        if (planoStatus) {
            if (typeof planoStatus === 'string') { try { planoStatus = JSON.parse(planoStatus); } catch(e) {} }
            if (Array.isArray(planoStatus)) {
                planoStatus.forEach(function(status) {
                    $('input[name="post_status_contaveis[]"][value="' + status + '"]').prop('checked', true);
                });
            }
        }

        // Atualiza a Interface
        $('#limiter-mkp-pro-form-title').text('Editar Plano: ' + nome);
        $('#limiter-mkp-pro-plano-submit').text('Atualizar Plano');
        $('#limiter-mkp-pro-plano-cancel').show();

        // Rola a tela até o formulário
        $('html, body').animate({
            scrollTop: $('.limiter-mkp-pro-admin-form-container').offset().top - 50
        }, 500);
    });

    // 3. Cancelar Edição
    $('#limiter-mkp-pro-plano-cancel').on('click', function(e) {
        e.preventDefault();
        
        // Reseta o formulário
        $('#limiter-mkp-pro-plano-form')[0].reset();
        $('#limiter-mkp-pro-plano-id').val(0);
        $('#limiter-mkp-pro-plano-woocommerce-products').val([]);
        
        // Desbloqueia o campo de duração
        $('#limiter-mkp-pro-plano-duracao')
            .prop('readonly', false)
            .css('background-color', '#fff')
            .css('cursor', 'text')
            .attr('title', '');

        // Restaura checkboxes padrão
        $('input[name="post_types_contaveis[]"]').prop('checked', false);
        $('input[name="post_types_contaveis[]"][value="post"]').prop('checked', true);
        $('input[name="post_types_contaveis[]"][value="page"]').prop('checked', true);

        $('input[name="post_status_contaveis[]"]').prop('checked', false);
        $('input[name="post_status_contaveis[]"][value="publish"]').prop('checked', true);
        $('input[name="post_status_contaveis[]"][value="draft"]').prop('checked', true);
        $('input[name="post_status_contaveis[]"][value="trash"]').prop('checked', true);

        // Restaura Interface
        $('#limiter-mkp-pro-form-title').text('Adicionar Novo Plano');
        $('#limiter-mkp-pro-plano-submit').text('Salvar Plano');
        $(this).hide();
    });

    // 4. Excluir Plano
    $('.limiter-mkp-pro-delete-plano').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        
        if (confirm(limiter_mkp_pro_admin.messages.confirm_delete)) {
            $.ajax({
                url: limiter_mkp_pro_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'limiter_mkp_pro_delete_plano',
                    'nonce': limiter_mkp_pro_admin.nonce,
                    'id': id
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(limiter_mkp_pro_admin.messages.error);
                }
            });
        }
    });

    // --- CORREÇÃO: Salvar Configurações (Automático e com Refresh) ---
    $('#limiter-mkp-pro-configuracoes-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Salvando...');
        
        // 1. Usa FormData para pegar TODOS os campos automaticamente
        var formData = new FormData(this);
        formData.append('action', 'limiter_mkp_pro_save_configuracoes');
        formData.append('nonce', limiter_mkp_pro_admin.nonce);

        // 2. Tratamento especial para a Blacklist (Converter texto para array)
        var blacklistText = $('#limiter-mkp-pro-subdomain-blacklist').val();
        
        // Remove a entrada original do texto para não duplicar
        formData.delete('subdomain_blacklist'); 

        if (blacklistText && blacklistText.trim() !== '') {
             var blacklistArray = blacklistText.split(',');
             blacklistArray.forEach(function(item) {
                 if(item.trim() !== '') {
                     formData.append('subdomain_blacklist[]', item.trim().toLowerCase());
                 }
             });
        } else {
            // Envia um marcador vazio para o PHP saber que queremos limpar a lista
            formData.append('subdomain_blacklist', ''); 
        }

        $.ajax({
            url: limiter_mkp_pro_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false, // Necessário para FormData
            contentType: false, // Necessário para FormData
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload(); // Atualiza a tela para mostrar os dados reais salvos
                } else {
                    alert('Erro: ' + response.data.message);
                    $btn.prop('disabled', false).text('Salvar Configurações');
                }
            },
            error: function(xhr, status, error) {
                alert('Erro de conexão ou erro interno do servidor.');
                console.error(error);
                $btn.prop('disabled', false).text('Salvar Configurações');
            }
        });
    });

    // Limpar logs antigos
    $('#limiter-mkp-pro-limpar-logs-submit').on('click', function(e) {
        e.preventDefault();
        var dias = $('#limiter-mkp-pro-dias-logs').val();
        
        if (confirm(limiter_mkp_pro_admin.messages.confirm_delete)) {
            $.ajax({
                url: limiter_mkp_pro_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'limiter_mkp_pro_limpar_logs',
                    'nonce': limiter_mkp_pro_admin.nonce,
                    'dias': dias
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(limiter_mkp_pro_admin.messages.error);
                }
            });
        }
    });

    // Adicionar subdomínio em massa (Mantido para compatibilidade)
    $('#limiter-mkp-pro-adicionar-subdominios-submit').on('click', function(e) {
        e.preventDefault();
        var plano_id = $('#limiter-mkp-pro-plano-massa').val();
        var blog_ids = [];
        
        $('input[name="blog_ids[]"]:checked').each(function() {
            blog_ids.push($(this).val());
        });
        
        if (blog_ids.length === 0) {
            alert('Por favor, selecione pelo menos um subdomínio.');
            return;
        }
        
        if (!plano_id) {
            alert('Por favor, selecione um plano.');
            return;
        }
        
        if (confirm('Tem certeza que deseja adicionar ' + blog_ids.length + ' subdomínios ao sistema?')) {
            $(this).prop('disabled', true).text('Processando...');
            
            $.ajax({
                url: limiter_mkp_pro_admin.ajax_url,
                type: 'POST',
                data: {
                    'action': 'limiter_mkp_pro_adicionar_subdominios_massa',
                    'nonce': limiter_mkp_pro_admin.nonce,
                    'blog_ids': blog_ids,
                    'plano_id': plano_id
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $('#limiter-mkp-pro-adicionar-subdominios-submit').prop('disabled', false).text('Adicionar Selecionados');
                    }
                },
                error: function() {
                    alert(limiter_mkp_pro_admin.messages.error);
                    $('#limiter-mkp-pro-adicionar-subdominios-submit').prop('disabled', false).text('Adicionar Selecionados');
                }
            });
        }
    });
    
    // Selecionar/deselecionar todos os subdomínios
    $('#limiter-mkp-pro-selecionar-todos').on('change', function() {
        $('input[name="blog_ids[]"]').prop('checked', $(this).prop('checked'));
    });
});