pipeline {
    agent any  // Usa qualquer agente disponível no Jenkins para rodar o pipeline

    stages {
        stage('Clonar código') {
            steps {
                // Faz o checkout do repositório Git com autenticação por Deploy Key SSH
                checkout([$class: 'GitSCM',
                    userRemoteConfigs: [[
                        url: 'git@github.com:Aida-Business-Intelligence/erp-backend.git',  // URL do repositório via SSH
                        credentialsId: 'deploy-key-erp'  // ID da credencial SSH cadastrada no Jenkins
                    ]],
                    branches: [[name: "${env.BRANCH_NAME}"]]  // Usa a branch atual detectada no Multibranch Pipeline
                ])
            }
        }

        stage('Deploy') {
            steps {
                script {
                    // Verifica qual branch está sendo executada para decidir o ambiente
                    if (env.BRANCH_NAME == 'main1') {
                        echo "🔧 Deployando homologação"

                        // Derruba os containers existentes do ambiente de homologação (se houver)
                        sh 'docker-compose --env-file .env.hml down || true'

                        // Sobe os containers usando as variáveis de ambiente do arquivo .env.hom
                        sh 'docker-compose --env-file .env.hnl up -d --build'

                    } else if (env.BRANCH_NAME == 'cliente1') {
                        echo "🚀 Deployando produção"

                        // Derruba os containers existentes do ambiente de produção
                        sh 'docker-compose --env-file .env.prd down || true'

                        // Sobe os containers usando as variáveis do arquivo .env.prod
                        sh 'docker-compose --env-file .env.prd up -d --build'

                    } else {
                        // Para outras branches, não faz deploy, apenas loga
                        echo "📦 Branch ${env.BRANCH_NAME} não faz deploy"
                    }
                }
            }
        }
    }
}

