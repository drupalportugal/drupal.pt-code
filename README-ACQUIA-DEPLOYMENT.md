All changes should be pushed against 8.x-dev in github
When a push is detected in github, acquia pipelines starts, executes composer update and pushes the result artifact to pipelines-build branch in acquia. The branch is deployed by default in the staging environment.

After everything is tested in staging environment a tag should be deployed in production coming from the pipelines-build branch.
