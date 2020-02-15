# 使用 GitLab Auto DevOps 在 Kubernetes 上部署 Yii2 应用

首先，感谢 [Guillaume Simon](https://gitlab.com/ipernet) 的 [Deploying Symfony test applications on Kubernetes with GitLab Auto DevOps, k3s and Let's Encrypt - A 30m guide](https://m42.sh/) 一文给予我最大的帮助，使得本文能够完成。

其次，这里只涉及到**部署**。也就是只包含下列内容：

- 自动构建 ([Auto Build](https://docs.gitlab.com/ee/topics/autodevops/#auto-build))
- 自动审查应用 ([Auto Review Apps](https://docs.gitlab.com/ee/topics/autodevops/#auto-review-apps))
- 自动部署 ([Auto Deploy](https://docs.gitlab.com/ee/topics/autodevops/#auto-deploy))
- 自动监控 ([Auto Monitoring](https://docs.gitlab.com/ee/topics/autodevops/#auto-monitoring))

在未来可能会支持：

- 自动测试 ([Auto Test](https://docs.gitlab.com/ee/topics/autodevops/#auto-test))
- 自动代码质量检测 ([Auto Code Quality](https://docs.gitlab.com/ee/topics/autodevops/#auto-code-quality-starter))

也希望有人在这方面为此项目[创建拉取请求](https://github.com/larryli/yii2-auto-devops/compare)。

最后，相信你看得懂上面的文字说明，并为此而来。我们继续~

## 在 Ubuntu 上部署 GitLab + MicroK8s

部署要求：

- GitLab 12.6+
- Kubernetes 1.12+

如果没有现存的部署环境，那么需要：

- 一台至少 8GB 内存、2 核 CPU、100G 硬盘的 vps、实体主机或本地 VirtualBox 虚拟机
- 可选的自有域名与 SSL 证书

两台 4GB 内存的实体主机也是可行，但不建议在同一宿主机配置两台虚拟机（除非宿主机 CPU 核心足够多）。

拥有公网 IP 且 80 与 443 端口可用，可以直接使用 [le-http](https://letsencrypt.org/zh-cn/docs/challenge-types/#http-01-%E9%AA%8C%E8%AF%81%E6%96%B9%E5%BC%8F) 自动配置 SSL。并且不需要配置自有域名，采用 [nip.io](https://nip.io) 的 IP 域名即可。如果仅在内网使用，要使用 https 就需要一个或两个二级域名以及其对应的 SSL 证书。仅使用一个域名时，容器镜像库将使用指定端口与同一域名下的 GitLab 区分。

本示例默认将使用 VirtualBox 虚拟机和仅 http 无 SSL 的 nip.io 域名部署。

### scoop 安装软件包

建议使用 [scoop](https://scoop.sh) 安装 `docker`、`git`、`helm`、`kubectl`、`virtualbox-np`/`virtualbox52-np`（建议 5.2）软件包。

以下脚本示例均使用 Git Bash 作为 Shell。

其中需要 `scoop install helm@2.16.1` 然后 `scoop hold helm` 或者在升级 3.0 版本后使用 `scoop reset helm@2.16.1`。

另外，默认的 helm 安装没有将 tiller 加入到 `~/scoop/shims` 目录中，需要：

```bash
cp ~/scoop/shims/helm.exe ~/scoop/shims/tiller.exe
cp ~/scoop/shims/helm.ps1 ~/scoop/shims/tiller.ps1
cp ~/scoop/shims/helm.shim ~/scoop/shims/tiller.shim
sed -i 's/helm.exe/tiller.exe/g' ~/scoop/shims/tiller.ps1
sed -i 's/helm.exe/tiller.exe/g' ~/scoop/shims/tiller.shim
```

为了方便执行命令，可以设置 helm 与 kubectl 的 Bash 下 Tab 键自动完成：

```bash
mkdir -p ~/.helm && helm completion bash > ~/.helm/completion.bash.inc
mkdir -p ~/.kube && kubectl completion bash > ~/.kube/completion.bash.inc
echo "source '$HOME/.helm/completion.bash.inc'" >> ~/.profile
echo "source '$HOME/.kube/completion.bash.inc'" >> ~/.profile
```

### Docker & docker-machine

另外，虚拟机使用的 Host-Only 网卡默认为 docker-machine 使用的 `192.168.99.0/24` 网段。如果不清楚如何配置，请使用 `docker-machine create` 创建默认的 docker 虚拟机即可。后续有些操作也会用到本地 docker 加速（解决）服务器 CI 构建缓慢（失败）的问题，详细内容请参阅本文末尾的 FAQ 内容。

具体的 docker-machine 创建脚本如下：

```bash
export CI_REGISTRY=registry.192-168-99-8.nip.io
export REGISTRY_MIRROR=https://dockerhub.azk8s.cn
docker-machine create default --engine-registry-mirror=$REGISTRY_MIRROR --engine-insecure-registry=http://$CI_REGISTRY
```

其中 `CI_REGISTRY` 仅在无 SSL 证书情况下指定，`REGISTRY_MIRROR` 为镜像配置，无网络因素时也可以不使用。

如果创建时自动下载 boot2docker.iso 文件缓慢或失败，请使用其他工具或方法下载对应文件到 `~/.docker/machine/cache/` 目录。也希望有组织可以提供 boot2docker.iso 镜像源，并[提交工单](https://github.com/larryli/yii2-auto-devops/issues/new)以修改此处内容。

### VirtualBox 安装 Ubuntu 18.04

虚拟机使用 Ubuntu 18.04 作为基础系统，请从[上交大镜像](https://mirrors.sjtug.sjtu.edu.cn/ubuntu-cd/18.04.3/ubuntu-18.04.3-live-server-amd64.iso)下载安装镜像。

创建虚拟机类型为 `Linux`，版本为 `Ubuntu (64bit)`，内存 `8192 MB`，硬盘 `100.00 GB`。创建完成后先不用启动，设置系统处理器的数量为 `2`；设置网络网卡 2 `启用网络连接`，连接方式为`仅主机 (Host-Only) 网络`，界面名称选择与 docker-machine 创建的 default 虚拟机相同的 Adapter。
（具体查看 default 虚拟机配置）

启动虚拟机，选择启动盘为已下载的 ubuntu-18.04.3-live-server-amd64.iso 文件。

不建议使用中文作为系统语言，默认 `English`。网络配置会显示两块网卡，默认都会 DHCP 配置；其中 `10.0.2.15/24` 是作为外网 NAT 访问用，不需要修改；另一块网卡则是内网使用，不建议使用 DHCP 需要修改为静态 IP，单机部署更需要设置两个静态 IP，当前先只配置一个 IP，选择该网卡 `Edit IPv4`，**IPv4 Method** 选择 `Manual`，**Subnet** 为 `192.168.99.0/24`，**Address** 为 `192.168.99.8`，其他项目留空（不使用该网卡访问外网也就不需要设置网关与域名解析）。**Proxy** 默认为空。**Mirror** 建议修改为 `http://mirrors.aliyun.com/ubuntu`。硬盘分区使用默认的 `Use An Entire Disk`。创建用户并选择 `Install OpenSSH Server`，从 Github.com 拉取公钥。

注意，如果网速没问题，可以在 Snaps 安装页面直接选择安装 microk8s；否则先不选择，安装完系统再说。

系统安装成功重启后，可以选择 ssh 登录系统。虚拟机也可以使用无界面启动节省宿主机资源。

然后，修改虚拟机 IP：

```bash
sudo nano /etc/netplan/50-cloud-init.yaml
```

在：

```yaml
            - 192.168.99.8/24
```

之后增加一行：

```yaml
            - 192.168.99.9/24
```

执行：

```bash
sudo netplan apply
```

如果采用双机部署，请分别安装系统，无需配置多个 IP。

### 安装配置 MicroK8s

没有在系统安装时选择 microk8s 需要先手工安装：

```bash
sudo snap install microk8s --classic
```

其实系统自动安装 microk8s 的进程也是在重启后自动在后台运行安装。前台安装的方便之处在于发现下载速度过慢，可以 Ctrl+C 中止再重试，有一定几率尝试到 1+ Mbs 的下载速度。

相当多的文档会提到安装旧版本的 microk8s，因为新版不再内置 docker 而是使用 containerd。经过试用之后，感觉最新版本要比指定版本的旧版方便许多。当然除了自带的 microk8s.ctr 还不支持 image tag 之外。

使用 `sudo microk8s.status` 可以查看运行状态。如有问题，使用 `sudo microk8s.inspect` 检查。

如果网络存在无法拉取 gcr.io 容器镜像库的情况，需要先调整 containerd：

```bash
sudo nano /var/snap/microk8s/current/args/containerd-template.toml
```

找到 `[plugins]` 下 `[plugins.cri]` 的 `sandbox_image`，修改为：

```ini
    sandbox_image = "gcr.azk8s.cn/google_containers/pause:3.1"
```

然后继续往下 `[plugins.cri.registry]` 下 `[plugins.cri.registry.mirrors]` 删除：

```ini
        [plugins.cri.registry.mirrors."docker.io"]
          endpoint = ["https://registry-1.docker.io"]
```

增加：

```ini
        [plugins.cri.registry.mirrors."docker.io"]
          endpoint = ["https://dockerhub.azk8s.cn"]
        [plugins.cri.registry.mirrors."quay.io"]
          endpoint = ["https://quay.azk8s.cn"]
        [plugins.cri.registry.mirrors."gcr.io"]
          endpoint = ["https://gcr.azk8s.cn/google_containers/"]
        [plugins.cri.registry.mirrors."k8s.gcr.io"]
          endpoint = ["https://gcr.azk8s.cn/google_containers/"]
        [plugins.cri.registry.mirrors."registry.192-168-99-8.nip.io"]
          endpoint = ["http://registry.192-168-99-8.nip.io"]
```

Ctrl+X 保存后退出。其中最后部分仅针对无 SSL 证书的情况。

继续修改 kube-apiserver：

```bash
sudo nano /var/snap/microk8s/current/args/kube-apiserver
```

增加：

```
--runtime-config=apps/v1beta1=true,apps/v1beta2=true,extensions/v1beta1/daemonsets=true,extensions/v1beta1/deployments=true,extensions/v1beta1/replicasets=true,extensions/v1beta1/networkpolicies=true,extensions/v1beta1/podsecuritypolicies=true
--allow-privileged=true
```

支持 chart 脚本与 dind 提权。

重启 microk8s：

```bash
sudo microk8s.stop && sudo microk8s.start
```

无误后，开启 dns 与 storage 组件：

```bash
sudo microk8s.enable dns storage
```

其中 dns 用于内部寻址，storage 用于持久化存储。

使用 `sudo microk8s.kubectl -n kube-system get pods` 查看组件部署状态。

使用 `sudo microk8s.config` 查看 kubectl 配置，复制到本地 `~/.kube/config`。需要替换默认的 `10.0.2.15` IP 换成 `192.168.99.9`。

在宿主机本地测试 `kubectl -n kube-system get pods` 命令。

### 安装配置 GitLab CE

增加软件源并指定域名安装：

```bash
export GITLAB_URL=http://gitlab.192-168-99-8.nip.io
# export GITLAB_MIRROR=https://packages.gitlab.com/gitlab
export GITLAB_MIRROR=https://mirrors.tuna.tsinghua.edu.cn
curl https://packages.gitlab.com/gpg.key 2> /dev/null | sudo apt-key add - &>/dev/null
echo "deb $GITLAB_MIRROR/gitlab-ce/ubuntu/ bionic main" | sudo tee /etc/apt/sources.list.d/gitlab-ce.list
sudo apt-get update
sudo EXTERNAL_URL=$GITLAB_URL apt-get install gitlab-ce
```

参见 [https://about.gitlab.com/install/#ubuntu?version=ce](https://about.gitlab.com/install/#ubuntu?version=ce) 与 [https://mirrors.tuna.tsinghua.edu.cn/help/gitlab-ce/](https://mirrors.tuna.tsinghua.edu.cn/help/gitlab-ce/) 说明。

修改 gitlab 配置：

```bash
sudo nano /etc/gitlab/gitlab.rb
```

找到：

```ruby
# registry_external_url 'https://registry.example.com'
```

修改为：

```ruby
registry_external_url 'http://registry.192-168-99-8.nip.io'
```

如果是单机部署，需要配置 nginx IP；找到：

```ruby
# nginx['listen_addresses'] = ['*', '[::]']
```

修改为：

```ruby
nginx['listen_addresses'] = ['192.168.99.8']
```

另外在：

```ruby
# registry_nginx['listen_port'] = 5050
```

后面增加一行：

```ruby
registry_nginx['listen_addresses'] = ['192.168.99.8']
```

生效 gitlab 配置：

```bash
sudo gitlab-ctl reconfigure
```

可以使用 `sudo gitlab-ctl diff-config` 查看配置修改项。

默认情况下，使用 `EXTERNAL_URL` 为 https 时，GitLab Omnibus 可以使用 [Let’s Encrypt](https://docs.gitlab.com/omnibus/settings/ssl.html#lets-encrypt-integration) 自动配置 SSL 证书。但这需要外网 IP 且 `80` 与 `443` 端口可用（不能修改为其他端口）。

对于内网和虚拟机环境，采用自有域名和手工申请的 SSL 证书可以减少无 https 需要 insecure-registry 的额外设置。

安装完成 gitlab 后，需要先解压复制到 `/etc/gitlab/ssl` 目录：

```bash
sudo mkdir -p /etc/gitlab/ssl
sudo chmod 700 /etc/gitlab/ssl
sudo cp gitlab.example.com.key gitlab.example.com.crt /etc/gitlab/ssl/
sudo cp registry.example.com.key registry.example.com.crt /etc/gitlab/ssl/
```

参见 [https://docs.gitlab.com/omnibus/settings/nginx.html#manually-configuring-https]https://docs.gitlab.com/omnibus/settings/nginx.html#manually-configuring-https)

如果 registry 与 gitlab 使用同一域名则需要，修改：

```ruby
registry_external_url 'https://gitlab.example.com:4567'
registry_nginx['ssl_certificate'] = "/etc/gitlab/ssl/gitlab.example.com.crt"
registry_nginx['ssl_certificate_key'] = "/etc/gitlab/ssl/gitlab.example.com.key"
```

参见 [https://docs.gitlab.com/ee/administration/packages/container_registry.html#configure-container-registry-under-an-existing-gitlab-domain](https://docs.gitlab.com/ee/administration/packages/container_registry.html#configure-container-registry-under-an-existing-gitlab-domain)

### 在 GitLab 上配置 Kubernetes

访问 http://gitlab.192-168-99-8.nip.io 设置 root 密码后登录。

点击 `Configure GitLab` 左侧 `Settings` 下的 `Network`（`/admin/application_settings/network`）。展开（Expand）`Outbound requests`，在白名单（Whitelist）填入 `192.168.99.9`。

继续左侧 `Kubernetes` 中 `Add Kubernetes cluster` 标签栏 `Add existing cluster`。

首先填入 **Kubernetes cluster name** 为 `MicroK8s`，**API URL** 为 `https://192.168.99.9:16443`。

参考 [https://docs.gitlab.com/ee/user/project/clusters/add_remove_clusters.html#existing-gke-cluster](https://docs.gitlab.com/ee/user/project/clusters/add_remove_clusters.html#existing-gke-cluster)

先使用 `kubectl get secrets` 选取一个 `default-token-xxx`，然后执行：

```bash
export SECRET_NAME=default-token-xxx
kubectl get secret $SECRET_NAME -o jsonpath="{['data']['ca\.crt']}" | base64 --decode
```

复制输出的证书内容到 **CA Certificate**。

然后建立一个 `gitlab-admin-service-account.yaml` 文件，内容如下：

```yaml
apiVersion: v1
kind: ServiceAccount
metadata:
  name: gitlab-admin
  namespace: kube-system
---
apiVersion: rbac.authorization.k8s.io/v1beta1
kind: ClusterRoleBinding
metadata:
  name: gitlab-admin
roleRef:
  apiGroup: rbac.authorization.k8s.io
  kind: ClusterRole
  name: cluster-admin
subjects:
- kind: ServiceAccount
  name: gitlab-admin
  namespace: kube-system
```

然后执行：

```bash
kubectl apply -f gitlab-admin-service-account.yaml
```

成功后，使用：

```bash
kubectl -n kube-system describe secret $(kubectl -n kube-system get secret | grep gitlab-admin | awk '{print $1}')
```

获取 `Data` `token` 部分填入 **Service Token**。

添加成功后，先设置 **Base domain** 为 `192-168-99-9.nip.io`。

然后，依次安装 **Helm Tiller**、**Ingress**、**GitLab Runner**，可选安装 **Prometheus**，对于公网 IP 建议安装 **Cert-Manager**。

如果 `kubectl -n gitlab-managed-apps get pods` 发现有 `ImagePullBackOff` 使用 `kubectl -n gitlab-managed-apps describe pod ingress-nginx-ingress-default-backend-xxx-yyy` 查看。

可以在虚拟机上使用类似 `docker image tag` 的操作：

```bash
export GCR_MIRROR=gcr.azk8s.cn/google_containers
export IMAGE_NAME=defaultbackend-amd64
export IMAGE_TAG=1.5
sudo microk8s.ctr image pull $GCR_MIRROR/$IMAGE_NAME:$IMAGE_TAG
sudo microk8s.ctr image export temp.tar $GCR_MIRROR/$IMAGE_NAME:$IMAGE_TAG
sudo microk8s.ctr image import --base-name k8s.gcr.io/$IMAGE_NAME temp.tar
sudo rm temp.tar
```

安装完毕后执行：

```bash
kubectl -n gitlab-managed-apps get svc ingress-nginx-ingress-controller
```

可以看到 `EXTERNAL-IP` 为 `<pending>`。

参考 [https://stackoverflow.com/a/54168660](https://stackoverflow.com/a/54168660)

指定具体 IP：

```bash
export EXTERNAL_IP=192.168.99.9
kubectl -n gitlab-managed-apps patch svc ingress-nginx-ingress-controller -p "{\"spec\": {\"type\": \"LoadBalancer\", \"externalIPs\":[\"$EXTERNAL_IP\"]}}"
```

## 部署 Yii2 应用

### 从 GitHub 导入代码

使用 **Import Project** 选择 **Repo by URL** 填入 `https://github.com/larryli/yii2-auto-devops.git` 创建项目。

### 功能

#### 首页、登录、退出、联系我们与关于

参见 [yii2-app-basic](https://github.com/yiisoft/yii2-app-basic) 

#### Post 表 CURD

使用 [gii](https://github.com/yiisoft/yii2-gii/) 生成的标准 yii2 curd 功能。

#### 下载

使用[后台队列](https://github.com/yiisoft/yii2-queue)下载然后通过 [nchan](https://nchan.io) 通知前台页面完成。

#### 上传

使用 [yii\web\UploadedFile](https://www.yiiframework.com/doc/guide/2.0/en/input-file-upload) 上传图像文件并显示。

### 组件

#### 数据库

采用 MySQL 数据库。

#### 缓存

采用 Redis 缓存，并为设置 `replicas` 去使用主从集群。

可选使用文件缓存，也就是缓存只存在与 pod 内部。在 scale 后无法在多个 pod 之间共享缓存，也就是无法利用缓存在不同请求之间交换数据。

注意：仅仅作为临时缓存（如页面缓存），除了性能问题是不存在其他问题的。

也可选使用数据库缓存，但需要注意**启用数据库结构缓存**与**创建缓存表**存在冲突。

#### 会话

采用 Redis 会话。

可选使用 PHP 系统会话，与文件缓存一样只支持单机。

也可选使用数据库会话。

#### 队列

采用 Redis 队列。

可选使用文件队列，与文件缓存一样只支持单机。

也可选使用数据库队列。

#### 前端资源

自动配置 `asset-bundles.php`。

#### Redis

配置 Redis 服务。

#### Nchan

使用 `yii\httpclient\Client` 调用 nginx nchan pub 接口。在 web 配置下因为处于同一 pod 下无需使用 `NCHAN_HOST`。

### 目录

#### runtime

应用运行时临时目录，其中 `runtime/logs/app.log` 为 Yii2 日志文件。在 pod 部署中挂载此目录为空目录，然后另外配置容器日志输出 `app.log` 内容。

#### vendor

此目录是 composer 自动下载的第三方包，会在构建阶段生成并包含在映像中。

#### web

前端内容。应用容器中 app（php-fpm）与 nginx 共享目录，仅在主 pod 中挂载此目录为空目录，并在 pod 生命周期开始时从 app 复制相关文件到 nginx。

#### web/assets

Yii2 前端资源目录。应用容器中 app（php-fpm）与 nginx 共享目录，仅在主 pod 中挂载此目录为空目录。

注意：在构建中使用 `BUILD_ASSET` = `true` 打包后，此目录为空，可选配置。当 `BUILD_ASSET` = `false` 时，此目录必须配置。

#### web/uploads

上传文件目录。应用容器中 app（php-fpm）与 nginx 共享目录，仅在主 pod 中挂载此目录为持久化目录。

### CI / CD 设置

#### staging 与 production 部署方式

**Auto DevOps** 的 **Deployment strategy** 的三项设置：

- *Continuous deployment to production*
- *Continuous deployment to production using timed incremental rollout*
- *Automatic deployment to staging, manual deployment to production*

会与 **Variables** 中的：

- `INCREMENTAL_ROLLOUT_MODE`
- `STAGING_ENABLED`

相互作用。

首先，**Deployment strategy** 的选择会决定 `INCREMENTAL_ROLLOUT_MODE` 和 `STAGING_ENABLED` 默认值，如下表所示：

**Deployment strategy** | `INCREMENTAL_ROLLOUT_MODE` | `STAGING_ENABLED`
--- | --- | ---
*Continuous deployment to production* | - | `false`
*Continuous deployment to production using timed incremental rollout* | `timed` | `false`
*Automatic deployment to staging, manual deployment to production* | `manual` | `true`

参见 [https://docs.gitlab.com/ce/topics/autodevops/#deployment-strategy](https://docs.gitlab.com/ce/topics/autodevops/#deployment-strategy)

所以，部署方式会有下列可能：

**Deployment strategy** | `INCREMENTAL_ROLLOUT_MODE` | `STAGING_ENABLED` | staging | production 
--- | --- | --- | --- | ---
*Continuous deployment to production* | - | - | 无 | 自动部署
*Continuous deployment to production* | - | `true` | 自动部署 | 手动部署
*Continuous deployment to production* | `manual` | - | 无 | 手动增量部署
*Continuous deployment to production* | `manual` | `true` | 自动部署 | 手动增量部署
*Continuous deployment to production using timed incremental rollout* | - | - | 无 | 延后 5 分钟自动增量部署
*Continuous deployment to production using timed incremental rollout* | - | `true` | 自动部署 | 延后 5 分钟自动增量部署
*Automatic deployment to staging, manual deployment to production* | - | `false` | 无 | 手动增量部署
*Automatic deployment to staging, manual deployment to production* | - | - | 自动部署 | 手动增量部署

实际六种部署方式，请按照需要预先选择好部署方式。

参见 [https://docs.gitlab.com/ce/topics/autodevops/#incremental-rollout-to-production-premium](https://docs.gitlab.com/ce/topics/autodevops/#incremental-rollout-to-production-premium)

对于增量部署，可选的 `ROLLOUT_STATUS_DISABLED` = `true` 可以在部署日志中不显示状态信息。

#### review 部署

仅一个 `REVIEW_DISABLED` = `false` 关闭默认的自动在分支上审查应用功能。

#### 附加域名

使用单独指定 **Scope** 的 `ADDITIONAL_HOSTS` 或具体 `<env>_ADDITIONAL_HOSTS` 如 `PRODUCTION_ADDITIONAL_HOSTS` 设置部署应用的附加域名。

域名可以为多个，以英文逗号分隔。如：`domain.com, www.domain.com`。

`<env>_ADDITIONAL_HOSTS` 的优先级要比 `ADDITIONAL_HOSTS` 高。

#### https 访问 与 SSL 证书

当前默认启用了 TLS（即 https 访问）和启用 Cert-Manager 管理证书（自动从 Let's Encrypt 申请）。**禁用**了 `nginx.ingress.kubernetes.io/ssl-redirect`（即访问 http 自动跳转 https）。

可以使用 `TLS_SSL_REDIRECT` = `true` 开启 http 自动跳转 https。

使用 `TLS_ENABLED` = `false` 关闭 https。

使用 `TLS_ACME` = `false` 禁用 Cert-Manager 自动从 Let's Encrypt 申请证书。

使用单独指定 **Scope** 的 `TLS_SECRET_NAME` 或具体 `<env>_TLS_SECRET_NAME` 如 `PRODUCTION_TLS_SECRET_NAME` 设置部署应用的 SSL 证书 secret name。

`<env>_TLS_SECRET_NAME` 的优先级要比 `TLS_SECRET_NAME` 高。

可以使用下面的命令创建 secret tls 存放对应的证书与私钥：

```bash
export KUBE_NAMESPACE=yii2-auto-devops-1-production
export TLS_SECRET_NAME=production-tls
export CERT_FILE=$HOME/ssl-certs/example.com.crt
export KEY_FILE=$HOME/ssl-certs/example.com.key
kubectl -n $KUBE_NAMESPACE create secret tls $TLS_SECRET_NAME --cert=$CERT_FILE --key=$KEY_FILE
```

也可以指定 **Type** 为 *File* 的 `TLS_CERT_FILE`（`<env>_TLS_CERT_FILE`）与 `TLS_KEY_FILE`（`<env>_TLS_KEY_FILE`）分别存放证书与私钥。

注意：**Type** 为 *File* 不能勾选 **Masked**，也就是当使用 `CI_DEBUG_TRACE` = `true` 调试 CI 时会在日志中显示完整的证书与私钥内容。存在安全隐患，请一定要在调试完成后删除 CI 日志。

当指定 `TLS_SECRET_NAME` 时会强制 `TLS_ENABLED` = `true` 和 `TLS_ACME` = `false`。

#### MySQL 数据库

可以使用 `MYSQL_ENABLED` = `false` 关闭默认的自动部署 MySQL 服务，从而使用外部 MySQL（可以手工在同一 Kubernets 上安装，也可以使用现有服务）。

当 `MYSQL_ENABLED` = `false` 时，建议按实际情况完整指定 `MYSQL_HOST`、`MYSQL_DB`、`MYSQL_USER` 与 `MYSQL_PASSWORD`。

当 `MYSQL_ENABLED` = `true` 时，一定不要设置 `MYSQL_HOST`，否则无法连接自动安装的 MySQL。`MYSQL_DB`、`MYSQL_USER` 与 `MYSQL_PASSWORD` 可以按需指定。另外可以使用 `MYSQL_VERSION` 指定 MySQL 版本。

#### 数据库初始化与迁移

- `DB_INITIALIZE` = `/app/wait-for -t 999 -- echo Initialized.`
- `DB_MIGRATE` = `/app/yii migrate/up --interactive=0`

其中 `DB_INITIALIZE` 直接使用 `wait-for` 脚本等待 k8s 创建 MySQL 服务成功。其中 `HOST` 与 `PORT` 是在环境变量中定义；无法在 `DB_INITIALIZE` 中直接使用 `/app/wait-for $MYSQL_HOST:3306`，因为 k8s 自动创建的 `MYSQL_HOST` 是在 `auto-deploy` 中才定义的（`.gitlab-ci.yml` 中引用 `$MYSQL_HOST` 会为空值）。

使用默认值即可，一般无需修改。

#### 数据库结构缓存

使用 `K8S_SECRET_ENABLE_SCHEMA_CACHE` = `true` 开始缓存，可以配置缓存时间 `K8S_SECRET_SCHEMA_CACHE_DURATION`（单位：秒，默认 `60` 秒）和缓存实体 `K8S_SECRET_SCHEMA_CACHE`（默认 `cache`）。

当 Yii2 缓存也使用数据库缓存时，请不要一开始就开启此项。一定要部署成功后，后续部署时开启表结构缓存。否则会在建表前出现无法读到缓存（因为缓存表未建）的问题。

#### Redis

可以使用 `REDIS_ENABLED` = `false` 关闭默认的自动部署 Redis 单例服务，从而使用外部 Redis（可以手工在同一 Kubernets 上安装，也可以使用现有服务）。

当 `REDIS_ENABLED` = `false` 时，建议按实际情况完整指定 `REDIS_HOST`、`REDIS_DB` 与 `REDIS_PASSWORD`。

当 `REDIS_ENABLED` = `true` 时，一定不要设置 `REDIS_HOST`，否则无法连接自动安装的 Redis。`REDIS_DB` 与 `REDIS_PASSWORD` 可以按需指定。另外可以使用 `REDIS_VERSION` 指定 Redis 版本。

当前 Yii2 缓存使用 Redis，并未设置 `replicas`。对于外部 Redis 建议使用 sentinel 哨兵集群。

#### 后台队列任务

对于 Yii2 Queue 来说，默认为：

- `QUEUE_CMD` = `/app/yii queue/listen --verbose`

设置为空时不使用后台队列任务。

#### Nchan

因为后台队列任务与前台 Web 应用在不同 Pod 中执行，所以提供有一个自动指定的 `NCHAN_HOST` 供后台队列使用。对于前台 Web 应用来说可以直接使用 `localhost`。

#### 定时任务

样例脚本和时间配置为：

- `CRON_CMD` = `/app/yii hello`
- `CORN_SCHEDULE` = `*/1 * * * *`

任意一项为空即不使用定时任务。

#### Cookie 配置

`K8S_SECRET_COOKIE_VALIDATION_KEY` 是唯一一个必须配置的变量，对于 production 也建议使用 **Scope** 单独指定，并勾选 **Masked**。

#### SMTP 邮件

配置 `K8S_SECRET_SMTP_ENABLED` = `true` 启用 SMTP 邮件发送功能，并配置下列参数：

- `K8S_SECRET_SMTP_HOST` SMTP 主机
- `K8S_SECRET_SMTP_PASSWORD` SMTP 密码
- `K8S_SECRET_SMTP_PORT` SMTP 端口
- `K8S_SECRET_SMTP_TLS` 是否使用 tls

#### Yii2 环境与调试以及开发构建

默认 `YII_DEBUG` = `false` `YII_ENV` = `prod`。

可以使用 `K8S_SECRET_YII_DEBUG` 和 `K8S_SECRET_YII_ENV` 分别配置为 `true` 与 `dev` 开启调试。

并设置 `K8S_SECRET_DEBUG_IP` = `*` 允许所有 IP 可访问调试面板。

默认 `BUILD_DEV` = `false` 会在 Dockerfile 构建时 `composer` 使用 `--no-dev` 参数不在映像中包含调试（开发测试）功能。如果需要线上调试，请配置 `BUILD_DEV` = `true` 构建参数包含调试（开发测试）功能。否则会部署失败（无法加载 dev 相关包）。

注意：`.gitlab-ci.yml` 构建阶段（build stage）是没有定义环境，也就是无法配置范围（Scope）。只能配置所有环境（All environments）或者手工执行（建议）时指定 `BUILD_DEV` = `true`、`K8S_SECRET_YII_DEBUG` = `true`、`K8S_SECRET_YII_ENV` = `dev` 和 `K8S_SECRET_DEBUG_IP` = `*`（后三个支持环境范围配置）。

#### 构建前端 assets 资源

默认 `BUILD_ASSET` = `true`，会在 Dockerfile 构建时对应用使用使用到的前端资源直接打包成独立的 js/css 文件。

具体的资源使用定义在 `config/assets.php` 中。如果发现前端仍有载入前端 `web/assets` 目录下的临时文件，请将对应的 Asset 类加入 `config/assets.php` 的 `bundles` 数组中。如果第三方包使用 CDN 也可以对应配置外部资源。

使用 `BUILD_ASSET` = `false` 可以关闭资源打包。

注意：`.gitlab-ci.yml` 构建阶段（build stage）是没有定义环境，也就是无法配置范围（Scope）。只能配置所有环境（All environments）或者手工执行（建议）时指定 `BUILD_ASSET` = `false`。

#### 其他变量

`K8S_SECRET_ADMIN_EMAIL`、`K8S_SECRET_SENDER_EMAIL`、`K8S_SECRET_SENDER_NAME` 均为演示目的。

注意：未配置 `K8S_SECRET_SMTP_ENABLED` = `true` 和其他 SMTP 变量时，无法发送邮件。

### 构建与部署技术架构

整个构建与部署是在 GitLab Auto DevOps 基础上完全自定义。

请先仔细阅读 [https://docs.gitlab.com/ce/topics/autodevops/#customizing](https://docs.gitlab.com/ce/topics/autodevops/#customizing) 的相关说明。

#### 自定义 .gitlab-ci.yml

目前只使用 Build 和 Deploy 两个组件，所以只包含了 `Auto-DevOps.gitlab-ci.yml`、`Jobs/Build.gitlab-ci.yml` 与 `Jobs/Deploy.gitlab-ci.yml` 三块内容。

首先，`variables` 增加了三部分内容：下载镜像源、MySQL 与数据库命令。

其次，`stages` 中删除了 `test` 等阶段。

然后是 Build 部分，针对 `docker:stable-dind` 服务修改了 `entrypoint` 入口。在 Build 的 `variables` 定义了下载镜像源（无网络问题可以去掉）与内部镜像源（非 http 的 CI_REGISTRY 需要，使用 https 可以去掉），以及并发下载数。再指明构建脚本为本地 `./build.sh` 脚本。

最后的 Deploy 部分，除了 `stop_review` 外也一样将部署脚本切换为本地 `./auto-deploy` 脚本。再删除了 `canary` 相关内容（GitLab EE Premium 功能）。`stop_review` 因为设置了 `GIT_STRATEGY: none` 不会检出项目文件，所以必须使用原始部署脚本。

#### 自定义 Dockerfile

采用 Docker multi-stage 多阶段构建。避免在 app 映像中包含 composer 下载缓存与 php 开发包内容。

需要注意的是，composer 执行与 app 的 php 执行并不在同一个映像中。也就是 app 映像中并没有 composer 命令。

在 composer 构建阶段，使用了两个参数 `BUILD_DEV` 与 `BUILD_ASSET`。

`BUILD_DEV` 切换直接使用了 `--dev` 和 `--no-dev` 两个 `composer install` 参数，以方便在 `BUILD_DEV` = `true` 时包含 test 测试与 debug 调试。

`BUILD_ASSET` = `true` 处理前端资源打包。需要注意两点：

- bootstrap 除了 css/js 外还有字体文件，需要先复制到 `web` 前端目录中，再打包
- 打包之后清理了相关资源目录

另外提供的 `Dockerfile-nginx` 时单独构建 nginx + nchan。该映像并未配置 nginx 自动载入 nchan 扩展。

#### 自定义 build.sh

去掉了 buildpacks 相关的内容。并针对 multi-stage 构建在每一个构建阶段都 pull & push 阶段映像，以便构建缓存可以正常工作。还增加了构建参数。

另外也分段构建了 nginx + nchan。

#### 自定义 chart

在 `requirements.yaml` 中使用 mysql 替换掉了 postgresql。默认使用最新的 1.x 版本，如需要兼容旧版本 kubernetes 请修改为 0.x 版本。修改后需更新 `requirements.lock` 文件，详见文末的 FAQ。

然后再增加了 redis。

在 `values.yaml` 同样使用 mysql 替换掉了 postgresql（没有实现对应 managed 逻辑）。增加了 `nginx.ingress.kubernetes.io/ssl-redirect` 控制是否自动从 http 跳转到 https。

扩充了 `application` 内容，配置后台队列任务的并发值 `parallelismCount`。

增加了 `redis` 与 `persistence`。

删除了不需要的 `workers`。

注意：增加的 redis 关闭了主从集群，只使用了单主部署。

增加 Nginx 需要的 `templates/nginx-configMap.yaml` 配置文件。其中针对 nchan 配置了 redis 相关配置，这里的 redis database 是直接指定的 `0` 值。

增加上传文件需要的 `templates/pvc.yaml` 持久化存储配置文件。

修改 `templates/db-initialize-job.yaml`、`templates/db-migrate-hook.yaml` 和 `templates/deployment.yaml`，使用 `MYSQL_HOST` 等替换掉 `DATABASE_URL` 环境变量设置，增加 `REDIS_HOST` 等变量设置。还增加了 `runtime` 运行目录的配置。

增加定时任务 `templates/cronJob.yaml` 配置文件。

增加后台队列任务 `templates/queue-worker-job.yaml` 配置文件。默认并发作业配置为 `2`。 

在 `templates/deployment.yaml` 增加了五个 `volumes` 挂载入口，其中 `assets` 用于 nginx 与 php-fpm 共享 yii2 生成的前端资源文件（`BUILD_ASSET` = `true` 时可不需要此配置），`nginx-config-volume` 为 nginx 配置，`runtime` 为应用运行临时目录（应用日志 `logs/app.log` 在此目录下），`shared-files` 将 php 入口文件与 css、js 等资源文件复制到 nginx，`uploads` 挂载的是持久化存储用于存放应用上传文件。并在后面的三个容器中分别挂载。同时在 app 主容器最后增加 `postStart` 生命周期，执行日志初始化与前端复制操作（使用 `tar` 而不是 `cp` 是因为 `cp` 无法排除 `assets` 与 `uploads` 目录，而且映像中没有提供 `rsync`）。

在 `containers` 后增加了 log 与 nginx 容器。应用日志直接在 log 容器下输出。Pod 存活检查移到 nginx 容器下，另外增加了 nchan 配置的 `9090` 端口。

同样在 `service.yaml` 也增加了 nchan 配置的 `9090` 端口。

删除了 `worker-deployment.yaml` 文件。

#### 自定义 auto-devops

在开始部分，将 `DATABASE_URL` 逻辑修改为 `MYSQL_HOST`。并类似处理 `REDIS_HOST` 逻辑，增加了 `NCHAN_HOST` 处理逻辑。

小幅调整了 `download_chart`。

主要修改在 `deploy` 的 `helm upgrade --install`。`--force` 参数是为了解决后台队列任务更新部署时无法自动删除旧有任务的问题。

然后额外处理了 SSL 证书相关的逻辑。

## 优化指南

本项目的代码是让一个独立项目展示所有技术细节。对于实际的项目，相关优化必不可少；否则缓慢的重复构建，多个项目之间的重复代码也让维护头疼。

### Docker 预先构建

对于 `composer`、`php` 与 `nginx` 可以创建三个内部项目单独构建，应用项目直接使用即可。

其中 `composer` 的基础内容与项目内容要分拆，即只拆出 `COPY composer.* /app` 之前的内容。

### Auto DevOps

#### auto-build-image

建议按照[官方项目](https://gitlab.com/gitlab-org/cluster-integration/auto-build-image/)去掉 ruby（Dockerfile.erb）与 herokuish（BUILDPACK）相关支持重新构建。

另外，构建脚本可以增加一个可选的 `BUILD_TARGET` 阶段构建选项来确定是否缓存中间阶段映像。

#### auto-deploy-image

建议在[官方项目](https://gitlab.com/gitlab-org/cluster-integration/auto-deploy-image/)的基础上修改，覆盖掉部署脚本。

首先，可以去掉 `AUTO_DEVOPS_CHART` 下载逻辑，只支持项目本地 chart。

其中，不同项目的 chart 不同，会导致 `helm upgrade --install` 的参数变化。除了有效利用 `HELM_UPGRADE_EXTRA_ARGS` 来传参外，建议另外定义一个 `DEPLOY_SCRIPT` 脚本来处理相关服务的逻辑，替换掉默认的操作。

#### auto-deploy-chart

如果许多项目存在共同的部署模板，可以考虑修改。但 chart 不能使用 git 项目，需要另外部署服务。

#### Auto-DevOps.gitlab-ci.yaml

自定义 `auto-build-image` 与 `auto-deploy-image` 后，就可以修改[官方脚本](https://gitlab.com/gitlab-org/gitlab-foss/blob/master/lib/gitlab/ci/templates/Auto-DevOps.gitlab-ci.yml)使用 [`include file`](https://docs.gitlab.com/ee/ci/yaml/#includefile) 引用。

而 `Auto-DevOps.gitlab-ci.yaml` 具体内容可以直接：

```yaml
include:
  - template: Jobs/Build.gitlab-ci.yml
  - template: Jobs/Deploy.gitlab-ci.yml

# https://gitlab.com/gitlab-org/gitlab-foss/blob/master/lib/gitlab/ci/templates/Jobs/Build.gitlab-ci.yml
build:
  image: "registry.192-168-99-8.nip.io/devops/auto-build-image"
  variables:
    DOCKER_CONCURRENT: 6
    DOCKER_DAEMON_OPTIONS: "--registry-mirror=${REGISTRY_MIRROR} --insecure-registry=${CI_REGISTRY} --max-concurrent-downloads=${DOCKER_CONCURRENT}"
  services:
    # https://gitlab.com/gitlab-org/gitlab-runner/issues/3808#note_244570527
    - name: docker:stable-dind
      entrypoint: [ "sh", "-c", "dockerd-entrypoint.sh ${DOCKER_DAEMON_OPTIONS}" ]

# https://gitlab.com/gitlab-org/gitlab-foss/blob/master/lib/gitlab/ci/templates/Jobs/Deploy.gitlab-ci.yml
.auto-deploy:
  image: "registry.192-168-99-8.nip.io/devops/auto-deploy-image"
```

## FAQ

### 使用本地 Docker 缓存加速（解决） CI 构建缓慢（失败）

如果服务器 CI 构建出现问题，可以选择在本地使用 Docker 构建后推到容器镜像库。当然，不同分支切换时因为缓存不存在，也可以拉取其他分支的镜像缓存回来，通过 tag 改名再推到容器镜像库作为缓存加速服务器 CI 执行。

Tag 改名：

```bash
export CI_REGISTRY=registry.192-168-99-8.nip.io
export PROJECT=root/yii2-auto-devops
export SOURCE_BRANCH=master
export BRANCH=foobar
docker login $CI_REGISTRY
docker pull $CI_REGISTRY/$PROJECT/$SOURCE_BRANCH:composer
docker tag $CI_REGISTRY/$PROJECT/$SOURCE_BRANCH:composer $CI_REGISTRY/root/yii2-auto-devops/$BRANCH:composer
docker push $CI_REGISTRY/$PROJECT/$BRANCH:composer
docker pull $CI_REGISTRY/$PROJECT/$SOURCE_BRANCH:builder
docker tag $CI_REGISTRY/$PROJECT/$SOURCE_BRANCH:builder $CI_REGISTRY/root/yii2-auto-devops/$BRANCH:builder
docker push $CI_REGISTRY/$PROJECT/$BRANCH:builder
```

本地构建：

```bash
export CI_REGISTRY=registry.192-168-99-8.nip.io
export PROJECT=root/yii2-auto-devops
export BRANCH=foobar
docker login $CI_REGISTRY
docker build --target composer --tag $CI_REGISTRY/$PROJECT/$BRANCH:composer .
docker push $CI_REGISTRY/$PROJECT/$BRANCH:composer
docker build --target composer --tag $CI_REGISTRY/$PROJECT/$BRANCH:builder .
docker push $CI_REGISTRY/$PROJECT/$BRANCH:builder
docker build --tag $CI_REGISTRY/$PROJECT/$BRANCH:latest .
docker push $CI_REGISTRY/$PROJECT/$BRANCH:latest
```

注意：本地构建会因为 Windows 与 Linux 文件系统不同造成差异，从而使得缓存失效。

### 更新 chart requirements.lock

```bash
export CHART_MIRROR=https://mirror.azure.cn/kubernetes/charts/
helm init --client-only --stable-repo-url $CHART_MIRROR
helm dependency update chart/
```

### 测试 chart 模板

```bash
helm template chart/
```

其他 `--set` 参数可以参考 `auto-deploy` 脚本中对应的内容。

### 部署出错提示 Error: error installing: namespaces "staging" not found

不小心直接 `kubectl delete namespace` 会出现此问题。

请使用 GitLab 12.6.0 以上版本，在 **Kubernetes** 的 **Advanced settings** 中点击 **Clear cluster cache**。

参见 [https://docs.gitlab.com/ee/user/project/clusters/index.html#clearing-the-cluster-cache](https://docs.gitlab.com/ee/user/project/clusters/index.html#clearing-the-cluster-cache)

### 部署出错提示 Error: UPGRADE FAILED: "staging" has no deployed releases

一般会出现在上一次部署失败或被取消后。

在本地使用 helm 清除（请先配置 kubectl config）：

```bash
export CHART_MIRROR=https://mirror.azure.cn/kubernetes/charts/
export KUBE_NAMESPACE=yii2-auto-devops-1-staging
export CHART_NAME=staging
export TILLER_NAMESPACE=$KUBE_NAMESPACE
tiller -listen localhost:44134 &
# export TILLER_PID=
export HELM_HOST="localhost:44134"
helm init --client-only --stable-repo-url $CHART_MIRROR
helm ls
helm delete $CHART_NAME --purge --tiller-namespace $KUBE_NAMESPACE
# kill -9 $TILLER_PID
```

参见 [https://gitlab.com/gitlab-org/gitlab-foss/issues/54760](https://gitlab.com/gitlab-org/gitlab-foss/issues/54760)

## 感谢

对上文中提到的所有 mirrors 维护者表示衷心的感谢！
