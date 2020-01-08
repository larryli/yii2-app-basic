# Yii2 Auto DevOps

Please visit [https://larryli.github.io/yii2-auto-devops (Chinese)](https://larryli.github.io/yii2-auto-devops) get more information.

## CI / CD Variables

Key | Value
--- | ---
`ADDITIONAL_HOSTS` | custom domains, comma-separated, usually with **Scope**
`<env>_ADDITIONAL_HOSTS` | custom domains for env, such as `PRODUCTION_ADDITIONAL_HOSTS`
`CRON_CMD` | default `/app/yii hello`
`CORN_SCHEDULE` | default `*/1 * * * *`
`DB_INITIALIZE` | default `/app/wait-for -- /app/yii migrate/up --interactive=0`
`DB_MIGRATE` | default `/app/yii migrate/up --interactive=0`
`INCREMENTAL_ROLLOUT_MODE` | default `timed` when **Deployment strategy** of **CI / CD Settings** is "*Continuous deployment to production using timed incremental rollout*", otherwise default `manual` when **Deployment strategy** is "*Automatic deployment to staging, manual deployment to production*", set `timed` enable timed rollout for production, set `manual` enable manual rollout for production  
`K8S_SECRET_ADMIN_EMAIL` | see app env `ADMIN_EMAIL`
`K8S_SECRET_COOKIE_VALIDATION_KEY` | see app env `COOKIE_VALIDATION_KEY`, suggest with **Masked**
`K8S_SECRET_DEBUG_IP` | see app env `DEBUG_IP`
`K8S_SECRET_ENABLE_SCHEMA_CACHE` | see app env `ENABLE_SCHEMA_CACHE`
`K8S_SECRET_QUEUE_CHANNEL` | see app env `QUEUE_CHANNEL`
`K8S_SECRET_REDIS_DB` | see app env `REDIS_DB`
`K8S_SECRET_SCHEMA_CACHE` | see app env `SCHEMA_CACHE`
`K8S_SECRET_SCHEMA_CACHE_DURATION` | see app env `SCHEMA_CACHE_DURATION`
`K8S_SECRET_SENDER_EMAIL` | see app env `SENDER_EMAIL`
`K8S_SECRET_SENDER_NAME` | see app env `SENDER_NAME`
`K8S_SECRET_YII_DEBUG` | see app env `YII_DEBUG`
`K8S_SECRET_YII_ENV` | see app env `YII_ENV`
`MYSQL_DB` | default set `$CI_ENVIRONMENT_SLUG` such as `staging` or `production`
`MYSQL_ENABLED` | default `true` means install app with mysql service, set `false` to use external mysql service without automatic installed mysql service
`MYSQL_HOST` | host of automatic installed mysql service when `MYSQL_ENABLED` = `true`, must be set when `MYSQL_ENABLED` = `false`
`MYSQL_PASSWORD` | default `testing-password`, suggest with **Masked**
`MYSQL_USER` | default `user`
`MYSQL_VERSION` | default `5.7.28`
`QUEUE_CMD` | default `/app/yii queue/listen --verbose`
`REDIS_ENABLED` | default `true` means install app with redis service, set `false` to use external redis service without automatic installed redis service
`REDIS_HOST` | host of automatic installed redis service when `REDIS_ENABLED` = `true`, must be set when `REDIS_ENABLED` = `false`
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

## App Env

`ADMIN_EMAIL` | default config `admin@example.com` if not set
`COOKIE_VALIDATION_KEY` | must be set a string
`DEBUG_IP` | default config `127.0.0.1` if not set, set `"*"` enable debug panel for everyone when `YII_DEBUG` = `true` and `YII_ENV` = `dev`
`ENABLE_SCHEMA_CACHE` | default config `false` if not set, set `ture` to enable schema cache
`MYSQL_DB` | must be set
`MYSQL_HOST` | default config `localhost` if not set
`MYSQL_PASSWORD` | default config empty if not set
`MYSQL_USER` | default config `root` if not set
`NCHAN_HOST` | default config `localhost` if not set 
`QUEUE_CHANNEL` | default config `queue` if not set
`REDIS_DB` | default config `0` if not set
`REDIS_HOST` | default config `localhost` if not set 
`REDIS_PASSWORD` | default config empty if not set
`SCHEMA_CACHE` | default config `cache` if not set
`SCHEMA_CACHE_DURATION` | default config `60` if not set
`SENDER_EMAIL` | default config `noreply@example.com"` if not set
`SENDER_NAME` | default config `Example.com mailer` if not set
`YII_DEBUG` | default define `false` if not set
`YII_ENV` | default define `prod` if not set
