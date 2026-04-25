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
  * Carregar os vetores em um array global
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

## API com PHP 8.5 + OpenSwoole

### Subir o projeto

```bash
docker compose up --build
```

### Health check

```bash
curl http://localhost:9999/ready
```
