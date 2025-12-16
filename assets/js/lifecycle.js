/**
 * JavaScript para o ciclo de vida do Limiter MKP Pro
 * * @version 2.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Inicializa o sistema de ciclo de vida
     */
    function initLifecycle() {
        console.log('Limiter MKP Pro Lifecycle JS inicializado');
        
        // Configura os event listeners
        setupEventListeners();
        
        // Inicializa componentes
        initComponents();
        
        // Carrega dados iniciais se necessário
        loadInitialData();
    }
    
    /**
     * Configura os event listeners
     */
    function setupEventListeners() {
        // Formulário de configurações do ciclo de vida
        if ($('#limiter-lifecycle-settings-form').length) {
            $('#limiter-lifecycle-settings-form').on('submit', handleSaveSettings);
        }
        
        // Botão de verificação manual
        $('.run-manual-check').on('click', handleManualCheck);
        
        // Botões de ação rápida no dashboard
        $('.quick-action-btn').on('click', handleQuickAction);
        
        // Botões de email individual
        $(document).on('click', '.send-email-btn', handleSendEmail);
        
        // Botões de extensão de carência
        $(document).on('click', '.extend-grace-btn', handleExtendGrace);
        
        // Botões de restauração
        $(document).on('click', '.restore-blog-btn', handleRestoreBlog);
        
        // Botões de suspensão manual
        $(document).on('click', '.suspend-blog-btn', handleSuspendBlog);
        
        // Botões de agendamento de exclusão
        $(document).on('click', '.schedule-deletion-btn', handleScheduleDeletion);
        
        // Botões de cancelar exclusão
        $(document).on('click', '.cancel-deletion-btn', handleCancelDeletion);
        
        // Exportação de relatórios
        $('.export-report-btn').on('click', handleExportReport);
        
        // Envio de lembretes em massa
        $('.send-bulk-reminders-btn').on('click', handleBulkReminders);
        
        // Atualização de estatísticas em tempo real
        $('.refresh-stats-btn').on('click', refreshStats);
        
        // Tooltips
        $(document).on('mouseenter', '[data-toggle="tooltip"]', showTooltip);
    }
    
    /**
     * Inicializa componentes
     */
    function initComponents() {
        // Datepickers
        if ($.fn.datepicker) {
            $('.datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0
            });
        }
        
        // Select2 para seleções múltiplas
        if ($.fn.select2) {
            $('.select2-multiple').select2({
                width: '100%',
                placeholder: 'Selecione...'
            });
        }
        
        // Tabs
        $('.lifecycle-tabs a').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).attr('href');
            $('.lifecycle-tabs a').removeClass('active');
            $(this).addClass('active');
            $('.tab-content').hide();
            $(tab).show();
        });
        
        // Modais
        initModals();
    }
    
    /**
     * Inicializa modais
     */
    function initModals() {
        // Modal de email customizado
        $(document).on('click', '.custom-email-modal-btn', function() {
            var blogId = $(this).data('blog-id');
            var blogName = $(this).data('blog-name');
            var customerEmail = $(this).data('customer-email');
            
            $('#custom-email-blog-id').val(blogId);
            $('#custom-email-blog-name').text(blogName);
            $('#custom-email-recipient').text(customerEmail);
            
            $('#custom-email-modal').show();
        });
        
        // Fechar modais
        $('.modal-close, .modal-cancel').on('click', function() {
            $(this).closest('.modal').hide();
        });
        
        // Fechar ao clicar fora
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('modal')) {
                $('.modal').hide();
            }
        });
    }
    
    /**
     * Carrega dados iniciais
     */
    function loadInitialData() {
        // Se estiver na página do dashboard, carrega estatísticas
        if ($('.churn-dashboard').length) {
            refreshStats();
            
            // Atualiza estatísticas a cada 30 segundos
            setInterval(refreshStats, 30000);
        }
    }
    
    /**
     * Handler para salvar configurações
     */
    function handleSaveSettings(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: formData + '&action=limiter_save_lifecycle_settings',
            beforeSend: function() {
                $('#save-settings-btn').prop('disabled', true).text('Salvando...');
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao salvar configurações:', error);
                showNotification('error', 'Erro ao salvar configurações. Verifique o console para detalhes.');
            },
            complete: function() {
                $('#save-settings-btn').prop('disabled', false).text('Salvar Configurações');
                hideLoader();
            }
        });
    }
    
    /**
     * Handler para verificação manual
     */
    function handleManualCheck() {
        if (!confirm(limiter_lifecycle.i18n.confirm_action)) {
            return;
        }
        
        var $button = $(this);
        
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_run_manual_check',
                nonce: limiter_lifecycle.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Verificando...');
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    // Recarrega após 2 segundos
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', limiter_lifecycle.i18n.error);
            },
            complete: function() {
                $button.prop('disabled', false).text('Executar Verificação Manual');
                hideLoader();
            }
        });
    }
    
    /**
     * Handler para ações rápidas
     */
    function handleQuickAction() {
        var action = $(this).data('action');
        
        switch(action) {
            case 'send_test_email':
                sendTestEmail();
                break;
            case 'clear_cache':
                clearCache();
                break;
            case 'export_logs':
                exportLogs();
                break;
        }
    }
    
    /**
     * Handler para enviar email individual
     */
    function handleSendEmail() {
        var $button = $(this);
        var blogId = $button.data('blog-id');
        var template = $button.data('template');
        
        if (!blogId || !template) {
            showNotification('error', 'Dados insuficientes para enviar email.');
            return;
        }
        
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_send_custom_email',
                nonce: limiter_lifecycle.nonce,
                blog_id: blogId,
                template: template
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Enviando...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Erro ao enviar email.');
            },
            complete: function() {
                setTimeout(function() {
                    $button.prop('disabled', false).text('Enviar Email');
                }, 2000);
            }
        });
    }
    
    /**
     * Handler para extensão de carência
     */
    function handleExtendGrace() {
        var $button = $(this);
        var blogId = $button.data('blog-id');
        var blogName = $button.data('blog-name');
        
        var days = prompt('Quantos dias deseja estender a carência para "' + blogName + '"?', '7');
        
        if (!days || isNaN(days) || parseInt(days) <= 0) {
            return;
        }
        
        var reason = prompt('Qual o motivo da extensão?', 'Extensão manual pelo administrador');
        
        if (!reason) {
            return;
        }
        
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_extend_grace_period',
                nonce: limiter_lifecycle.nonce,
                blog_id: blogId,
                days: days,
                reason: reason
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Processando...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    // Atualiza a linha na tabela
                    updateBlogRow(blogId, {
                        grace_ends: response.data.formatted_date,
                        status: 'grace_period'
                    });
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Erro ao estender carência.');
            },
            complete: function() {
                setTimeout(function() {
                    $button.prop('disabled', false).text('Estender Carência');
                }, 2000);
            }
        });
    }
    
    /**
     * Handler para restauração de blog
     */
    function handleRestoreBlog() {
        var $button = $(this);
        var blogId = $button.data('blog-id');
        var blogName = $button.data('blog-name');
        
        if (!confirm('Tem certeza que deseja restaurar o blog "' + blogName + '"?')) {
            return;
        }
        
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_restore_blog',
                nonce: limiter_lifecycle.nonce,
                blog_id: blogId
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Restaurando...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    // Remove a linha da tabela ou atualiza status
                    updateBlogRow(blogId, { status: 'active' });
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Erro ao restaurar blog.');
            },
            complete: function() {
                setTimeout(function() {
                    $button.prop('disabled', false).text('Restaurar');
                }, 2000);
            }
        });
    }
    
    /**
     * Handler para suspensão manual
     */
    function handleSuspendBlog() {
        var $button = $(this);
        var blogId = $button.data('blog-id');
        var blogName = $button.data('blog-name');
        
        var reason = prompt('Qual o motivo da suspensão manual para "' + blogName + '"?', 'Suspensão manual pelo administrador');
        
        if (!reason) {
            return;
        }
        
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_suspend_blog',
                nonce: limiter_lifecycle.nonce,
                blog_id: blogId,
                reason: reason
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Suspendo...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    // Atualiza a linha na tabela
                    updateBlogRow(blogId, { status: 'suspended' });
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Erro ao suspender blog.');
            },
            complete: function() {
                setTimeout(function() {
                    $button.prop('disabled', false).text('Suspender');
                }, 2000);
            }
        });
    }
    
    /**
     * Handler para agendamento de exclusão
     */
    function handleScheduleDeletion() {
        var $button = $(this);
        var blogId = $button.data('blog-id');
        var blogName = $button.data('blog-name');
        
        var deletionDate = prompt('Data para exclusão (YYYY-MM-DD) para "' + blogName + '"?\nDeixe em branco para 7 dias a partir de hoje.', getDateInFuture(7));
        
        if (deletionDate === null) {
            return;
        }
        
        // Valida a data
        if (deletionDate && !isValidDate(deletionDate)) {
            showNotification('error', 'Data inválida. Use o formato YYYY-MM-DD.');
            return;
        }
        
        var reason = prompt('Qual o motivo do agendamento de exclusão?', 'Exclusão manual pelo administrador');
        
        if (!reason) {
            return;
        }
        
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_schedule_deletion',
                nonce: limiter_lifecycle.nonce,
                blog_id: blogId,
                deletion_date: deletionDate,
                reason: reason
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Agendando...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    // Atualiza a linha na tabela
                    updateBlogRow(blogId, {
                        status: 'scheduled_deletion',
                        deletion_date: response.data.deletion_date
                    });
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Erro ao agendar exclusão.');
            },
            complete: function() {
                setTimeout(function() {
                    $button.prop('disabled', false).text('Agendar Exclusão');
                }, 2000);
            }
        });
    }
    
    /**
     * Handler para cancelar exclusão
     */
    function handleCancelDeletion() {
        var $button = $(this);
        var blogId = $button.data('blog-id');
        var blogName = $button.data('blog-name');
        
        if (!confirm('Cancelar exclusão agendada para "' + blogName + '"?')) {
            return;
        }
        
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_cancel_deletion',
                nonce: limiter_lifecycle.nonce,
                blog_id: blogId
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Cancelando...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    // Atualiza a linha na tabela
                    updateBlogRow(blogId, { status: 'active' });
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Erro ao cancelar exclusão.');
            },
            complete: function() {
                setTimeout(function() {
                    $button.prop('disabled', false).text('Cancelar Exclusão');
                }, 2000);
            }
        });
    }
    
    /**
     * Handler para exportação de relatórios
     */
    function handleExportReport() {
        var type = $(this).data('type') || 'csv';
        var url = limiter_lifecycle.ajax_url + '?action=limiter_export_churn_report&type=' + type + '&nonce=' + limiter_lifecycle.nonce;
        
        window.open(url, '_blank');
    }
    
    /**
     * Handler para lembretes em massa
     */
    function handleBulkReminders() {
        var $button = $(this);
        var blogIds = [];
        
        // Coleta IDs dos blogs selecionados
        $('.blog-checkbox:checked').each(function() {
            blogIds.push($(this).val());
        });
        
        if (blogIds.length === 0) {
            showNotification('warning', 'Selecione pelo menos um blog.');
            return;
        }
        
        if (!confirm('Enviar lembretes para ' + blogIds.length + ' blog(s) selecionado(s)?')) {
            return;
        }
        
        var template = prompt('Qual template de email deseja usar?', 'payment_failed_day1');
        
        if (!template) {
            return;
        }
        
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_send_bulk_reminders',
                nonce: limiter_lifecycle.nonce,
                blog_ids: blogIds,
                template: template
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Enviando...');
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Erro ao enviar lembretes.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Enviar Lembretes em Massa');
                hideLoader();
            }
        });
    }
    
    /**
     * Atualiza estatísticas em tempo real
     */
    function refreshStats() {
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_get_realtime_stats',
                nonce: limiter_lifecycle.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatsDisplay(response.data.stats);
                }
            }
        });
    }
    
    /**
     * Atualiza a exibição de estatísticas
     */
    function updateStatsDisplay(stats) {
        // Atualiza cada elemento de estatística
        $.each(stats, function(key, value) {
            var $element = $('.stat-' + key);
            if ($element.length) {
                // Anima a mudança de valor
                var current = $element.text().replace(/[^0-9.]/g, '');
                if (current !== value.toString()) {
                    $element.fadeOut(200, function() {
                        $(this).text(typeof value === 'number' ? value.toLocaleString() : value);
                        $(this).fadeIn(200);
                    });
                }
            }
        });
        
        // Atualiza timestamp
        $('.stats-timestamp').text('Atualizado: ' + new Date().toLocaleTimeString());
    }
    
    /**
     * Atualiza uma linha na tabela de blogs
     */
    function updateBlogRow(blogId, updates) {
        var $row = $('tr[data-blog-id="' + blogId + '"]');
        
        $.each(updates, function(key, value) {
            var $cell = $row.find('.cell-' + key);
            if ($cell.length) {
                $cell.text(value);
                
                // Atualiza classes CSS baseadas no status
                if (key === 'status') {
                    $row.removeClass('status-active status-grace_period status-suspended status-locked status-scheduled_deletion');
                    $row.addClass('status-' + value);
                }
            }
        });
    }
    
    /**
     * Mostra um tooltip
     */
    function showTooltip() {
        var text = $(this).data('tooltip');
        var $tooltip = $('<div class="limiter-tooltip">' + text + '</div>');
        
        $('body').append($tooltip);
        
        var pos = $(this).offset();
        $tooltip.css({
            top: pos.top - $tooltip.outerHeight() - 10,
            left: pos.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2)
        });
        
        $(this).on('mouseleave', function() {
            $tooltip.remove();
        });
    }
    
    /**
     * Mostra uma notificação
     */
    function showNotification(type, message) {
        var $notification = $('<div class="limiter-notification limiter-notification-' + type + '">' + message + '</div>');
        
        $('body').append($notification);
        
        // Posiciona a notificação
        $notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            zIndex: '9999'
        });
        
        // Remove após 5 segundos
        setTimeout(function() {
            $notification.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Mostra loader
     */
    function showLoader() {
        if ($('#limiter-loader').length === 0) {
            $('body').append('<div id="limiter-loader" class="limiter-loader"><div class="spinner"></div></div>');
        }
        $('#limiter-loader').show();
    }
    
    /**
     * Esconde loader
     */
    function hideLoader() {
        $('#limiter-loader').fadeOut();
    }
    
    /**
     * Envia email de teste
     */
    function sendTestEmail() {
        var email = prompt('Para qual email deseja enviar o teste?', 'admin@example.com');
        
        if (!email) {
            return;
        }
        
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_send_test_email',
                nonce: limiter_lifecycle.nonce,
                email: email
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Email de teste enviado!');
                } else {
                    showNotification('error', response.data.message);
                }
            }
        });
    }
    
    /**
     * Limpa cache
     */
    function clearCache() {
        $.ajax({
            url: limiter_lifecycle.ajax_url,
            type: 'POST',
            data: {
                action: 'limiter_clear_cache',
                nonce: limiter_lifecycle.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Cache limpo com sucesso!');
                }
            }
        });
    }
    
    /**
     * Exporta logs
     */
    function exportLogs() {
        var url = limiter_lifecycle.ajax_url + '?action=limiter_export_logs&nonce=' + limiter_lifecycle.nonce;
        window.open(url, '_blank');
    }
    
    /**
     * Helper: Obtém data futura
     */
    function getDateInFuture(days) {
        var date = new Date();
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    }
    
    /**
     * Helper: Valida data
     */
    function isValidDate(dateString) {
        var regex = /^\d{4}-\d{2}-\d{2}$/;
        if (!regex.test(dateString)) {
            return false;
        }
        
        var date = new Date(dateString);
        return date instanceof Date && !isNaN(date);
    }
    
    /**
     * Inicializa quando o documento estiver pronto
     */
    $(document).ready(function() {
        initLifecycle();
    });
    
})(jQuery);