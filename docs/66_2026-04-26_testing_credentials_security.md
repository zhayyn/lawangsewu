# Security: Test Credentials Management

## Overview
Test database credentials are properly managed to prevent exposure in version control.

## Files
- `.env.testing` - Test environment variables (committed)
- `.env.testing.local` - Local overrides (gitignored)
- `phpunit.xml.dist` - Test configuration template (committed)
- `phpunit.xml` - Local test configuration (gitignored)

## How It Works

### Local Development
1. Copy `phpunit.xml.dist` to `phpunit.xml`
2. Update credentials in `phpunit.xml` as needed
3. `phpunit.xml` is gitignored and won't be committed

Alternatively, use `.env.testing` to provide credentials:
```bash
# .env.testing (committed)
DB_PASSWORD=semakinhebat@26
SIPP_DB_PASSWORD=R4h4514@
```

### GitHub Actions / CI/CD
1. Use `phpunit.xml.dist` as template
2. Set repository secrets for sensitive values
3. Inject during test run:

```yaml
# .github/workflows/test.yml
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_PASSWORD: ${{ secrets.TESTING_DB_PASSWORD }}
          MYSQL_ROOT_PASSWORD: ${{ secrets.TESTING_ROOT_PASSWORD }}
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    steps:
      - uses: actions/checkout@v3
      - uses: php-actions/setup-php@v1
      - run: composer install
      - run: npm ci && npm run build
      - run: cp phpunit.xml.dist phpunit.xml
      - run: php artisan test
        env:
          DB_PASSWORD: ${{ secrets.TESTING_DB_PASSWORD }}
          SIPP_DB_PASSWORD: ${{ secrets.TESTING_DB_SIPP_PASSWORD }}
```

## Credentials Location
Actual test credentials are stored in:
- `.env.testing` (for local development, MAKE SURE IT'S SAFE)
- Repository Secrets (for GitHub Actions)
- Environment variables (for Docker/Container runs)

## Best Practices
1. ✅ Never commit `phpunit.xml` with credentials
2. ✅ Use `.env.testing` for local development
3. ✅ Use CI/CD secrets for automated testing
4. ✅ Rotate credentials periodically
5. ✅ Don't share `.env.testing` externally
6. ✅ Document credentials location in team wiki

## Troubleshooting

### Tests failing with "Access denied for user"
Check that credentials in `.env.testing` match your local MySQL:
```bash
# Run these commands in terminal to verify
mysql -u dbprakom -p -e "SELECT USER();"
```

### CI/CD failing with credential errors
1. Verify secrets are set in GitHub Settings > Secrets
2. Check secret names match environment variable names
3. Ensure workflow YAML uses correct secret syntax: `${{ secrets.NAME }}`

## Related Files
- `.gitignore` - Excludes test config files
- `.env.testing` - Test environment configuration
- `phpunit.xml.dist` - Distribution test template
