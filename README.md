# Instruções de Instalação do Plugin Limiter MKP Pro

## Requisitos
- WordPress Multisite versão 6.8.1 ou superior
- PHP 7.4 ou superior
- MySQL 5.7 ou superior

## Instalação

1. Faça o upload do arquivo `limiter-mkp-pro.zip` para o diretório `/wp-content/plugins/` do seu WordPress Multisite
2. Acesse o painel de administração da rede (Super Admin)
3. Navegue até "Plugins" > "Plugins Instalados"
4. Ative o plugin "Limiter MKP Pro" para toda a rede

## Configuração Inicial

1. Após a ativação, acesse o menu "Limiter MKP Pro" no painel de administração da rede
2. Verifique se os planos padrão foram criados corretamente (Starter, Pro, Master)
3. Navegue até "Limiter MKP Pro" > "Subdomínios" para associar os subdomínios aos planos
4. Configure as mensagens personalizadas em "Limiter MKP Pro" > "Configurações"

## Uso do Plugin

### Para o Super Admin:
- **Dashboard**: Visualize estatísticas gerais do sistema
- **Planos**: Gerencie os planos disponíveis (criar, editar, excluir)
- **Subdomínios**: Associe subdomínios a planos e defina limites personalizados
- **Adicionar Subdomínio**: Adicione novos subdomínios ao sistema
- **Solicitações**: Visualize e processe solicitações de mudança de plano
- **Configurações**: Configure mensagens personalizadas e outras opções
- **Logs**: Visualize o histórico de ações no sistema

### Para os Administradores de Subdomínios:
- Um widget será exibido no dashboard mostrando o plano atual, limite de páginas e opções
- Um dashboard completo estará disponível no menu "Limiter MKP Pro"
- Poderão solicitar mudança de plano quando necessário
- Receberão alertas quando estiverem próximos do limite de páginas

## Suporte

Para dúvidas, sugestões ou suporte técnico, entre em contato:
- E-mail: suporte@marketing-place.store

## Notas Importantes

- O plugin limita a criação de páginas e posts quando o limite é atingido
- O limite considera tanto páginas/posts publicados quanto na lixeira
- Recomenda-se esvaziar a lixeira regularmente para liberar espaço no limite
- As solicitações de mudança de plano são notificadas por e-mail para o endereço configurado e todos os super administradores
