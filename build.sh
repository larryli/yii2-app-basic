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

docker image pull "$CI_APPLICATION_REPOSITORY:composer" || true

# Build the composer stage:
docker build --target composer \
  --cache-from "$CI_APPLICATION_REPOSITORY:composer" \
  --tag "$CI_APPLICATION_REPOSITORY:composer" .

docker push "$CI_APPLICATION_REPOSITORY:composer"

docker image pull "$CI_APPLICATION_REPOSITORY:builder" || true

# Build the builder stage:
docker build --target builder \
  --cache-from "$CI_APPLICATION_REPOSITORY:composer" \
  --cache-from "$CI_APPLICATION_REPOSITORY:builder" \
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
  --tag "$CI_APPLICATION_REPOSITORY:$CI_APPLICATION_TAG" \
  --tag "$CI_APPLICATION_REPOSITORY:latest" .

docker push "$CI_APPLICATION_REPOSITORY:$CI_APPLICATION_TAG"
docker push "$CI_APPLICATION_REPOSITORY:latest"
