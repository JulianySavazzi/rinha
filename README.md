# Rinha de backend 2026 - Fraud Score Detection

Ideia de fluxo do projeto:
* **Não é necessário persistência**
  * Não salvar novas transações em banco, cache ou memória
  * Cada requisição é independente (stateless)

* **Dataset histórico é fixo**
  * Os arquivos não mudam durante o teste
  * Base de comparação: `references.json.gz` com ~100k transações

* **Uso do OpenSwoole (vantagem chave)**
  * Servidor permanece em memória (não reinicia a cada request)
  * Permite carregar dados uma única vez no boot

* **Inicialização da aplicação**
  * Ler `references.json.gz` antes do `$server->start()`
  * Carregar os vetores em um array global (já ordenados pelo amount)
  * Baixo consumo de memória (alguns MB)

* **Fluxo da requisição `/fraud-score`**
  * Validar JSON de entrada
  * Converter dados para vetor (14 dimensões)
  * Comparar com os vetores do histórico em memória (loop/foreach)
  * Calcular distância (ex: Euclidiana)
  * Selecionar os 5 vizinhos mais próximos (KNN)

* **Cálculo do resultado**
  * Calcular proporção de fraudes entre os 5 vizinhos
  * Retornar:
    * `approved`
    * `fraud_score`

* **Arquitetura final**
  * Aplicação stateless
  * Sem dependência de banco externo
  * Sempre compara novas transações com o dataset carregado na inicialização

---
## 🚀 Resumo da Implementação

### 🔢 Vetorização e Regras de Negócio `FraudScoreRequest.php`

* Payload transformado em vetor de **14 dimensões normalizadas ([0,1])**
* Tratamento de ausência de `last_transaction` com valor sentinela `-1`
* Proteção contra divisão por zero (`amountVsAvg`)
* Mapeamento de risco baseado em categoria (MCC)

---

### ⚡ Motor de Busca (ANN otimizado) `VectorSearch.php`

* Busca binária para encontrar região relevante do dataset (**O(log N)**)
* Janela deslizante para limitar cálculos
* Distância euclidiana aplicada apenas em subconjunto relevante
* Latência reduzida de ~340ms (windowRadius 100000) → **~5ms (windowRadius 500) por request**

---

### 🔥 Otimizações de Performance

* Loop unrolling na distância euclidiana (menos overhead de CPU - cálculo da distância dos 14 indices do vetor)
* Remoção de `usort()` no hot path
* Controle manual dos k-vizinhos mais próximos (Max-Heap manual)
* Redução de chamadas e operações desnecessárias

---

### 🧠 Uso eficiente de memória (OpenSwoole) `server.php`

* Dataset carregado antes do `server->start()`
* Aproveitamento de **Copy-on-Write**
* Workers compartilham memória sem duplicação
* Redução significativa de consumo de RAM

---

### 🐳 Arquitetura - docker e nginx

* Load balancer com **round-robin simples** via NGINX
* 2 instâncias da API com OpenSwoole
* Porta pública: **9999**
* Rede Docker `bridge`

---

### ⚙️ Limites de Recursos

* Total: **1 CPU / 350MB RAM**
* APIs: ~0.45 CPU / ~150MB cada
* Load balancer leve (~0.1 CPU / ~40MB)

---

### 🧩 Compatibilidade

* Imagens compatíveis com `linux/amd64`
* Funciona em ambientes ARM (Mac M1/M2/M3) via build adequado

---

## 🏁 Resultado

* Alta taxa de requisições por segundo
* Baixa latência
* Uso eficiente de CPU e memória
* 100% aderente às regras da competição

---

## API com PHP 8.5 + OpenSwoole

### Subir o projeto

```bash
docker compose up --build
```

### Health check

```bash
curl http://localhost:9999/ready
```
