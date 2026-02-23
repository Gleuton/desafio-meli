# Git Hooks

Este diretório contém scripts de git hooks configurados para automatizar verificações de qualidade de código antes de enviar (push) as alterações para o repositório.

## Como Ativar os Git Hooks

Para usar os git hooks deste projeto, você precisa configurar o Git para reconhecer este diretório como a origem dos hooks.

### Passo 1: Configure o Git

Execute o seguinte comando na raiz do projeto para indicar ao Git que os hooks estão no diretório `.githooks`:

```bash
git config core.hooksPath .githooks
```

### Passo 2: Verifique a Configuração

Para confirmar que a configuração foi aplicada corretamente:

```bash
git config core.hooksPath
```

Você deverá ver `.githooks` como resposta.

### Passo 3: Torne os Scripts Executáveis (se necessário)

Se os scripts não tiverem permissão de execução, execute:

```bash
chmod +x .githooks/*
```

## Hooks Disponíveis

### pre-push

**Arquivo:** `pre-push`

**Descrição:** Executado antes de fazer push das alterações para o repositório remoto.

**Verificações realizadas:**
1. **PHPStan** - Análise estática do código PHP para detectar erros e problemas
2. **Testes** - Execução da suite de testes com Pest

Se alguma verificação falhar, o push será impedido e você precisará corrigir os problemas antes de tentar novamente.

## Configuração Global (Opcional)

Se você deseja usar os git hooks automaticamente sem precisar configurar cada clone, você pode definir uma configuração global:

```bash
git config --global core.hooksPath /caminho/completo/para/.githooks
```

> **Nota:** Isso aplicará os hooks a todos os repositórios Git no seu sistema que usem este diretório.

## Troubleshooting

### Os hooks não estão sendo executados

1. Verifique se `core.hooksPath` está configurado:
   ```bash
   git config core.hooksPath
   ```

2. Certifique-se de que os scripts têm permissão de execução:
   ```bash
   ls -l .githooks/
   ```

3. Verifique se os scripts contêm a shebang correta (`#!/bin/bash` ou similar).

### Contornar os Hooks (Não Recomendado)

Se você precisar pular os hooks temporariamente (use com cautela):

```bash
git push --no-verify
```

> ⚠️ **Atenção:** Usar `--no-verify` ignora as verificações de qualidade. Use apenas em casos excepcionais.

## Mais Informações

- [Documentação oficial de Git Hooks](https://git-scm.com/docs/githooks)
- [Git Config Core Hooks Path](https://git-scm.com/docs/git-config#core.hooksPath)

