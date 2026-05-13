#!/usr/bin/env sh
set -eu

REPO="${1:-}"
if [ -z "$REPO" ]; then
  echo "Usage: $0 <owner/repo>"
  echo "Example: $0 techieshukla/radius-kisaanu"
  exit 1
fi

gh label create "type:bug" --repo "$REPO" --color "d73a4a" --description "Defects, regressions, runtime failures" --force
gh label create "type:feature" --repo "$REPO" --color "0e8a16" --description "New functionality" --force
gh label create "type:docs" --repo "$REPO" --color "1d76db" --description "Docs/readme/deploy notes updates" --force
gh label create "type:ops" --repo "$REPO" --color "5319e7" --description "Deployment/infra/monitoring/firewall" --force

gh label create "area:portal" --repo "$REPO" --color "fbca04" --description "Captive portal UI/backend" --force
gh label create "area:radius" --repo "$REPO" --color "b60205" --description "FreeRADIUS/auth/accounting logic" --force
gh label create "area:mysql" --repo "$REPO" --color "006b75" --description "Schema/data/migrations" --force
gh label create "area:daloradius" --repo "$REPO" --color "c2e0c6" --description "daloRADIUS panel/config/runtime" --force
gh label create "area:omada" --repo "$REPO" --color "0052cc" --description "Omada controller/AP integration" --force

gh label create "prio:P0" --repo "$REPO" --color "b60205" --description "Critical outage or production blocker" --force
gh label create "prio:P1" --repo "$REPO" --color "d93f0b" --description "High priority" --force
gh label create "prio:P2" --repo "$REPO" --color "fbca04" --description "Medium priority" --force
gh label create "prio:P3" --repo "$REPO" --color "0e8a16" --description "Low priority or backlog" --force

gh label create "status:blocked" --repo "$REPO" --color "000000" --description "External dependency blocker" --force
gh label create "status:ready" --repo "$REPO" --color "0e8a16" --description "Ready for implementation" --force
gh label create "status:needs-info" --repo "$REPO" --color "ededed" --description "Missing info from reporter" --force

echo "Labels synced for $REPO"
