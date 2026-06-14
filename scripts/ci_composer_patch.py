#!/usr/bin/env python3
"""
CI-only patcher for composer.json: strip scripts that need .env/DB,
add 'extra.laravel.dont-discover: ["*"]' so package:discover succeeds
without needing a working Laravel environment (no .env, no DB).

Production composer.json is NOT modified. We cp back from .real after
composer install completes.
"""
import json
import sys

d = json.load(open('composer.json'))

# Strip ALL scripts (they call artisan which needs .env + DB)
d['scripts'] = {}

# Add dont-discover all to skip package:discover provider loading
d.setdefault('extra', {}).setdefault('laravel', {})['dont-discover'] = ['*']

json.dump(d, open('composer.json', 'w'), indent=4)
print("✓ composer.json patched for CI")
