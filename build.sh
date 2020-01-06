#!/bin/bash -e

# https://gitlab.com/gitlab-org/cluster-integration/auto-build-image/blob/master/src/build.sh
# https://pythonspeed.com/articles/faster-multi-stage-builds/
# build multi-stage script for Auto-DevOps

if ! docker info &>/dev/null; then
  if [ -z "$DOCKER_HOST" ] && [ "$KUBERNETES_PORT" ]; then
    export DOCKER_HOST='tcp://localhost:2375'
  fi
fi

if [[ -n "$CI_REGISTRY" && -n "$CI_REGISTRY_USER" ]]; then
  echo "Logging to GitLab Container Registry with CI credentials..."
  docker login -u "$CI_REGISTRY_USER" -p "$CI_REGISTRY_PASSWORD" "$CI_REGISTRY"
fi

echo "Building Dockerfile-based application..."

build_secret_args=''
if [[ -n "$AUTO_DEVOPS_BUILD_IMAGE_FORWARDED_CI_VARIABLES" ]]; then
  build_secret_file_path=/tmp/auto-devops-build-secrets
  "$(dirname "$0")"/export-build-secrets > "$build_secret_file_path"
  build_secret_args="--secret id=auto-devops-build-secrets,src=$build_secret_file_path"

  echo 'Activating Docker BuildKit to forward CI variables with --secret'
  export DOCKER_BUILDKIT=1
fi

docker image pull "$CI_APPLICATION_REPOSITORY:composer" || true

# Build the composer stage:
docker build \
  --target composer \
  --cache-from "$CI_APPLICATION_REPOSITORY:composer" \
  $build_secret_args \
  --build-arg HTTP_PROXY="$HTTP_PROXY" \
  --build-arg http_proxy="$http_proxy" \
  --build-arg HTTPS_PROXY="$HTTPS_PROXY" \
  --build-arg https_proxy="$https_proxy" \
  --build-arg FTP_PROXY="$FTP_PROXY" \
  --build-arg ftp_proxy="$ftp_proxy" \
  --build-arg NO_PROXY="$NO_PROXY" \
  --build-arg no_proxy="$no_proxy" \
  $AUTO_DEVOPS_BUILD_IMAGE_EXTRA_ARGS \
  --tag "$CI_APPLICATION_REPOSITORY:composer" .

docker push "$CI_APPLICATION_REPOSITORY:composer"

docker image pull "$CI_APPLICATION_REPOSITORY:builder" || true

# Build the builder stage:
docker build \
  --target builder \
  --cache-from "$CI_APPLICATION_REPOSITORY:composer" \
  --cache-from "$CI_APPLICATION_REPOSITORY:builder" \
  $build_secret_args \
  --build-arg HTTP_PROXY="$HTTP_PROXY" \
  --build-arg http_proxy="$http_proxy" \
  --build-arg HTTPS_PROXY="$HTTPS_PROXY" \
  --build-arg https_proxy="$https_proxy" \
  --build-arg FTP_PROXY="$FTP_PROXY" \
  --build-arg ftp_proxy="$ftp_proxy" \
  --build-arg NO_PROXY="$NO_PROXY" \
  --build-arg no_proxy="$no_proxy" \
  $AUTO_DEVOPS_BUILD_IMAGE_EXTRA_ARGS \
  --tag "$CI_APPLICATION_REPOSITORY:builder" .

docker push "$CI_APPLICATION_REPOSITORY:builder"

# pull images for cache - this is required, otherwise --cache-from will not work
docker image pull "$CI_APPLICATION_REPOSITORY:$CI_COMMIT_BEFORE_SHA" || \
docker image pull "$CI_APPLICATION_REPOSITORY:latest" || \
true

# Build the runtime stage, using cached compile stage:
# shellcheck disable=SC2154 # missing variable warning for the lowercase variables
docker build \
  --cache-from "$CI_APPLICATION_REPOSITORY:composer" \
  --cache-from "$CI_APPLICATION_REPOSITORY:builder" \
  --cache-from "$CI_APPLICATION_REPOSITORY:$CI_COMMIT_BEFORE_SHA" \
  --cache-from "$CI_APPLICATION_REPOSITORY:latest" \
  $build_secret_args \
  --build-arg HTTP_PROXY="$HTTP_PROXY" \
  --build-arg http_proxy="$http_proxy" \
  --build-arg HTTPS_PROXY="$HTTPS_PROXY" \
  --build-arg https_proxy="$https_proxy" \
  --build-arg FTP_PROXY="$FTP_PROXY" \
  --build-arg ftp_proxy="$ftp_proxy" \
  --build-arg NO_PROXY="$NO_PROXY" \
  --build-arg no_proxy="$no_proxy" \
  $AUTO_DEVOPS_BUILD_IMAGE_EXTRA_ARGS \
  --tag "$CI_APPLICATION_REPOSITORY:$CI_APPLICATION_TAG" \
  --tag "$CI_APPLICATION_REPOSITORY:latest" .

docker push "$CI_APPLICATION_REPOSITORY:$CI_APPLICATION_TAG"
docker push "$CI_APPLICATION_REPOSITORY:latest"

if [[ -f Dockerfile-nginx ]]; then
  echo "Building nginx..."

  docker image pull "$CI_APPLICATION_REPOSITORY:nginx-builder" || true

  # Build the nginx:
  docker build \
    -f Dockerfile-nginx \
    --target builder \
    --cache-from "$CI_APPLICATION_REPOSITORY:nginx-builder" \
    $build_secret_args \
    --build-arg HTTP_PROXY="$HTTP_PROXY" \
    --build-arg http_proxy="$http_proxy" \
    --build-arg HTTPS_PROXY="$HTTPS_PROXY" \
    --build-arg https_proxy="$https_proxy" \
    --build-arg FTP_PROXY="$FTP_PROXY" \
    --build-arg ftp_proxy="$ftp_proxy" \
    --build-arg NO_PROXY="$NO_PROXY" \
    --build-arg no_proxy="$no_proxy" \
    $AUTO_DEVOPS_BUILD_IMAGE_EXTRA_ARGS \
    --tag "$CI_APPLICATION_REPOSITORY:nginx-builder" .

  docker push "$CI_APPLICATION_REPOSITORY:nginx-builder"

  docker image pull "$CI_APPLICATION_REPOSITORY:nginx" || true

  # Build the nginx:
  docker build \
    -f Dockerfile-nginx \
    --cache-from "$CI_APPLICATION_REPOSITORY:nginx-builder" \
    --cache-from "$CI_APPLICATION_REPOSITORY:nginx" \
    $build_secret_args \
    --build-arg HTTP_PROXY="$HTTP_PROXY" \
    --build-arg http_proxy="$http_proxy" \
    --build-arg HTTPS_PROXY="$HTTPS_PROXY" \
    --build-arg https_proxy="$https_proxy" \
    --build-arg FTP_PROXY="$FTP_PROXY" \
    --build-arg ftp_proxy="$ftp_proxy" \
    --build-arg NO_PROXY="$NO_PROXY" \
    --build-arg no_proxy="$no_proxy" \
    $AUTO_DEVOPS_BUILD_IMAGE_EXTRA_ARGS \
    --tag "$CI_APPLICATION_REPOSITORY:nginx" .

  docker push "$CI_APPLICATION_REPOSITORY:nginx"
fi
