# Deploy Automatizado

Este projeto configura o **build e deploy automático**

- **GitHub Actions** para build, push e deploy.  
- **Docker Hub** como registro de imagens.  
- **VPS** para execução do container via SSH.

---
# Ambientes Existentes
http://31.97.17.80:8081/  **HML**
http://31.97.17.80:8082/  **imperio**
http://31.97.17.80:8083/  **lojas7**

## Fluxo implementado

### Configurações
- Os Arquivos .env que passam os segredos para o container, são configurados da seguinte forma:
  - Cada cliente ou branch, tem sua pasta no vps que sobem os continers, exemplo:
    - lojas7: srv/erp/lojas
    - imperio: srv/erp/imperio
  - Dentro dessas pastas existe um arquivo .env que passa os segredos ao Docker.
    
### Build da Imagem

- Ao fazer **push na branch** `main`, o GitHub Actions:
  - Lê o arquivo `VERSION` com duas linhas:  
    - **1ª linha** → ambiente (ex.: `hml`, `prd`).  
    - **2ª linha** → versão (ex.: `v1.0.0`).
  - Gera a tag de imagem:
    ```
    aidabusiness/aida-repo:<ENV>-<VER>
    ```

    **Exemplo:**  
    `aidabusiness/aida-repo:TESTE-V0.0.1`

  - Executa `docker build` e `docker push` para o **Docker Hub**.

---

### Deploy automático

- Após o build, o workflow:
  - Conecta na **VPS** via SSH.
  - Garante que a pasta de aplicação `/srv/erp/*branch*` existe.
  - Autentica no **Docker Hub**.
  - Executa:
    - `docker pull` para pegar a nova imagem.
    - `docker stop` e `docker rm` para parar e remover o container anterior.
    - `docker run` para subir o novo container, usando o arquivo `.env` local.

- O comando utilizado é:

```bash
docker run -d \
  --name aida-app-hml \
  --env-file ./.env \
  -p 80X:80 \
  aidabusiness/aida-repo:TESTE-V0.0.1
