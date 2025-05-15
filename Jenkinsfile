pipeline {
    agent any

    stages {
        stage('Deploy') {
            steps {
                script {
                    if (env.BRANCH_NAME == 'main1') {
                        echo "🔧 Deployando homologação"
                        sh 'docker compose -p erp_hml --env-file .env.hml down || true'
                        sh 'docker compose -p erp_hml --env-file .env.hml up -d --build'
                        // 🔧 Corrige as permissões para evitar 403                        
                       // sh 'docker exec erp_hml_app_1 chown -R www-data:www-data /var/www/html || true'
                    } else if (env.BRANCH_NAME == 'cliente1') {
                        echo "🚀 Deployando produção"
                        sh 'docker compose -p erp_prd --env-file .env.prd down || true'
                        sh 'docker compose -p erp_prd --env-file .env.prd up -d --build'
                        // 🔧 Corrige as permissões para evitar 403
                        // sh 'docker exec erp_prd_app_1 chown -R www-data:www-data /var/www/html || true'
                    } else {
                        echo "📦 Branch ${env.BRANCH_NAME} não faz deploy"
                    }
                }
            }
        }
    }
}
