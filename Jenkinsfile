pipeline {
 agent any
 stages {
        stage("Build") {
            steps {
                sh 'composer install'
                sh '''
                    sed -i '/^ARG single_binary_location_url/s/^/#/g' ./docker/clickhouse/Dockerfile
                    sed -i '/#ARG single_binary_location_url.*amd64/s/^#//g' ./docker/clickhouse/Dockerfile
                    sed -i '/image: yandex.clickhouse-server/s/^/#/g' docker-compose.yml
                '''
                sh 'cp .env.example .env'
                sh './vendor/bin/sail up -d'
                sh 'sleep 30'
                sh './vendor/bin/sail artisan migrate:fresh --seed'
                sh 'npm install'
            }
        }
        stage("Unit test") {
            steps {
                sh './vendor/bin/sail test --log-junit ./storage/logs/report.xml'
            }
        }
    }
    post {
        always {
                sh './vendor/bin/sail down'
                junit 'storage/logs/*.xml'
        }
    }
}