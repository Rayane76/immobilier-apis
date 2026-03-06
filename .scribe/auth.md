# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_SANCTUM_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Authenticate via Laravel Sanctum. Obtain a token from <code>POST /api/auth/login</code> and pass it as a Bearer token: <code>Authorization: Bearer {token}</code>.
