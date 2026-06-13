// env.ts — single source of truth for E2E credentials.
//
// Credentials must never be hardcoded in specs. They are read from the
// environment (loaded from the root .env by playwright.config.ts via dotenv)
// so the same suite runs locally and in CI without committing secrets.
// See .env.example for the required keys.

function required(name: string): string {
    const value = process.env[name];
    if (!value) {
        throw new Error(
            `Missing required E2E env var "${name}". ` +
                `Copy .env.example to .env and set it (see the E2E section).`
        );
    }
    return value;
}

export const E2E_USER = {
    email: required('E2E_EMAIL'),
    password: required('E2E_PASSWORD'),
} as const;
