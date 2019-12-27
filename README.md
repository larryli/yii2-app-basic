# 使用 GitLab Auto DevOps 在 Kubernetes 上部署 Yii2 应用

首先，感谢 [Guillaume Simon](https://gitlab.com/ipernet) 的 [Deploying Symfony test applications on Kubernetes with GitLab Auto DevOps, k3s and Let's Encrypt - A 30m guide](https://m42.sh/) 一文给予我最大的帮助，使得本文能够完成。

其次，这里只涉及到**部署**。也就是只包含下列内容：

- 自动构建 ([Auto Build](https://docs.gitlab.com/ee/topics/autodevops/#auto-build))
- 自动审查应用 ([Auto Review Apps](https://docs.gitlab.com/ee/topics/autodevops/#auto-review-apps))
- 自动部署 ([Auto Deploy](https://docs.gitlab.com/ee/topics/autodevops/#auto-deploy))
- 自动监控 ([Auto Monitoring](https://docs.gitlab.com/ee/topics/autodevops/#auto-monitoring))

再未来可能会支持：

- 自动测试 ([Auto Test](https://docs.gitlab.com/ee/topics/autodevops/#auto-test))
- 自动代码质量检测 ([Auto Code Quality](https://docs.gitlab.com/ee/topics/autodevops/#auto-code-quality-starter))

也希望有人在这方面为此项目[创建拉取请求](https://github.com/larryli/yii2-auto-devops/compare)。

最后，相信你看得懂上面的文字说明，并为此而来。我们继续~
