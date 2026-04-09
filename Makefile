.PHONY: setup db dev-api dev-triage dev-web test test-api test-triage test-web deploy-triage deploy-api build-web

setup: ## Install dependencies and prepare env files
	cp -n backend/.env.example backend/.env || true
	cp -n triage-service/.env.example triage-service/.env || true
	cp -n frontend/.env.example frontend/.env || true
	cd backend && composer install && php artisan key:generate
	cd triage-service && npm install
	cd frontend && npm install

db: ## Start MySQL and run migrations + seed data
	docker compose up -d mysql
	cd backend && php artisan migrate --seed

dev-api:
	cd backend && php artisan serve --port=8000

dev-triage:
	cd triage-service && set -a && . ./.env && set +a && npm run dev

dev-web:
	cd frontend && npm run dev

test: test-api test-triage test-web

test-api:
	cd backend && php artisan test

test-triage:
	cd triage-service && npm test

test-web:
	cd frontend && npm test

build-web:
	cd frontend && npm run build

deploy-triage:
	cd triage-service && npx serverless deploy --stage $${STAGE:-dev}

deploy-api:
	cd backend && composer install --no-dev --optimize-autoloader && php artisan config:clear && \
		npx serverless@3 deploy --stage $${STAGE:-dev} && \
		npx serverless@3 bref:cli --stage $${STAGE:-dev} --args="migrate --force"
