# Plugin ImportConfig para OJS

## Descrição

O plugin **ImportConfig** permite importar configurações do portal para uma revista específica. Isso inclui configurações de plugins, temas, barras laterais, folha de estilo e menus de navegação.

## Instalação

1. Clone o repositório e coloque-o no diretório de plugins do OJS dentro de uma pasta chamada **importConfig**.
2. No painel de administração do OJS, vá até a seção de plugins genéricos e ative o plugin.

## Uso

### Configurações

Após a ativação, você pode configurar o plugin:

1. Navegue até a página de configuração do plugin.
2. Leia as informações do formulário e clique em "Salvar".

### Importação de Configurações

O plugin permite a importação de diversas configurações, incluindo:
- **Plugins Customizados**: `customblockmanagerplugin`, `customheaderplugin`, `defaultchildthemeplugin`.
- **Configurações**: `plugin_theme`, `style_sheet`, `sidebar`, `navigation_menu`.

## Arquivos Importantes

### ImportConfigPlugin.inc.php

Métodos principais:
- `register($category, $path, $mainContextId = NULL)`: Registra o plugin no sistema e o ImportConfigDAO.
- `getActions($request, $actionArgs)`: Define as ações disponíveis para o plugin.
- `manage($args, $request)`: Gerencia as requisições feitas ao plugin, incluindo a manipulação de configurações.

### ImportConfigSettingsForm.inc.php

Métodos principais:
- `execute(...$functionArgs)`: Executa a importação das configurações após a submissão do formulário.
- `importPlugins`: Executa o PluginSettingsDAO.
- `insertPlugin`: Pega as configurações específicas do site e insere na revista atual.
- `insertBlocks`: Insere os blocos personalizados na revista atual.
- `applyConfiguration`: Executa o SiteJournalDAO.
- `insertConfigurationInContext`: Aplica as configurações específicas do portal na revista atual
- `insertNavigationMenu($portalId, $currentContextId, $navigationDao)`: Insere os menus e itens de navegação na revista.
- `insertNavigationSettings($portalId, $currentContextId, $navigationDao)`: Insere as configurações de itens de navegação específicas do portal na revista.
- `applyNavigation($portalId, $currentContextId, $navigationDao)`: Aplica as atribuições de navegação no contexto atual baseado nos dados importados.
