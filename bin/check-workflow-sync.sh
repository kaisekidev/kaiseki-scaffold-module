#!/usr/bin/env bash
#
# Guards against drift between the caller workflows this scaffold emits into
# generated modules (templates/shared/.github/workflows/*.yml) and the canonical
# org starter workflows in kaisekidev/.github (workflow-templates/*.yml).
#
# The starters are the single source of truth for the org's caller workflows
# (see README "Org-wide baselines"). This repo keeps a synced copy purely so the
# scaffold works offline; this check fails loudly if that copy falls behind.
#
# Run locally with `composer check-workflows`; CI runs it on every PR/push.

set -euo pipefail

base="https://raw.githubusercontent.com/kaisekidev/.github/master/workflow-templates"
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
status=0

for name in checks update-changelog; do
    local_file="$root/templates/shared/.github/workflows/$name.yml"
    remote_file="$(mktemp)"

    if ! curl -fsSL "$base/$name.yml" -o "$remote_file"; then
        echo "ERROR: could not fetch canonical $base/$name.yml" >&2
        rm -f "$remote_file"
        exit 2
    fi

    if diff -u "$local_file" "$remote_file"; then
        echo "OK: $name.yml matches the canonical starter"
    else
        echo >&2
        echo "DRIFT: templates/shared/.github/workflows/$name.yml differs from" >&2
        echo "       $base/$name.yml — re-sync it from the canonical starter." >&2
        status=1
    fi

    rm -f "$remote_file"
done

exit "$status"
