# Yii2 Auto DevOps

Please visit [https://larryli.github.io/yii2-auto-devops](https://larryli.github.io/yii2-auto-devops) get more information.

## CI / CD Variables

Key | Value
--- | ---
`ADDITIONAL_HOSTS` | custom domains, comma-separated, usually with **Scope**
`<env>_ADDITIONAL_HOSTS` | custom domains for env, such as `PRODUCTION_ADDITIONAL_HOSTS`
`DB_INITIALIZE` | default `/app/wait-for -- /app/yii migrate/up --interactive=0`
`DB_MIGRATE` | default `/app/yii migrate/up --interactive=0`
`INCREMENTAL_ROLLOUT_MODE` | default `timed` when **Deployment strategy** of **CI / CD Settings** is "*Continuous deployment to production using timed incremental rollout*", otherwise default `manual` when **Deployment strategy** is "*Automatic deployment to staging, manual deployment to production*", set `timed` enable timed rollout for production, set `manual` enable manual rollout for production  
`K8S_SECRET_COOKIE_VALIDATION_KEY` | must be set a string, suggest with **Masked**
`K8S_SECRET_YII_DEBUG` | default define `false` if not set
`K8S_SECRET_YII_ENV` | default define `prod` if not set
`K8S_SECRET_DEBUG_IP` | default config `127.0.0.1` if not set, set `"*"` enable debug panel for everyone when `K8S_SECRET_YII_DEBUG` = `true` and `K8S_SECRET_YII_ENV` = `dev`
`K8S_SECRET_ADMIN_EMAIL` | default config `admin@example.com` if not set
`K8S_SECRET_QUEUE_CHANNEL` | default config `queue` if not set
`K8S_SECRET_REDIS_DB` | default config `0` if not set
`K8S_SECRET_SENDER_EMAIL` | default config `noreply@example.com"` if not set
`K8S_SECRET_SENDER_NAME` | default config `Example.com mailer` if not set
`K8S_SECRET_ENABLE_SCHEMA_CACHE` | default config `false` if not set, set `ture` to enable schema cache
`K8S_SECRET_SCHEMA_CACHE_DURATION` | default config `60` if not set
`K8S_SECRET_SCHEMA_CACHE` | default config `cache` if not set
`MYSQL_ENABLED` | default `true` means install app with mysql service, set `false` to use external mysql service without automatic installed mysql service
`MYSQL_HOST` | host of automatic installed mysql service when `MYSQL_ENABLED` = `true`, or default `localhost` if not set when `MYSQL_ENABLED` = `false`
`MYSQL_DB` | default set `$CI_ENVIRONMENT_SLUG` such as `staging` or `production`
`MYSQL_USER` | default `user`
`MYSQL_PASSWORD` | default `testing-password`, suggest with **Masked**
`MYSQL_VERSION` | default `5.7.14`
`REDIS_ENABLED` | default `true` means install app with redis service, set `false` to use external redis service without automatic installed redis service
`REDIS_HOST` | host of automatic installed redis service when `REDIS_ENABLED` = `true`, or default `localhost` if not set when `REDIS_ENABLED` = `false`
`REDIS_PASSWORD` | default `testing-password`, suggest with **Masked**
`REDIS_VERSION` | default `5.0.7`
`REVIEW_DISABLED` | default `false`, set `true` disable auto deploy review app on branch
`ROLLOUT_STATUS_DISABLED` | default `false`, set `true` do not show rollout status on deploy
`STAGING_ENABLED` | default `true` when **Deployment strategy** of **CI / CD Settings** is "*Automatic deployment to staging, manual deployment to production*", otherwise default `false`, set `true` enable staging
`TLS_ACME` | default `true`, set `false` to disable apply cert and key by acme
`TLS_ENABLE` | default `true`, set `false` to disable tls
`TLS_SECRET_NAME` | custom secret name of the tls cert and key
`<env>_TLS_SECRET_NAME` | custom secret name for env, such as `PRODUCTION_TLS_SECRET_NAME`
`TLS_SSL_REDIRECT` | default `false`, set `true` to enable redirect 443 from 80
