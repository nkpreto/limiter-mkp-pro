# Alterações Realizadas no Plugin Limiter MKP Pro

## Correções no Sistema de Limite de Páginas

### 1. Correção do Cálculo Total de Páginas
- Ajustado o cálculo para incluir corretamente todos os tipos de conteúdo: posts publicados, páginas publicadas, rascunhos, pendentes, agendados e itens na lixeira
- Implementada contagem separada para cada tipo de conteúdo para melhor visualização nas estatísticas
- Garantido que o limite seja aplicado ao total geral, conforme especificado

### 2. Implementação do Alerta na Penúltima Página
- Adicionada lógica para exibir alerta apenas quando o usuário atinge a penúltima página do limite
- O alerta não é mais exibido com base no campo "Limite para Alerta", mas sim quando resta apenas 1 página para atingir o limite
- O alerta é automaticamente ocultado quando uma solicitação de mudança de plano está pendente

### 3. Bloqueio Correto ao Atingir o Limite
- Garantido que o bloqueio de criação de novas páginas ocorra exatamente ao atingir o limite do plano
- O campo "Limite para Alerta" não aumenta mais o número total de páginas permitidas
- Mantida a possibilidade de publicar páginas existentes mesmo após atingir o limite

### 4. Personalização de Mensagens
- Adicionado novo campo no painel de configurações para personalizar a mensagem de alerta da penúltima página
- Mantidas todas as opções de personalização de mensagens existentes
- Garantido que todas as mensagens (alerta, bloqueio, etc.) sejam carregadas das configurações da rede

## Arquivos Modificados
1. `/models/class-database-get-estatisticas.php` - Correção do cálculo de páginas e implementação do alerta
2. `/public/partials/widget.php` - Adição do alerta visual no widget
3. `/admin/partials/configuracoes.php` - Adição do campo para personalizar a mensagem de alerta

## Instruções de Instalação
1. Desative e remova completamente qualquer versão anterior do plugin
2. Faça upload e extraia o conteúdo do ZIP para a pasta `/wp-content/plugins/limiter-mkp-pro-corrigido`
3. Ative o plugin pela área de administração da rede
4. Verifique as configurações e personalize as mensagens conforme necessário
